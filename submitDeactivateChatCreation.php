<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: login.php");

	if(!empty($_POST["groupChatId"])){
		$groupChatId = strip_tags(trim($_POST['groupChatId']));
		
		// Update privateChat table acknowledging chat deactivation
    	$table = "privateGroupChat";
		$cols = array("status");
		$where1 = array("id", "idUserCreator");
		$where2 = array($groupChatId, $_SESSION["userId"]);
		$vals = array(2);
		$dbResults = dbUpdate($table, $cols, $vals, $where1, $where2, $dbc);
		if($dbResults == "success"){
		} //else echo "error 2";
	} //else echo "error 1";
	$dbc->close();
?>