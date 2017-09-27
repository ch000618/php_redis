<?php
include_once('class.redis.php');
include_once('../conf/sys_config.php');
//建立 redis 簡單連線
$redis=mke_redis_link($red_set);
init();
function init(){
	//這裡是接收需求端
	/*
		mod=命令 
	*/
	$mod=$_GET['mod'];
	switch($mod){
		case 'set':
			init_set();
			break;
		case 'get':
			init_get();
			break;
		case 'del':
			init_del();
			break;
	}
}
function init_set(){
	global $redis;
	$key=$_POST['key'];
	$val=$_POST['val'];
	$json=json_encode($val);
	if($json==''){return;}
	$ret=$redis->php_redis_set($key,$json);
	echo $ret;
}
function init_get(){
	global $redis;
	$key=$_POST['key'];
	if($key==''){return;}
	$ret=$redis->php_redis_get($key);
	echo json_decode($ret,true);
}
function init_del(){
	global $redis;
	$key=$_POST['key'];
	if($key==''){return;}
	$ret=$redis->php_redis_del($key);
	echo $ret;
}
?>