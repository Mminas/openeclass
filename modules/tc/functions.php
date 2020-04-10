<?php

/* ========================================================================
 * Open eClass
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2017  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ========================================================================
 */


require_once 'bbb-api.php';

require "f_session.php";

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
 * @param string $record 'true' or 'false'
 */
function create_meeting($title, $meeting_id, $mod_pw, $att_pw, $record)
{
    global $langBBBCreationRoomError, $langBBBConnectionError, $course_code, $langBBBWelcomeMsg, $langBBBConnectionErrorOverload;

    $run_to = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE meeting_id = ?s", $meeting_id)->running_at;
    if (isset($run_to)) {
        $participants = get_tc_participants($meeting_id);
        if (!is_tc_server_available($run_to, $participants)) { // if existing bbb server is busy try to find next one
            $r = Database::get()->queryArray("SELECT id FROM tc_servers
                            WHERE `type`= 'bbb' AND enabled='true' AND id <> ?d ORDER BY weight ASC", $run_to);
            if (($r) and count($r) > 0) {
                foreach ($r as $server) {
                    if (is_tc_server_available($server->id, $participants)) {
                        $run_to = $server->id;
                        Database::get()->query("UPDATE tc_session SET running_at = ?d WHERE meeting_id = ?s", $run_to, $meeting_id);
                        break;
                    } else {
                        $run_to = -1; // no bbb server available
                    }
                }
            } else {
                $run_to = -1; // no bbb server exists
            }
        }
    }

    if ($run_to == -1) {
        Session::Messages($langBBBConnectionErrorOverload, 'alert-danger');
        redirect_to_home_page("modules/tc/index.php?course=$course_code");
    } else { // create the meeting
        $res = Database::get()->querySingle("SELECT * FROM tc_servers WHERE id=?d AND `type`='bbb'", $run_to);
        $salt = $res->server_key;
        $bbb_url = $res->api_url;
        $duration = 0;
        $r = Database::get()->querySingle("SELECT start_date, end_date FROM tc_session WHERE meeting_id = ?s", $meeting_id);
        if (($r->start_date != null) and ($r->end_date != null)) {
            $date_start = new DateTime($r->start_date);
            $date_end = new DateTime($r->end_date);
            $hour_duration = $date_end->diff($date_start)->h; // hour
            $min_duration = $date_end->diff($date_start)->i; // minutes
            $duration = $hour_duration*60 + $min_duration;
        }

        $bbb = new BigBlueButton($salt, $bbb_url);

        $creationParams = array(
            'meetingId' => $meeting_id, // REQUIRED
            'meetingName' => $title, // REQUIRED
            'attendeePw' => $att_pw, // Match this value in getJoinMeetingURL() to join as attendee.
            'moderatorPw' => $mod_pw, // Match this value in getJoinMeetingURL() to join as moderator.
            'welcomeMsg' => $langBBBWelcomeMsg, // ''= use default. Change to customize.
            'dialNumber' => '', // The main number to call into. Optional.
            'voiceBridge' => '', // PIN to join voice. Optional.
            'webVoice' => '', // Alphanumeric to join voice. Optional.
            'logoutUrl' => '', // Default in bigbluebutton.properties. Optional.
            'maxParticipants' => '-1', // Optional. -1 = unlimited. Not supported in BBB. [number]
            'record' => $record, // New. 'true' will tell BBB to record the meeting.
            'duration' => $duration // Default = 0 which means no set duration in minutes. [number]
            //'meta_category' => '', // Use to pass additional info to BBB server. See API docs.
        );


        // Create the meeting and get back a response:
        $result = $bbb->createMeeting($creationParams);
        // If it's all good, then we've interfaced with our BBB php api OK:
        if ($result == null) {
            // If we get a null response, then we're not getting any XML back from BBB.
            Session::Messages($langBBBConnectionError, 'alert-danger');
            redirect_to_home_page("modules/tc/index.php?course=$course_code");
        }
        if ($result['returncode'] != 'SUCCESS') {
            Session::Messages($langBBBCreationRoomError, 'alert-danger');
            redirect_to_home_page("modules/tc/index.php?course=$course_code");
        }
    }
}


/**
 * @brief create join as moderator link
 * @param string $meeting_id
 * @param string $mod_pw
 * @param string $att_pw
 * @param string $surname
 * @param string $name
 * @return string
 */
function bbb_join_moderator($meeting_id, $mod_pw, $att_pw, $surname, $name) {

    $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE meeting_id = ?s",$meeting_id);
    if ($res) {
        $running_server = $res->running_at;
    }

    $res = Database::get()->querySingle("SELECT * FROM tc_servers WHERE id=?s", $running_server);

    if ($res) {
        $salt = $res->server_key;
        $bbb_url = $res->api_url;

        // Instantiate the BBB class:
        $bbb = new BigBlueButton($salt, $bbb_url);

        $joinParams = array(
            'meetingId' => $meeting_id, // REQUIRED - We have to know which meeting to join.
            'username' => $surname . " " . $name,   // REQUIRED - The user display name that will show in the BBB meeting.
            'password' => $mod_pw,  // REQUIRED - Must match either attendee or moderator pass for meeting.
            'createTime' => '', // OPTIONAL - string
            'userId' => '', // OPTIONAL - string
            'webVoiceConf' => ''    // OPTIONAL - string
        );
        // Get the URL to join meeting:

        try {
            $result = $bbb->getJoinMeetingURL($joinParams);
        }
        catch (Exception $e) {
            echo $e->getMessage();
            return $result;
        }
    }
    return $result;
}

/**
 * @brief create join as simple user link
 * @param string $meeting_id
 * @param string $att_pw
 * @param string $surname
 * @param string $name
 * @return string
 */
function bbb_join_user($meeting_id, $att_pw, $surname, $name) {

    $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE meeting_id = ?s",$meeting_id);
    if ($res) {
        $running_server = $res->running_at;
    }

    $res = Database::get()->querySingle("SELECT * FROM tc_servers WHERE id = ?d", $running_server);

    $salt = $res->server_key;
    $bbb_url = $res->api_url;

    // Instantiate the BBB class:
    $bbb = new BigBlueButton($salt,$bbb_url);

    $joinParams = array(
        'meetingId' => $meeting_id, // REQUIRED - We have to know which meeting to join.
        'username' => $surname . " " . $name,   // REQUIRED - The user display name that will show in the BBB meeting.
        'password' => $att_pw,  // REQUIRED - Must match either attendee or moderator pass for meeting.
        'createTime' => '', // OPTIONAL - string
        'userId' => '', // OPTIONAL - string
        'webVoiceConf' => ''    // OPTIONAL - string
    );
    // Get the URL to join meeting:
    $result = $bbb->getJoinMeetingURL($joinParams);

    return $result;
}

/**
 * @brief get connected number of users of BBB server
 * @param string $salt
 * @param string $bbb_url
 * @param string $ip
 * @return int
 */
function get_connected_users($salt, $bbb_url)
{

    $sum = 0;
    // instantiate the BBB class
    $bbb = new BigBlueButton($salt,$bbb_url);

    $meetings = $bbb->getMeetingsWithXmlResponseArray();
    if (!$meetings) {
        $meetings = array();
    }
    foreach ($meetings as $meeting) {
        $mid = $meeting['meetingId'];
        $pass = $meeting['moderatorPw'];
        if ($mid != null) {
            $info = $bbb->getMeetingInfoWithXmlResponseArray($bbb,$bbb_url,$salt,array('meetingId' => $mid, 'password' => $pass));
            $sum += $info['participantCount'];
        }
    }
    return $sum;

}

/**
 * @brief get number of active rooms
 * @param string $salt
 * @param string $bbb_url
 * @return int
 */
function get_active_rooms($salt,$bbb_url)
{
    $sum = 0;
    // instantiate the BBB class
    $bbb = new BigBlueButton($salt,$bbb_url);

    $meetings = $bbb->getMeetingsWithXmlResponseArray();

    if ($meetings) {
        foreach ($meetings as $meeting) {
            $mid = $meeting['meetingId'];
            //$pass = $meeting['moderatorPw'];
            if ($mid != null) {
                $sum += 1;
            }
        }
    }

    return $sum;
}

/**
 * @brief display video recordings in multimedia
 * @global string $langBBBImportRecordingsOK
 * @global string $langBBBImportRecordingsNo
 * @global string $tool_content;
 * @param int $course_id
 * @param int $id
 * @return string
 */
function publish_video_recordings($course_id, $id)
{
    global $langBBBImportRecordingsOK, $langBBBImportRecordingsNo, $langBBBImportRecordingsNoNew;

    $sessions = Database::get()->queryArray("SELECT tc_session.id, tc_session.course_id AS course_id,"
            . "tc_session.title, tc_session.description, tc_session.start_date,"
            . "tc_session.meeting_id, course.prof_names FROM tc_session "
            . "LEFT JOIN course ON tc_session.course_id=course.id WHERE course.code=?s AND tc_session.id=?d", $course_id, $id);

    $servers = Database::get()->queryArray("SELECT * FROM tc_servers WHERE enabled='true' AND `type` = 'bbb'");

    $perServerResult = array(); /*AYTO THA EINAI TO ID THS KATASTASHS GIA KATHE SERVER*/

    $tool_content = '';
    if (($sessions) && ($servers)) {
        $msgID = array();
        foreach ($servers as $server) {
            $salt = $server->server_key;
            $bbb_url = $server->api_url;

            $bbb = new BigBlueButton($salt, $bbb_url);
            $sessionsCounter = 0;
            foreach ($sessions as $session) {
                $recordingParams = array(
                    'meetingId' => $session->meeting_id,
                );
                $ch = curl_init();
                $timeout = 0;
                curl_setopt ($ch, CURLOPT_URL, $bbb->getRecordingsUrl($recordingParams));
                curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                $recs = curl_exec($ch);
                curl_close($ch);

                $xml = simplexml_load_string($recs);
                // If not set, it means that there is no video recording.
                // Skip and search for next one
                if (isset($xml->recordings->recording/*->playback->format->url*/)) {
                   foreach($xml->recordings->recording as $recording) {
                        $url = (string) $recording->playback->format->url;
                        // Check if recording already in videolinks and if not insert
                        $c = Database::get()->querySingle("SELECT COUNT(*) AS cnt FROM videolink WHERE url = ?s",$url);
                        if ($c->cnt == 0) {
                            Database::get()->querySingle("INSERT INTO videolink (course_id,url,title,description,creator,publisher,date,visible,public)"
                            . " VALUES (?s,?s,?s,IFNULL(?s,'-'),?s,?s,?t,?d,?d)",$session->course_id,$url,$session->title,strip_tags($session->description),$session->prof_names,$session->prof_names,$session->start_date,1,1);
                            $msgID[$sessionsCounter] = 2;  /*AN EGINE TO INSERT SWSTA PAIRNEI 2*/
                        } else {
                            if(isset($msgID[$sessionsCounter])) {
                                if($msgID[$sessionsCounter] <= 1)  $msgID[$sessionsCounter] = 1;  /*AN DEN EXEI GINEI KANENA INSERT MEXRI EKEINH TH STIGMH PAIRNEI 1*/
                            }
                            else  $msgID[$sessionsCounter] = 1;
                        }
                    }
                } else {
                    $msgID[$sessionsCounter] = 0;  /*AN DEN YPARXOUN KAN RECORDINGS PAIRNEI 0*/
                }
                $sessionsCounter++;
            }
            $finalMsgPerSession = max($msgID);
            array_push($perServerResult, $finalMsgPerSession);
        }
        $finalMsg = max($perServerResult);
        switch($finalMsg)
        {
            case 0:
                $tool_content .= "<div class='alert alert-warning'>$langBBBImportRecordingsNo</div>";
                break;
            case 1:
                $tool_content .= "<div class='alert alert-warning'>$langBBBImportRecordingsNoNew</div>";
                break;
            case 2:
                $tool_content .= "<div class='alert alert-success'>$langBBBImportRecordingsOK</div>";
                break;
        }
    }
    return $tool_content;
}

/**
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
function get_meeting_users($salt,$bbb_url,$meeting_id,$pw)
{
    global $langBBBGetUsersError, $langBBBConnectionError, $course_code;

    // Instantiate the BBB class:
    $bbb = new BigBlueButton($salt,$bbb_url);

    $infoParams = array(
        'meetingId' => $meeting_id, // REQUIRED - We have to know which meeting.
        'password' => $pw,  // REQUIRED - Must match moderator pass for meeting.
    );

    // Now get meeting info and display it:
    $result = $bbb->getMeetingInfoWithXmlResponseArray($bbb,$bbb_url,$salt,$infoParams);
    // If it's all good, then we've interfaced with our BBB php api OK:
    if ($result == null) {
        // If we get a null response, then we're not getting any XML back from BBB.
        Session::Messages($langBBBConnectionError, 'alert-danger');
        redirect("index.php?course=$course_code");
    } else {
        // We got an XML response, so let's see what it says:
        if (isset($result['messageKey'])) {
            Session::Messages($langBBBGetUsersError, 'alert-danger');
            redirect("index.php?course=$course_code");
        } else {
            return $result['participantCount'];
        }
    }
    return $result['participantCount'];
}


/**
 * @brief check if tc_server is enabled for all courses
 * @param string $tc_type
 * @return boolean
 */
function is_tc_server_enabled_for_all($tc_type) {

    $q = Database::get()->queryArray("SELECT all_courses FROM tc_servers WHERE enabled='true' AND `type` = '$tc_type'");
    if (count($q) > 0) {
       foreach ($q as $data) {
           if ($data->all_courses == 1) { // server is enabled for all courses
               return true;
           } else {
               return false;
           }
       }
    } else { // no active servers
        return false;
    }
}
/**
 * @brief find enabled tc server
 * @param int $course_id
 * @param string $tc_type
 * @return boolean
 */
function is_active_tc_server($tc_type, $course_id) {    
    
    $q = Database::get()->queryArray("SELECT id, all_courses FROM tc_servers WHERE enabled='true'
                                AND `type` = ?s ORDER BY weight",$tc_type);
    
    if (count($q) > 0) {
        foreach ($q as $data) {
            if ($data->all_courses == 1) { // tc_server is enabled for all courses
                return true;
            } else { // check if tc_server is enabled for specific course                
                $q = Database::get()->querySingle("SELECT * FROM course_external_server
                                    WHERE course_id = ?d AND external_server = ?d", $course_id,$data->id);
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
 * @brief checks if tc server is configured
 * @return string|boolean
 */
function is_configured_tc_server() {

    if (get_config('ext_bigbluebutton_enabled')) {
        $tc_type = 'bbb';
    } elseif (get_config('ext_openmeetings_enabled')) {
        $tc_type = 'om';
    } elseif (get_config('ext_webconf_enabled')) {
        $tc_type = 'webconf';
    } else {
        return false;
    }
    return $tc_type;
}

/**
 * @brief check if bbb server is available
 * @global type $course_id
 * @param int $server_id
 * @param int $participants
 * @return boolean
 */
function is_tc_server_available($server_id, $participants)
{

    global $course_id;

    //Get all course participants
    if ($participants == 0) {
        $users_to_join = Database::get()->querySingle("SELECT COUNT(*) AS count FROM course_user, user
                                WHERE course_user.course_id = ?d AND course_user.user_id = user.id", $course_id)->count;
    } else { // get specific participants
        $users_to_join = $participants;
    }

    $row = Database::get()->querySingle("SELECT id, server_key, api_url, max_rooms, max_users
                                    FROM tc_servers WHERE id = ?d AND enabled = 'true'", $server_id);
    if ($row) {
        $max_rooms = $row->max_rooms;
        $max_users = $row->max_users;
        // get connected users
        $connected_users = get_connected_users($row->server_key, $row->api_url);
        // get active rooms
        $active_rooms = get_active_rooms($row->server_key ,$row->api_url);
        //cases
        // max_users = 0 && max_rooms = 0 - UNLIMITED
        // active_rooms < max_rooms && active_users < max_users
        // active_rooms < max_rooms && max_users = 0 (UNLIMITED)
        // active_users < max_users && max_rooms = 0 (UNLIMITED)
        if (($max_rooms == 0 && $max_users == 0)
            or (($max_users >= ($users_to_join + $connected_users)) and $active_rooms <= $max_rooms)
            or ($active_rooms <= $max_rooms and $max_users == 0)
            or (($max_users >= ($users_to_join + $connected_users)) && $max_rooms == 0)) // YOU FOUND THE SERVER
            {
                return true;
            } else {
                return false;
        }
    } else {
        return false;
    }

}


/**
 * @brief get tc title given its meeting id
 * @param string $meeting_id
 * @return string
 */
function get_tc_title($meeting_id) {
    
    global $course_id;
    
    $result = Database::get()->querySingle("SELECT title FROM tc_session 
                    WHERE meeting_id = ?s AND course_id = ?d", $meeting_id, $course_id)->title;
    
    return $result;
    
}

/**
 * @brief get encoded tc meeting id given its db id
 * @param int $id
 * @return mixed
 */
function get_tc_meeting_id($id) {
    
    $result = Database::get()->querySingle("SELECT meeting_id FROM tc_session 
                    WHERE id = ?d", $id)->meeting_id;
    
    return $result;
}

/**
 * @brief get tc meeting id given its encoded meeting id
 * @param string $meeting_id
 * @return mixed
 */
function get_tc_id($meeting_id) {
    $result = Database::get()->querySingle("SELECT id FROM tc_session 
                    WHERE meeting_id = ?s", $meeting_id)->id;
    
    return $result;
    
}


/**
 * @brief get tc meeting participants
 * @param $meeting_id
 * @return int
 */
function get_tc_participants($meeting_id) {
    $r = Database::get()->querySingle("SELECT participants FROM tc_session 
                    WHERE meeting_id = ?s", $meeting_id)->participants;

    if ($r == '0') {
        return 0;
    } else {
        return count(explode(',', preg_replace('/_/','', $r)));
    }

}