<?php
include_once('class.redis.php');
include_once('class.db.PDO.php');
include_once('../conf/sys_config.php');
//建立 redis 簡單連線
$redis=mke_redis_link($redis_set);
$db=mke_pdo_link($insert_db);
$db_s=mke_pdo_link($select_db);
// 查詢test 表 所有資料
/*
*/
init();
function init(){
	//$aData=SELECT_test();
	//echo '<pre>';
	//print_r($aData);
	//echo '</pre>';
	INSERT_test2();
}
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
		//echo $sTable."\n";
		//print_r($aWhere);
		$redis->del_row($sTable,$aWhere);
	}
}
//新增測試表資料
function INSERT_test2(){
  global $db;
	global $redis;
	$aRet=array();
	$aTmp=array();
	$aData=array();
	$aCol=array(
     'bet_status','result_status'
    ,'id_bm','id_mm','id_sc','id_co','id_sa','id_ag','id_mem'
    ,'order_ip','time_bet','time_bet_ms'
    ,'rpt_date','date_sn'
    ,'ptype','item_type','item','even_code','gold'
    ,'odds_set','odds_basis','odds_gap'
    ,'odds_adjust','odds_adjust_auto','odds_adjust_auto_rep'
    ,'share_mm','share_sc','share_co','share_sa','share_ag'
    ,'water_basis'
    ,'water_gap_mm','water_gap_sc','water_gap_co','water_gap_sa','water_gap_ag','water_gap_mem'
    ,'share_gold_mm','share_gold_sc','share_gold_co','share_gold_sa','share_gold_ag'
    ,'share_gold_mm_out','share_gold_sc_out','share_gold_co_out','share_gold_sa_out','share_gold_ag_out'
    ,'share_gold_mm_in','share_gold_sc_in','share_gold_co_in','share_gold_sa_in','share_gold_ag_in'
    ,'water_gold_mm','water_gold_sc','water_gold_co','water_gold_sa','water_gold_ag','water_gold_mem'
    ,'win_gold_mem'
   );
	$init_value=array();
	$aFabric_id=array();
	$aFabric_set=array();
	$aPlay_set=array();
	$aMem_set=array();
	
  $aFabric_id['bmid']=999;
  $aFabric_id['mmid']=1;
  $aFabric_id['scid']=2;
  $aFabric_id['coid']=3;
  $aFabric_id['said']=4;
  $aFabric_id['agid']=5;
  $aFabric_id['memid']=6;
	
	$aFabric_set['mm'][100]['share']=20;
	$aFabric_set['sc'][100]['share']=15;
	$aFabric_set['co'][100]['share']=25;
	$aFabric_set['sa'][100]['share']=20;
	$aFabric_set['ag'][100]['share']=20;
	
	$aFabric_set['mm'][100]['water_gap']=0;
	$aFabric_set['sc'][100]['water_gap']=2;
	$aFabric_set['co'][100]['water_gap']=3;
	$aFabric_set['sa'][100]['water_gap']=4;
	$aFabric_set['ag'][100]['water_gap']=5;
	
	$aMem_set[100]['water_gap']=0;
	$aPlay_set[100]['water_basis']=85;
	
	$aPlay_set[100]['odds'][1]['odds_basis']=99110;
  $aPlay_set[100]['odds'][1]['odds_gap']=0;
	$aPlay_set[100]['odds'][1]['odds_adjust']=0;
  $aPlay_set[100]['odds'][1]['odds_adjust_relate']=0;
  $aPlay_set[100]['odds'][1]['odds_adjust_auto'];
  $aPlay_set[100]['odds'][1]['odds_adjust_auto_relate']=0;
  $aPlay_set[100]['odds'][1]['odds_adjust_auto_rep']=0;
	
	//當前狀態
	$dws['rpt_date']='2017-09-27';
	$dws['date_sn']=1;
	//單子的假資料

	$aTmp['ptype']=100;
	$aTmp['item']=1;
	$aTmp['gold']=100;
	$aData[]=$aTmp;
	$rate=0.1;
	foreach($aData as &$v){
		$iPtype=$v['ptype'];
		$sItem=$v['item'];
		$iGold=$v['gold'];
		//print_r($iPtype);
		//print_r($sItem);
	
		$v['share_mm']=$aFabric_set['mm'][$iPtype]['share'];
		$v['share_sc']=$aFabric_set['sc'][$iPtype]['share'];
		$v['share_co']=$aFabric_set['co'][$iPtype]['share'];
		$v['share_sa']=$aFabric_set['sa'][$iPtype]['share'];
		$v['share_ag']=$aFabric_set['ag'][$iPtype]['share'];
		
		$v['share_gold_mm']=$v['gold']*$aFabric_set['mm'][$iPtype]['share'];
		$v['share_gold_sc']=$v['gold']*$aFabric_set['sc'][$iPtype]['share'];
		$v['share_gold_co']=$v['gold']*$aFabric_set['co'][$iPtype]['share'];
		$v['share_gold_sa']=$v['gold']*$aFabric_set['sa'][$iPtype]['share'];
		$v['share_gold_ag']=$v['gold']*$aFabric_set['ag'][$iPtype]['share'];
		
		$v['share_gold_mm_out']=0;
		$v['share_gold_sc_out']=0;
		$v['share_gold_co_out']=0;
		$v['share_gold_sa_out']=0;
		$v['share_gold_ag_out']=0;
		$v['share_gold_mm_in']=0;
		$v['share_gold_sc_in']=0;
		$v['share_gold_co_in']=0;
		$v['share_gold_sa_in']=0;
		$v['share_gold_ag_in']=0;
		
		$v['water_basis']=$aPlay_set[$iPtype]['water_basis'];
		$v['water_gap']=$aMem_set[$iPtype]['water_gap'];
		
		$v['odds_basis']=$aPlay_set[$iPtype]['odds'][$sItem]['odds_basis'];
    $v['odds_gap']=$aPlay_set[$iPtype]['odds'][$sItem]['odds_gap'];
    $v['odds_adjust']=$aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust'];
    $v['odds_adjust']+=$aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_relate'];
    $v['odds_adjust_auto']=$aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto'];
    $v['odds_adjust_auto']+=$aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto_relate'];
    $v['odds_adjust_auto_rep']=$aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto_rep'];
		
		$v['water_gap_ag']=$aFabric_set['ag'][$iPtype]['water_gap'];			
    $v['water_gap_sa']=$aFabric_set['sa'][$iPtype]['water_gap'];			
    $v['water_gap_co']=$aFabric_set['co'][$iPtype]['water_gap'];			
    $v['water_gap_sc']=$aFabric_set['sc'][$iPtype]['water_gap'];
    $v['water_gap_mm']=$aFabric_set['mm'][$iPtype]['water_gap'];
    $v['water_gap_mem']=$aMem_set[$iPtype]['water_gap'];
		
		$v['water_gold_mem']=$iGold*($v['water_basis']-$v['water_gap_mem'])*$rate;	
		$v['water_gold_ag']=$iGold*($v['water_basis']-$v['water_gap_ag'])*$rate;	
		$v['water_gold_sa']=$iGold*($v['water_basis']-$v['water_gap_sa'])*$rate;	
		$v['water_gold_co']=$iGold*($v['water_basis']-$v['water_gap_co'])*$rate;	
		$v['water_gold_sc']=$iGold*($v['water_basis']-$v['water_gap_sc'])*$rate;	
		$v['water_gold_mm']=$iGold*($v['water_basis']-$v['water_gap_mm'])*$rate;	
		
		$final_odds =$v['odds_basis'];
    $final_odds+=$v['odds_gap'];
    $final_odds+=$v['odds_adjust'];
    $final_odds+=$v['odds_adjust_auto'];
    $final_odds+=$v['odds_adjust_auto_rep'];
    $win_gold_mem=$v['gold']*$final_odds*0.1;
    $v['win_gold_mem']=$win_gold_mem;
	}
	
	foreach($aCol as $k => $column){
		$init_value[$column]='';
	}
	/*echo '</pre>';
	echo 'count: '.count($aCol);
	print_r($init_value);
	echo '</pre>';
	exit;
	*/
	$sSQL_v="('[".implode("]','[",$aCol)."]')";
  $sSQL_i='INSERT INTO draws_ssc_bet ('.implode(',',$aCol).') VALUES';
	$order_ip='127.0.0.1';
	foreach($aData as $k => $v){
		$valueSQL=$sSQL_v;
		$value=$init_value;//初始化值陣列
		$value['bet_status']='N';
		$value['result_status']='U';
		$value['id_bm']=$aFabric_id['bmid'];
		$value['id_mm']=$aFabric_id['mmid'];
		$value['id_sc']=$aFabric_id['scid'];
		$value['id_co']=$aFabric_id['coid'];
		$value['id_sa']=$aFabric_id['said'];
		$value['id_ag']=$aFabric_id['agid'];
		$value['id_mem']=$aFabric_id['memid'];
		$value['order_ip']=$order_ip;
    $value['time_bet']=date('Y-m-d H:i:s');
    $value['time_bet_ms']=200;
		$value['rpt_date']=$dws['rpt_date'];
		$value['date_sn']=$dws['date_sn'];
		$value['ptype']=$v['ptype'];
		$value['item_type']='I';
		$value['item']=$v['item'];
		$value['gold']=$v['gold'];
		
		$value['odds_set']='A';
    $value['odds_basis']=$v['odds_basis'];
    $value['odds_gap']=$v['odds_gap'];
    $value['odds_adjust']=$v['odds_adjust'];
    $value['odds_adjust_auto']=$v['odds_adjust_auto'];
    $value['odds_adjust_auto_rep']=$v['odds_adjust_auto_rep'];
		
    $value['share_mm']=$v['share_mm'];
    $value['share_sc']=$v['share_sc'];
    $value['share_co']=$v['share_co'];
    $value['share_sa']=$v['share_sa'];
    $value['share_ag']=$v['share_ag'];
		
    $value['water_basis']=$v['water_basis'];
    $value['water_gap_mm']=$v['water_gap_mm'];
    $value['water_gap_sc']=$v['water_gap_sc'];
    $value['water_gap_co']=$v['water_gap_co'];
    $value['water_gap_sa']=$v['water_gap_sa'];
    $value['water_gap_ag']=$v['water_gap_ag'];
    
		$value['water_gap_mem']=$v['water_gap_mem'];
    $value['share_gold_mm']=$v['share_gold_mm'];
    $value['share_gold_sc']=$v['share_gold_sc'];
    $value['share_gold_co']=$v['share_gold_co'];
    $value['share_gold_sa']=$v['share_gold_sa'];
    $value['share_gold_ag']=$v['share_gold_ag'];
		//---
    $value['share_gold_mm_out']=$v['share_gold_mm_out'];
    $value['share_gold_sc_out']=$v['share_gold_sc_out'];
    $value['share_gold_co_out']=$v['share_gold_co_out'];
    $value['share_gold_sa_out']=$v['share_gold_sa_out'];
    $value['share_gold_ag_out']=$v['share_gold_ag_out'];
    //---
    $value['share_gold_mm_in']=$v['share_gold_mm_in'];
    $value['share_gold_sc_in']=$v['share_gold_sc_in'];
    $value['share_gold_co_in']=$v['share_gold_co_in'];
    $value['share_gold_sa_in']=$v['share_gold_sa_in'];
    $value['share_gold_ag_in']=$v['share_gold_ag_in'];
			
		$value['water_gold_mm']=$v['water_gold_mm'];
    $value['water_gold_sc']=$v['water_gold_sc'];
    $value['water_gold_co']=$v['water_gold_co'];
    $value['water_gold_sa']=$v['water_gold_sa'];
    $value['water_gold_ag']=$v['water_gold_ag'];
    $value['water_gold_mem']=$v['water_gold_mem'];
		
		$value['win_gold_mem']=$v['win_gold_mem'];
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
		//echo $sTable."\n";
		//print_r($aWhere);
		$redis->del_row($sTable,$aWhere);
	}
}
?>