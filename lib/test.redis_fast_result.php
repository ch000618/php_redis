<?php
include_once('class.redis.php');
include_once('../conf/redis_connect.php');
//建立 redis 簡單連線
$redis=mke_redis_link($db_set);
echo '<pre>';
init();
echo '</pre>';
function init(){
	$test=$_GET['t'];
	$list=array(
		 1=>'測試寫入 redis 資料'
		,2=>'測試讀出 redis 資料'
		,3=>'測試刪除 redis 資料'
	);
	switch($test){
		case 1:
			echo $list[$test]."\n";
			test_set_fast_result();
			break;
		case 2:
			echo $list[$test]."\n";
			test_get_fast_result();
			break;
		case 3:
			echo $list[$test]."\n";
			test_del_fast_result();
			break;
		case 4:
			echo phpinfo();
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
function test_set_fast_result(){
	global $db_set;
	global $redis;
	$sTable='draws_klc_result'; 
	$aWhere=array(
		'rpt_date'=>'2017-08-25'
		,'date_sn'=>20
		,'draws_num'=>'2017082520'
	);
	$aData=array(4,18,11,1,10,9,7,17);
	echo "set Where: \n";
  print_r($aWhere);
	echo "set aData: \n";
  print_r($aData);
	$redis->set_row($sTable,$aWhere,$aData);
}
//測試 在redis 取得 某個站 某個中間商 某個彩種 的某個玩法的aagents_conf 
function test_get_fast_result(){
	global $db_set;
	global $redis;
	$sTable='draws_klc_result'; 
	$aWhere=array(
		'rpt_date'=>'2017-08-25'
		,'date_sn'=>20
		,'draws_num'=>'2017082520'
	);
	echo "get Where :\n";
  print_r($aWhere);
	$result=$redis->get_row($sTable,$aWhere);
	if($result==''){$result=array();}
	echo "get result :\n";
	print_r($result);
}
//測試 在redis 刪除 某個站 某個中間商 某個彩種 的某個玩法的aagents_conf 
function test_del_fast_result(){
	global $db_set;
	global $redis;
	$sTable='draws_klc_result'; 
	$aWhere=array(
		'rpt_date'=>'2017-08-25'
		,'date_sn'=>20
		,'draws_num'=>'2017082520'
	);
	echo "del Where :\n";
  print_r($aWhere);
	$redis->del_row($sTable,$aWhere);
}
?>