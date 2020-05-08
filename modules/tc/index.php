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
global $langBBB,$langNewBBBSession,$langBBBRecordUserParticipation,
$langBBBUpdateSuccessful,$langBBBDeleteSuccessful,$langBBBCreationRoomError,$langBBBConnectionError,$langBBBAddSuccessful;
global $langModify,$langParticipate,$langGeneralError;
global $is_editor,$langBack,$uid,$language,$course_id,$course_code;

//TODO: these are globals set in this file, check their declaration/use
global $pageName,$toolName,$navigation;
global $require_current_course,$require_login,$require_help,$helpTopic,$guest_allowed;


$require_current_course = TRUE;
$require_login = TRUE;
$require_help = TRUE;
$helpTopic = 'tc';
$guest_allowed = false;

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
require_once 'include/log.class.php'; // for logging
require_once 'functions.php';

require_once 'include/lib/modalboxhelper.class.php';
ModalBoxHelper::loadModalBox();

/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_TC);
/* * *********************************** */

$toolName = $langBBB;

load_js('tools.js');
load_js('bootstrap-datetimepicker');
load_js('validation.js');
load_js('select2');

$head_content .= '<script type="text/javascript" src="tc.js"></script>';

$tc_types = tc_configured_apis(); //all available apis globally
$tc_session_helper = new TcSessionHelper($course_id,$course_code,$tc_types);
$isactiveserver = $tc_session_helper->is_active_tc_server();

if ($is_editor) {
    if (isset($_GET['add']) or isset($_GET['choice'])) {
        if (isset($_GET['add'])) {
            $pageName = $langNewBBBSession;
        } elseif ((isset($_GET['choice'])) and $_GET['choice'] == 'edit') {
            $pageName = $langModify;
        }
        $tool_content .= action_bar(array(
            array(
                'title' => $langBack,
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
                'icon' => 'fa-reply',
                'level' => 'primary-label'
            )
        ));
    } else {
        if (isset($_GET['id'])) {
            $tool_content .= action_bar(array(
                array(
                    'title' => $langBack,
                    'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
                    'icon' => 'fa-reply',
                    'level' => 'primary-label'
                )
            ));
        } else {
            $tool_content .= action_bar(array(
                array(
                    'title' => $langNewBBBSession,
                    'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;add=1",
                    'icon' => 'fa-plus-circle',
                    'button-class' => 'btn-success',
                    'level' => 'primary-label',
                    'show' => $isactiveserver
                ),
                array(
                    'title' => $langBBBRecordUserParticipation,
                    'url' => "tc_attendance.php?course=$course_code",
                    'icon' => 'fa-group',
                    'level' => 'primary-label',
                    'link-attrs' => "id=popupattendance1",
                    'show' => $isactiveserver
                ),
                array(
                    'title' => $langParticipate,
                    'url' => "tcuserduration.php?course=$course_code",
                    'icon' => 'fa-clock-o',
                    'level' => 'primary-label'
                )
            ));
        }
    }
} else {
    $tool_content .= action_bar(array(
        array(
            'title' => $langParticipate,
            'url' => "tcuserduration.php?course=$course_code&amp;u=true",
            'icon' => 'fa-clock-o',
            'level' => 'primary-label'
        )
    ));
}


if (isset($_GET['add'])) {
    $navigation[] = array(
        'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
        'name' => $langBBB
    );
    $tool_content .= $tc_session_helper->form();
} elseif (isset($_POST['update_bbb_session'])) {
    if (! isset($_POST['token']) || ! validate_csrf_token($_POST['token']))
        csrf_token_error();

    if (! $tc_session_helper->process_form(getDirectReference($_POST['id']))) {
        Session::Messages($langGeneralError, 'alert-danger');
    } else {
        Session::Messages($langBBBUpdateSuccessful, 'alert-success');
        redirect("index.php?course=$course_code");
    }
} elseif (isset($_GET['choice'])) {
    $navigation[] = array(
        'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
        'name' => $langBBB
    );
    
    //Set up the session
    if (isset($_GET['id'])) {
        $session_id = getDirectReference($_GET['id']);
        $tc_session = $tc_session_helper->getSessionById($session_id);
        if ( !$tc_session )
            throw new RuntimeException('Failed to load session '.$session_id);
    } elseif (isset($_GET['session_id'])) {
        $tc_session = $tc_session_helper->getSessionById($_GET['session_id']);
    }
    
    switch ($_GET['choice']) {
        case 'edit':
            $tool_content .= $tc_session_helper->form($session_id);
            break;
        case 'do_delete':
            if ($tc_session->delete()) {
                Session::Messages($langBBBDeleteSuccessful, 'alert-success');
            } else {
                Session::Messages($langGeneralError, 'alert-danger');
            }
            redirect_to_home_page("modules/tc/index.php?course=$course_code");
            break;
        case 'do_disable':
            if ($tc_session->disable()) {
                Session::Messages($langBBBUpdateSuccessful, 'alert-success');
            } else {
                Session::Messages($langGeneralError, 'alert-danger');
            }
            redirect_to_home_page("modules/tc/index.php?course=$course_code");
            break;
        case 'do_enable':
            if ($tc_session->enable()) {
                Session::Messages($langBBBUpdateSuccessful, 'alert-success');
            } else {
                Session::Messages($langGeneralError, 'alert-danger');
            }
            redirect_to_home_page("modules/tc/index.php?course=$course_code");
            break;
        case 'do_join':
            $serv = $tc_session->getRunningServer();
            if (! $serv) {
                die("This session is not running on any server. I don't know where to check...");
            }

            // gotta be careful here - we want to put all participants in the same session, not split them around servers
            // we assume we'll only create the session once, so getting set up on a server should lock the session there
            //TODO: Add support in plugins for LOCKING a session to a server - this needs DB modification
            echo 'Checking if meeting is scheduled (known to server)...<br>';
            
            if ($tc_session->IsKnownToServer()) {
                echo 'Meeting is Known to server<br>';
            } else {
                echo 'Meeting is not known to server. Creating meeting....<br>';
                if (! $tc_session->create_meeting()) {
                    Session::Messages($langBBBCreationRoomError, 'alert-danger');
                    redirect_to_home_page("modules/tc/index.php?course=$course_code");
                } else
                    echo 'Meeting created<br>';
            }

            echo 'Checking if meeting is currently running...<br>';
            if (! $tc_session->IsRunning()) {
                echo 'Meeting is not running.<br>';
                if (! $tc_session->start_meeting()) {
                    Session::Messages($langBBBConnectionError, 'alert-danger');
                    redirect_to_home_page("modules/tc/index.php?course=$course_code");
                } else
                    echo 'Meeting Started<br>';
            } else {
                echo 'Meeting is running.<br>';
            }

            // TODO: handle possible errors: $langBBBConnectionError,$langBBBMaxUsersJoinError,
            // redirect("index.php?course=$course_code");
            // $tool_content .= "<div class='alert alert-warning'>$langBBBMaxUsersJoinError</div>";
            $x = $tc_session->join_user([
                'host' => $is_editor,
                'pw' => $is_editor ? $tc_session->mod_pw : $tc_session->mod_att,
                'name' => $_SESSION['surname'] . ' ' . $_SESSION['givenname'],
                'uid' => $uid
            ]);
            if (! $x) {
                Session::Messages('FAILED TO JOIN MEETING - PROBABLY FULL');
                redirect_to_home_page("modules/tc/index.php?course=$course_code");
            }

            break;
        case 'import_video':
            $tool_content .= $tc_session->publish_video_recordings(getDirectReference($_GET['id']));
            break;
    }
} elseif (isset($_POST['new_bbb_session'])) { // new BBB session
    if (! isset($_POST['token']) || ! validate_csrf_token($_POST['token']))
        csrf_token_error();
    if (! $tc_session_helper->process_form()) {
        Session::Messages($langGeneralError, 'alert-danger');
    } else {
        Session::Messages($langBBBAddSuccessful, 'alert-success');
    }
    redirect_to_home_page("modules/tc/index.php?course=$course_code");
} else { // display list of conferences
    $tool_content .= $tc_session_helper->tc_session_details();
}

add_units_navigation(TRUE);
draw($tool_content, 2, null, $head_content);
