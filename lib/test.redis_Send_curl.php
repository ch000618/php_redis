<?php
include_once('../conf/sys_config.php');
init();
function init(){
	//為了在console 可以下參數 
	/*
		使用方式  php
		test.redis_Send_curl.php -m 
		用m這個參數來決定要執行哪一隻
	*/
	$param_arr = getopt('m:');
	$mod=$param_arr['m'];
	switch($mod){
		case 'set':
			test_multi_curl_redis_sand_set();
			break;
		case 'get':
			test_multi_curl_redis_sand_get();
			break;
		case 'del':
			test_multi_curl_redis_sand_del();
			break;
	}
}
//用curl 多執行緒 寫資料到redis
function test_multi_curl_redis_sand_set(){
	$bet=api_get_bet();
	//print_r($bet);
	$i=0;
	if(count($bet)<1){return;}
	foreach($bet as $key =>$val){
		$hset=json_encode($val);
		$url_list[$i]['url']='1.aj.me/php_redis/lib/test.redis_curl.php?mod=set';
		$url_list[$i]['post']['key']=$key;
		$url_list[$i]['post']['val']=$hset;
		$i++;
	}
	//print_r($url_list);
	test_sand_curl($url_list);
}
//用curl 多執行緒 到redis讀資料
function test_multi_curl_redis_sand_get(){
	$bet=api_get_bet();
	if(count($bet)<1){return;}
	$i=0;
	foreach($bet as $key =>$val){
		$hset=$val;
		$url_list[$i]['url']='1.aj.me/php_redis/lib/test.redis_curl.php?mod=get';
		$url_list[$i]['post']['key']=$key;
		$i++;
	}
	test_sand_curl($url_list);
}
//用curl 多執行緒 到redis刪資料
function test_multi_curl_redis_sand_del(){
	$max=1000;
	for($i=1;$i<=$max;$i++){
		$key="bet_".$i;
		$url_list[$i]['url']='1.aj.me/php_redis/lib/test.redis_curl.php?mod=del';
		$url_list[$i]['post']['key']=$key;
	}
	test_sand_curl($url_list);
}
//多執行緒執行redis 送post 
/*
*/
function test_sand_curl($url_list){
	$debug=false;
	$ret=array();
	$handle  = array();
	$mh = curl_multi_init();
	$hosts=array();
	$i = 0;
	$running = 0;
	foreach($url_list as $k => $data) {
		$url=$data['url'];
		$POST=$data['post'];
		$ch = curl_init();
		$user_agent="Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($POST));
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_multi_add_handle($mh, $ch);
		$handle[$i++] = $ch;
	}
	if($debug){echo "do{}while()\n";}
	/* 執行CURL */
	do{
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
  } while ($running > 0);
	
	foreach($handle as $k =>$ch) {
		$content  = curl_multi_getcontent($ch);
		$contents[$k] = (curl_errno($ch) == 0) ? $content : false;
		echo $content;
	}
	/* 移除 handle*/
	foreach($handle as $i =>$ch) {
		curl_multi_remove_handle($mh, $ch);
	}
  curl_multi_close($mh);
	//print_r($contents);
}

function api_get_bet(){
	// 建立CURL連線
	$ch = curl_init();
	$http = "http://rdfast.net/server/service/test_api_draws_bet_sum.php";
	//echo $http;
	$URL=$http;
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	// 執行
	$str=curl_exec($ch);
	// 關閉CURL連線
	curl_close($ch);
	//echo $obj;
	$obj=json_decode($str,true);
	return $obj;
}
?>