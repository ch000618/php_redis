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
init();
function init(){
	$cmd=$_GET['c'];
	switch($cmd){
		case 1:
			init_order();
			break;
		case 2:
			init_select();
			break;
		case 3:
			make_redis_cache();
			break;
		default:
			echo '<pre>';
			echo "c=1 下單 \n";
			echo "c=1 取得計算結果 \n";
			echo "c=3 製作redis 計算結果快取 \n";
			echo '</pre>';
			break;
	}
}
//init();
function init_order(){
	$aOrder=$_POST;
	if(empty($aOrder)){return ;}
	$oOrder=new test_order();
  $oOrder->fake_order($aOrder);//設定下注注單
  $oOrder->exec();//執行下單
	echo "ok!";
}

function make_redis_cache(){
	set_time_limit(0);
	global $redis;
	$sGame='ssc';
	$sRpt_date='2017-09-28';
	$iDate_sn='12';
	$aUlv=array('sc','co','sa','ag');
	$aData=array();
	$sTable='draws_[game]_bet';
	$sTable=str_replace('[game]',$sGame,$sTable);
	$time_start = microtime(true);
	foreach($aUlv as $sn => $ulv){
		$monit=ser_select_order($sGame,$sRpt_date,$iDate_sn,$ulv);
		foreach($monit as $skey => $value){
			$akey=explode("|",$skey);
			$sId_lv_col=$akey[0];
			$iId_lv=$akey[1];
			$iPtype=$akey[2];
			$aWhere=array(
				$sId_lv_col=>$iId_lv
				,'rpt_date'=>$sRpt_date
				,'date_sn'=>$iDate_sn
				,'ptype'=>$iPtype
			);
			$redis->set_row($sTable,$aWhere,$value,60);
		}
	}
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	echo "exec:$time";
}

function init_select(){
	$sGame='ssc';
	$ulv='sc';
	$uid='42';
	$sRpt_date='2017-09-28';
	$iDate_sn='12';
	$iPtype='203';
	$aData=array();
	$time_start = microtime(true);
	$aRet=select_order($sGame,$sRpt_date,$iDate_sn,$iPtype,$ulv,$uid);
	print_r($aRet);
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	echo "exec:$time";
}

// 查詢test 表 所有資料
/*
*/
class test_order{
var	$aOrder=array();
var	$aFabric_id=array();
var	$aFabric_set=array();
var	$aPlay_set=array();
var	$aMem_set=array();
var	$aMem_data=array();
var	$dws=array();
	function __construct(){
	}
	function exec(){
		$this->test_Fabric_data();
		$this->add_Order_data();
		$this->INSERT_order();
	}
	//假資料
	function test_Fabric_data(){
		$this->aFabric_id['id_bm']=999;
		$this->aFabric_id['id_mm']=1;
		$this->aFabric_id['id_sc']=52;
		$this->aFabric_id['id_co']=53;
		$this->aFabric_id['id_sa']=54;
		$this->aFabric_id['id_ag']=55;
		$this->aMem_data['id']=56;
		foreach($this->aOrder as $k => $v){
			$iPtype=$v['ptype'];
			$this->aFabric_set['mm'][$iPtype]['share']=20;
			$this->aFabric_set['sc'][$iPtype]['share']=15;
			$this->aFabric_set['co'][$iPtype]['share']=25;
			$this->aFabric_set['sa'][$iPtype]['share']=20;
			$this->aFabric_set['ag'][$iPtype]['share']=20;
			
			$this->aFabric_set['mm'][$iPtype]['water_gap']=0;
			$this->aFabric_set['sc'][$iPtype]['water_gap']=2;
			$this->aFabric_set['co'][$iPtype]['water_gap']=3;
			$this->aFabric_set['sa'][$iPtype]['water_gap']=4;
			$this->aFabric_set['ag'][$iPtype]['water_gap']=5;
			
			$this->aMem_set[$iPtype]['water_gap']=5;
			$this->aPlay_set[$iPtype]['water_basis']=85;
		}
		foreach($this->aOrder as $k => $v){
			$iPtype=$v['ptype'];
			$sItem=$v['item'];
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_basis']=99110;
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_gap']=0;
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust']=0;
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_relate']=0;
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto'];
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto_relate']=0;
			$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto_rep']=0;
		}
		$this->dws['rpt_date']='2017-09-28';
		$this->dws['date_sn']='12';
	} 
	//加上 設定值 其他資料
	function add_Order_data(){
		$rate=0.1;
		foreach($this->aOrder as &$v){
			$iPtype=$v['ptype'];
			$sItem=$v['item'];
			$iGold=$v['gold'];
		
			$v['share_mm']=$this->aFabric_set['mm'][$iPtype]['share'];
			$v['share_sc']=$this->aFabric_set['sc'][$iPtype]['share'];
			$v['share_co']=$this->aFabric_set['co'][$iPtype]['share'];
			$v['share_sa']=$this->aFabric_set['sa'][$iPtype]['share'];
			$v['share_ag']=$this->aFabric_set['ag'][$iPtype]['share'];
			
			$v['share_gold_mm']=$v['gold']*$this->aFabric_set['mm'][$iPtype]['share'];
			$v['share_gold_sc']=$v['gold']*$this->aFabric_set['sc'][$iPtype]['share'];
			$v['share_gold_co']=$v['gold']*$this->aFabric_set['co'][$iPtype]['share'];
			$v['share_gold_sa']=$v['gold']*$this->aFabric_set['sa'][$iPtype]['share'];
			$v['share_gold_ag']=$v['gold']*$this->aFabric_set['ag'][$iPtype]['share'];
			
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
			
			$v['water_basis']=$this->aPlay_set[$iPtype]['water_basis'];
			$v['water_gap']=$this->aMem_set[$iPtype]['water_gap'];
			
			$v['odds_basis']=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_basis'];
			$v['odds_gap']=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_gap'];
			$v['odds_adjust']=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust'];
			$v['odds_adjust']+=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_relate'];
			$v['odds_adjust_auto']=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto'];
			$v['odds_adjust_auto']+=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto_relate'];
			$v['odds_adjust_auto_rep']=$this->aPlay_set[$iPtype]['odds'][$sItem]['odds_adjust_auto_rep'];
			
			$v['water_gap_ag']=$this->aFabric_set['ag'][$iPtype]['water_gap'];			
			$v['water_gap_sa']=$this->aFabric_set['sa'][$iPtype]['water_gap'];			
			$v['water_gap_co']=$this->aFabric_set['co'][$iPtype]['water_gap'];			
			$v['water_gap_sc']=$this->aFabric_set['sc'][$iPtype]['water_gap'];
			$v['water_gap_mm']=$this->aFabric_set['mm'][$iPtype]['water_gap'];
			$v['water_gap_mem']=$this->aMem_set[$iPtype]['water_gap'];
			
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
	}
	
	//假單
	function fake_order($aOrder){
		$aTmp=array();
		foreach($aOrder as $k => $v){
			$aTmp['ptype']=$v['ptype'];
			$aTmp['item']=$v['item'];
			$aTmp['gold']=$v['gold'];
			$this->aOrder[]=$aTmp;
		}
	}
	//print_r($aOrder);
	//新增測試表資料
	function INSERT_order(){
		global $db;
		global $redis;
		$aRet=array();
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
		foreach($aCol as $k => $column){
			$init_value[$column]='';
		}
		/*
		echo '</pre>';
		echo 'count: '.count($aCol);
		print_r($init_value);
		echo '</pre>';
		exit;
		*/
		$sSQL_v="('[".implode("]','[",$aCol)."]')";
		$sSQL_i='INSERT INTO draws_ssc_bet ('.implode(',',$aCol).') VALUES';
		$order_ip='127.0.0.1';
		$db->beginTransaction();//交易機制開始
		foreach($this->aOrder as $k => $v){
			$valueSQL=$sSQL_v;
			$value=$init_value;//初始化值陣列
			$value['bet_status']='N';
			$value['result_status']='U';
			$value['id_bm']=$this->aFabric_id['id_bm'];
			$value['id_mm']=$this->aFabric_id['id_mm'];
			$value['id_sc']=$this->aFabric_id['id_sc'];
			$value['id_co']=$this->aFabric_id['id_co'];
			$value['id_sa']=$this->aFabric_id['id_sa'];
			$value['id_ag']=$this->aFabric_id['id_ag'];
			$value['id_mem']=$this->aMem_data['id'];
			$value['order_ip']=$order_ip;
			$value['time_bet']=date('Y-m-d H:i:s');
			$value['time_bet_ms']=200;
			$value['rpt_date']=$this->dws['rpt_date'];
			$value['date_sn']=$this->dws['date_sn'];
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
		$db->commit();//交易機制結束
	}
}

function select_order_v2($sGame,$sRpt_date,$iDate_sn,$iPtype,$ulv,$uid){
  global $db_s;
  global $redis;
	$aRet=array();
	$aTmp=array();
	$_aLevel=array('bm','mm','sc','co','sa','ag');
	$ulv=strtolower($ulv);
	
  $aLevel=$_aLevel;
  $aLevel_index=array_flip($_aLevel);
  $iLv=$aLevel_index[$ulv];
  $lv_next=($ulv=='ag')?'mem':$aLevel[$iLv+1];
	
	$sTable='draws_[game]_bet';
	$sId_lv='[lv]_id';
	$sId_lv=str_replace('[lv]',$ulv,$sId_lv);
	$sTable=str_replace('[game]',$sGame,$sTable);
	$aWhere=array(
		$sId_lv=>$uid
		,'rpt_date'=>$sRpt_date
		,'date_sn'=>$iDate_sn
		,'ptype'=>$iPtype
	);
	$cahce=$redis->get_row($sTable,$aWhere);
	if(!empty($cahce)){
		$aRet=$cahce;
		return $aRet;
	}
	//把公視轉換成代數的方式
	$share_gold='CAST(share_gold_[lv] AS SIGNED)-CAST(share_gold_[lv]_out AS SIGNED)+CAST(share_gold_[lv]_in AS SIGNED)';
	$share='(CAST(share_gold_[lv] AS SIGNED)-CAST(share_gold_[lv]_out AS SIGNED)+CAST(share_gold_[lv]_in AS SIGNED)
	*0.001)/(gold*0.1)';
	$share_out='(share_gold_[lv]_out)';
	$water_next='water_basis-water_gap_[lv_next]';
	$water='water_basis-water_gap_[lv]';
	$odds='(odds_basis+odds_gap+odds_adjust+odds_adjust_auto+odds_adjust_auto_rep)';
	//因為還有補貨的部分 把外拋減去
	$gold='(gold*0.1)';
	$share=str_replace('[lv]',$ulv,$share);
	$share_gold=str_replace('[lv]',$ulv,$share_gold);
	$share_out=str_replace('[lv]',$ulv,$share_out);
	$gold=str_replace('[lv]',$ulv,$gold);
	$water_next=str_replace('[lv_next]',$lv_next,$water_next);
	$water=str_replace('[lv]',$ulv,$water);
	$aSQL=array();
	$aSQL[]='SELECT';
	$aSQL[]='ptype';
	$aSQL[]=',item';
	$aSQL[]=',(gold*0.1) AS GOLD';
	$aSQL[]=',([share_out]*0.001) AS share_out';
	$aSQL[]=',([share_gold])*0.001*([water])*0.0001 AS share_water';
	$aSQL[]=',([share_gold])*0.001 AS share_gold';
	$aSQL[]=',([gold]*([water_next])*0.0001)*[share]*0.001 AS water_gold';
	$aSQL[]=',([share_gold])*0.001';
	$aSQL[]='*[odds]*0.0001';
	$aSQL[]='AS win_gold';
	$aSQL[]=',';
	$aSQL[]='((gold*0.1)';
	$aSQL[]='*(([odds])*0.0001))';
	$aSQL[]='AS fake_win_gold';
	$aSQL[]='FROM `draws_[game]_bet`';
	$aSQL[]='force index([lv]_drwas)';
	$aSQL[]='WHERE 1';
	$aSQL[]="AND bet_status = 'N'";
	$aSQL[]='AND id_[lv]=[lv_id]';
	$aSQL[]='AND rpt_date ="[rpt_date]"';
	$aSQL[]='AND date_sn="[date_sn]"';
	$aSQL[]='AND ptype="[ptype]"';
	$sSQL=implode(' ',$aSQL);
	$sSQL=str_replace('[game]',$sGame,$sSQL);
	$sSQL=str_replace('[lv]',$ulv,$sSQL);
	$sSQL=str_replace('[lv_id]',$uid,$sSQL);
	$sSQL=str_replace('[lv_next]',$lv_next,$sSQL);
	$sSQL=str_replace('[rpt_date]',$sRpt_date,$sSQL);
	$sSQL=str_replace('[date_sn]',$iDate_sn,$sSQL);
	$sSQL=str_replace('[ptype]',$iPtype,$sSQL);
	$sSQL=str_replace('[share_gold]',$share_gold,$sSQL);
	$sSQL=str_replace('[share]',$share,$sSQL);
	$sSQL=str_replace('[water_next]',$water_next,$sSQL);
	$sSQL=str_replace('[water]',$water,$sSQL);
	$sSQL=str_replace('[odds]',$odds,$sSQL);
	$sSQL=str_replace('[gold]',$gold,$sSQL);
	$sSQL=str_replace('[share_out]',$share_out,$sSQL);
	/*
	echo "<pre> ";
	echo $sSQL." \n ";
	echo "</pre> ";
	*/
	$q=$db_s->sql_query($sSQL);
	while($r=$db_s->nxt_row('ASSOC')){
		$iPtype=$r['ptype'];
    $sItem=$r['item'];
    unset($r['item']);
    $aTmp[$iPtype][$sItem]['GOLD'][]=$r['GOLD'];
    $aTmp[$iPtype][$sItem]['share_out'][]=$r['share_out'];
    $aTmp[$iPtype][$sItem]['share_water'][]=$r['share_water'];
    $aTmp[$iPtype][$sItem]['share_gold'][]=$r['share_gold'];
    $aTmp[$iPtype][$sItem]['water_gold'][]=$r['water_gold'];
    $aTmp[$iPtype][$sItem]['win_gold'][]=$r['win_gold'];
    $aTmp[$iPtype][$sItem]['fake_win_gold'][]=$r['fake_win_gold'];
	}
	foreach($aTmp as $iPtype => $aItme){
		foreach($aItme as $sItme => $val){
			$aRet[$sItme]['cnt']=count($val['GOLD']);
			$aRet[$sItme]['GOLD']=array_sum($val['GOLD']);
			$aRet[$sItme]['share_out']=array_sum($val['share_out']);
			$aRet[$sItme]['share_water']=array_sum($val['share_water']);
			$aRet[$sItme]['share_gold']=array_sum($val['share_gold']);
			$aRet[$sItme]['water_gold']=array_sum($val['water_gold']);
			$aRet[$sItme]['win_gold']=array_sum($val['win_gold']);
			$aRet[$sItme]['fake_win_gold']=array_sum($val['fake_win_gold']);
		}
	}
	$redis->set_row($sTable,$aWhere,$aRet,60);
	return $aRet;
}

function select_order($sGame,$sRpt_date,$iDate_sn,$iPtype,$ulv,$uid){
  global $db_s;
  global $redis;
	$aRet=array();
	$aTmp=array();
	$_aLevel=array('bm','mm','sc','co','sa','ag');
	$ulv=strtolower($ulv);
	
  $aLevel=$_aLevel;
  $aLevel_index=array_flip($_aLevel);
  $iLv=$aLevel_index[$ulv];
  $lv_next=($ulv=='ag')?'mem':$aLevel[$iLv+1];
	
	$sTable='draws_[game]_bet';
	$sId_lv='[lv]_id';
	$sId_lv=str_replace('[lv]',$ulv,$sId_lv);
	$sTable=str_replace('[game]',$sGame,$sTable);
	$aWhere=array(
		$sId_lv=>$uid
		,'rpt_date'=>$sRpt_date
		,'date_sn'=>$iDate_sn
		,'ptype'=>$iPtype
	);
	$cahce=$redis->get_row($sTable,$aWhere);
	if(!empty($cahce)){
		$aRet=$cahce;
		return $aRet;
	}
	/*
	echo "<pre>";
	echo "這是 redis 答案 : \n";
	print_r($cahce);
	echo "</pre>";
	*/
	//把公視轉換成代數的方式
	$share_gold='CAST(share_gold_[lv] AS SIGNED)-CAST(share_gold_[lv]_out AS SIGNED)+CAST(share_gold_[lv]_in AS SIGNED)';
	$share='(CAST(share_gold_[lv] AS SIGNED)-CAST(share_gold_[lv]_out AS SIGNED)+CAST(share_gold_[lv]_in AS SIGNED)
	*0.001)/(gold*0.1)';
	$share_out='(share_gold_[lv]_out)';
	$water_next='water_basis-water_gap_[lv_next]';
	$water='water_basis-water_gap_[lv]';
	$odds='(odds_basis+odds_gap+odds_adjust+odds_adjust_auto+odds_adjust_auto_rep)';
	//因為還有補貨的部分 把外拋減去
	$gold='(gold*0.1)';
	$share=str_replace('[lv]',$ulv,$share);
	$share_gold=str_replace('[lv]',$ulv,$share_gold);
	$share_out=str_replace('[lv]',$ulv,$share_out);
	$gold=str_replace('[lv]',$ulv,$gold);
	$water_next=str_replace('[lv_next]',$lv_next,$water_next);
	$water=str_replace('[lv]',$ulv,$water);
	$aSQL=array();
	$aSQL[]='SELECT';
	$aSQL[]='item';
	$aSQL[]=',COUNT(gold) AS cnt';
	$aSQL[]=',SUM(gold)*0.1 AS GOLD';
  $aSQL[]=',SUM([share_out]*0.001) AS share_out';
  $aSQL[]=',SUM(([share_gold])*0.001*([water])*0.0001) AS share_water';
  $aSQL[]=',SUM([share_gold])*0.001 AS share_gold';
  $aSQL[]=',SUM([gold]*(([water_next])*0.0001)*([share])*0.001) AS water_gold';
  $aSQL[]=',SUM(';
  $aSQL[]='(([share_gold])*0.001)';
  $aSQL[]='*[odds]*0.0001';
  $aSQL[]=') AS win_gold';
	$aSQL[]=',SUM(';
  $aSQL[]='(gold*0.1)';
  $aSQL[]='*[odds]*0.0001';
  $aSQL[]=') AS fake_win_gold';
	$aSQL[]='FROM `draws_[game]_bet`';
	$aSQL[]='force index([lv]_drwas)';
	$aSQL[]='WHERE 1';
	$aSQL[]="AND bet_status = 'N'";
	$aSQL[]='AND id_[lv]=[lv_id]';
	$aSQL[]='AND rpt_date ="[rpt_date]"';
	$aSQL[]='AND date_sn="[date_sn]"';
	$aSQL[]='AND ptype="[ptype]"';
	$aSQL[]='GROUP BY item';
	$sSQL=implode(' ',$aSQL);
	$sSQL=str_replace('[game]',$sGame,$sSQL);
	$sSQL=str_replace('[lv]',$ulv,$sSQL);
	$sSQL=str_replace('[lv_id]',$uid,$sSQL);
	$sSQL=str_replace('[lv_next]',$lv_next,$sSQL);
	$sSQL=str_replace('[rpt_date]',$sRpt_date,$sSQL);
	$sSQL=str_replace('[date_sn]',$iDate_sn,$sSQL);
	$sSQL=str_replace('[ptype]',$iPtype,$sSQL);
	$sSQL=str_replace('[share_gold]',$share_gold,$sSQL);
	$sSQL=str_replace('[share]',$share,$sSQL);
	$sSQL=str_replace('[water_next]',$water_next,$sSQL);
	$sSQL=str_replace('[water]',$water,$sSQL);
	$sSQL=str_replace('[odds]',$odds,$sSQL);
	$sSQL=str_replace('[gold]',$gold,$sSQL);
	$sSQL=str_replace('[share_out]',$share_out,$sSQL);
	$q=$db_s->sql_query($sSQL);
	while($r=$db_s->nxt_row('ASSOC')){
    $item=$r['item'];
    unset($r['item']);
    $aRet[$item]=$r;
	}
	/*
	echo "<pre>";
	echo "這是 sql 答案 : \n";
	print_r($aRet);
	echo "</pre>";
	*/
	return $aRet;
}

function ser_select_order($sGame,$sRpt_date,$iDate_sn,$ulv){
  global $db_s;
  global $redis;
	$aRet=array();
	$aTmp=array();
	$_aLevel=array('bm','mm','sc','co','sa','ag');
	$ulv=strtolower($ulv);
	
  $aLevel=$_aLevel;
  $aLevel_index=array_flip($_aLevel);
  $iLv=$aLevel_index[$ulv];
  $lv_next=($ulv=='ag')?'mem':$aLevel[$iLv+1];
	
	//把公視轉換成代數的方式
	$share_gold='CAST(share_gold_[lv] AS SIGNED)-CAST(share_gold_[lv]_out AS SIGNED)+CAST(share_gold_[lv]_in AS SIGNED)';
	$share='(CAST(share_gold_[lv] AS SIGNED)-CAST(share_gold_[lv]_out AS SIGNED)+CAST(share_gold_[lv]_in AS SIGNED)
	*0.001)/(gold*0.1)';
	$share_out='(share_gold_[lv]_out)';
	$water_next='water_basis-water_gap_[lv_next]';
	$water='water_basis-water_gap_[lv]';
	$odds='(odds_basis+odds_gap+odds_adjust+odds_adjust_auto+odds_adjust_auto_rep)';
	//因為還有補貨的部分 把外拋減去
	$gold='(gold*0.1)';
	$share=str_replace('[lv]',$ulv,$share);
	$share_gold=str_replace('[lv]',$ulv,$share_gold);
	$share_out=str_replace('[lv]',$ulv,$share_out);
	$gold=str_replace('[lv]',$ulv,$gold);
	$water_next=str_replace('[lv_next]',$lv_next,$water_next);
	$water=str_replace('[lv]',$ulv,$water);
	$aSQL=array();
	$aSQL[]='SELECT';
	$aSQL[]='id_[lv]';
	$aSQL[]=',ptype';
	$aSQL[]=',item';
	$aSQL[]=',COUNT(gold) AS cnt';
	$aSQL[]=',SUM(gold)*0.1 AS GOLD';
  $aSQL[]=',SUM([share_out]*0.001) AS share_out';
  $aSQL[]=',SUM(([share_gold])*0.001*([water])*0.0001) AS share_water';
  $aSQL[]=',SUM([share_gold])*0.001 AS share_gold';
  $aSQL[]=',SUM([gold]*(([water_next])*0.0001)*([share])*0.001) AS water_gold';
  $aSQL[]=',SUM(';
  $aSQL[]='(([share_gold])*0.001)';
  $aSQL[]='*[odds]*0.0001';
  $aSQL[]=') AS win_gold';
	$aSQL[]=',SUM(';
  $aSQL[]='(gold*0.1)';
  $aSQL[]='*[odds]*0.0001';
  $aSQL[]=') AS fake_win_gold';
	$aSQL[]='FROM `draws_[game]_bet`';
	$aSQL[]='WHERE 1';
	$aSQL[]="AND bet_status = 'N'";
	$aSQL[]="AND result_status = 'U'";
	$aSQL[]='AND rpt_date ="[rpt_date]"';
	$aSQL[]='AND date_sn="[date_sn]"';
	//$aSQL[]='AND ptype="[ptype]"';
	$aSQL[]='GROUP BY id_[lv],rpt_date,date_sn,ptype,item';
	$sSQL=implode(' ',$aSQL);
	$sSQL=str_replace('[game]',$sGame,$sSQL);
	$sSQL=str_replace('[lv]',$ulv,$sSQL);
	//$sSQL=str_replace('[ptype]',$iPtype,$sSQL);
	$sSQL=str_replace('[rpt_date]',$sRpt_date,$sSQL);
	$sSQL=str_replace('[date_sn]',$iDate_sn,$sSQL);
	$sSQL=str_replace('[share_gold]',$share_gold,$sSQL);
	$sSQL=str_replace('[share]',$share,$sSQL);
	$sSQL=str_replace('[water_next]',$water_next,$sSQL);
	$sSQL=str_replace('[water]',$water,$sSQL);
	$sSQL=str_replace('[odds]',$odds,$sSQL);
	$sSQL=str_replace('[gold]',$gold,$sSQL);
	$sSQL=str_replace('[share_out]',$share_out,$sSQL);
	//echo $sSQL." </br> ";
	$q=$db_s->sql_query($sSQL);
	$slv_id='id_'.$ulv;
	while($r=$db_s->nxt_row('ASSOC')){
		$iPtype=$r['ptype'];
    $sItem=$r['item'];
    $ilv_id=$r[$slv_id];
		unset($r[$slv_id]);
		unset($r['ptype']);
		unset($r['item']);
		$aRet[$slv_id."|".$ilv_id."|".$iPtype][$sItem]=$r;
	}
	return $aRet;
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
		$redis->del_row($sTable,$aWhere);
	}
}

?>