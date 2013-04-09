<?php

// check data first
if (empty($_POST)){
	echo 'empty post';
	exit;
} 
require_once '../config.php';

$URL = $_POST['url'];

// check proxy requests
$pattern = "proxy/index.php?url=";
if (strpos($URL, $pattern)) {
  list($remove, $URL) = explode($pattern, $URL);
  $URL = base64_decode($URL);
}
  
    $logid = db_insert(TBL_PREFIX.TBL_CACHE, "file, url, title, saved", "'".$htmlfile."', '".$URL."', '".$_POST['urltitle']."', NOW()");

// verify
if (!isset($logid)) {
	echo 'no logid';
	exit;
}

/* client browser stats ----------------------------------------------------- */

$browser = new Browser();

// save browser id
$bname = db_select(TBL_PREFIX.TBL_BROWSERS, "id", "name='".$browser->getBrowser()."'");
if (!$bname) {
  $browserid = db_insert(TBL_PREFIX.TBL_BROWSERS, "name", "'".$browser->getBrowser()."'");
} else {
  $browserid = $bname['id'];
}
// save OS id
$osname = db_select(TBL_PREFIX.TBL_OS, "id", "name='".$browser->getPlatform()."'");
if (!$osname) {
  $osid = db_insert(TBL_PREFIX.TBL_OS, "name", "'".$browser->getPlatform()."'");
} else {
  $osid = $osname['id'];
}

/* create database entry ---------------------------------------------------- */
$fields  = "client_id,cache_id,os_id,browser_id,browser_ver,user_agent,";
$fields .= "ftu,ip,scr_width,scr_height,vp_width,vp_height,";
$fields .= "sess_date,sess_time,fps,coords_x,coords_y,clicks,hovered,clicked,body"; 

$values  = "'". $_POST['client']                    ."',";
$values .= "'". $logid                              ."',";
$values .= "'". $osid                               ."',";
$values .= "'". $browserid                          ."',";
$values .= "'". (float) $browser->getVersion()      ."',";
$values .= "'". $browser->getUserAgent()            ."',";

$values .= "'". (int) $_POST['ftu']                 ."',";
$values .= "'". get_ip()                            ."',";
$values .= "'". (int) $_POST['screenw']             ."',";
$values .= "'". (int) $_POST['screenh']             ."',";
$values .= "'". (int) $_POST['pagew']               ."',";
$values .= "'". (int) $_POST['pageh']               ."',";

$values .= "NOW(),";
$values .= "'". (float) $_POST['time']              ."',";
$values .= "'". (int)   $_POST['fps']               ."',";
$values .= "'". $_POST['xcoords']                   ."',";
$values .= "'". $_POST['ycoords']                   ."',";
$values .= "'". $_POST['clicks']                    ."',";
$values .= "'". array_sanitize($_POST['elhovered']) ."',";
$values .= "'". array_sanitize($_POST['elclicked']) ."'";
$values .= "'". $_POST['body'] ."'";

$uid = db_insert(TBL_PREFIX.TBL_RECORDS, $fields, $values);
// send user ID back to the record script
echo $uid;
?>
