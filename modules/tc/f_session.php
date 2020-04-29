<?php
require_once "TcServer.php";
require_once "TcApi.php";

class TcSessionHelper
{

    private $course_id;

    private $course_code;

    private $tc_types;

    private static $tc_types_available = null;

    // types cache - only stores available ones
    const MAX_USERS = 80;

    const MAX_USERS_RECOMMENDATION_RATIO = 2;

    // recommends USERS/RATIO to user
    const AVAILABLE_APIS = [
        'bbb' => 'BigBlueButton',
        'om' => 'OpenMeetings',
        'webconf' => 'WebConf',
        'zoom' => 'Zoom'
    ];

    /**
     *
     * @brief checks if tc server is configured
     * @return string|boolean
     */
    public function __construct($course_id, $course_code)
    {
        $this->course_code = $course_code;
        $this->course_id = $course_id;

        if (! self::$tc_types_available)
            self::$tc_types_available = tc_configured_apis();

        $c = Database::get()->querySingle("SELECT * FROM tc_course_info WHERE course_id=?d", $this->course_id);
        if ($c === null)
            throw new RuntimeException('Query failed for info table query: ' . $this->course_id);
        if (!$c)
            $this->tc_types = self::$tc_types_available;
        else
            $this->tc_types = $c->types;
    }

    public function getApi(array $params = [])
    {
        $apiclassname = self::AVAILABLE_APIS[$this->tc_type];
        require_once $this->tc_type . '-api.php';
        return new $apiclassname($params);
    }

    //TODO: Fix this to make more sense and avoid double DB query
    public function getSessionById($id) {
        $q = Database::get()->querySingle("SELECT type from tc_servers 
                        INNER JOIN tc_session ON tc_session.running_at=tc_servers.id 
                        WHERE tc_session.id=?d",$id);
        if ( $q ) {
            $classname = self::AVAILABLE_APIS[$q->type];
            require_once $q->type . '-api.php';
        }
        else { //probably null running_at field
            throw new RuntimeException('Failed to get session '.$id);
        }
        $classname = 'Tc' . $classname . 'Session';
        $obj = new $classname();
        return $obj->LoadById($id);
    }
    
/*    public function getSessionByTypes()
    {
        $apisess = [];
        foreach ($this->tc_types as $tc_type) {
            if (! array_key_exists($tc_type, $apisess)) {
                $classname = self::AVAILABLE_APIS[$tc_type];
                require_once $tc_type . '-api.php';

                $classname = 'Tc' . $classname . 'Session';
                $obj = new $classname();
                $apisess[$tc_type] = $obj->LoadById($id);
            }
        }
        return $apisess;
    }*/

    /*
    public function getSessionByMeetingIdAndType($meetingid)
    {
        $classname = self::AVAILABLE_APIS[$this->tc_type];
        require_once $this->tc_type . '-api.php';

        $classname = 'Tc' . $classname . 'Session';
        $obj = new $classname();
        return $obj->LoadByMeetingId($meetingid);
    }*/
    
    /**
     *
     * @brief Create form for new session scheduling
     */
    public function form($session_id = 0)
    {
        global $uid;
        global $langAdd, $langType, $langBBBRecordingNotAvailable, $langBBBRecordingMayNotBeAvailable;
        global $langUnitDescr, $langNewBBBSessionStart;
        global $langVisible, $langInvisible;
        global $langNewBBBSessionStatus, $langBBBSessionAvailable, $langBBBMinutesBefore;
        global $start_session, $BBBEndDate, $langAnnouncements, $langBBBAnnDisplay;
        global $langTitle, $langBBBNotifyExternalUsersHelpBlock, $langBBBRecordFalse;
        global $langBBBNotifyUsers, $langBBBNotifyExternalUsers, $langBBBSessionMaxUsers;
        global $langAllUsers, $langParticipants, $langBBBRecord, $langBBBRecordTrue;
        global $langBBBSessionSuggestedUsers, $langBBBSessionSuggestedUsers2;
        global $langBBBAlertTitle, $langBBBAlertMaxParticipants, $langJQCheckAll, $langJQUncheckAll;
        global $langEnd, $langBBBEndHelpBlock, $langModify, $langBBBExternalUsers;

        $BBBEndDate = Session::has('BBBEndDate') ? Session::get('BBBEndDate') : "";
        $enableEndDate = Session::has('enableEndDate') ? Session::get('enableEndDate') : ($BBBEndDate ? 1 : 0);

        $c = Database::get()->querySingle("SELECT COUNT(*) AS count FROM course_user WHERE course_id=?d", $this->course_id)->count;
        if ($c > self::MAX_USERS) {
            $c = floor($c / self::MAX_USERS_RECOMMENDATION_RATIO); // If more than 80 course users, we suggest 50% of them
        }
        $found_selected = false;

        if ($session_id > 0) { // edit session details
            $row = Database::get()->querySingle("SELECT * FROM tc_session WHERE id = ?d", $session_id);
            $status = ($row->active == 1 ? 1 : 0);
            $record = ($row->record == "true" ? true : false);
            // $running_at = $row->running_at; -- UNUSED
            $unlock_interval = $row->unlock_interval;
            $r_group = explode(",", $row->participants);
            $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $row->start_date);
            $start_session = q($start_date->format('d-m-Y H:i'));
            $end_date = DateTime::createFromFormat('Y-m-d H:i:s', $row->end_date);
            if (isset($row->end_date)) {
                $BBBEndDate = $end_date->format('d-m-Y H:i');
            } else {
                $BBBEndDate = NULL;
            }
            $enableEndDate = Session::has('BBBEndDate') ? Session::get('BBBEndDate') : ($BBBEndDate ? 1 : 0);

            $textarea = rich_text_editor('desc', 4, 20, $row->description);
            $value_title = q($row->title);
            $value_session_users = $row->sessionUsers;
            $data_external_users = trim($row->external_users);
            if ($data_external_users) {
                $init_external_users = 'data: ' . json_encode(array_map(function ($item) {
                    $item = trim($item);
                    return array(
                        'id' => $item,
                        'text' => $item,
                        'selected' => true
                    );
                }, explode(',', $data_external_users))) . ',';
            } else {
                $init_external_users = '';
            }
            $submit_name = 'update_bbb_session';
            $submit_id = "<input type=hidden name = 'id' value=" . getIndirectReference($session_id) . ">";
            $value_message = $langModify;
            $server = TcServer::LoadOneByCourse($this->course_id); // Find the server for this course as previously assigned. This may return false
        } else { // creating new session: set defaults
            $record = true;
            $types = $this->tc_types;
            $status = 1;
            $unlock_interval = '10';
            $r_group = array();
            $start_date = new DateTime();
            $start_session = $start_date->format('d-m-Y H:i');
            $end_date = new DateTime();
            $BBBEndDate = $end_date->format('d-m-Y H:i');
            $textarea = rich_text_editor('desc', 4, 20, '');
            $value_title = '';
            $init_external_users = '';
            $value_session_users = $c;
            $submit_name = 'new_bbb_session';
            $submit_id = '';
            $value_message = $langAdd;

            // Pick a server for the course
            $server = TcServer::LoadOneByTypes($this->tc_types, true);
            if (! $server) {
                debug_print_backtrace();
                die('[TcSessionHelper] No servers enabled for types ' . implode(',', $this->tc_types));
            }
        }

        $tool_content = "
        <div class='form-wrapper'>
        <form class='form-horizontal' role='form' name='sessionForm' action='$_SERVER[SCRIPT_NAME]' method='post' >
        <fieldset>
        <div class='form-group'>
            <label class='col-sm-2 control-label'>$langType:</label>
            <div class='col-sm-10'>
                    <div class='radio'>";
        foreach ($this->tc_types as $at) {
            $tool_content .= '<label><input type="checkbox" id="type_' . $at . '_button" name="type[]" value="' . 
                $at . '" ' . (in_array($at, $this->tc_types) ? " checked " : '') . '"> ' . $at.'</label>';
        }
        $tool_content .= "
                    </div>
            </div>
        </div>
        <div class='form-group'>
            <label for='title' class='col-sm-2 control-label'>$langTitle:</label>
            <div class='col-sm-10'>
                <input class='form-control' type='text' name='title' id='title' value='$value_title' placeholder='$langTitle' size='50'>
            </div>
        </div>
        <div class='form-group'>
            <label for='desc' class='col-sm-2 control-label'>$langUnitDescr:</label>
            <div class='col-sm-10'>
                $textarea
            </div>
        </div>
        <div class='form-group'>
            <label for='start_session' class='col-sm-2 control-label'>$langNewBBBSessionStart:</label>
            <div class='col-sm-10'>
                <input class='form-control' type='text' name='start_session' id='start_session' value='$start_session'>
            </div>
        </div>";
        $tool_content .= "<div class='input-append date form-group" . (Session::getError('BBBEndDate') ? " has-error" : "") . "' id='enddatepicker' data-date='$BBBEndDate' data-date-format='dd-mm-yyyy'>
            <label for='BBBEndDate' class='col-sm-2 control-label'>$langEnd:</label>
            <div class='col-sm-10'>
                <div class='input-group'>
                    <span class='input-group-addon'>
                        <input style='cursor:pointer;' type='checkbox' id='enableEndDate' name='enableEndDate' value='1'" . ($enableEndDate ? ' checked' : '') . ">
                    </span>
                    <input class='form-control' name='BBBEndDate' id='BBBEndDate' type='text' value='$BBBEndDate'" . ($enableEndDate ? '' : ' disabled') . ">
                </div>
                <span class='help-block'>" . (Session::hasError('BBBEndDate') ? Session::getError('BBBEndDate') : "&nbsp;&nbsp;&nbsp;<i class='fa fa-share fa-rotate-270'></i> $langBBBEndHelpBlock") . "</span>
            </div>
        </div>";
        $tool_content .= "<div class='form-group'>";

        // if no server assigned to course, show options. If the course is server-locked, show recoding options if server allows it
        if (! $server || $server->recording() && $server->locked) {
            $tool_content .= "<label for='group_button' class='col-sm-2 control-label'>$langBBBRecord:</label>
                        <div class='col-sm-10'>
                            <div class='radio'>
                              <label>
                                <input type='radio' id='user_button' name='record' value='true' " . (($record == true) ? 'checked' : '') . ">
                                $langBBBRecordTrue
                              </label>
                            </div>
                            <div class='radio'>
                              <label>
                                <input type='radio' id='group_button' name='record' value='false' " . (($record == false) ? 'checked' : '') . ">
                               $langBBBRecordFalse
                              </label>
                            </div>
                        </div>";
            if (! $server) {
                $tool_content .= "<div>$langBBBRecordingMayNotBeAvailable</div>";
            }
        } else {
            $tool_content .= "<div>$langBBBRecordingNotAvailable</div>";
        }

        $tool_content .= "</div>";

        $tool_content .= "<div class='form-group'>
            <label for='active_button' class='col-sm-2 control-label'>$langNewBBBSessionStatus:</label>
            <div class='col-sm-10'>
                    <div class='radio'>
                      <label>
                        <input type='radio' id='active_button' name='status' value='1' " . (($status == 1) ? "checked" : "") . ">
                        $langVisible
                      </label>
                      <label style='margin-left: 10px;'>
                        <input type='radio' id='inactive_button' name='status' value='0' " . (($status == 0) ? "checked" : "") . ">
                       $langInvisible
                      </label>
                    </div>
            </div>
        </div>
        <div class='form-group'>
        <label for='active_button' class='col-sm-2 control-label'>$langAnnouncements:</label>
            <div class='col-sm-10'>
                     <div class='checkbox'>
                      <label>
                        <input type='checkbox' name='addAnnouncement' value='1'>$langBBBAnnDisplay
                      </label>
                    </div>
            </div>
        </div>
        <div class='form-group'>
            <label for='minutes_before' class='col-sm-2 control-label'>$langBBBSessionAvailable:</label>
            <div class='col-sm-10'>" . selection(array(
            10 => '10',
            15 => '15',
            30 => '30'
        ), 'minutes_before', $unlock_interval, "id='minutes_before'") . "
                $langBBBMinutesBefore
            </div>
        </div>
        <div class='form-group'>
            <label for='sessionUsers' class='col-sm-2 control-label'>$langBBBSessionMaxUsers:</label>
            <div class='col-sm-10'>
                <input class='form-control' type='text' name='sessionUsers' id='sessionUsers' value='$value_session_users'> $langBBBSessionSuggestedUsers:
                <strong>$c</strong> (" . str_replace("{{RATIO}}", self::MAX_USERS_RECOMMENDATION_RATIO, str_replace("{{MAX}}", self::MAX_USERS, $langBBBSessionSuggestedUsers2)) . ")
            </div>
        </div>";
        $tool_content .= "<div class='form-group'>
                <label for='select-groups' class='col-sm-2 control-label'>$langParticipants:</label>
                <div class='col-sm-10'>
                <select name='groups[]' multiple='multiple' class='form-control' id='select-groups'>";
        // select available course groups (if exist)
        $res = Database::get()->queryArray("SELECT `group`.`id`,`group`.`name` FROM `group`
                                                    RIGHT JOIN course ON group.course_id=course.id
                                                    WHERE course.id=?d ORDER BY UPPER(NAME)", $this->course_id);
        foreach ($res as $r) {
            if (isset($r->id)) {
                $tool_content .= "<option value= '_{$r->id}'";
                if (in_array(("_{$r->id}"), $r_group)) {
                    $found_selected = true;
                    $tool_content .= ' selected';
                }
                $tool_content .= ">" . q($r->name) . "</option>";
            }
        }
        // select all users from this course except yourself
        $sql = "SELECT u.id user_id, CONCAT(u.surname,' ', u.givenname) AS name, u.username
                            FROM user u, course_user cu
                            WHERE cu.course_id = ?d
                            AND cu.user_id = u.id
                            AND cu.status != ?d
                            AND u.id != ?d
                            GROUP BY u.id, name, u.username
                            ORDER BY UPPER(u.surname), UPPER(u.givenname)";
        $res = Database::get()->queryArray($sql, $this->course_id, USER_GUEST, $uid);
        foreach ($res as $r) {
            if (isset($r->user_id)) {
                $tool_content .= "<option value='{$r->user_id}'";
                if (in_array(("$r->user_id"), $r_group)) {
                    $found_selected = true;
                    $tool_content .= ' selected';
                }
                $tool_content .= ">" . q($r->name) . " (" . q($r->username) . ")</option>";
            }
        }
        if ($found_selected == false) {
            $tool_content .= "<option value='0' selected><h2>$langAllUsers</h2></option>";
        } else {
            $tool_content .= "<option value='0'><h2>$langAllUsers</h2></option>";
        }

        $tool_content .= "</select><a href='#' id='selectAll'>$langJQCheckAll</a> | <a href='#' id='removeAll'>$langJQUncheckAll</a>
                </div>
            </div>";

        $tool_content .= "<div class='form-group'>
            <div class='col-sm-10 col-sm-offset-2'>
                     <div class='checkbox'>
                      <label>
                        <input type='checkbox' name='notifyUsers' value='1'>$langBBBNotifyUsers
                      </label>
                    </div>
            </div>
        </div>";

        $tool_content .= "
        <div class='form-group'>
            <label for='tags_1' class='col-sm-2 control-label'>$langBBBExternalUsers:</label>
            <div class='col-sm-10'>
                <select id='tags_1' class='form-control' name='external_users[]' multiple></select>
                <span class='help-block'>&nbsp;&nbsp;&nbsp;<i class='fa fa-share fa-rotate-270'></i> $langBBBNotifyExternalUsersHelpBlock</span>
            </div>
        </div>
        <div class='form-group'>
            <div class='col-sm-10 col-sm-offset-2'>
                     <div class='checkbox'>
                      <label>
                        <input type='checkbox' name='notifyExternalUsers' value='1'>$langBBBNotifyExternalUsers
                      </label>
                    </div>
            </div>
        </div>
        $submit_id
        <div class='form-group'>
            <div class='col-sm-10 col-sm-offset-2'>
                <input class='btn btn-primary' type='submit' name='$submit_name' value='$value_message'>
            </div>
        </div>
        </fieldset>
         " . generate_csrf_token_form_field() . "
        </form></div>";
        $tool_content .= "<script language='javaScript' type='text/javascript'>
        //<![CDATA[
            var chkValidator  = new Validator('sessionForm');
            chkValidator.addValidation('title', 'req', '" . js_escape($langBBBAlertTitle) . "');
            chkValidator.addValidation('sessionUsers', 'req', '" . js_escape($langBBBAlertMaxParticipants) . "');
            chkValidator.addValidation('sessionUsers', 'numeric', '" . js_escape($langBBBAlertMaxParticipants) . "');
            $(function () {
                $('#tags_1').select2({
                    $init_external_users
                    tags: true,
                    tokenSeparators: [',', ' '],
                    width: '100%',
                    selectOnClose: true});
                });
        //]]></script>";

        return $tool_content;
    }

    /*
     * @brief Process incoming session edit/add form
     * @return bool
     */
    public function process_form($session_id = 0)
    {
        if (isset($_POST['enableEndDate']) and ($_POST['enableEndDate'])) {
            $endDate_obj = DateTime::createFromFormat('d-m-Y H:i', $_POST['BBBEndDate']);
            $end = $endDate_obj->format('Y-m-d H:i:s');
        } else {
            $end = NULL;
        }

        $startDate_obj = DateTime::createFromFormat('d-m-Y H:i', $_POST['start_session']);
        $start = $startDate_obj->format('Y-m-d H:i:s');
        $notifyUsers = $addAnnouncement = $notifyExternalUsers = 0;
        if (isset($_POST['notifyUsers']) and $_POST['notifyUsers']) {
            $notifyUsers = 1;
        }
        if (isset($_POST['notifyExternalUsers']) and $_POST['notifyExternalUsers']) {
            $notifyExternalUsers = 1;
        }
        if (isset($_POST['addAnnouncement']) and $_POST['addAnnouncement']) {
            $addAnnouncement = 1;
        }
        $record = 'false';
        if (isset($_POST['record'])) {
            $record = $_POST['record'];
        }
        if (isset($_POST['external_users'])) {
            $ext_users = implode(',', $_POST['external_users']);
        } else {
            $ext_users = null;
        }

        if (isset($_POST['groups'])) {
            $r_group = $_POST['groups'];
        } else {
            $r_group = '0';
        }
        
        if ( isset($_POST['type']) ) {
            $this->tc_types = [];
            foreach($_POST['type'] as $t) {
                $t = strtolower(trim($t));
                if ( !array_key_exists($t,self::AVAILABLE_APIS) )
                    die('Invalid form data');
                $this->tc_types[] = $t;
            }
        }
        
        return $this->add_update($_POST['title'], $_POST['desc'], $start, $end, $_POST['status'], $notifyUsers, $notifyExternalUsers, $addAnnouncement, $_POST['minutes_before'], $ext_users, $record, $_POST['sessionUsers'], $r_group, $session_id);
    }

    /**
     *
     * @brief Insert scheduled session data into database
     * @global string $langBBBAddSuccessful
     * @global string $langBBBScheduledSession
     * @global string $langBBBScheduleSessionInfo
     * @global string $langBBBScheduleSessionInfoJoin
     * @param string $title
     * @param string $desc
     * @param string $start_session
     *            -- startDate in DB
     * @param string $BBBEndDate
     * @param int $status
     *            -- 1/0 (active)
     * @param string $notifyUsers
     *            -- "1" / "0" -- beyond booleans (tm)
     * @param string $notifyExternalUsers
     *            -- "1" / "0"
     * @param string $addAnnouncement
     *            -- "1" / "0"
     * @param int $minutes_before
     *            -- unlock_interval in DB
     * @param string $external_users
     * @param string $record
     *            --"true" / "false" -- gods help us
     * @param int $sessionUsers
     * @param array $groups
     *            - array of: 0=all users, _X=group id X, X=user X
     * @param int $session_id
     * @return bool
     */
    function add_update($title, $desc, $start_session, $BBBEndDate, $status, $notifyUsers, $notifyExternalUsers, 
        $addAnnouncement, $minutes_before, $external_users, $record, $sessionUsers, $groups, $session_id = 0)
    {
        global $langBBBScheduledSession, $langBBBScheduleSessionInfo, $langBBBScheduleSessionInfo2, $langBBBScheduleSessionInfoJoin,
        $langDescription, $course_code, $course_id, $urlServer, $tc_type;

        //Take this opportunity to re-select a server. Also, the type may have changed.
        $server = $this->pickServer();
        if ( !$server ) {
            Session::Messages($langAvailableBBBServers, 'alert-danger');
            return false;
        }
        
        if (is_array($groups) && count($groups) > 0) {
            $r_group = implode(',', $groups);
        } else {
            $r_group = '0';
        }
        if ($session_id != 0) { // updating/editing session
            $q = Database::get()->querySingle("UPDATE tc_session SET title=?s, description=?s, start_date=?t, end_date=?t,
                                        public=?s, active=?s, unlock_interval=?d, external_users=?s,running_at=?d,
                                        participants=?s, record=?s, sessionUsers=?d WHERE id=?d", $title, $desc, $start_session, 
                                        $BBBEndDate, 1, $status, $minutes_before, $external_users,$server->id,
                                        $r_group, $record, $sessionUsers, $session_id);

            if ($q === NULL)
                return false;

            // logging
            Log::record($course_id, MODULE_ID_TC, LOG_MODIFY, array(
                'id' => $session_id,
                'title' => $title,
                'desc' => html2text($desc)
            ));

            $q = Database::get()->querySingle("SELECT meeting_id, title, mod_pw, att_pw FROM tc_session WHERE id = ?d", $session_id);
        } else { // adding new session
            $server = $this->pickServer();
            if ( !$server ) {
                Session::Messages($langAvailableBBBServers, 'alert-danger');
                return false;
            }
            
            $q = Database::get()->query("INSERT INTO tc_session SET course_id = ?d,
                                                            title = ?s,
                                                            description = ?s,
                                                            start_date = ?t,
                                                            end_date = ?t,
                                                            public = 1,
                                                            active = ?s,
                                                            running_at = ?d,
                                                            meeting_id = ?s,
                                                            mod_pw = ?s,
                                                            att_pw = ?s,
                                                            unlock_interval = ?s,
                                                            external_users = ?s,
                                                            participants = ?s,
                                                            record = ?s,
                                                            sessionUsers = ?s", 
                $course_id, $title, $desc, $start_session, $BBBEndDate, $status, $server->id, generateRandomString(), 
                generateRandomString(), generateRandomString(), $minutes_before, $external_users, $r_group, $record, $sessionUsers);

            if (! $q)
                return false;

            $session_id = $q->lastInsertID; 
            $tc_session = self::getSessionById($session_id);
            print_r($tc_session);

            if (! $tc_session->create_meeting([
                'meetingId'=>$tc_session->meeting_id,
                'meetingName'=>$tc_session->title,
                'attendeePw'=>$tc_session->att_pw,
                'moderatorPw'=>$tc_session->mod_pw,
                'maxParticipants'=>$tc_session->sessionUsers,
                'record'=>$tc_session->record,
                //'duration'=>$tc_session->???,
            ]))
                die('Failed to create/schedule the meeting.');

            // logging
            Log::record($course_id, MODULE_ID_TC, LOG_INSERT, array(
                'id' => $q->lastInsertID,
                'title' => $_POST['title'],
                'desc' => html2text($_POST['desc']),
                'tc_type' => $tc_type
            ));

            $q = Database::get()->querySingle("SELECT meeting_id, title, mod_pw, att_pw FROM tc_session WHERE id = ?d", $q->lastInsertID);
        }
        $new_meeting_id = $q->meeting_id;
        $new_title = $q->title;
        // $new_mod_pw = $q->mod_pw; -- UNUSED
        $new_att_pw = $q->att_pw;
        // if we have to notify users for new session
        if ($notifyUsers == "1" && is_array($groups) and count($groups) > 0) {
            $recipients = array();
            if (in_array(0, $groups)) { // all users
                $result = Database::get()->queryArray("SELECT cu.user_id, u.email FROM course_user cu
                                                    JOIN user u ON cu.user_id=u.id
                                                WHERE cu.course_id = ?d
                                                AND u.email <> ''
                                                AND u.email IS NOT NULL", $course_id);
            } else {
                $r_group = '';
                foreach ($groups as $group) {
                    if ($group[0] == '_') { // find group users (if any) - groups start with _
                        $g_id = intval((substr($group, 1, strlen($group))));
                        $q = Database::get()->queryArray("SELECT user_id FROM group_members WHERE group_id = ?d", $g_id);
                        if ($q) {
                            foreach ($q as $row) {
                                $r_group .= "'$row->user_id'" . ',';
                            }
                        }
                    } else {
                        $r_group .= "'$group'" . ',';
                    }
                }
                $r_group = rtrim($r_group, ',');
                $result = Database::get()->queryArray("SELECT course_user.user_id, user.email
                                                        FROM course_user, user
                                                   WHERE course_id = ?d AND user.id IN ($r_group) AND
                                                         course_user.user_id = user.id", $course_id);
            }
            foreach ($result as $row) {
                $emailTo = $row->email;
                $user_id = $row->user_id;
                // we check if email notification are enabled for each user
                if (get_user_email_notification($user_id)) {
                    // and add user to recipients
                    array_push($recipients, $emailTo);
                }
            }
            if (count($recipients) > 0) {
                $emailsubject = $langBBBScheduledSession;
                //return $urlServer . "modules/tc/index.php?course=$course_code&amp;choice=do_join&amp;meeting_id=$new_meeting_id&amp;title=" . urlencode($new_title) . "&amp;att_pw=$new_att_pw";
                $bbblink = $this->get_join_link($urlServer.'modules/tc/index.php',$session_id,['att_pw'=>$new_att_pw,'title'=>urlencode($new_title)]);
                $emailheader = "
                <div id='mail-header'>
                    <div>
                        <div id='header-title'>$langBBBScheduleSessionInfo" . q($title) . " $langBBBScheduleSessionInfo2" . q($start_session) . "</div>
                    </div>
                </div>
            ";

                $emailmain = "
            <div id='mail-body'>
                <div><b>$langDescription:</b></div>
                <div id='mail-body-inner'>
                    $desc
                    <br><br>$langBBBScheduleSessionInfoJoin:<br><a href='$bbblink'>$bbblink</a>
                </div>
            </div>
            ";

                $emailcontent = $emailheader . $emailmain;
                $emailbody = html2text($emailcontent);
                // Notify course users for new bbb session
                send_mail_multipart('', '', '', $recipients, $emailsubject, $emailbody, $emailcontent);
            }
        }

        // Notify external users for new bbb session
        if ($notifyExternalUsers == "1") {
            if (isset($external_users)) {
                $recipients = explode(',', $external_users);
                $emailsubject = $langBBBScheduledSession;
                $emailheader = "
                    <div id='mail-header'>
                        <div>
                            <div id='header-title'>$langBBBScheduleSessionInfo" . q($title) . " $langBBBScheduleSessionInfo2" . q($start_session) . "</div>
                        </div>
                    </div>
                ";
                foreach ($recipients as $row) {
                    $bbblink = $this->get_join_link($urlServer.'modules/tc/ext.php',$session_id,['att_pw'=>$new_att_pw,'username'=>urlencode($row)]);
                    //$bbblink = $urlServer . "modules/tc/ext.php?course=$course_code&amp;meeting_id=$new_meeting_id&amp;username=" . urlencode($row);

                    $emailmain = "
                <div id='mail-body'>
                    <div><b>$langDescription:</b></div>
                    <div id='mail-body-inner'>
                        $desc
                        <br><br>$langBBBScheduleSessionInfoJoin:<br><a href='$bbblink'>$bbblink</a>
                    </div>
                </div>
                ";
                    $emailcontent = $emailheader . $emailmain;
                    $emailbody = html2text($emailcontent);
                    send_mail_multipart('', '', '', $row, $emailsubject, $emailbody, $emailcontent);
                }
            }
        }

        if ($addAnnouncement == '1') { // add announcement
            $orderMax = Database::get()->querySingle("SELECT MAX(`order`) AS maxorder FROM announcement
                                                   WHERE course_id = ?d", $course_id)->maxorder;
            $order = $orderMax + 1;
            Database::get()->querySingle("INSERT INTO announcement (content,title,`date`,course_id,`order`,visible)
                                    VALUES ('" . $langBBBScheduleSessionInfo . " \"" . q($title) . "\" " . $langBBBScheduleSessionInfo2 . " " . $start_session . "',
                                             '$langBBBScheduledSession', " . DBHelper::timeAfter() . ", ?d, ?d, '1')", $course_id, $order);
        }

        return true; // think positive
    }

    /**
     * * @brief Print a box with the details of a bbb session
     *
     * @param integer $course_id
     * @global type $tool_content
     * @global type $is_editor
     * @param string $course_code
     * @global type $uid
     * @global string $langBBBServer
     * @global type $langNewBBBSessionStart
     * @global type $langNewBBBSessionDesc,
     * @global type $langNewBBBSessionEnd,
     * @global type $langParticipants
     * @global type $langConfirmDelete
     * @global type $langBBBSessionJoin
     * @global type $langNote
     * @global type $langBBBNoteEnableJoin
     * @global type $langTitle
     * @global type $langActivate
     * @global type $langDeactivate
     * @global type $langEditChange
     * @global type $langDelete
     * @global type $langParticipate
     * @global type $langNoBBBSesssions
     * @global type $langDaysLeft
     * @global type $langHasExpiredS
     * @global type $langBBBNotServerAvailableStudent
     * @global type $langBBBNotServerAvailableTeacher
     * @global type $langBBBImportRecordings
     * @global type $langAllUsers
     * @global type $langBBBNoServerForRecording
     * @param string $tc_type
     */
    function tc_session_details()
    {
        global $is_editor, $uid, $langBBBServer, $langNewBBBSessionStart, $langParticipants, $langConfirmDelete, $langHasExpiredS, $langBBBSessionJoin, $langNote, $langBBBNoteEnableJoin, $langTitle, $langActivate, $langDeactivate, $langEditChange, $langDelete, $langParticipate, $langNoBBBSesssions, $langDaysLeft, $langBBBNotServerAvailableStudent, $langNewBBBSessionEnd, $langBBBNotServerAvailableTeacher, $langBBBImportRecordings, $langAllUsers, $langDate, $langBBBNoServerForRecording;

        $tool_content = '';

        $isActiveTcServer = $this->is_active_tc_server(); // cache this since it involves DB queries

        if (! $isActiveTcServer) { // check availability
            if ($is_editor) {
                $tool_content .= "<div class='alert alert-danger'>$langBBBNotServerAvailableTeacher</div>";
            } else {
                $tool_content .= "<div class='alert alert-danger'>$langBBBNotServerAvailableStudent</div>";
            }
        }

        load_js('trunk8');

        $myGroups = Database::get()->queryArray("SELECT group_id FROM group_members WHERE user_id=?d", $_SESSION['uid']);
        $activeClause = $is_editor ? '' : "AND active = '1'";
        $result = Database::get()->queryArray("SELECT tc_session.*,tc_servers.id as serverid,type FROM tc_session
                                                    INNER JOIN tc_servers ON tc_session.running_at=tc_servers.id
                                                    WHERE course_id = ?d $activeClause
                                                    ORDER BY start_date DESC", $this->course_id);
        if ($result) {
            if ((! $is_editor) and $isActiveTcServer) {
                $tool_content .= "<div class='alert alert-info'><label>$langNote</label>: $langBBBNoteEnableJoin</div>";
            }
            $headingsSent = false;
            $headings = "<div class='row'>
                           <div class='col-md-12'>
                             <div class='table-responsive'>
                               <table class='table-default'>
                                 <tr class='list-header'>
                                   <th style='width: 50%'>$langTitle</th>
                                   <th class='text-center'>$langDate</th>
                                   <th class='text-center'>$langParticipants</th>
                                   <th class='text-center'>$langBBBServer</th>
                                   <th class='text-center'>" . icon('fa-gears') . "</th>
                                 </tr>";
            foreach ($result as $row) {
                $participants = '';
                // Get participants
                $r_group = explode(",", $row->participants);
                foreach ($r_group as $participant_uid) {
                    if ($participants) {
                        $participants .= ', ';
                    }
                    $participant_uid = str_replace("'", '', $participant_uid);
                    if (preg_match('/^_/', $participant_uid)) {
                        $participants .= gid_to_name(str_replace("_", '', $participant_uid));
                    } else {
                        if ($participant_uid == 0) {
                            $participants .= $langAllUsers;
                        } else {
                            $participants .= uid_to_name($participant_uid, 'fullname');
                        }
                    }
                }
                $participants = "<span class='trunk8'>$participants</span>";
                $serverinfo = '#'.$row->serverid.'<br>'.$row->type;
                $title = $row->title;
                $start_date = $row->start_date;
                $end_date = $row->end_date;
                if ($end_date) {
                    $timeLeft = date_diff_in_minutes($end_date, date('Y-m-d H:i:s'));
                    $timeLabel = nice_format($end_date, TRUE);
                } else {
                    $timeLeft = date_diff_in_minutes($start_date, date('Y-m-d H:i:s'));
                    $timeLabel = '&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;';
                }
                if ($timeLeft > 0) {
                    $timeLabel .= "<br><span class='label label-warning'><small>$langDaysLeft " . format_time_duration($timeLeft * 60) . "</small></span>";
                } elseif (isset($end_date) and ($timeLeft < 0)) {
                    $timeLabel .= "<br><span class='label label-danger'><small>$langHasExpiredS</small></span>";
                }
                $meeting_id = $row->meeting_id;
                $record = $row->record;
                $desc = isset($row->description) ? $row->description : '';

                if (isset($end_date) and ($timeLeft < 0)) {
                    $canJoin = FALSE;
                } elseif (($row->active == '1') and (date_diff_in_minutes($start_date, date('Y-m-d H:i:s')) < $row->unlock_interval) and $isActiveTcServer) {
                    $canJoin = TRUE;
                } else
                    $canJoin = FALSE;

                if ($canJoin) {
                    $joinLink = '<a href="'.$this->get_join_link('',$row->id).'">'.q($title).'</a>';
                    //$joinLink = "<a href='$_SERVER[SCRIPT_NAME]?choice=do_join&amp;course=$this->course_code&amp;meeting_id=" . urlencode($meeting_id) . "' target='_blank'>" . q($title) . "</a>";
                } else {
                    $joinLink = q($title);
                }

                if ($row->running_at)
                    $course_server = TcServer::LoadById($row->running_at);
                else
                    $record = 'false';

                if ($record == 'false' or ! $course_server->recording()) {
                    $warning_message_record = "<span class='fa fa-info-circle' data-toggle='tooltip' data-placement='right' title='$langBBBNoServerForRecording'></span>";
                } else {
                    $warning_message_record = '';
                }

                if ($is_editor) {
                    if (! $headingsSent) {
                        $tool_content .= $headings;
                        $headingsSent = true;
                    }
                    $tool_content .= '<tr' . ($row->active ? '' : " class='not_visible'") . ">
                        <td>
                            <div class='table_td'>
                                <div class='table_td_header clearfix'>$joinLink $warning_message_record</div> 
                                <div class='table_td_body'>
                                    $desc
                                </div>
                            </div>
                        </td>
                        <td class='text-center'>
                            <div style='padding-top: 7px;'>  
                                <span class='text-success'>$langNewBBBSessionStart</span>: " . nice_format($start_date, TRUE) . "<br/>
                            </div>
                            <div style='padding-top: 7px;'>
                                <span class='text-danger'>$langNewBBBSessionEnd</span>: $timeLabel</br></br>
                            </div>
                        </td>
                        
                        <td style='width: 20%'>$participants</td>
                        <td>".$serverinfo."</td>
                        <td class='option-btn-cell'>" . action_button(array(
                        array(
                            'title' => $langEditChange,
                            'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($row->id) . "&amp;choice=edit",
                            'icon' => 'fa-edit'
                        ),
                        array(
                            'title' => $langBBBImportRecordings,
                            'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($row->id) . "&amp;choice=import_video",
                            'icon' => "fa-edit",
                            'show' => in_array('bbb', $this->tc_types)
                        ),
                        array(
                            'title' => $langParticipate,
                            'url' => "tcuserduration.php?id=$row->id",
                            'icon' => "fa-clock-o",
                            'show' => in_array('bbb', $this->tc_types)
                        ),
                        array(
                            'title' => $row->active ? $langDeactivate : $langActivate,
                            'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($row->id) . "&amp;choice=do_" . ($row->active ? 'disable' : 'enable'),
                            'icon' => $row->active ? 'fa-eye' : 'fa-eye-slash'
                        ),
                        array(
                            'title' => $langDelete,
                            'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($row->id) . "&amp;choice=do_delete",
                            'icon' => 'fa-times',
                            'class' => 'delete',
                            'confirm' => $langConfirmDelete
                        )
                    )) . "</td></tr>";
                } else {
                    $access = FALSE;
                    // Allow access to session only if user is in participant group or session is scheduled for everyone
                    $r_group = explode(",", $row->participants);
                    if (in_array('0', $r_group)) { // all users
                        $access = TRUE;
                    } else {
                        if (in_array("$uid", $r_group)) { // user search
                            $access = TRUE;
                        } else {
                            foreach ($myGroups as $user_gid) { // group search
                                if (in_array("_$user_gid->group_id", $r_group)) {
                                    $access = TRUE;
                                }
                            }
                        }
                    }

                    // Always allow access to editor switched to student view
                    $access = $access || (isset($_SESSION['student_view']) and $_SESSION['student_view'] == $course_code);

                    if ($access) {
                        if (! $headingsSent) {
                            $tool_content .= $headings;
                            $headingsSent = true;
                        }
                        $tool_content .= "<tr>
                            <td>
                            <div class='table_td'>
                                <div class='table_td_header clearfix'>$joinLink</div> $warning_message_record
                                <div class='table_td_body'>
                                    $desc
                                </div>
                            </div>
                        </td>
                        <td class='text-center'>
                            <div style='padding-top: 7px;'>  
                                <span class='text-success'>$langNewBBBSessionStart</span>: " . nice_format($start_date, TRUE) . "<br/>
                            </div>
                            <div style='padding-top: 7px;'>
                                <span class='text-danger'>$langNewBBBSessionEnd</span>: $timeLabel</br></br>
                            </div>
                        </td>
                            <td style='width: 20%'>$participants</td>
                            <td class='text-center'>";
                        // Join url will be active only X minutes before scheduled time and if session is visible for users
                        if ($canJoin) {
                            $tool_content .= icon('fa-sign-in', $langBBBSessionJoin, $joinLink);
                        } else {
                            $tool_content .= "-</td>";
                        }
                        $tool_content .= "</tr>";
                    }
                }
            }
            if ($headingsSent) {
                $tool_content .= "</table></div></div></div>";
            }

            if (! $is_editor and ! $headingsSent) {
                $tool_content .= "<div class='alert alert-warning'>$langNoBBBSesssions</div>";
            }
        } else {
            $tool_content .= "<div class='alert alert-warning'>$langNoBBBSesssions</div>";
        }
        return $tool_content;
    }

    /**
     *
     * @brief find enabled tc server for this course
     * @return boolean
     */
    function is_active_tc_server()
    {
        $s = TcServer::LoadAllByTypes($this->tc_types, true); // only get enabled servers
        if (! $s || count($s) == 0)
            return false;

        if (count($s) > 0) {
            foreach ($s as $data) {
                if ($data->all_courses == 1) { // tc_server is enabled for all courses
                    return true;
                } else { // check if tc_server is enabled for specific course
                    $q = Database::get()->querySingle("SELECT * FROM course_external_server
                                    WHERE course_id = ?d AND external_server = ?d", $this->course_id, $data->id);
                    if ($q) {
                        return true;
                    }
                }
            }
            return false;
        } else { // no active tc_servers
            return false;
        }
    }
    
    /**
     *  @brief Pick a server for a new session based on all available information for the course
     */
    function pickServer() {
        $types = $this->tc_types;
        array_walk($types,function(&$value) { $value = '"'.$value.'"'; });
        $types = implode(',',$types);
        $t = Database::get()->querySingle("SELECT tcs.* FROM course_external_server ces 
                INNER JOIN tc_servers tcs ON tcs.id=ces.external_server
                WHERE ces.course_id = ?d AND tcs.type IN(".$types.") AND enabled='true' 
                ORDER BY tcs.weight ASC", $this->course_id);
        if ($t) { //course uses specific tc_servers
            $server = $t;
        } else { // will use default tc_server
            //get type first via the servers table
            $server = Database::get()->querySingle("SELECT * FROM tc_servers WHERE `type` IN(".$types.") and enabled = 'true' ORDER BY weight ASC");
        }
        return $server;
    }
    
    private function get_join_link($url,$session_id, $additionalParams=[]) {
        $params = [ 'choice'=>'do_join', 'session_id'=>$session_id];
        $params = array_merge($params,$additionalParams);
        return $url.'?'.http_build_query($params);
    }
}

/**
 *
 * @brief Generate random strings. Used to create meeting_id, attendance password and moderator password
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10)
{
    return substr(str_shuffle(implode(array_merge(range(0, 9), range('A', 'Z'), range('a', 'z')))), 0, $length);
}

/**
 *
 * @brief function to calculate date diff in minutes in order to enable join link
 * @param string $start_date
 * @param string $current_date
 * @return int
 */
function date_diff_in_minutes($start_date, $current_date)
{
    return round((strtotime($start_date) - strtotime($current_date)) / 60);
}
