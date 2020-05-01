<?php

abstract class TcApi
{

    public abstract function __construct($params = []);

    /*
     * USAGE:
     * $creationParams = array(
     * 'name' => 'Meeting Name', -- A name for the meeting (or username)
     * 'meetingId' => '1234', -- A unique id for the meeting
     * 'attendeePw' => 'ap', -- Set to 'ap' and use 'ap' to join = no user pass required.
     * 'moderatorPw' => 'mp', -- Set to 'mp' and use 'mp' to join = no user pass required.
     * 'welcomeMsg' => '', -- ''= use default. Change to customize.
     * 'dialNumber' => '', -- The main number to call into. Optional.
     * 'voiceBridge' => '', -- PIN to join voice. Optional.
     * 'webVoice' => '', -- Alphanumeric to join voice. Optional.
     * 'logoutUrl' => '', -- Default in bigbluebutton.properties. Optional.
     * 'maxParticipants' => '-1', -- Optional. -1 = unlimitted. Not supported in BBB. [number]
     * 'record' => 'false', -- New. 'true' will tell BBB to record the meeting.
     * 'duration' => '0', -- Default = 0 which means no set duration in minutes. [number]
     * 'meta_category' => '', -- Use to pass additional info to BBB server. See API docs to enable.
     * );
     */
    public abstract function getCreateMeetingUrl($creationParams);

    public abstract function createMeeting($creationParams);

    /*
     * USAGE:
     * $joinParams = array(
     * 'meetingId' => '1234', -- REQUIRED - A unique id for the meeting
     * 'username' => 'Jane Doe', -- REQUIRED - The name that will display for the user in the meeting
     * 'password' => 'ap', -- REQUIRED - The attendee or moderator password, depending on what's passed here
     * 'createTime' => '', -- OPTIONAL - string. Leave blank ('') unless you set this correctly.
     * 'userID' => '', -- OPTIONAL - string
     * 'webVoiceConf' => '' -- OPTIONAL - string
     * );
     */
    public abstract function getJoinMeetingURL($joinParams);

    /*
     * USAGE:
     * $endParams = array (
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public abstract function getEndMeetingURL($endParams);

    public abstract function endMeeting($endParams);

    /*
     * USAGE:
     * $meetingId = '1234' -- REQUIRED - The unique id for the meeting
     */
    public abstract function getIsMeetingRunningUrl($meetingId);

    /**
     *
     * @param string $meetingId
     * @return boolean
     */
    public abstract function isMeetingRunning($meetingId);

    /*
     * Simply formulate the getMeetings URL
     * We do this in a separate function so we have the option to just get this
     * URL and print it if we want for some reason.
     */
    public abstract function getGetMeetingsUrl();

    public abstract function getMeetings();

    /*
     * USAGE:
     * $infoParams = array(
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public abstract function getMeetingInfoUrl($infoParams);

    public abstract function getMeetingInfo($infoParams);

    /*
     * $result = array(
     * 'returncode' => $xml->returncode,
     * 'meetingName' => $xml->meetingName,
     * 'meetingId' => $xml->meetingID,
     * 'createTime' => $xml->createTime,
     * 'voiceBridge' => $xml->voiceBridge,
     * 'attendeePw' => $xml->attendeePW,
     * 'moderatorPw' => $xml->moderatorPW,
     * 'running' => $xml->running,
     * 'recording' => $xml->recording,
     * 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
     * 'startTime' => $xml->startTime,
     * 'endTime' => $xml->endTime,
     * 'participantCount' => $xml->participantCount,
     * 'maxUsers' => $xml->maxUsers,
     * 'moderatorCount' => $xml->moderatorCount,
     * );
     * // Then interate through attendee results and return them as part of the array:
     * foreach ($xml->attendees->attendee as $a) {
     * $result[] = array(
     * 'userId' => $a->userID,
     * 'fullName' => $a->fullName,
     * 'role' => $a->role
     * );
     * }
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'meetingId' => '1234', -- OPTIONAL - comma separate if multiple ids
     * );
     */
    public abstract function getRecordingsUrl($recordingParams);

    public abstract function getRecordings($recordingParams);

    /*
     * foreach ($xml->recordings->recording as $r) {
     * $result[] = array(
     * 'recordId' => $r->recordID,
     * 'meetingId' => $r->meetingID,
     * 'name' => $r->name,
     * 'published' => $r->published,
     * 'startTime' => $r->startTime,
     * 'endTime' => $r->endTime,
     * 'playbackFormatType' => $r->playback->format->type,
     * 'playbackFormatUrl' => $r->playback->format->url,
     * 'playbackFormatLength' => $r->playback->format->length,
     * 'metadataTitle' => $r->metadata->title,
     * 'metadataSubject' => $r->metadata->subject,
     * 'metadataDescription' => $r->metadata->description,
     * 'metadataCreator' => $r->metadata->creator,
     * 'metadataContributor' => $r->metadata->contributor,
     * 'metadataLanguage' => $r->metadata->language,
     * // Add more here as needed for your app depending on your
     * // use of metadata when creating recordings.
     * );
     * }
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * 'publish' => 'true', -- REQUIRED - boolean: true/false
     * );
     */
    public abstract function getPublishRecordingsUrl($recordingParams);

    public abstract function publishRecordings($recordingParams);

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * );
     */
    public abstract function getDeleteRecordingsUrl($recordingParams);

    public abstract function deleteRecordings($recordingParams);

    public abstract function clearCaches();
    
    public abstract function generatePassword();
    
    public abstract function generateMeetingId(); 
}

/**
 *
 * @author User
 *        
 */
abstract class TcSession
{

    public $session_id;

    public abstract function LoadById($id);

    public abstract function disable();

    public abstract function enable();

    public abstract function delete();

    public abstract function IsKnownToServer();

    public abstract function IsRunning();

    public abstract function usersTotal();

    public abstract function join_user(array $joinParams);

    public abstract function createMeeting();

    public abstract function startMeeting();

    public abstract function clearCaches();
}

/**
 *
 * @author User
 *        
 */
abstract class TcDbSession
{

    public $session_id;

    private $server_id = null;

    private $meeting_id = null;

    // TcServer data cache
    private $server;

    // this is an associative array
    public $data = false;

    public function LoadById($id = null)
    {
        if (! $id) {
            if (! $this->session_id)
                throw new RuntimeException('[TC API] Unable to load session without session id.');
        } else {
            $this->session_id = $id;
        }
        $this->data = Database::get()->querySingle("SELECT * FROM tc_session WHERE id = ?d", $this->session_id);
        return $this->data ? $this : false;
    }

    // FIXME: meeting_id is not a unique identifier across types
    public function LoadByMeetingId($id)
    {
        $this->meeting_id = $id;
        $this->data = Database::get()->querySingle("SELECT * FROM tc_session WHERE meeting_id = ?d", $this->meeting_id);
        if ($this->data) {
            $this->session_id = $this->data->id;
            return $this;
        }
        return false;
    }

    public function __get($name)
    {
        if (isset($this->$name))
            return $this->$name;

        if (! $this->data)
            return false;
        if (isset($this->data->$name))
            return $this->data->$name;
        return false;
    }

    function __construct(array $params = [])
    {
        if (count($params) > 0) {
            if (isset($params['session_id']))
                $this->session_id = $params['session_id'];

            if (isset($params['meeting_id']))
                $this->meeting_id = $params['meeting_id']; // OPTIONAL

            if (isset($params['server']))
                $this->server_id = $params['server']->server_id;
            else
                $this->server_id = $params['server_id'];
        }
    }

    /**
     *
     * @throws Exception
     * @return int|boolean
     */
    private function loadRunningServerId()
    {
        if ($this->session_id)
            $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE id = ?s", $this->session_id);
        elseif ($this->meeting_id)
            $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE meeting_id = ?s", $this->meeting_id);
        if ($res) {
            $this->server_id = $res->running_at;
            return $this->server_id;
        } else {
            throw new Exception("Failed to get running server!");
        }
        return false;
    }

    /**
     *
     * @throws Exception
     * @return TcServer|boolean
     */
    public function getRunningServer()
    {
        if (! $this->server_id)
            $this->loadRunningServerId();

        if ($this->server_id) {
            $this->server = TcServer::LoadById($this->server_id);
            if ($this->server)
                return $this->server;
            else
                throw new Exception("Server not found for id " . $this->server_id);
        }
        return false;
    }

    /**
     *
     * @brief Disable bbb session (locally)
     * @param int $session_id
     * @return bool
     */
    function disable()
    {
        $x = Database::get()->querySingle("UPDATE tc_session set active='0' WHERE id=?d", $this->session_id);
        return $x !== NULL;
    }

    /**
     *
     * @brief enable bbb session (locally)
     * @param int $session_id
     * @return bool
     */
    function enable()
    {
        $x = Database::get()->querySingle("UPDATE tc_session SET active='1' WHERE id=?d", $this->session_id);
        return $x !== NULL;
    }

    /**
     *
     * @brief delete bbb sessions (locally)
     * @param int $session_id
     * @return bool
     */
    function delete()
    {
        $q = Database::get()->querySingle("DELETE FROM tc_session WHERE id = ?d", $this->session_id);
        if ($q === null) // false is returned when deletion is successful
            return false;
        Log::record($this->course_id, MODULE_ID_TC, LOG_DELETE, array(
            'id' => $this->session_id,
            'title' => $this->tc_title
        ));
        unset($this->data);
        unset($this->session_id);
        return true;
    }

    /**
     *
     * @brief check if session is running (locally)
     * @return boolean
     */
    function IsRunningInDB()
    {
        $server = TcServer::LoadById($this->running_at);

        if (! $server)
            die('Server not found for meeting id  ' . $this->meeting_id);

        if (! $this->running_at)
            return false;

        /*
         * if ($server->type != $this->tc_type)
         * die('Error: mismatched session and server type for meeting id ' . $meeting_id);
         */

        return $server->enabled;
    }

    /**
     *
     * @brief Check is this session is known to server (scheduled)
     * @return boolean
     */
    public function IsKnownToServer()
    {
        return false;
    }

    /**
     *
     * @brief check if session is running (locally)
     * @return boolean
     */
    function IsRunning()
    {
        return $this->IsRunningInDB();
    }

    /**
     *
     * @brief Return count of everybody in this course + external participants
     * @return number
     */
    public function usersTotal()
    {
        $q = Database::get()->querySingle("SELECT COUNT(*) AS count FROM course_user, user
                            WHERE course_user.course_id = ?d AND course_user.user_id = user.id", $this->course_id)->count;
        if ($q === null)
            die('Failed to get user count for course ' . $this->course_id);

        $total = $q;

        $total += $this->external_users ? count(explode(',', $this->external_users)) : 0;

        return $total;
    }

    //
    /**
     *
     * @brief Return count of actual participants in this session. if groups are specified, we can't include all users of this course
     * @return number
     */
    public function usersToBeJoined()
    {
        $participants = explode(',', $this->participants);

        // If participants includes "all users" (of this course) get them
        if ($this->participants == '0' || in_array("0", $participants)) {
            $total = $this->usersTotal(); // this includes external users
        } else { // There are special entries, could be groups or users of this course
            $group_ids = [];
            $user_ids = [];
            foreach ($participants as $p) {
                $p = trim($p);
                if ($p[0] == '_') { // this is a group
                    $gid = (int) substr($p, 1);
                    if (! in_array($gid, $group_ids, true))
                        $group_ids[] = $gid;
                } else { // this is a user id
                    $uid = (int) $p;
                    if (! in_array($uid, $user_ids))
                        $user_ids[] = $uid;
                }
            }
            $total = count($user_ids);

            // Get the users for the groups
            $q = Database::get()->querySingle("SELECT COUNT(DISTINCT u.id) as `count` FROM user u
                INNER JOIN group_members gm ON u.id=gm.user_id
                WHERE gm.group_id IN (?s)", implode(',', $group_ids));
            if ($q === null)
                die('Failed to get users count for groups ' . implode(',', $group_ids));

            $total += $q->count;

            // must re-count the external users
            $total += $this->external_users ? count(explode(',', $this->external_users)) : 0;
        }

        return $total;
    }

    /**
     *
     * @brief create join as moderator/user link
     * --@param string $meeting_id
     * --@param string $mod_pw
     * --@param string $att_pw
     * --@param string $username
     * --@param string $name
     * --@return string
     */
    public abstract function join_user(array $joinParams);
}




