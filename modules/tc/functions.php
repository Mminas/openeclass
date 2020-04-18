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

