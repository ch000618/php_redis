<?php
$db_name='test';
//---寫入專用---
$db_user_w='fst_rst_w';
$db_pass_w='Frw_rst_2016';
//---讀取專用---
$db_user_r='fst_rst_r';
$db_pass_r='Frr_rst_2016';
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
