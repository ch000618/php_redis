<?php
date_default_timezone_set('Asia/Taipei');
//-------- WEB目錄
$web_cfg= array();
$web_cfg['path']=dirname(dirname(__FILE__)).'/';
$web_cfg['path_text']=$web_cfg['path'].'text/';
$web_cfg['path_conf']=$web_cfg['path'].'conf/';//設定檔
$web_cfg['path_lib']=$web_cfg['path'].'lib/';//函式
include_once('redis_connect.php');
include_once('connect.php');
?>
