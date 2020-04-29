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
$require_current_course = TRUE;
$require_login = TRUE;
$require_help = TRUE;
$helpTopic = 'tc';
$guest_allowed = false;

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
// for logging
require_once 'include/log.class.php';
// For creating bbb urls & params
require_once 'bbb-api.php';
require_once 'om-api.php';
require_once 'webconf-api.php';
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

$head_content .= "
<script type='text/javascript'>

// Bootstrap datetimepicker Initialization
$(function() {
$('input#start_session').datetimepicker({
        format: 'dd-mm-yyyy hh:ii',
        pickerPosition: 'bottom-right',
        language: '" . $language . "',
        autoclose: true
    });
});

</script>";

$head_content .= "<script type='text/javascript'>
        $(function() {
            $('#BBBEndDate').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                language: '" . $language . "',
                autoclose: true
            }).on('changeDate', function(ev){
                if($(this).attr('id') === 'BBBEndDate') {
                    $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
                }
            }).on('blur', function(ev){
                if($(this).attr('id') === 'BBBEndDate') {
                    var end_date = $(this).val();
                    if (end_date === '') {
                        if ($('input[name=\"dispresults\"]:checked').val() == 4) {
                            $('input[name=\"dispresults\"][value=\"1\"]').prop('checked', true);
                        }
                        $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
                    }
                }
            });
            $('#enableEndDate').change(function() {
                var dateType = $(this).prop('id').replace('enable', '');
                if($(this).prop('checked')) {
                    $('input#BBB'+dateType).prop('disabled', false);
                    if (dateType === 'EndDate' && $('input#BBBEndDate').val() !== '') {
                        $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
                    }
                } else {
                    $('input#BBB'+dateType).prop('disabled', true);
                    if ($('input[name=\"dispresults\"]:checked').val() == 4) {
                        $('input[name=\"dispresults\"][value=\"1\"]').prop('checked', true);
                    }
                    $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
                }
            });
        });
    </script>";

load_js('select2');

$head_content .= "<script type='text/javascript'>
    $(document).ready(function () {
        $('#popupattendance1').click(function() {
	     window.open($(this).prop('href'), '', 'height=200,width=500,scrollbars=no,status=no');
	     return false;
	});

        $('#select-groups').select2();
        $('#selectAll').click(function(e) {
            e.preventDefault();
            var stringVal = [];
            $('#select-groups').find('option').each(function(){
                stringVal.push($(this).val());
            });
            $('#select-groups').val(stringVal).trigger('change');
        });
        $('#removeAll').click(function(e) {
            e.preventDefault();
            var stringVal = [];
            $('#select-groups').val(stringVal).trigger('change');
        });
    });

    function onAddTag(tag) {
        alert('Added a tag: ' + tag);
    }
    function onRemoveTag(tag) {
        alert('Removed a tag: ' + tag);
    }

    function onChangeTag(input,tag) {
        alert('Changed a tag: ' + tag);
    }

    $(function() {
        $('#tags_1').select2({tags:[], formatNoMatches: ''});
    });
</script>
";

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
                die('This session is not running on any server.');
            }

            // gotta be careful here - we want to put all participants in the same session, not split them around servers
            // we assume we'll only create the session once, so getting set up on a server should lock the session there
            //TODO: Add support in plugins for LOCKING a session to a server - this needs DB modification
            echo 'Checking if meeting is scheduled (known to server)...<br>';
            //print_r($tc_session);
            //die();
            
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

            // TODO: MOVE THESE TO THE PLUGINS
            /*
             * } elseif ($tc_type == 'om') { // if tc server is `om`
             * if (om_session_running($_GET['meeting_id']) == false) { // create meeting
             * create_om_meeting($_GET['title'],$_GET['meeting_id'],$_GET['record']);
             * }
             * if(isset($_GET['mod_pw'])) { // join moderator (== $is_editor)
             * header('Location: ' . om_join_user($_GET['meeting_id'],$_SESSION['uname'], $_SESSION['uid'], $_SESSION['email'], $_SESSION['surname'], $_SESSION['givenname'], 1));
             * } else { // join user
             * header('Location: ' . om_join_user($_GET['meeting_id'],$_SESSION['uname'], $_SESSION['uid'], $_SESSION['email'], $_SESSION['surname'], $_SESSION['givenname'], 0));
             * }
             * } elseif ($tc_type == 'webconf') { // if tc server is `webconf`
             * create_webconf_jnlp_file($_GET['meeting_id']);
             * $webconf_server = $serv->hostname;
             * $screenshare_server = $serv->screenshare;
             * header('Location: ' . get_config('base_url') . '/modules/tc/webconf/webconf.php?user=' . $_SESSION['surname'] . ' ' . $_SESSION['givenname'].'&meeting_id='.$_GET['meeting_id'].'&base_url='. base64_encode(get_config('base_url')).'&webconf_server='. base64_encode($webconf_server).'&screenshare_server='. base64_encode($screenshare_server) .'&course='.$course_code);
             * }
             */
            break;
        case 'import_video':
            $tool_content .= publish_video_recordings($course_code, getDirectReference($_GET['id']));
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
    require_once 'zoom-api.php';
    /*$za = new Zoom(['url'=>'https://api.zoom.us/v2/','key'=>'a','secret'=>'b,c']);
    $za->x();
    die();*/
    
    $tool_content .= $tc_session_helper->tc_session_details();
}

add_units_navigation(TRUE);
draw($tool_content, 2, null, $head_content);
