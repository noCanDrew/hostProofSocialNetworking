<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: login.php");

	if(!empty($_POST["ack"]) &&
		!empty($_POST["chatId"]) &&
		!empty($_POST["requestId"])){
		
		$ack = strip_tags(trim($_POST['ack']));
		$chatId = strip_tags(trim($_POST['chatId']));
		$requestId = strip_tags(trim($_POST['requestId']));
		if($ack != "no thanks") $ack = str_replace(" ","+", $ack);
		
		// Update privateChat table acknowledging chat establishment
    	$table = "privateGroupChatRequest";
		$cols = array("acknowledge");
		$where1 = array("id");
		$where2 = array($requestId);
		$vals = array($ack);
		$dbResults = dbUpdate($table, $cols, $vals, $where1, $where2, $dbc);
		if($dbResults == "success"){
			echo "Your respnse has been sent. The chat will be made once the chat's creator preforms final acknowledgement.";
		} else echo "error 2";
	} else echo "error 1";
	$dbc->close();
?>
