<?php

/*
 * ========================================================================
 * Open eClass
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014 Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 * Network Operations Center, University of Athens,
 * Panepistimiopolis Ilissia, 15784, Athens, Greece
 * e-mail: info@openeclass.org
 * ========================================================================
 */
require 'TcApi.php';
require 'paramsTrait.php';

class BigBlueButton extends TcApi
{
    use paramsTrait;

    private static $sessionInfoCache = null;
    
    private $_bbbServerBaseUrl;

    private $_securitySalt;

    private $params = [
        'create' => [
            'required' => [
                'meetingID' => 'meetingId'
            ],
            'optional' => [
                'name' => 'meetingName',
                'attendeePW' => 'attendeePw',
                'moderatorPW' => 'moderatorPw',
                'welcome' => 'welcomeMsg',
                'dialNumber',
                'voiceBridge',
                'webVoice',
                'logoutURL' => 'logoutUrl',
                'maxParticipants:number',
                'record:boolstr',
                'duration:number',
                'isBreakout:boolstr',
                'parentMeetingID',
                'sequence:number',
                'freeJoin:bool',
                // 'meta_* - string / eg: meta_category='.urlencode($creationParams['meta_category']);
                'moderatorOnlyMessage',
                'autoStartRecording:boolstr',
                'allowStartStopRecording:boolstr',
                'webcamsOnlyForModerator:boolstr',
                'logo',
                'bannerText',
                'bannerColor', // - hex #FFFFFF
                'copyright',
                'muteOnStart:boolstr',
                'allowModsToUnmuteUsers:boolstr',
                'lockSettingsDisableCam:boolstr',
                'lockSettingsDisableMic:boolstr',
                'lockSettingsDisablePrivateChat:boolstr',
                'lockSettingsDisablePublicChat:boolstr',
                'lockSettingsDisableNote:boolstr',
                'lockSettingsLockedLayout:boolstr',
                'lockSettingsLockOnJoin:boolstr',
                'lockSettingsLockOnJoinConfigurable:boolstr',
                'guestPolicy:enum(ALWAYS_ACCEPT|ALWAYS_DENY|ASK_MODERATOR)'
            ]
        ]
    ];

    public function __construct($params = [])
    {
        if (is_array($params) && count($params) > 0) {
            if (array_key_exists('server', $params)) {
                $this->_bbbServerBaseUrl = $params['server']->api_url;
                $this->_securitySalt = $params['server']->server_key;
            } elseif (array_key_exists('url', $params))
                $this->_bbbServerBaseUrl = $params['url'];
            if (array_key_exists('salt', $params))
                $this->_securitySalt = $params['salt'];
        }
    }

    /*
     * A private utility method used by other public methods to process XML responses.
     */
    private function _processXmlResponse($url)
    {
        echo '[_processXmlResponse] ' . $url . ':<br>';
        if (! extension_loaded('libxml'))
            die('No XML processing capability in environment. Need libxml enabled.');

        if (extension_loaded('curl')) {
            $ch = curl_init() or die(curl_error());
            $timeout = 10;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            if ($data === false) {
                curl_close($ch);
                throw new \RuntimeException('Unhandled curl error: ' . curl_error($ch));
            }
            if ($data === '') {
                var_dump($data);
                $x = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                curl_close($ch);
                throw new \RuntimeException('Curl error: HTTP CODE:' . $x);
            }
            curl_close($ch);

            $element = new SimpleXMLElement($data);
            echo '<pre>';
            print_r($element);
            echo '</pre>';
            return $element;
        } else
            return simplexml_load_file($url);
    }

    /* __________________ BBB ADMINISTRATION METHODS _________________ */
    /*
     * The methods in the following section support the following categories of the BBB API:
     * -- create
     * -- join
     * -- end
     */

    /*
     * USAGE:
     * (see $creationParams array in createMeetingArray method.)
     */
    public function getCreateMeetingUrl($creationParams)
    {
        if (! array_key_exists('guestPolicy', $creationParams))
            $creationParams['guestPolicy'] = 'ASK_MODERATOR';

        $params = $this->_checkParams($this->params['create'], $creationParams);
        array_walk($params, function (&$val, $idx) {
            $val = $idx . '=' . urlencode($val);
        });
        $params = implode('&', $params);
        return $this->_bbbServerBaseUrl . "api/create?" . $params . '&checksum=' . sha1("create" . $params . $this->_securitySalt);
    }

    public function createMeeting($creationParams)
    {
        $xml = $this->_processXmlResponse($this->getCreateMeetingURL($creationParams));

        if ($xml) {
            if ($xml->meetingID) {
                /*
                 * return array(
                 * 'returncode' => $xml->returncode,
                 * 'message' => $xml->message,
                 * 'messageKey' => $xml->messageKey,
                 * 'meetingId' => $xml->meetingID,
                 * 'attendeePw' => $xml->attendeePW,
                 * 'moderatorPw' => $xml->moderatorPW,
                 * 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
                 * 'createTime' => $xml->createTime
                 * );
                 */
                return (array) $xml;
            } else {
                return array(
                    'returncode' => $xml->returncode,
                    'message' => $xml->message,
                    'messageKey' => $xml->messageKey
                );
            }
        } else {
            return null;
        }
    }

    /*
     * NOTE: At this point, we don't use a corresponding joinMeetingWithXmlResponse here because the API
     * doesn't respond on success, but you can still code that method if you need it. Or, you can take the URL
     * that's returned from this method and simply send your users off to that URL in your code.
     * USAGE:
     * $joinParams = array(
     * 'meetingID' => '1234', -- REQUIRED - A unique id for the meeting
     * 'fullname' => 'Jane Doe', -- REQUIRED - The name that will display for the user in the meeting
     * 'password' => 'ap', -- REQUIRED - The attendee or moderator password, depending on what's passed here
     * 'createTime' => '', -- OPTIONAL - string. Leave blank ('') unless you set this correctly.
     * 'userID' => '', -- OPTIONAL - string
     * 'webVoiceConf' => '' -- OPTIONAL - string
     * 'configToken','defaultLayout','avatarURL','redirect','clientURL','joinViaHtml5','guest'
     *
     * );
     */
    public function getJoinMeetingURL($joinParams)
    {
        $meetingId = $this->_requiredParam('meetingID', $joinParams);
        $fullname = $this->_requiredParam('fullName', $joinParams);
        $password = $this->_requiredParam('password', $joinParams);
        // Establish the basic join URL:
        $joinUrl = $this->_bbbServerBaseUrl . "api/join?";
        // Add parameters to the URL:
        $params = 'meetingID=' . urlencode($meetingId) . '&fullName=' . urlencode($fullname) . '&password=' . urlencode($password) . '&userID=' . urlencode($joinParams['userID']);
        // Only use createTime if we really want to use it. If it's '', then don't pass it:
        if (((isset($joinParams['createTime'])) && ($joinParams['createTime'] != ''))) {
            $params .= '&createTime=' . urlencode($joinParams['createTime']);
        }
        // Return the URL:
        return $joinUrl . $params . '&checksum=' . sha1("join" . $params . $this->_securitySalt);
    }

    /*
     * USAGE:
     * $endParams = array (
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public function getEndMeetingURL($endParams)
    {
        $this->_meetingId = $this->_requiredParam($endParams['meetingId']);
        $this->_password = $this->_requiredParam($endParams['password']);
        $endUrl = $this->_bbbServerBaseUrl . "api/end?";
        $params = 'meetingID=' . urlencode($this->_meetingId) . '&password=' . urlencode($this->_password);
        return $endUrl . $params . '&checksum=' . sha1("end" . $params . $this->_securitySalt);
    }

    /*
     * USAGE:
     * $endParams = array (
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public function endMeeting($endParams)
    {
        $xml = $this->_processXmlResponse($this->getEndMeetingURL($endParams));
        if ($xml) {
            return array(
                'returncode' => $xml->returncode,
                'message' => $xml->message,
                'messageKey' => $xml->messageKey
            );
        } else {
            return null;
        }
    }

    /* __________________ BBB MONITORING METHODS _________________ */
    /*
     * The methods in the following section support the following categories of the BBB API:
     * -- isMeetingRunning
     * -- getMeetings
     * -- getMeetingInfo
     */

    /*
     * USAGE:
     * $meetingId = '1234' -- REQUIRED - The unique id for the meeting
     */
    public function getIsMeetingRunningUrl($meetingId)
    {
        $this->_meetingId = $this->_requiredParam($meetingId);
        $runningUrl = $this->_bbbServerBaseUrl . "api/isMeetingRunning?";
        $params = 'meetingID=' . urlencode($this->_meetingId);
        return $runningUrl . $params . '&checksum=' . sha1("isMeetingRunning" . $params . $this->_securitySalt);
    }

    /*
     * USAGE:
     * $meetingId = '1234' -- REQUIRED - The unique id for the meeting
     */
    public function isMeetingRunning($meetingId)
    {
        $xml = $this->_processXmlResponse($this->getIsMeetingRunningUrl($meetingId));
        if ($xml) {
            return $xml->running == 'true';
        }
        return null;
    }

    /*
     * Simply formulate the getMeetings URL
     * We do this in a separate function so we have the option to just get this
     * URL and print it if we want for some reason.
     */
    public function getGetMeetingsUrl()
    {
        $getMeetingsUrl = $this->_bbbServerBaseUrl . "api/getMeetings?checksum=" . sha1("getMeetings" . $this->_securitySalt);
        return $getMeetingsUrl;
    }

    /*
     * USAGE:
     * We don't need to pass any parameters with this one, so we just send the query URL off to BBB
     * and then handle the results that we get in the XML response.
     */
    public function getMeetings()
    {
        $xml = $this->_processXmlResponse($this->getGetMeetingsUrl());
        if ($xml) {
            // If we don't get a success code, stop processing and return just the returncode:
            if ($xml->returncode != 'SUCCESS') {
                $result = array(
                    'returncode' => $xml->returncode
                );
                return $result;
            } elseif ($xml->messageKey == 'noMeetings') {
                /* No meetings on server, so return just this info: */
                $result = array(
                    'returncode' => $xml->returncode,
                    'messageKey' => $xml->messageKey,
                    'message' => $xml->message
                );
                return $result;
            } else {
                // In this case, we have success and meetings. First return general response:
                $result = array(
                    'returncode' => $xml->returncode,
                    'messageKey' => $xml->messageKey,
                    'message' => $xml->message
                );
                // Then iterate through meeting results and return them as part of the array:
                foreach ($xml->meetings->meeting as $m) {
                    $result['meetings'][] = (array) $m;
                }
                return $result;
            }
        } else {
            return null;
        }
    }

    /*
     * USAGE:
     * $infoParams = array(
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * //'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public function getMeetingInfoUrl($infoParams)
    {
        $this->_meetingId = $this->_requiredParam($infoParams['meetingId']);
        // $this->_password = $this->_requiredParam($infoParams['password']);
        $infoUrl = $this->_bbbServerBaseUrl . "api/getMeetingInfo?";

        $params = 'meetingID=' . urlencode($this->_meetingId);
        // '&password='.urlencode($this->_password);

        return $infoUrl . $params . '&checksum=' . sha1("getMeetingInfo" . $params . $this->_securitySalt);
    }

    /*
     * USAGE:
     * $infoParams = array(
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * //'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public function getMeetingInfo($infoParams)
    {
        $meetingId = $this->_requiredParam('meetingId',$infoParams);
        echo 'GETMEETINGINFO FOR '.$meetingId.'<br>';
        if ( self::$sessionInfoCache && array_key_exists($meetingId,self::$sessionInfoCache) ) {
            echo 'USING CACHE for '.$meetingId.'<br>';
            return self::$sessionInfoCache[$meetingId];
        }
        
        $xml = $this->_processXmlResponse($this->getMeetingInfoUrl($infoParams));

        if ($xml) {
            if (($xml->returncode != 'SUCCESS') || ($xml->messageKey == null)) {
                $result = (array) $xml;
                self::$sessionInfoCache[$meetingId] = $result;
                return $result;
            } else {
                $result = (array) $xml;

                // Then interate through attendee results and return them as part of the array:
                foreach ($xml->attendees->attendee as $a) {
                    $result['attendees'][] = (array) $a;
                }
                
                self::$sessionInfoCache[$meetingId] = $result;
                
                return $result;
            }
        } else {
            return null;
        }
    }

    /* __________________ BBB RECORDING METHODS _________________ */
    /*
     * The methods in the following section support the following categories of the BBB API:
     * -- getRecordings
     * -- publishRecordings
     * -- deleteRecordings
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'meetingId' => '1234', -- OPTIONAL - comma separate if multiple ids
     * );
     */
    public function getRecordingsUrl($recordingParams)
    {
        $recordingsUrl = $this->_bbbServerBaseUrl . "api/getRecordings?";
        $params = 'meetingID=' . urlencode($recordingParams['meetingId']);
        return ($recordingsUrl . $params . '&checksum=' . sha1("getRecordings" . $params . $this->_securitySalt));
    }

    /*
     * USAGE:
     * $recordingParams = array(
     * 'meetingId' => '1234', -- OPTIONAL - comma separate if multiple ids
     * );
     * NOTE: 'duration' DOES work when creating a meeting, so if you set duration
     * when creating a meeting, it will kick users out after the duration. Should
     * probably be required in user code when 'recording' is set to true.
     */
    public function getRecordings($recordingParams)
    {
        $xml = $this->_processXmlResponse($this->getRecordingsUrl($recordingParams));
        if ($xml) {
            // If we don't get a success code or messageKey, find out why:
            if (($xml->returncode != 'SUCCESS') || ($xml->messageKey == null)) {
                $result = array(
                    'returncode' => $xml->returncode,
                    'messageKey' => $xml->messageKey,
                    'message' => $xml->message
                );
                return $result;
            } else {
                // In this case, we have success and recording info:
                $result = array(
                    'returncode' => $xml->returncode,
                    'messageKey' => $xml->messageKey,
                    'message' => $xml->message
                );

                foreach ($xml->recordings->recording as $r) {
                    $result[] = array(
                        'recordId' => $r->recordID,
                        'meetingId' => $r->meetingID,
                        'name' => $r->name,
                        'published' => $r->published,
                        'startTime' => $r->startTime,
                        'endTime' => $r->endTime,
                        'playbackFormatType' => $r->playback->format->type,
                        'playbackFormatUrl' => $r->playback->format->url,
                        'playbackFormatLength' => $r->playback->format->length,
                        'metadataTitle' => $r->metadata->title,
                        'metadataSubject' => $r->metadata->subject,
                        'metadataDescription' => $r->metadata->description,
                        'metadataCreator' => $r->metadata->creator,
                        'metadataContributor' => $r->metadata->contributor,
                        'metadataLanguage' => $r->metadata->language
                        // Add more here as needed for your app depending on your
                        // use of metadata when creating recordings.
                    );
                }
                return $result;
            }
        } else {
            return null;
        }
    }

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * 'publish' => 'true', -- REQUIRED - boolean: true/false
     * );
     */
    public function getPublishRecordingsUrl($recordingParams)
    {
        $recordingsUrl = $this->_bbbServerBaseUrl . "api/publishRecordings?";
        $params = 'recordID=' . urlencode($recordingParams['recordId']) . '&publish=' . urlencode($recordingParams['publish']);
        return ($recordingsUrl . $params . '&checksum=' . sha1("publishRecordings" . $params . $this->_securitySalt));
    }

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * 'publish' => 'true', -- REQUIRED - boolean: true/false
     * );
     */
    public function publishRecordings($recordingParams)
    {
        $xml = $this->_processXmlResponse($this->getPublishRecordingsUrl($recordingParams));
        if ($xml) {
            return array(
                'returncode' => $xml->returncode,
                'published' => $xml->published // -- Returns true/false.
            );
        } else {
            return null;
        }
    }

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * );
     */
    public function getDeleteRecordingsUrl($recordingParams)
    {
        $recordingsUrl = $this->_bbbServerBaseUrl . "api/deleteRecordings?";
        $params = 'recordID=' . urlencode($recordingParams['recordId']);
        return ($recordingsUrl . $params . '&checksum=' . sha1("deleteRecordings" . $params . $this->_securitySalt));
    }

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * );
     */
    public function deleteRecordings($recordingParams)
    {
        $xml = $this->_processXmlResponse($this->getDeleteRecordingsUrl($recordingParams));
        if ($xml) {
            return array(
                'returncode' => $xml->returncode,
                'deleted' => $xml->deleted // -- Returns true/false.
            );
        } else {
            return null;
        }
    }

    /**
     *
     * @brief get number of active rooms
     * @param StdClass $meetings
     *            -- the data from getMeetings
     * @return int
     */
    public function get_active_rooms()
    {
        $sum = 0;
        $meetings = $this->getMeetings();
        if ($meetings) {
            foreach ($meetings as $meeting) {
                $mid = $meeting['meetingId'];
                // $pass = $meeting['moderatorPw'];
                if ($mid != null) {
                    $sum += 1;
                }
            }
        }
        return $sum;
    }
    
    public function clearCaches() {
        self::$sessionInfoCache = null;
    }
    
    public function generatePassword() {
        return $this->generateRandomString();
    }
    
    public function generateMeetingId() {
        return $this->generateRandomString();
    }
    
    /**
     *
     * @brief Generate random strings. Used to create meeting_id, attendance password and moderator password
     * @param int $length
     * @return string
     */
    private function generateRandomString($length = 10)
    {
        return substr(str_shuffle(implode(array_merge(range(0, 9), range('A', 'Z'), range('a', 'z')))), 0, $length);
    }
}

/**
 *
 * @author User
 *        
 */
class TcBigBlueButtonSession extends TcDbSession
{
    use paramsTrait;
    
    function __construct(array $params = [])
    {
        parent::__construct($params);
        if (count($params) > 0) {
            $this->securitySalt = $params['salt'];
            $this->mod_pw = $params['mod_pw'];
            $this->att_pt = $params['att_pw'];
            $this->username = $params['username'];
        }
    }

    /**
     *
     * @brief Disable bbb session (locally)
     * @return bool
     */
    function disable()
    {
        // TODO:attempt to disable on server
        return parent::disable(); // disable locally
    }

    /**
     *
     * @brief enable bbb session (locally)
     * @param int $session_id
     * @return bool
     */
    function enable()
    {
        // TODO:attempt to enable on server
        return parent::enable();
    }

    /**
     *
     * @brief delete bbb sessions (locally)
     * @param int $session_id
     * @return bool
     */
    function delete()
    {
        // TODO:check if it's running and if so, KILL IT NOW
        return parent::delete(); // delete from DB
    }

    /**
     *
     * @brief get number of meeting users
     * @global type $langBBBGetUsersError
     * @global type $langBBBConnectionError
     * @global type $course_code
     * @param string $salt
     * @param string $bbb_url
     * @param string $meeting_id
     * @param string $pw
     * @return int
     */
    function get_meeting_users($pw)
    {
        //global $langBBBGetUsersError, $langBBBConnectionError, $course_code;

        // Instantiate the BBB class:
        $bbb = new BigBlueButton([
            'salt' => $this->server->server_key,
            'url' => $this->server->api_url
        ]);

        $infoParams = array(
            'meetingId' => $this->meeting_id, // REQUIRED - We have to know which meeting.
            'password' => $pw // REQUIRED - Must match moderator pass for meeting.
        );

        // Now get meeting info:
        $result = $bbb->getMeetingInfo($infoParams);
        return ($result && isset($result['participantCount'])) ? $result['participantCount'] : false;

        /*
         * if ($result == null) {
         * // If we get a null response, then we're not getting any XML back from BBB.
         * Session::Messages($langBBBConnectionError, 'alert-danger');
         * } else {
         * // We got an XML response, so let's see what it says:
         * if (isset($result['messageKey'])) {
         * Session::Messages($langBBBGetUsersError, 'alert-danger');
         * redirect("index.php?course=$course_code");
         * }
         */
    }

    public function isFull()
    {
        $ssUsers = $this->get_meeting_users($this->mod_pw);
        return ($this->sessionUsers > 0) && ($this->sessionUsers < $ssUsers);
    }

    /**
     *
     * @brief Join a user to the session
     * @param array $joinParams
     * @return boolean
     */
    public function join_user(array $joinParams)
    {
        $this->getRunningServer();

        $fullname = $this->_requiredParams([
            'username',
            'name'
        ], $joinParams);
        $pw = $this->_requiredParam('pw', $joinParams);
        $uid = $this->_requiredParam('uid', $joinParams);

        if (($this->mod_pw && $pw != $this->mod_pw) || ($this->mod_att && $pw != $this->att_pw))
            return false; // die('Invalid password');

        if ($this->isFull())
            return false;

        $joinParams = array(
            'meetingID' => $this->meeting_id, // REQUIRED - We have to know which meeting to join.
            'fullName' => $fullname, // REQUIRED - The user display name that will show in the BBB meeting.
            'password' => $pw, // REQUIRED - Must match either attendee or moderator pass for meeting.
            'userID' => $uid // OPTIONAL - string
        );

        $bbbApi = new BigBlueButton([
            'server' => $this->server
        ]);
        $uri = $bbbApi->getJoinMeetingURL($joinParams);
        redirect($uri);
        // exit; //FIXME: Probably need to check flow in callers to enforce this there, some plugins may need to continue?
        return true;
    }

    /**
     *
     * @brief Check is this session is known to server (scheduled)
     * @return boolean
     */
    public function IsKnownToServer()
    {
        $api = new BigBlueButton([
            'server' => $this->server
        ]);

        if (! $api)
            die('Api creation failed for isRunning');

        $x = $api->getMeetingInfo(['meetingId'=>$this->meeting_id]);
        return ( $x && $x['returncode']=='SUCCESS' && $x['meetingID'] );
    }

    /**
     *
     * @brief check if session is running
     * @param string $meeting_id
     * @return boolean
     */
    function IsRunning()
    {
        // First check if it's flagged as running in the database
        if (! parent::IsRunning())
            return false;

        $api = new BigBlueButton([
            'server' => $this->server
        ]);

        if (! $api)
            die('Api creation failed for isRunning');

        return $api->isMeetingRunning($this->meeting_id);
    }
    
    
    /**
     * @brief BBB does not really use the schedule->start flow. Sessions are created/started when people join. Empty sessions are purged quickly.
     * @return boolean
     */
    function create_meeting() {
        echo 'BBB meeting creation is a stub<br>';
        return true; 
    }
    
    /**
     *
     * @global type $course_code
     * @global type $langBBBCreationRoomError
     * @global type $langBBBConnectionError
     * @global type $langBBBConnectionErrorOverload
     * @global type $langBBBWelcomeMsg
     * @param string $title
     * @param string $meeting_id
     * @param string $mod_pw
     * @param string $att_pw
     * @param string $record
     *            'true' or 'false'
     */
    function start_meeting()
    {
        global $langBBBWelcomeMsg;

        //If a maximum limit of simultaneous meeting users has been set, use it
        if (!$this->sessionUsers || $this->sessionUsers <= 0) {
            $users_to_join = $this->sessionUsers;
        }
        else { //otherwise just count participants
            $users_to_join = $this->usersToBeJoined(); // this is DB-expensive so call before the loop
        }

/*
        // At this point we must start the meeting on a new server if a higher priority slot has opened...
        // no matter what the previous assigned server was, therefore...
        // Check each available server of this type
        $r = TcServer::LoadAllByTypes(['bbb'], true);
        if (($r) and count($r) > 0) {
            foreach ($r as $server) {
                echo 'Checking space for ' . $users_to_join . ' users on server ' . $server->id . '/' . $server->api_url . '....<br>';
                if ($server->available($users_to_join)) { // careful, this is an API request on each server
                    echo 'Server ' . $server->id . ' is AVAILABLE.' . "\n";
                    break;
                }
            }
        } else {
            //Session::Messages($langBBBConnectionErrorOverload, 'alert-danger');
            return false;
        }

        // Move the session even if the server won't let us set it up, we can use this to check the last server chosen
        Database::get()->query("UPDATE tc_session SET running_at = ?d WHERE meeting_id = ?s", $server->id, $this->meeting_id);
*/        

        $duration = 0;
        if (($this->start_date != null) and ($this->end_date != null)) {
            $date_start = new DateTime($this->start_date);
            $date_end = new DateTime($this->end_date);
            $hour_duration = $date_end->diff($date_start)->h; // hour
            $min_duration = $date_end->diff($date_start)->i; // minutes
            $duration = $hour_duration * 60 + $min_duration;
        }

        $server = TcServer::LoadById($this->running_at);
        $bbb = new BigBlueButton([
            'server' => $server
        ]);

        $creationParams = array(
            'meetingId' => $this->meeting_id, // REQUIRED
            'meetingName' => $this->title,
            'attendeePw' => $this->att_pw, // Match this value in getJoinMeetingURL() to join as attendee.
            'moderatorPw' => $this->mod_pw, // Match this value in getJoinMeetingURL() to join as moderator.
            'welcomeMsg' => $langBBBWelcomeMsg, // ''= use default. Change to customize.
            'logoutUrl' => '', // Default in bigbluebutton.properties. Optional.
            'maxParticipants' => $this->sessionUsers, // Optional. Max concurrent users at any time [number]
            'record' => ($this->record ? 'true' : 'false'), // 'true' will tell BBB to record the meeting.
            'duration' => $duration // Default = 0 which means no set duration in minutes. [number]
                                    // 'meta_category' => '', // Use to pass additional info to BBB server. See API docs.
        );

        $result = $bbb->createMeeting($creationParams);
        if ($result == null || $result['returncode'] != 'SUCCESS') {
            return false;
        }

        return true;
    }
    
    public function clearCaches() {
        $bbb = new BigBlueButton([
            'server' => $server
        ]);
        $bbb->clearCaches();
    }
}



