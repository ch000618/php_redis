<?php
$db_name='test';
//---寫入專用---
$db_user_w='aj_w';
$db_pass_w='aj1234';
//---讀取專用---
$db_user_r='aj_r';
$db_pass_r='aj1234';
//---
//db insert
$insert_db['host']='localhost';
$insert_db['user']=$db_user_w;
$insert_db['password']=$db_pass_w;
$insert_db['db']=$db_name;
//db select
$select_db['host']='localhost';
$select_db['user']=$db_user_r;
$select_db['password']=$db_pass_r;
$select_db['db']=$db_name;
?>
