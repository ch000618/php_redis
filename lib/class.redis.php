<?php
/*
	*建構子
	*解構子
	*建立 Redis 連線 
	*連線失敗傳 錯誤訊息
	*檢查密碼 
	*將value存入 某個key
	
	設置 key 生存時間
	取得 某個key 的value
	執行刪除 某個key 的value
	簡單使用方式
  $db_set=array(
		 'host'=ip
		,'port'=埠號
		,'db_rds'= redis 資料庫
		,'rds_enabled'= redis是否啟用 站台設置
		,'db_mysql'= mysql 資料庫
		,'pass'= 密碼(沒有就給空字串)
  );
  $redis = mke_redis_link($db_set);
  新增資料 : 
  $redis->set_row($sTable,$aWhere,$aData)
  取回資料 : 
  $redis->get_row($sTable,$aWhere)
  刪除資料 : 
  $redis->get_row($sTable,$aWhere)
  ---
	class 使用方式
     $host=主機位置
    ,$port=連線POST
    ,$db
		redis=new php_redis($host,$port,$db)
		有設密碼 的話 否則不會跑
   	redis->php_redis_auth($passowd);
		塞資料進 redis 要傳 key 跟 val
		redis->php_redis_set($key,$val);
		取資料 傳key 進去 回傳資料
		redis->php_redis_get($key);
*/
class php_redis{
	private $host='';//主機
	private $port='';//連接port
	private $db=0;//資料庫
	private $pass='';//密碼
	private $connection='';//連線
	private $debug=false;//debug 模式
  private $db_mysql = '';//對應的mysql資料庫
  private $redis_enabled = '';//redis是否啟用 站台設置
  private $redis_multi = 0;
	private $error_code=array(
		 'no_conn'		=>0
		,'conn_ok'		=>1
		,'conn_ng'		=>2
		,'auth_ok'		=>3
		,'auth_ng'		=>4
	);
	public  $link;
	//狀態:文字
	public	$status_text='no connection';
	//狀態碼
	public	$status_code=0;
	// 建立連線
	private function php_redis_open(){
		$host=$this->host;
		$port=$this->port;
		if(!class_exists('Redis')){
			return $this->status_code;
		}
		if($this->redis_enabled==0){
			return $this->status_code;
		}
		$this->connection = $this->link->connect($host,$port);
		if(!$this->connection) {
      $this->status_code = $this->error_code['conn_ng'];
      $this->status_text = "connection failed !";
			return $this->status_code;
    }
		$this->status_code = $this->error_code['conn_ok'];
    $this->status_text = "connection success !";
		return $this->status_code;
	}
	//確認目前 連線狀態 如果任一種失敗 就會停掉
	private function php_redis_chk_status(){
		$status=true;
		switch($this->status_code){
			case 4:// 驗證失敗
				$status=false;
				break;
			case 0:// 無法連線
				$status=false;
				break;
			case 2:// 連線失敗
				$status=false;
				break;
		}
		return $status;
	}
	// 建構子
	function  __construct($host,$port,$db_redis,$db_mysql,$redis_enabled){
		if($host!=''){$this->host=$host;}
		if($port!=''){$this->port=$port;}
		if($db_redis!=''){$this->db=$db_redis;}
		if($db_mysql!=''){$this->db_mysql=$db_mysql;}
		if($redis_enabled!=''){$this->redis_enabled=$redis_enabled;}
		if(!class_exists('Redis')){
			return $this->status_code;
		}
		$this->link=new Redis();
		$this->php_redis_open();
	}
	// 檢查密碼
	function php_redis_auth($pass){
		if(!class_exists('Redis')){
			return $this->status_code;
		}
		if($this->redis_enabled==0){
			return $this->status_code;
		}
		$this->pass=$pass;
		$r=$this -> link -> auth($this->pass);
		if($r) {
      $this->status_code = $this->error_code['auth_ok'];
      $this->status_text = "Authorization success !";
			return $this->status_code;
    }
		$this->status_code = $this->error_code['auth_ng'];
    $this->status_text = "Authorization failed !";
		return $this->status_code;
	}
	function chg_redis_db(){
		if(!class_exists('Redis')){
			return $this->status_code;
		}
		if($this->redis_enabled==0){
			return $this->status_code;
		}
		try{
			$this -> link -> SELECT($db);
		}catch(exception $e){
			$this->status_code=2;
		}
	}
	//啟動交易模式
	function php_redis_multi(){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$this->link->multi(Redis::MULTI);
	}
	//取得 某些key 
	/*
		傳入 
			$sKey=要查詢 的key名 
		回傳
			$result=結果
	*/
	function php_redis_keys($sKey='*'){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->keys($sKey);
		return $result;
	}
	//將value存入 某個key
	/*
		相同 key 會被覆蓋 
		跟陣列的概念相同
		只支援字串 數字 json 但不能放陣列
	*/
	function php_redis_set($key,$val){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->set($key,$val);
		return $result;
	}
	//取得 某個key 的value
	/*
		傳入 
			$key=要查詢 的key值
		回傳
			$result=結果
	*/
	function php_redis_get($key){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->get($key);
		return $result;
	}
	//執行刪除 某個key 的value
	/*
		傳入 
			$key=要刪除 的key值
	*/
	function php_redis_del($key){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->del($key);
		return $result;
	}
	//設置 key 生存時間
	/*
		傳入
			$key=要設定 的key
			$time=生存秒數
	*/
	function php_redis_expire($key,$time){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$this->link->expire($key,$time);
		$result=$this->link->TTL($key);
		return $result;
	}
	//寫入redis  
	/* 
	傳入
		sTable=mysql 表名稱;
		aWhere=array();
		aData=array();
	回傳 
		1;成功
		0;失敗
	*檢查 資料庫名稱 有沒有值
	*檢查 資料表名稱 有沒有值
	*檢查欄位 格式 是否正確
	*將資料庫名稱 跟 資料表 跟條件式 彙整成key 值
	*將資料轉成json 
	*再將 key 和 資料寫進 redis
	*170930 新增 設定到期時間 不設定就是永久存在  有設定的話到期就消失了
	*/
	function set_row($sTable,$aWhere,$aData,$expire_time=false){
    $sMdb_name=$this->db_mysql;
		$sRet=0;
		if($sMdb_name==''){return $sRet;}
		if($sTable==''){return $sRet;}
		if(count($aWhere)<1){return $sRet;}
		if(count($aData)<1){return $sRet;}
		$sRet=1;
		$aKeys=array();
		$aTmp=array();
		$aTmp['db_name']=$sMdb_name;
		$aTmp['table']=$sTable;
		$aKeys=array_merge($aTmp,$aWhere);
		$aData['update_time']=date('Y-m-d H:i:s');
		$sKey=implode('#',$aKeys);
		$json=json_encode($aData,true);
		$sRet=$this->php_redis_set($sKey,$json);
		if($expire_time!=false){
			$q=$this->php_redis_expire($sKey,$expire_time);
		}
		return $sRet;
	}
	//取出redis 
	/*
	傳入
		sTable=mysql 表名稱;
		aWhere=array();
	回傳
		$aRet=array()
	*檢查 資料庫名稱 有沒有值
	*檢查 資料表名稱 有沒有值
	*將資料庫名稱 跟 資料表 跟條件式 彙整成key 值
	*/
	function get_row($sTable,$aWhere){
    $sMdb_name=$this->db_mysql;
		$sRet=array();
		if($sMdb_name==''){return $aRet;}
		if($sTable==''){return $aRet;}
		if(count($aWhere)<1){return $sRet;}
		$aKeys=array();
		$aTmp=array();
		$aTmp['db_name']=$sMdb_name;
		$aTmp['table']=$sTable;
		$aKeys=array_merge($aTmp,$aWhere);
		$sKey=implode('#',$aKeys);
		$json=$this->php_redis_get($sKey);
		$aRet=json_decode($json,true);
		if($this->debug==false){
			unset($aRet['update_time']);
		}
		return $aRet;
	}
	// 刪除redis  
	/*
	傳入
		sTable=mysql 表名稱;
		aWhere=array();
	*檢查 資料庫名稱 有沒有值
	*檢查 資料表名稱 有沒有值
	*將資料庫名稱 跟 資料表 跟條件式 彙整成key 值
	*/
	function del_row($sTable,$aWhere){
    $sMdb_name=$this->db_mysql;
		$sRet=0;
		if($sMdb_name==''){return $sRet;}
		if($sTable==''){return $sRet;}
		if(count($aWhere)<1){return $sRet;}
		$sRet=1;
		$aKeys=array();
		$aTmp=array();
		$aTmp['db_name']=$sMdb_name;
		$aTmp['table']=$sTable;
		$aKeys=array_merge($aTmp,$aWhere);
		$sKey=implode('#',$aKeys);
		$sRet=$this->php_redis_del($sKey);
		return $sRet;
	}
	//取得某個key 的列表
	/*
		如果這個key 是一個列表就要
		用這種方式
		$key=key值
		$start=開始列
		$end=結束列
	*/
	function php_redis_lrange($key,$start,$end){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->lrange($key,$start,$end);
		return $result;
	}
	//將一个或多个值 value 插入到列表 key 的表尾
	function php_redis_rpush($key,$val){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->rpush($key,$val);
		return $result;
	}
	//將一个或多个值 value 插入到列表 key 的表頭
	function php_redis_lpush($key,$val){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->lpush($key,$val);
		return $result;
	}
	//從列表開頭數過來 將列表key中index的元素改為new_val
	function php_redis_lset($key,$index,$new_val){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->lset($key,$index,$new_val);
		return $result;
	}
	//返回列表key中index的元素
	function php_redis_lindex($key,$index){
		$r=$this->php_redis_chk_status();
		if($r!=true){return 0;}
		$result=$this->link->lindex($key,$index);
		return $result;
	}
	// 解構子
	function __destruct(){
		if(!class_exists('Redis')){
			return $this->status_code;
		}
		if($this->redis_enabled==0){
			return $this->status_code;
		}
		$this->link->close();
	}
}
//簡單建立 redis 資料庫連線
/*
	$db_set={
		'host'=ip
		'port'=埠號
		'db_rds'= 資料庫編號
		'db_mysql'= 資料庫編號
		'pass'= 密碼
	}
	回傳
		$redis
*/
function mke_redis_link($db_set){
	$redis=new php_redis($db_set['host'],$db_set['port'],$db_set['db_rds'],$db_set['db_mysql'],$db_set['rds_enabled']);
	//有設密碼的機器 設定這個欄位不能是空的 否則不會去做驗證
	if($db_set['pass']!='' || !isset($db_set['pass'])){
		$redis->php_redis_auth($db_set['pass']);
	}
	if($db_set['db_rds']!=0 || $db_set['db_rds']!=''){
		$redis->chg_redis_db();
	}
	return $redis;
}
?>