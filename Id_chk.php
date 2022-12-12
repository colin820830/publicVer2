<?

	if(empty($_SESSION['userid']))
	{
		print('<script type="text/javascript">alert("請先登入 !");</script>');				
		print('<script type="text/javascript">location.href = "login.php";</script>');
	}

?>