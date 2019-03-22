<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: login.php");

	if(!empty($_POST["groupChatId"]) &&
	   !empty($_POST["iv"]) &&
	   !empty($_POST["aesEncryptedMessage"]) &&
	   strlen($_POST["aesEncryptedMessage"]) < 1025){
	   	$userId = $_SESSION["userId"];
		$iv = strip_tags(trim($_POST['iv']));
		$groupChatId = strip_tags(trim($_POST['groupChatId']));
		$aesEncryptedMessage = strip_tags(trim($_POST['aesEncryptedMessage']));

		// Check access control to make sure user is posting to a chat legally
	    if($_SESSION["accessibleChats"][$groupChatId] != null){ 
	    	// Insert message into DB that user posted 	    	
	    	$table = "privateGroupMessage";
			$cols = array(
				"idUser", 
				"idPrivateGroupChat", 
				"initializationVector", 
				"encryptedMessage"
			);
			$vals = array($userId, $groupChatId, $iv, $aesEncryptedMessage);
			$dbResult = dbInsert($table, $cols, $vals, $dbc);
			echo $dbResult;
	    } else echo "error 2";
	} else echo "error 1";
	$dbc->close();
?>