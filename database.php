<?php
require_once 'log.php';

	class Database {
		private $ctr;    //connect string
		private $driver;
		private $host;
		private $port;
		private $sid;
		private $conn;   //連線物件
		private $rs;    //Recordset

		private $where;    //sql的where子句
		private $table;
		private $fields;    //select子句用到的欄位名
		private $values;    //insert子句用到的值
		private $orderby;     //select子句用到的order by
		private $fieldName = array();			//sql子句欄位名稱
		private $sqlStmt;      //sql子句
		private $sqlResult = array();      //sql子句執行結果

		private $numrows;    //取出筆數
		private $offset;     //偏移量
		private $totRec;     //總筆數
		private $totPage;   //總頁數 = ceil(總筆數 / 10)
		private $curPage;    //目前頁碼
		private $firstPage;     //起始頁碼
		private $lastPage;     //結束頁碼

		//定義建構方法
		public function __construct($drive, $host, $port, $sid) {
			$this->driver = $drive;
			$this->host = $host;
			$this->port = $port;
			$this->sid = $sid;
			
			//預設值
		}
	
		//定義解構方法
		public function __destruct() {
			$this->rs->Close();
			$this->conn->Close();    //optional
		}
	
		public function initDB($usr, $pwd) {
			try {
				$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
				$ADODB_COUNTRECS = false;
				//連接資料庫之前先設定Global變數
				$this->conn = &ADONEWConnection($this->driver);
				$this->conn->debug = false;
				$this->conn->connectSID = true;
				$this->ctr = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST= '.$this->host.' )(PORT= '.$this->port.' ))(CONNECT_DATA=(SID= '.$this->sid.' )))';
				
				$this->conn->Connect($this->ctr, $usr, $pwd);
			} catch(exception $e) {
				//var_dump($e);
				//adodb_backtrace($e->gettrace());
				echo 'Database connect error!';
			}
		}

		private function setWhere($where) {
			$this->where = $where;
		}
		
		public function selStmt($table, $fields, $where, $orderby) {    //Select敘述句
			$this->setWhere($where);  //先設定where子句
			$this->table = $table;
			$this->fields = $fields;
			$this->orderby = $orderby;
			$this->sqlStmt = 'select '.$this->fields.' from '.$this->table.' '.$this->where.' '.$this->orderby;
			
			//$log   = new Log();
			//$log->write($this->sqlStmt, "BeanCode");

			$this->execSql();
			
			array_splice($this->sqlResult, 0);
			while (!$this->rs->EOF) {
				array_push($this->sqlResult, $this->rs->fields);
				$this->rs->MoveNext();
			}
			
			return $this->sqlResult;
		}
		
		private function execSql() {  //執行sql子句
			$this->rs =& $this->conn->Execute($this->sqlStmt);
		}

		public function retSqlstmt() {  //傳回sqlStmt屬性
			return $this->sqlStmt;
		}

		public function selLimit($table, $fields, $where, $orderby, $rows, $offset) {   //取出一定筆數的記錄
			$this->setWhere($where);  //先設定where子句
			$this->table = $table;
			$this->fields = $fields;
			$this->orderby = $orderby;
			$this->numrows = $rows;
			$this->offset = $offset;
			
			$this->sqlStmt = 'select '.$this->fields.' from '.$this->table.' '.$this->where.' '.$this->orderby;
			$this->execSql();
			
			$this->getRecCnt($this->sqlStmt);  //先求出總筆數
			$this->getTotPage();    //求總頁數
			$this->lastPage = (ceil($this->curPage / 10) * 10 < $this->totPage) ? ceil($this->curPage / 10) * 10 : $this->totPage;  //求結束頁碼
			$this->firstPage = ceil($this->lastPage / 10) * 10 - 9;  //求起始頁碼
			
			$this->execSqlLimit();
			
			array_splice($this->sqlResult, 0);
			while (!$this->rs->EOF) {
				array_push($this->sqlResult, $this->rs->fields);
				$this->rs->MoveNext();
			}
			return $this->sqlResult;
		}

		private function execSqlLimit() {  //執行selectLimit取出一定筆數
			$this->rs =& $this->conn->SelectLimit($this->sqlStmt, $this->numrows, $this->offset);
		}

		public function setCurPage($page) {   //設定目前頁碼
			$this->curPage = $page;
		}
		
		public function retCurPage() {   //傳回目前頁碼
			return $this->curPage;
		}
		
		public function retFirstPage() {   //傳回起始頁碼
			return $this->firstPage;
		}
		
		public function retLastPage() {   //傳回結束頁碼
			return $this->lastPage;
		}
		
		private function getRecCnt($sql) {
			$this->totRec =& $this->rs->RecordCount($sql);
		}
		
		public function retTotRec() {  //回傳總筆數
			return $this->totRec;
		}
	
		private function getTotPage() {   //求總頁數
			$this->totPage = ceil($this->totRec / 10);
		}
	
		public function retTotPage() {   //回傳總頁數
			return $this->totPage;
		}

		public function retField() {   //傳回欄位名稱
			array_splice($this->fieldName, 0);		
			for($i = 0; $i < $this->rs->FieldCount(); $i++) 
				$this->fieldName[$i] = $this->rs->FetchField($i);
				
			return $this->fieldName;
		}

		public function updStmt($table, $fields, $where) {    //Update敘述句
			$this->setWhere($where);  //先設定where子句
			$this->table = $table;
			$this->fields = $fields;
			$this->sqlStmt = 'update '.$this->table.' set '.$this->fields.' '.$this->where;
			$this->execSql();
		}
	
		public function insStmt($table, $fields, $values) {    //Insert into敘述句
			$this->table = $table;
			$this->fields = $fields;
			$this->values = $values;
			$this->sqlStmt = 'insert into '.$this->table.' ('.$this->fields.') values('.$this->values.')';

			$log   = new Log();
			$log->write($this->sqlStmt, "BeanCode");
			
			$this->execSql();
		}
	
		public function delStmt($table, $where) {    //Delete敘述句
			$this->setWhere($where);  //先設定where子句
			$this->table = $table;
			$this->sqlStmt = '';			
			$this->sqlStmt = 'delete from '.$this->table.' '.$this->where;
			$this->execSql();
		}

	}
?>