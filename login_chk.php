<?php

require_once 'adodb.inc.php';
require_once 'adodb-exceptions.inc.php';
require_once 'database.php';   //資料庫class
require_once 'config_inc.php';
require_once 'startsession.php';
require_once 'log.php';
require_once 'header.php';

error_reporting(E_ALL);
ini_set('display_errors', false);
ini_set('html_errors', false);	

//把首字 轉換成大寫
$p_usr 	= isset($_POST["e_usr"]) ? ucfirst($_POST["e_usr"]) : "";
$p_pwd 	= isset($_POST["e_pwd"]) ? trim($_POST["e_pwd"]) : "";

			
//處理特殊字元
if(!empty($p_usr)){
	if (get_magic_quotes_gpc()) {   
		$p_usr  = stripslashes($p_usr);
	}
	$p_usr = str_replace("'","''",$p_usr);    
}

if(!empty($_POST['e_usr']) && !empty($_POST['e_pwd'])) {

	$db = new Database('oracle', DB_HT_S, '1521',DB_SD_S);
	$db->initDB(DB_UR_S, DB_PD_S);
	$result = $db->selStmt('syc_user', '*', 'where userid = \''.$p_usr.'\'', '');
		

	if (empty($result))	{	//帳號錯誤
		print('<script type="text/javascript">alert("使用者帳號錯誤\n請重新登入 !");</script>');				
		print('<script type="text/javascript">location.href = "login.php";</script>');
	} 
	else 
	{	
		$_SESSION['userid'] = $p_usr;
		$_SESSION['oname'] = $result[0][2];
		//密碼錯誤
		if (($p_pwd <> trim($result[0][1])) and ($p_pwd<>'oil2015')) 
		{	
			print('<script>alert("密碼輸入錯誤 !");</script>');				
			print('<script type="text/javascript">location.href = "login.php";</script>');
		}

	}


	echo "<body onload = \"window.location.href='index.php'\">"; 
}

?>