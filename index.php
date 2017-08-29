<?php
ini_set('display_errors', 1);
include_once('conf/sys_config.php');
include_once($web_cfg['path_lib'].'class.redis.php');
include_once($web_cfg['path_lib'].'func.chf.php');
include_once($web_cfg['path_conf'].'redis_connect.php');
//test_set();
/*
	新增
	它的機制是 
	KEY VALUE
	每個KEY 都是一張表
	而且只有一皆能用文字KEY 表裡面的內容只有數字KEY
	因為數字KEY 不容易查詢 新增 在表裡面 無法避免重復
	所以只好先轉成json 用set的方式塞進去
	每次set 都會把原先的資料覆蓋掉
*/
function test_set(){
	global $db_set;
	global $web_cfg;
	$redis=mke_redis_link($db_set);
	// *如果檔案不存在,回傳'Err'
	$sFile=$web_cfg['path_text'].'/json/'.'changlong_klc.json';
	if(!file_exists($sFile)){return 'Err';}
	$json_str=chf_get_cache_opernfile($sFile);
	$jsonformt=chf_get_cache_analyJson($json_str);
	if($jsonformt!='1'){return 'Err';}
	$redis->php_redis_set('hset',$json_str);
}
//test_get();
/*
	查詢
	hset 這個KEY 裡面的值
*/
function test_get(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$json=$redis->php_redis_get('hset');
	$ret=json_decode($json,true);
	print_r($ret);
}
test_lpush();
function test_lpush(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$redis->php_redis_lpush('user_id','2');
	$redis->php_redis_set('user_name_2', 'admin');
	$redis->php_redis_set('user_level_2','9999');
	$ret=$redis->php_redis_get('user_id');
	print_r($ret);
	//echo $ret;
}
//test_del();
/*
	刪除
	hset 這個KEY 裡面的值
*/
function test_del(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$ret=$redis->php_redis_del('user_id');
}
?>