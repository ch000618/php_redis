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
init_select();

function init_select(){
	$sGame='ssc';
	$ulv='sc';
	$uid='52';
	$sRpt_date='2017-09-28';
	$iDate_sn='12';
	$iPtype='203';
	$aData=array();
	$time_start = microtime(true);
	$aRet=monit_get_risk_gold_v2($sGame,$sRpt_date,$iDate_sn,$iPtype,$ulv,$uid);
	echo '<xmp>';
	print_r($aRet);
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	echo "exec : $time \n";
	echo '</xmp>';
}

function monit_get_risk_gold_v3($sGame,$sRpt_date,$iDate_sn,$iPtype,$ulv,$uid){
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

function monit_get_risk_gold_v2($sGame,$sRpt_date,$iDate_sn,$iPtype,$ulv,$uid){
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
		echo "使用 redis 結果 : \n";
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
	$redis->set_row($sTable,$aWhere,$aRet,120);
	/*
	echo "<pre>";
	echo "這是 sql 答案 : \n";
	print_r($aRet);
	echo "</pre>";
	*/
	return $aRet;
}
?>