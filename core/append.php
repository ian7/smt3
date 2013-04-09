<?php
// check data first
if (empty($_POST)) exit;
require_once '../config.php';

$fields  = "body";
$values  = "COMPRESS('".  $_POST['body']  ."')";

$body_id = db_insert("smt2_bodies",$fields, $values);
echo $body_id;

$values  = "sess_time = '".                         (float) $_POST['time']    ."',";
$values .= "vp_width  = '".                         (int)   $_POST['pagew']   ."',";
$values .= "vp_height = '".                         (int)   $_POST['pageh']   ."',";
$values .= "coords_x  = CONCAT(COALESCE(coords_x, ''), ',". $_POST['xcoords'] ."'),";
$values .= "coords_y  = CONCAT(COALESCE(coords_y, ''), ',". $_POST['ycoords'] ."'),";
$values .= "clicks    = CONCAT(COALESCE(clicks,   ''), ',". $_POST['clicks']  ."'),";
$values .= "hovered   = CONCAT(COALESCE(hovered,  ''), ',". array_sanitize($_POST['elhovered']) ."'),";
$values .= "clicked   = CONCAT(COALESCE(clicked,  ''), ',". array_sanitize($_POST['elclicked']) ."'),";
$values .= "bodies    = CONCAT(COALESCE(bodies,   ''), ',". strval($body_id)  ."')";

db_update(TBL_PREFIX.TBL_RECORDS, $values, "id='".$_POST['uid']."'");
?>