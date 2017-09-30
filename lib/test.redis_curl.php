<?php

init_multi_order();
function init_multi_order(){
	$url_list=array();
	for($i=1;$i<=250;$i++){
		$url_list[$i]['url']="http://192.168.1.190/php_redis/lib/test.redis_PDO.php?c=1";
		$url_list[$i]['post'][0]['ptype']=200;
		$url_list[$i]['post'][0]['item']=4;
		$url_list[$i]['post'][0]['gold']=1000;
	}
	multi_order($url_list);
}
//多執行緒執行redis 送post 
/*
*/
function multi_order($url_list){
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
	echo "<pre>";
	foreach($handle as $k =>$ch) {
		$content  = curl_multi_getcontent($ch);
		echo "$k : $content \n";
	}
	echo "</pre>";
	/* 移除 handle*/
	foreach($handle as $i =>$ch) {
		curl_multi_remove_handle($mh, $ch);
	}
  curl_multi_close($mh);
}
?>