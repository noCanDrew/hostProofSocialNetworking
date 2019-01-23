<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";

	if(!empty($_POST["chatId"]) &&
	   !empty($_POST["iv"]) &&
	   !empty($_POST["aesEncryptedMessage"]) &&
	   strlen($_POST["aesEncryptedMessage"]) < 1025){
	   	$userId = $_SESSION["userId"];
		$chatId = strip_tags(trim($_POST['chatId']));
		$iv = strip_tags(trim($_POST['iv']));
		$aesEncryptedMessage = strip_tags(trim($_POST['aesEncryptedMessage']));

		// Check DB to make sure user is posting to a chat legally
		$table = "privateChatKeys";
	    $cols = array("id");
	    $where1 = array("idUser", "idPrivateChat");
	    $where2 = array($userId, $chatId);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
	    if(count($dbResults) > 0){
	    	// Insert message into DB that user posted 	    	
	    	$table = "privateMessage";
			$cols = array(
				"idPrivateChat", "idUser", "initializationVector", "encryptedMessage"
			);
			$vals = array($chatId, $userId, $iv, $aesEncryptedMessage);
			$dbResult = dbInsert($table, $cols, $vals, $dbc);
			echo $dbResult;
	    } else echo "error 2";
	} else echo "error 1";
?>