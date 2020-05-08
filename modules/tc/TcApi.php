<?php
require_once "paramsTrait.php";

abstract class TcApi
{

    const AVAILABLE_APIS = [
        'bbb' => 'BigBlueButton',
        'om' => 'OpenMeetings',
        'webconf' => 'WebConf',
        'zoom' => 'Zoom'
    ];

    private static $_cache;

    protected static function cacheStore($key, $data)
    {
        if (is_array($key))
            $key = md5(implode('_', $key));
        self::$_cache[$key] = $data;
    }

    protected static function cacheLoad($key)
    {
        if (is_array($key))
            $key = md5(implode('_', $key));
        if (self::$_cache && array_key_exists($key, self::$_cache))
            return self::$_cache[$key];
        else
            return null;
    }

    protected function cacheClear($key = null)
    {
        if (is_array($key))
            $key = md5(implode('_', $key));
        unset(self::$_cache[$key]);
    }

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
    public abstract function createMeeting($creationParams);

    /*
     * USAGE:
     * $endParams = array (
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public abstract function endMeeting($endParams);

    /*
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
    public abstract function getMeetings();

    /*
     * USAGE:
     * $infoParams = array(
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
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
    public abstract function publishRecordings($recordingParams);

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * );
     */
    public abstract function deleteRecordings($recordingParams);

    public static abstract function generatePassword();

    public static abstract function generateMeetingId();

    public abstract function getServerUsers(TcServer $server);
}

/**
 *
 * @author User
 *        
 */
abstract class TcSession
{

    public $session_id;

    private $is_new = true;

    public function __construct($params = [])
    {
        $this->is_new = true;
        if (array_key_exists('sessionId', $params)) {
            $this->session_id = $params['sessionId'];
        }
    }

    /*
     * public function LoadById($id) {
     * $this->is_new = false;
     * return $this;
     * }
     */
    public abstract function disable();

    public abstract function enable();

    public function delete()
    {
        $this->is_new = false;
        return true;
    }

    public abstract function IsKnownToServer();

    public abstract function IsRunning();

    public abstract function join_user(array $joinParams);

    public abstract function createMeeting();

    public abstract function startMeeting();

    /**
     * This function should update both local and remote
     */
    public function save()
    {
        $this->is_new = false;
        return true;
    }

    /**
     * This function "loads" based on the session_id from disk, db, remote, etc
     */
    public function load()
    {
        $this->is_new = false;
        return true;
    }
}

/**
 *
 * @author User
 *        
 */
abstract class TcDbSession extends TcSession
{
    use paramsTrait;

    private $params = [
        'required' => [],
        'optional' => [
            'id' => 'sessionId',
            'course_id',
            'meeting_id',

            'title',
            'description',
            'start_date',
            'end_date',
            'public:bool',
            'active:bool',
            'running_at:integer',
            'mod_pw',
            'att_pw',
            'unlock_interval',
            'external_users',
            'participants',
            'record:bool',
            'sessionUsers:integer'
        ]
    ];

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
        $this->load();
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
        if (property_exists($this,$name) )
            return $this->$name;

        if ( $this->data)
            if ( property_exists($this->data,$name))
                return $this->data->$name;

        throw new RuntimeException(__METHOD__.' Nothing to get for '.$name.'!'); //to check for existence of a variable use the function
    }
    
    public function __isset($name)
    {
        return ($this->data && isset($this->data->$name));
    }
    
    public function __unset($name)
    {
        if ( propert_exists($this,$name) )
            unset($this->$name);
        elseif ( $this->data )
            unset($this->data->$name);
    }
    
    public function __set($name, $value)
    {
        if (isset($this->$name))
            $this->$name = $value;
        elseif ($this->data) {
            $this->data->$name = $value;
        } else
            throw new RuntimeException('Setting session data with not data object.');
    }

    function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->session_id) {
            if (! $this->load() ) // This fills in $this->data->id (same as session_id)
                throw new RuntimeException('Failed to load session with id '.$this->session_id);
        }

        if (count($params) > 0) {
            $validparams = $this->_checkParams($this->params, $params);
            foreach ($validparams as $n => $v) {
                $this->{$n} = $v;
            }

            $this->meeting_id = $this->data->meeting_id;
        }
    }

    /**
     *
     * @throws Exception
     * @return int|boolean
     */
    private function getRunningServerId()
    {
        if ($this->session_id)
            $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE id = ?s", $this->session_id);
        elseif ($this->meeting_id)
            $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE meeting_id = ?s", $this->meeting_id);
        if ($res) {
            return $res->running_at;
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
        if (! $this->server) {
            $sid = $this->getRunningServerId();
            $this->server = TcServer::LoadById($sid);
            if ($this->server)
                return $this->server;
            else
                throw new Exception("Server not found for id " . $this->server_id);
        }
        else
            return $this->server;
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

    public function createMeeting()
    {
        return true;
    }

    public function startMeeting()
    {
        return true;
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
            $q = Database::get()->querySingle("SELECT COUNT(*) AS count FROM course_user, user
                            WHERE course_user.course_id = ?d AND course_user.user_id = user.id", $this->course_id)->count;
            if ($q === null)
                die('Failed to get user count for course ' . $this->course_id);
            $total = $q;
            $total += $this->external_users ? count(explode(',', $this->external_users)) : 0;
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
     *  @brief Pick a server for a session based on all available information for the course. This is specifically a static and used to instantiate descendants
     */
    public static function pickServer($types, $course_id)
    {
        array_walk($types, function (&$value) {
            $value = '"' . $value . '"';
        });
        $types = implode(',', $types);
        $t = Database::get()->querySingle("SELECT tcs.* FROM course_external_server ces
                INNER JOIN tc_servers tcs ON tcs.id=ces.external_server
                WHERE ces.course_id = ?d AND tcs.type IN(" . $types . ") AND enabled='true'
                ORDER BY tcs.weight ASC", $course_id);
        if ($t) { // course uses specific tc_servers
            $server = $t;
        } else { // will use default tc_server
                 // get type first via the servers table
            $server = Database::get()->querySingle("SELECT * FROM tc_servers WHERE `type` IN(" . $types . ") and enabled = 'true' ORDER BY weight ASC");
        }
        return $server;
    }

    public function save()
    {
        if ($this->session_id) { // updating/editing session
            $q = Database::get()->querySingle("UPDATE tc_session SET title=?s, description=?s, start_date=?t, end_date=?t,
                                        public=?s, active=?s, running_at=?d, unlock_interval=?d, external_users=?s,running_at=?d,
                                        participants=?s, record=?s, sessionUsers=?d WHERE id=?d", 
                $this->title, $this->description, $this->start_date, $this->end_date, $this->public ? '1' : '0', $this->active ? '1' : '0', 
                $this->running_at, $this->unlock_interval, $this->external_users, $this->running_at, $this->participants, ($this->record ? 'true' : 'false'),
                $this->sessionUsers, $this->session_id);
            
            if ($q === NULL )
                return false;
        } else { // adding new session
            $q = Database::get()->query("INSERT INTO tc_session SET course_id = ?d,
                                                            title = ?s,
                                                            description = ?s,
                                                            start_date = ?t,
                                                            end_date = ?t,
                                                            public = ?s,
                                                            active = ?s,
                                                            running_at = ?d,
                                                            meeting_id = ?s,
                                                            mod_pw = ?s,
                                                            att_pw = ?s,
                                                            unlock_interval = ?d,
                                                            external_users = ?s,
                                                            participants = ?s,
                                                            record = ?s,
                                                            sessionUsers = ?d", 
                $this->course_id, $this->title, $this->description, $this->start_date, $this->end_date, $this->public ? '1' : '0', $this->active ? '1' : '0', 
                $this->running_at, $this->meeting_id, $this->mod_pw, $this->att_pw, $this->unlock_interval, $this->external_users, $this->running_at, 
                $this->participants, $this->record ? 'true' : 'false', $this->sessionUsers);

            if (! $q)
                return false;
        }
        return parent::save();
    }

    public function load()
    {
        if ($this->session_id) {
            $q = Database::get()->querySingle("SELECT * FROM tc_session WHERE id=?s", $this->session_id);
            if ( $q  ) {
                $this->data = $q;
                
                if ( $this->data->meeting_id )
                    $this->meeting_id = $this->data->meeting_id;
                
                //TODO: Sigh
                $this->data->public = $this->data->public == '1';
                $this->data->active = $this->data->active == '1';
                $this->data->record = $this->data->record == 'true';
                $this->data->sessionUsers = (int) $this->data->sessionUsers;
                
                $this->server = TcServer::LoadById($this->running_at);
                return parent::load();
            }
            else 
                return false;
        }
    }
}




