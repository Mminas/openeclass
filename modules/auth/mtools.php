<?php
/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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
 * ======================================================================== */


$require_mlogin = true;
$require_mcourse = true;
require_once('../../include/minit.php');
require_once('../../include/tools.php');


$toolArr = getSideMenu(2);

$groupsArr = array();
$toolsArr = array();

if (is_array($toolArr)) {
    $numOfToolGroups = count($toolArr);
    
    for ($i = 0; $i < $numOfToolGroups; $i++) {
        if ($toolArr[$i][0]['type'] == 'text') {
            $group = new stdClass();
            $group->id = $i;
            $group->name = $toolArr[$i][0]['text'];
            $groupsArr[] = $group;
            
            $numOfTools = count($toolArr[$i][1]);
            for ($j = 0; $j < $numOfTools; $j++) {
                $tool = new stdClass();
                $tool->id = (isset($toolArr[$i][4][$j])) ? $toolArr[$i][4][$j] : null;
                $tool->name = $toolArr[$i][1][$j];
                $tool->link = $toolArr[$i][2][$j];
                $tool->img = $toolArr[$i][3][$j];
                $tool->type = getTypeFromImage($toolArr[$i][3][$j]);
                $tool->active = getActiveFromImage($toolArr[$i][3][$j]);
                $toolsArr[$i][] = $tool;
            }
        }
    }
}

echo createDom($groupsArr, $toolsArr);
exit();


//////////////////////////////////////////////////////////////////////////////////////

function createDom($groupsArr, $toolsArr) {
	$dom = new DomDocument('1.0', 'utf-8');
        
        $root = $dom->appendChild($dom->createElement('tools'));
        
        foreach ($groupsArr as $group) {
            
            if (isset($toolsArr[$group->id])) {
                
                $g = $root->appendChild($dom->createElement('toolgroup'));
                $gname = $g->appendChild(new DOMAttr('name', $group->name));
                
                foreach($toolsArr[$group->id] as $tool) {
                    $t = $g->appendChild($dom->createElement('tool'));
                    
                    $name = $t->appendChild(new DOMAttr('name', $tool->name));
                    $link = $t->appendChild(new DOMAttr('link', correctLink($tool->link)));
                    $type = $t->appendChild(new DOMAttr('type', $tool->type));
                    $acti = $t->appendChild(new DOMAttr('active', $tool->active));
                    
                }
            }
        }

	$dom->formatOutput = true;
        $ret = $dom->saveXML();
        return $ret;
}

function correctLink($value) {
    global $urlServer;
    
    $containsRelPath = (substr($value, 0, strlen("../..")) === "../..") ? true : false;
    
    $ret = $value;
    if ($containsRelPath)
        $ret = $urlServer . substr($value, strlen("../../"), strlen($value));
    
    $profile = (isset($_SESSION['profile'])) ? '&profile='.$_SESSION['profile'] : '' ;
    $redirect = '&redirect='. urlencode($ret);
    
    $ret = $urlServer .'modules/auth/mlogin.php?token='. session_id() . $profile . $redirect;
        
    return $ret;
}

function getTypeFromImage($value) {
    $ret = $value;
    
    if (substr($value, (strlen('_on.png') * -1)) == '_on.png')
        $ret = substr($value, 0, (strlen('_on.png') * -1));
            
    if (substr($value, (strlen('_off.png') * -1)) == '_off.png')
        $ret = substr($value, 0, (strlen('_off.png') * -1));
    
    return $ret;
}

function getActiveFromImage($value) {
    $ret = "true";
    
    if (substr($value, (strlen('_off.png') * -1)) === '_off.png')
        $ret = "false";
    
    return $ret;
}
