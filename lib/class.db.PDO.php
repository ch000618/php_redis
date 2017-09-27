<?php
//使用PDO 作為資料連線的CLASS
/*
	*介面原則上跟classbasic.php 一樣
	*預設會全部關掉,有需要再打開,減少記憶體以及效能浪費
	錯誤碼
		#01! 資料庫連線失敗
		#02! sql_query 時的連線失敗 (有其他可能性)
		#03! sql語法錯誤
*/
class db_PDO{
	// 公開變數
	// public $sqlhost;
	public $link;
	public $sqlusr;
	public $sqlpwd;
	public $sqldb;
	public $path_root;
	public $fetch_type='NUM';//預設回傳方式:[0],[1],....
 	public $row;
 	public $result;
 	public $affected_rows=0; //影響筆數
	public $insert_id=0;
  public $beginTransaction=0; //是否啟用交易
	//-------
	//私有變數
	private $bConnected = false;
	//-------
	//建構子
	function  __construct(){}
	//解構子
	/*
		# Set the PDO object to null to close the connection
		# http://www.php.net/manual/en/pdo.connections.php
	*/
	function __destruct(){
    //資料恢復,解除交易機制
    if($this->beginTransaction!=0){
      $this->link->rollBack();
      $this->beginTransaction=0;
    }
		$this->pdo = null;
	}
	//切換DB
	/*
		$host:主機,$usr:使用者,$pwd:密碼,$db:資料庫
		*170417:使用固定連線
		*170705:加上port 判斷
	*/
	function change_db($host,$usr,$pwd,$db){
		$this->sqlhost=$host;
		$this->sqlusr=$usr;
		$this->sqlpwd=$pwd;
		$this->sqldb=$db;
		$dsn = "host=".$host.";dbname=".$db;
		if(strpos($host,':')>0){
			$aHost = explode(':',$host);
			$host = $aHost[0];
			$port = $aHost[1];
			$dsn = "host={$host};port={$port};dbname={$db};";
		}
		$mysql = "mysql:unix_socket=/var/lib/mysql/mysql.sock;$dsn";
		$options = array(
			 PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
			,PDO::ATTR_PERSISTENT => true
			,PDO::ATTR_EMULATE_PREPARES => false
			,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		try{
			$this->link = new PDO($mysql, $this->sqlusr , $this->sqlpwd , $options );
			$this->bConnected = true;
		}catch(PDOException $e){
			die("#01!");
		}
	}
	//送出query
	/*
		$op=0:只負責送query
		$op=1:順便取一筆資料回來
		$op=2:順便取最後insert 的 id
	*/
	Function sql_query($sql,$op=0){
		if (!$this->bConnected){
			die("#02!");
		}
		$this->insert_id=0;
		$this->affected_rows=0;
		//只有送query
		if($op==0){
			$this->exec_sql($sql);
			// $this->affected_rows=$this->result->rowCount();
			$this->insert_id=$this->link->lastInsertId();
			return $this->result;
		//順便取一筆資料
		}else if($op==1){
			$this->exec_sql($sql);
			$row=$this->nxt_row();
			return $row;
		//取得最後新增的id
		}else if($op==2){
			$result = $this->exec_sql($sql);
			$this->insert_id=$this->link->lastInsertId();
			return $result;
		}
	}
	//執行SQL
	/*
		*回傳resule
		*順便記錄改了多少筆
		---
		170323: 修正問題,如果執行有錯,就不會有affected_rows
	*/
	Function exec_sql($sql){
		$this->affected_rows=0;
    $result=false;
		try {
			$this->result = $this->link->prepare($sql,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
			$result=$this->result->execute();
			$this->affected_rows=(!$this->result)?0:$this->result->rowCount();
		}catch(PDOException $e){
      $errorCode=$this->errorCode();
      if ($this->errorCode() != '00000'){
        //echo "errorCode=$errorCode\n";
        $this->errorstatus($sql,$e);
        $this->result = false;
        if($this->beginTransaction==1){ 
					$this->beginTransaction=0;    
					$this->link->rollBack(); 
				}
        $msg="#03!";
        //$msg.=$sql;
        die($msg);
      }else{
        $this->errorstatus($sql,$e);
        $this->result = false;
        if($this->beginTransaction==1){
					$this->beginTransaction=0;
					$this->link->rollBack(); 
				}
			}
		}
		return $result;
	}
  //取回錯誤碼
  Function errorCode(){
    $aErrorInfo=$this->link->errorInfo();
    return $aErrorInfo[0];
  }
	//多重query
	Function sql_query_muti($arySql){
		$nCount = count($arySql);
		if (!is_array($arySql) || $nCount ==0) {
			return false;
		}
		for ($i=0;$i<$nCount;$i++) {
			if ($this->sql_query($arySql[$i])) {
			}else {
				return false;
			}
		}
		return true;
	}
  //啟動交易
  Function beginTransaction(){
    $this->beginTransaction=1;
    $this->link->beginTransaction();
  }
  //結束交易
  Function commit(){
		if($this->beginTransaction == 0){return ;}
    $this->beginTransaction=0;
    $this->link->commit();
  }
	//回傳筆數
	Function numRows(){
		return $this->result->rowCount();
	}
	//釋放並關閉連線
	Function free_and_close(){
		$this->pdo->closeCursor();
		$this->pdo = null;
	}
	//取一筆資料
	/*
		$type=回傳格式,如果沒有設定,或者不合法,會使用$this->fetch_type
	*/
	Function nxt_row($type=''){
		$fetch_type_all=array('ASSOC','NUM');
		$fetch_type=(!in_array($type,$fetch_type_all))?$this->fetch_type:$type;
    switch($fetch_type){
			case 'ASSOC':
				$this->row = $this->result->fetch(PDO::FETCH_ASSOC,PDO::FETCH_ORI_NEXT);
				break;
			case 'NUM':
				$this->row = $this->result->fetch(PDO::FETCH_NUM	,PDO::FETCH_ORI_NEXT);
				break;
			case 'BOTH':
				$this->row = $this->result->fetch(PDO::FETCH_BOTH	,PDO::FETCH_ORI_NEXT);
				break;
		}
		return $this->row;
	}
	//紀錄SQL錯誤
  /*
    寫入:[
      [日期時間]
      sql語法
      錯誤資訊
      程式檔
    ]
		*161013:加上_GET 跟 _POST
		*170323:加上 Exception
    *170417:如果發生Deadlock,另外寫一個Deadlock的LOG
  */
	Function errorstatus($sSQL,$oException){
    global $_UserData;
		$Mypath=$this->path_root."text/";
		$sDate=date('Ymd');
		$log_file=$Mypath."sql_error_log_{$sDate}.log";
    $errorstring='';
		if (file_exists($log_file)){
			$filep=fopen($log_file,"r");
			fseek($filep,0);
			$errorstring=fread($filep,filesize($log_file));
		}
    $sNow=date("Y-m-d H:i:s");
    if($oException->errorInfo[0]=="40001"){$this->deadlock_log($sSQL,$sNow);}
		$aryStr=array();
		$aryStr[]=$errorstring;
		$aryStr[]='['.$sNow.']';
		$aryStr[]='SQL : '.$sSQL;
		$aryStr[]=implode(' ',$this->link->errorInfo());
		$aryStr[]='Exception:'.json_encode($oException);
		$aryStr[]='SCRIPT_NAME:'.$_SERVER['SCRIPT_NAME'];
		$strError = implode("\n",$aryStr);
		$bug_trace =  debug_backtrace();
		$strError .= PHP_EOL . ' bug_trace start :';
    $argvalue='';
		foreach( $bug_trace AS  &$item ){
			$linebug = '';
			foreach( $item AS $key => &$item2 ){
				if( $key == 'object' ){continue;}
				if( $key == 'args' ){continue;}
				if( $key == 'type' ){continue;}
				$linebug .= " $key : $item2 \t ";
			}
			$strError .= PHP_EOL . $linebug . $argvalue;
		}
    if(isset($_UserData)){
      $strError .= PHP_EOL . '$_UserData:'.var_export($_UserData,true);
    }
		if(isset($_GET)){
			$strError .= PHP_EOL . '$_GET:'.var_export($_GET,true);
		}
		if(isset($_POST)){
			$strError .= PHP_EOL . '$_POST:'.var_export($_POST,true);
		}
		$this->wFile($log_file,$strError);
	}
  //紀錄SQL deadlock
  /*
    $sSQL=發生死鎖的語法,$sTime=發生的時間
  */
  Function deadlock_log($sSQL,$sTime){
		$Mypath=$this->path_root."text/";
		$log_file=$Mypath."sql_deadlock.log";
		if (file_exists($log_file)){
			$filep=fopen($log_file,"r");
			fseek($filep,0);
			$errorstring=fread($filep,filesize($log_file));
		}
		$aryStr=array();
		$aryStr[]=$errorstring;
		$aryStr[]='['.$sTime.']';
		$aryStr[]='SQL : '.$sSQL;
		$strError = implode("\n",$aryStr);
		$this->wFile($log_file,$strError);
  }
	//寫檔
	Function wFile($file_wr,$strw){
    $file_tmp=$file_wr.'.tmp';
		// if($file_tmp==""){$file_tmp = $file_wr;}
		$filep=fopen($file_tmp,"w");
		@flock($filep,LOCK_EX);
		fputs($filep,$strw."\n");
		@flock($filep,LOCK_UN);
		fclose($filep);
		@chmod($file_tmp,0777);
		rename($file_tmp,$file_wr);
		@chmod($file_wr,0777);
	}
}
//簡單建立資料庫連線
/*
	$db_set={
		select_db
		insert_db
	}
	回傳:{
		db_s
		db
	}
*/
function mke_pdo_link($db_set){
	global $web_cfg;
	$db  = new db_PDO();
	$db->change_db($db_set['host'],$db_set['user'],$db_set['password'],$db_set['db']);
	$db->path_root=$web_cfg['path'];
	return $db;
}
?>