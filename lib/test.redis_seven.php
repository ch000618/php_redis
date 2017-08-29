<?php
//ini_set('display_errors', 1);
date_default_timezone_set("Asia/Taipei");
include_once('class.redis_seven.php');
//建立 redis 必要的設定
$db_set=array(
	'host'=>'192.168.1.190'
	,'port'=>6379
	,'db_rds'=>0
	,'db_mysql'=>'139JRD_seven'
	,'pass'=>'1234'
);
//建立 redis 簡單連線
$redis=mke_redis_link($db_set);
echo '<pre>';
init();
echo '</pre>';
function init(){
	$test=$_GET['t'];
	$list=array(
		 1=>'測試寫入 redis 中間商設定資料'
		,2=>'測試讀出 redis 中間商設定資料'
		,3=>'測試刪除 redis 中間商設定資料'
	);
	switch($test){
		case 1:
			echo $list[$test]."\n";
			test_set_seven_agents_conf();
			break;
		case 2:
			echo $list[$test]."\n";
			test_get_seven_agents_conf();
			break;
		case 3:
			echo $list[$test]."\n";
			test_del_seven_agents_conf();
			break;
		default:
			echo "參數?t=$test \n";
			echo "功能清單 \n";
			echo '<pre>';
			print_r($list);
			echo '</pre>';
			break;
	}
}
//測試 在redis 寫入 某個站 某個中間商 某個彩種 的某個玩法的agents_conf 
function test_set_seven_agents_conf(){
	global $db_set;
	global $redis;
	$sTable='agents_conf'; 
	$aWhere=array(
		'master_id'=>165
		,'gtype'=>701
		,'rtype'=>705
	);
	$aData=array(
		'max_percent'=>1000
		,'percent'=>900 
		,'up_percent'=>0
		,'show_id'=> 0
		,'si'=>1 
		,'sc'=>1000 
		,'so'=>10000 
		,'sp'=>100000 
		,'cg'=>100000 
		,'soc'=>0
		,'war_a'=>0
		,'war_b'=>0
		,'war_c'=>0
		,'ahead_time'=>0
		,'stop_time'=>'00:00:00'
		,'menu'=>0
	);
	echo "set Where: \n";
  print_r($aWhere);
	echo "set aData: \n";
  print_r($aData);
	$redis->set_row($sTable,$aWhere,$aData);
}
//測試 在redis 取得 某個站 某個中間商 某個彩種 的某個玩法的aagents_conf 
function test_get_seven_agents_conf(){
	global $db_set;
	global $redis;
	$sTable='agents_conf'; 
	$aWhere=array(
		'master_id'=>165
		,'gtype'=>701
		,'rtype'=>705
	);
	echo "get Where :\n";
  print_r($aWhere);
	$result=$redis->get_row($sTable,$aWhere);
	if($result==''){$result=array();}
	echo "get result :\n";
	print_r($result);
}
//測試 在redis 刪除 某個站 某個中間商 某個彩種 的某個玩法的aagents_conf 
function test_del_seven_agents_conf(){
	global $db_set;
	global $redis;
	$sTable='agents_conf'; 
	$aWhere=array(
		'master_id'=>165
		,'gtype'=>701
		,'rtype'=>705
	);
	echo "del Where :\n";
  print_r($aWhere);
	$redis->del_row($sTable,$aWhere);
}
?>