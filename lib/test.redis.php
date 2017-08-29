<?php
ini_set('display_errors', 1);
include_once('class.redis.php');
$db_set=array(
	'host'=>'192.168.1.188'
	,'port'=>6379
	,'db'=>0
	,'pass'=>''
);
test_set();
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
	$redis=mke_redis_link($db_set);
	$hset=array("1234"=>'aaaa');
	$json=json_encode($hset);
	$redis->php_redis_set('hset',$json);
}

test_get();
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
//test_del();
/*
	刪除
	hset 這個KEY 裡面的值
*/
function test_del(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$ret=$redis->php_redis_del('hset');
}
//test_fork_redis_set();
//
function test_fork_redis_set(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$max=15;
	$chk_time=10000;
	$list_key="abc";
	//$redis->php_redis_del($list_key);
	for($i=1;$i<=$max;$i++){
		$key="000".$i;
		$hset=array($key=>$i);
		$json=json_encode($hset);
		$pid =pcntl_fork();
		if($pid == -1){
				echo '無法多工執行';
				exit(1);
		}
		if($pid){
			//如果是程式 本身就產生分身 並休息
			$children[] = $pid;
			usleep($chk_time);
		}else{
			//是程式的分身,就出 foreach 迴圈去跑程式
			break ;
		}
	}
	//等待副程序執行完
	$status = null;
	if($pid){
		foreach($children as $pid){
			pcntl_waitpid($pid, $status);
		}
	}
	
	//副程序多工執行區
	if($pid == -1){
		echo '無法多功執行';
		exit(1);
	}else if($pid){
		
	}else {
		$redis->php_redis_rpush($list_key,$json);
		//$redis->php_redis_del($list_key);
		//$redis->php_redis_set($key,$json);
		//$sRet=$redis->php_redis_get($key);
		/*if($sRet!=""){
			echo $key." : ".$sRet."\n";
		}*/
		//$redis->php_redis_del($key);
		exit(0);
	}
}
//test_redis_get();
//
function test_redis_get(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$max=100;
	$chk_time=0;
	for($i=0;$i<$max;$i++){
		$key="000".$i;
		$sRet=$redis->php_redis_get($key);
		if($sRet==""){continue;}
		echo $key.":".$sRet."\n";
	}
	//print_r($ret);
}
//test_redis_redis_lrange();
//
function test_redis_redis_lrange(){
	global $db_set;
	$redis=mke_redis_link($db_set);
	$start=0;
	$end=-1;
	$list_key="abc";
	$ret=$redis->php_redis_lrange($list_key,$start,$end);
	print_r($ret);
}
?>