<?php
include_once('class.redis.php');
include_once('class.db.PDO.php');
include_once('../config/sys_config.php');
//建立 redis 簡單連線
$redis=mke_redis_link($redis_set);
$db=mke_pdo_link($insert_db);
$db_s=mke_pdo_link($select_db);
init();
function init(){
	init_order();
}
function init_order(){
	$aOrder=$_POST;
	if(empty($aOrder)){return ;}
	$oOrder=new test_order();
  $oOrder->fake_order($aOrder);//設定下注注單
  $oOrder->exec();//執行下單
	echo "ok!";
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
?>