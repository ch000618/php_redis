<?php
include_once('class.redis.php');
include_once('../conf/sys_config.php');
//建立 redis 簡單連線
$redis=mke_redis_link($db_set);
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
	global $redis;
	$hset=array("1234"=>'aaaa');
	$json=json_encode($hset);
	$redis->php_redis_set('hset',$json);
}

//test_get();
/*
	查詢
	hset 這個KEY 裡面的值
*/
function test_get(){
	global $redis;
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
	global $redis;
	$redis->php_redis_del('hset');
}
//test_fork_redis_set();
//test_fork_redis_get();
//test_fork_redis_del();
//test_multi_redis_set();
//test_multi_redis_get();
//test_multi_redis_del();
//
function test_fork_redis_set(){
	global $redis;
	$max=6;
	$chk_time=10000;
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
		$hset=array(
			'id'=>$i
			,'time'=>date('Y-m-d H:i:s')
		);
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
		$redis->php_redis_set($key,$json);
		exit(0);
	}
}
function test_fork_redis_get(){
	global $redis;
	$max=6;
	$chk_time=10000;
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
		$pipe=$redis->php_redis_get($key);
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
		$sRet=$redis->php_redis_get($key);
		if($sRet!=""){
			echo "[".$sRet."]"."\n";
		}
		exit(0);
	}
}
//
function test_fork_redis_del(){
	global $redis;
	$max=6;
	$chk_time=10000;
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
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
		$redis->php_redis_del($key);
		exit(0);
	}
}
//
function test_multi_redis_get(){
	global $redis;
	$max=6;
	$redis->php_redis_multi();
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
		$pipes=$redis->php_redis_get($key);
	}
	$aRet=$pipes->exec();
	print_r($aRet);
}
//
function test_multi_redis_set(){
	global $redis;
	$max=6;
	$redis->php_redis_multi();
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
		$hset=array(
			'id'=>$i
			,'time'=>date('Y-m-d H:i:s')
		);
		$json=json_encode($hset);
		$pipes=$redis->php_redis_set($key,$json);
	}
	$aRet=$pipes->exec();
	print_r($aRet);
}
//
function test_multi_redis_del(){
	global $redis;
	$max=6;
	$redis->php_redis_multi();
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
		$pipes=$redis->php_redis_del($key);
	}
	$aRet=$pipe->exec();
	print_r($aRet);
}
?>