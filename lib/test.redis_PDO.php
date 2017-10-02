<?php
ini_set('display_errors', 1); 
error_reporting(E_ERROR);
include_once('class.redis.php');
include_once('class.db.PDO.php');
include_once('../conf/sys_config.php');
//print_r($redis_set);
//建立 redis 簡單連線
$redis=mke_redis_link($redis_set);
$db=mke_pdo_link($insert_db);
$db_s=mke_pdo_link($select_db);

function SELECT_test(){
  global $db_s;
  global $redis;
	$aRet=array();
	$aTmp=array();
	$sTable='test2'; 
	$aWhere=array(
		'mmid'=>1
	);
	$cahce=$redis->get_row($sTable,$aWhere);
	if(count($cahce)<1){
		$aSQL=array();
		$aSQL[]='SELECT';
		$aSQL[]='ptype';
		$aSQL[]=',item';
		$aSQL[]=',gold';
		$aSQL[]='FROM test2';
		$aSQL[]='force index(mm_gold)';
		$aSQL[]='WHERE 1';
		$aSQL[]='AND bet_status ="N"';
		$aSQL[]='AND result_status ="U"';
		$aSQL[]='AND mmid ="1"';
		$aSQL[]='AND rpt_date ="2017-09-27"';
		$aSQL[]='AND ptype ="100"';
		$sSQL=implode(' ',$aSQL);
		//echo $sSQL." \n ";
		$q=$db_s->sql_query($sSQL);
		while($r=$db_s->nxt_row('ASSOC')){
			$iPtype=$r['ptype'];
			$sItme=$r['item'];
			$aTmp[$iPtype][$sItme]['gold'][]=$r['gold'];
			$aTmp[$iPtype][$sItme]['cnt'][]=1;
		}
		foreach($aTmp as $iPtype => $aItme){
			foreach($aItme as $sItme => $val){
				$aRet[$sItme]['ptype']=$iPtype;
				$aRet[$sItme]['gold']=array_sum($val['gold']);
				$aRet[$sItme]['cnt']=array_sum($val['cnt']);
			}
		}
		$redis->set_row($sTable,$aWhere,$aRet);
	}else{
		$aRet=$cahce;
	}
	return $aRet;
}
//新增測試表資料
function INSERT_test(){
  global $db;
	global $redis;
	$aData=array();
	$aCol=array('bet_status','result_status','mmid','scid','coid','said','agid','rpt_date','date_sn','ptype','item','gold');
	$aRet=array();
	$init_value=array();
	$aFabric_id=array();
  $aFabric_id['mmid']=1;
  $aFabric_id['scid']=2;
  $aFabric_id['coid']=3;
  $aFabric_id['said']=4;
  $aFabric_id['agid']=5;
  $aFabric_id['memid']=6;
	$aData[0]['rpt_date']='2017-09-27';
	$aData[0]['date_sn']=1;
	$aData[0]['ptype']=100;
	$aData[0]['item']=1;
	$aData[0]['gold']=100;
	foreach($aCol as $k => $column){
		$init_value[$column]='';
	}
	$sSQL_v="('[".implode("]','[",$aCol)."]')";
  $sSQL_i='INSERT INTO test2 ('.implode(',',$aCol).') VALUES';
	foreach($aData as $k => $v){
		$valueSQL=$sSQL_v;
		$value=$init_value;//初始化值陣列
		$value['bet_status']='N';
		$value['result_status']='U';
		$value['mmid']=$aFabric_id['mmid'];
		$value['scid']=$aFabric_id['scid'];
		$value['coid']=$aFabric_id['coid'];
		$value['said']=$aFabric_id['said'];
		$value['agid']=$aFabric_id['agid'];
		$value['rpt_date']=$v['rpt_date'];
		$value['date_sn']=$v['date_sn'];
		$value['ptype']=$v['ptype'];
		$value['item']=$v['item'];
		$value['gold']=$v['gold'];
		foreach($value as $column => $v){
      $valueSQL=str_replace("[$column]",$v,$valueSQL);
    }
		$valueSQLs[]=$valueSQL;
	}
	$sSQL=$sSQL_i.(implode(',',$valueSQLs));
	$db->sql_query($sSQL);
	$sTable='test2'; 
	foreach($aFabric_id as $k => $v){
		$aWhere=array(
			$k=>$v
		);
		$redis->del_row($sTable,$aWhere);
	}
}

?>