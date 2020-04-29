<?php

/* ========================================================================
 * Open eClass 
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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

// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_admin = true;
require_once '../../include/baseTheme.php';
require_once 'modules/tc/functions.php';

$toolName = $langBBBConf;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$navigation[] = array('url' => 'extapp.php', 'name' => $langExtAppConfig);

$available_themes = active_subdirs("$webDir/template", 'theme.html');

load_js('tools.js');
load_js('validation.js');
load_js('select2');

$head_content .= "<script type='text/javascript'>
    $(document).ready(function () {                
        $('#select-courses').select2();
        $('#selectAll').click(function(e) {
            e.preventDefault();
            var stringVal = [];
            $('#select-courses').find('option').each(function(){
                stringVal.push($(this).val());
            });
            $('#select-courses').val(stringVal).trigger('change');
        });
        $('#removeAll').click(function(e) {
            e.preventDefault();
            var stringVal = [];
            $('#select-courses').val(stringVal).trigger('change');
        });
    });
</script>";

$available_themes = active_subdirs("$webDir/template", 'theme.html');
if (isset($_GET['delete_server'])) {
    $id = getDirectReference($_GET['delete_server']);
    Database::get()->querySingle("DELETE FROM tc_servers WHERE id=?d", $id);
    Session::Messages($langFileUpdatedSuccess, 'alert-success');
    redirect_to_home_page('modules/admin/bbbmoduleconf.php');
}
else if (isset($_POST['submit'])) { // PROCESS CREATE / EDIT
    $key = $_POST['key_form'];
    $api_url = $_POST['api_url_form'];
    if (!preg_match('/\/$/', $api_url)) { // append '/' if doesn't exist
        $api_url = $api_url . '/';
    }
    $max_rooms = $_POST['max_rooms_form'];
    $max_users = $_POST['max_users_form'];
    $enable_recordings = $_POST['enable_recordings'];
    $enabled = $_POST['enabled'];
    $weight = $_POST['weight'];
    $tc_courses = $_POST['tc_courses'];    
    if (in_array(0, $tc_courses)) {
        $allcourses = 1; // tc server is assigned to all courses
    } else {
        $allcourses = 0; // tc server is assigned to specific courses
    }
    
    echo 'all courses: '.$allcourses;
    if (isset($_POST['id_form'])) { //EDIT
        $tc_id = getDirectReference($_POST['id_form']);
        Database::get()->querySingle("UPDATE tc_servers SET server_key = ?s,
                api_url = ?s,
                max_rooms =?s,
                max_users =?s,
                enable_recordings =?s,
                enabled = ?s,
                weight = ?d,
                all_courses = ?d
                WHERE id =?d", $key, $api_url, $max_rooms, $max_users, $enable_recordings, $enabled, $weight, $allcourses, $tc_id);
        Database::get()->query("DELETE FROM course_external_server WHERE external_server = ?d", $tc_id);
    }
    else { //CREATE
        $q = Database::get()->query("INSERT INTO tc_servers (`type`, server_key, api_url, max_rooms, max_users, enable_recordings, enabled, weight, all_courses) VALUES
        ('bbb', ?s, ?s, ?s, ?s, ?s, ?s, ?d, ?d)", $key, $api_url, $max_rooms, $max_users, $enable_recordings, $enabled, $weight, $allcourses);
        $tc_id = $q->lastInsertID;
    }
    if ($allcourses == 0) {
        foreach ($tc_courses as $tc_data) {
            Database::get()->query("INSERT INTO course_external_server SET course_id = ?d, external_server = ?d", $tc_data, $tc_id);
            Database::get()->query("UPDATE tc_session SET running_at = ?d WHERE course_id = ?d", $tc_id, $tc_data);
        }
    }
    // Display result message
    Session::Messages($langFileUpdatedSuccess,"alert-success");
    redirect_to_home_page("modules/admin/tcmoduleconf.php");
}
elseif (isset($_GET['add_server']) || isset($_GET['edit_server'])) { //SHOW CREATE/EDIT FORM
    $pageName = isset($_GET['add_server']) ? $langAddServer : $langEdit;
    $toolName = $langBBBConf;
    $navigation[] = array('url' => 'tcmoduleconf.php', 'name' => $langBBBConf);
    $data['action_bar'] = action_bar([
                [
                    'title' => $langBack,
                    'url' => "tcmoduleconf.php",
                    'icon' => 'fa-reply',
                    'level' => 'primary-label'
                ]
            ]);
    $data['enabled_recordings'] = true;
    $data['enabled'] = true;


    $available_courses_list = Database::get()->queryArray("SELECT id, code, title FROM course WHERE id
                                                        NOT IN (SELECT course_id FROM course_external_server)
                                                        AND visible != " . COURSE_INACTIVE . "
                                                    ORDER BY title");
    $listcourses = '';
    if (isset($_GET['add_server'])) {
        $listcourses .=  "<option value='0' selected><h2>$langToAllCourses</h2></option>";
    } else {        
        $data['bbb_server'] = getDirectReference($_GET['edit_server']);
        $data['server'] = TcServer::LoadById($data['bbb_server']);

        $data['enabled_recordings'] = $data['server']->enable_recordings;
        $data['enabled'] = $data['server']->enabled;
                
        if ($data['server']->all_courses) {
            $listcourses .= "<option value='0' selected><h2>$langToAllCourses</h2></option>";
        } else {
            $linked_courses_list = Database::get()->queryArray("SELECT id, code, title FROM course WHERE id 
                                        IN (SELECT course_id FROM course_external_server WHERE external_server = ?d) 
                                        ORDER BY title", $data['bbb_server']);
            if ( $linked_courses_list === null )
                die('Failed to get linked courses list!');
            foreach($linked_courses_list as $c) {
                $listcourses .= "<option value='$c->id' selected>" . q($c->title) . " (" . q($c->code) . ")</option>";
            }
            $listcourses .= "<option value='0'><h2>$langToAllCourses</h2></option>";
        }

        
    }
    foreach($available_courses_list as $c) {
        $listcourses .= "<option value='$c->id'>" . q($c->title) . " (" . q($c->code) . ")</option>";
    }
    $data['listcourses'] = $listcourses;
    $view = 'admin.other.extapps.bbb.create';
} else {    // Display config edit form

    //display available BBB servers
    $data['action_bar'] = action_bar(array(
        array('title' => $langAddServer,
            'url' => "bbbmoduleconf.php?add_server",
            'icon' => 'fa-plus-circle',
            'level' => 'primary-label',
            'button-class' => 'btn-success'),
        array('title' => $langBack,
            'url' => "extapp.php",
            'icon' => 'fa-reply',
            'level' => 'primary-label')));

    $data['bbb_servers'] = TcServer::LoadAll();
    $view = 'admin.other.extapps.bbb.index';
}

$data['menuTypeID'] = 3;
view($view, $data);

