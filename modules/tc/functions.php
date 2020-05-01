<?php

/*
 * ========================================================================
 * Open eClass
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2017 Greek Universities Network - GUnet
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
require_once 'bbb-api.php';

require "f_session.php";

function tc_configured_apis() {
    $tc_types =[];
    if (get_config('ext_bigbluebutton_enabled')) {
        $tc_types[] = 'bbb';
    }
    if (get_config('ext_openmeetings_enabled')) {
        $tc_types[] = 'om';
    }
    if (get_config('ext_webconf_enabled')) {
        $tc_types[] = 'webconf';
    }
    if (get_config('ext_zoom_enabled')) {
        $tc_types[] = 'zoom';
    }
    return $tc_types;
}

//FIXME: Used in include/tools.php to display teleconference link in course tools
function is_configured_tc_server() {
    global $course_id;
    return tc_configured_apis();
}
