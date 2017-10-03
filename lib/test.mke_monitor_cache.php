<?php
ini_set('display_errors', 1); 
error_reporting(E_ERROR);
include_once('class.redis.php');
include_once('class.db.PDO.php');
include_once('../conf/sys_config.php');
//建立 redis 簡單連線
$redis=mke_redis_link($redis_set);
$db=mke_pdo_link($insert_db);
$db_s=mke_pdo_link($select_db);
init();
function init(){
	$sGame='ssc';
	//while(true){
		make_monit_risk_gold_cache($sGame);
		//sleep(1);
	//}
}
//製作每一層的實盤 盈虧快取
/*
	傳入
		遊戲=$sGame
		報表日期=$sRpt_date
		期數編號=$iDate_sn
	
	*取當前期數資料
	*設定redis key 快取製作狀態 總筆數 
	*查詢最大筆數
	*會先檢查 redis 目前有無最大單號 跟 快取製作狀態 沒有就給預設值
	*判斷 是否需要 做快取 快取製作狀態正在做時離開 總筆數 沒變離開
	*快取製作狀態 改為製作中
	*依照層級 去計算 實盤金額 
	*依照各層級的id 玩法 日期 期數 作為查詢條件 將結果放入redis
	*查詢最大筆數 放到redis
	*快取製作狀態 製作完成
*/
function make_monit_risk_gold_cache($sGame){
	global $redis;
	$debug=true;
	//取當前期數資料
	$sRpt_date='2017-09-28';
	$iDate_sn='12';
	$aUlv=array('mm','sc','co','sa','ag');
	//設定redis key 快取製作狀態 總筆數 
	$aCache_col=array(
		'risk_gold_cache_status'
		,'rpt_date'=>$sRpt_date
		,'date_sn',$iDate_sn
	);
	$aCnt_col=array(
		'cnt_bet'
		,'rpt_date'=>$sRpt_date
		,'date_sn',$iDate_sn
	);
	
	$sTable='draws_[game]_bet';
	$sTable=str_replace('[game]',$sGame,$sTable);
	$time_start = microtime(true);
	//查詢最大筆數
	$aCnt=ser_get_bet_cnt($sGame,$sRpt_date,$iDate_sn);
	$sCnt=$aCnt['cnt'];
	//會先檢查 redis 目前有無最大單號 跟 快取製作狀態 沒有就給預設值
	$aOld_cnt=$redis->get_row($sTable,$aCnt_col);
	$aCache_status=$redis->get_row($sTable,$aCache_col);
	if(empty($aOld_cnt)){
		$aOld_cnt['cnt']=0;
	}
	if(empty($aCache_status)){
		$aCache_status['status']='cachend';
	}
	$sOld_cnt=$aOld_cnt['cnt'];
	if($debug){
		echo "<xmp>";
		echo "SQL : ".$aCnt['cnt']." \n";
		echo "redis : ".$aOld_cnt['cnt']." \n";;
		echo $aCache_status['status']." \n";
		echo "</xmp>";
	}
	//判斷 是否需要 做快取 快取製作狀態正在做時離開 總筆數 沒變離開
	if($sCnt==$sOld_cnt){ return ; }
	if($aCache_status['status']=='caching'){ return ; }
	//快取製作狀態 改為製作中
	$cache_status=array('status'=>'caching');
	$redis->set_row($sTable,$aCache_col,$cache_status,120);
	foreach($aUlv as $sn => $ulv){
		//依照層級 去計算 實盤金額 
		$risk_gold=ser_monit_get_risk_gold($sGame,$sRpt_date,$iDate_sn,$ulv);
		//依照各層級的id 玩法 日期 期數 作為查詢條件 將結果放入redis
		foreach($risk_gold as $skey => $aValue){
			$akey=explode("|",$skey);
			$sId_lv_col=$akey[0];
			$iId_lv=$akey[1];
			$iPtype=$akey[2];
			$aWhere=array(
				'risk_gold'
				,$sId_lv_col=>$iId_lv
				,'rpt_date'=>$sRpt_date
				,'date_sn'=>$iDate_sn
				,'ptype'=>$iPtype
			);
			$redis->set_row($sTable,$aWhere,$aValue,120);
		}
		usleep(200000);
	}
	$cache_status=array('status'=>'cachend');
	//查詢最大筆數 放到redis
	$aNow_cnt=ser_get_bet_cnt($sGame,$sRpt_date,$iDate_sn);
	$redis->set_row($sTable,$aCnt_col,$aNow_cnt,120);
	//快取製作狀態 製作完成
	$redis->set_row($sTable,$aCache_col,$cache_status,120);
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	if($debug){
		echo "exec:$time";
	}
}
//抓取這期總筆數
/*
	傳入
		遊戲=$sGame
		報表日期=$sRpt_date
		期數編號=$iDate_sn
	回傳
		[cnt]=>筆數
*/
function ser_get_bet_cnt($sGame,$sRpt_date,$iDate_sn){
  global $db_s;
	$aRet=array(
		'cnt'=>0
	);
	$aSQL=array();
	$aSQL[]='SELECT';
	$aSQL[]='COUNT(sn) as cnt';
	$aSQL[]='FROM draws_[game]_bet';
	$aSQL[]='WHERE 1';
	$aSQL[]="AND bet_status = 'N'";
	$aSQL[]="AND result_status = 'U'";
	$aSQL[]='AND rpt_date ="[rpt_date]"';
	$aSQL[]='AND date_sn="[date_sn]"';
	$sSQL=implode(' ',$aSQL);
	$sSQL=str_replace('[game]',$sGame,$sSQL);
	$sSQL=str_replace('[rpt_date]',$sRpt_date,$sSQL);
	$sSQL=str_replace('[date_sn]',$iDate_sn,$sSQL);
	$q=$db_s->sql_query($sSQL);
	if($db_s->numRows() < 1){return $aRet;}
	$r=$db_s->nxt_row('ASSOC');
	$aRet=$r;
	return $aRet;
}
//計算 這個層級 這一期的 所有玩法 和項目 實盤金額 
/*
	傳入
		遊戲=$sGame
		報表日期=$sRpt_date
		期數編號=$iDate_sn
	回傳
		結果[層級id 欄位."|".層級id 值."|".玩法][項目]=計算結果[];
	
	*因為如果要做快取 每一層每個人每個玩法去訪問 會有效能上的問題
*/
function ser_monit_get_risk_gold($sGame,$sRpt_date,$iDate_sn,$ulv){
  global $db_s;
  global $redis;
	$aRet=array();
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
	$aSQL[]='GROUP BY id_[lv],rpt_date,date_sn,ptype,item';
	$sSQL=implode(' ',$aSQL);
	$sSQL=str_replace('[game]',$sGame,$sSQL);
	$sSQL=str_replace('[lv]',$ulv,$sSQL);
	$sSQL=str_replace('[rpt_date]',$sRpt_date,$sSQL);
	$sSQL=str_replace('[date_sn]',$iDate_sn,$sSQL);
	$sSQL=str_replace('[share_gold]',$share_gold,$sSQL);
	$sSQL=str_replace('[share]',$share,$sSQL);
	$sSQL=str_replace('[water_next]',$water_next,$sSQL);
	$sSQL=str_replace('[water]',$water,$sSQL);
	$sSQL=str_replace('[odds]',$odds,$sSQL);
	$sSQL=str_replace('[gold]',$gold,$sSQL);
	$sSQL=str_replace('[share_out]',$share_out,$sSQL);
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
?>