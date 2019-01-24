<?php
	include "library/sessionStart.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: https://collaber.org/harpocrates/login.php");

	include "library/dbInterface.php";
	include 'library/phpRandomStringGenerator.php';

	if(!empty($_POST["requesterUserName"]) &&
		!empty($_POST["requesterEncryptedSecretAesKey"]) &&
		!empty($_POST["receiverEncryptedSecretAesKey"])){
		
		// Get user id of the original requester
		$requesterUserName = strip_tags(trim($_POST['requesterUserName']));
		$table = "user";
	    $cols = array("id");
	    $where1 = array("userName");
	    $where2 = array($requesterUserName);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

	    // Set the two user ids and clean their encryptedSecretAesKeys
	    $receiverUserId = $_SESSION["userId"];
	    $requesterUserId = $dbResults[0][0];
	    $requesterEncryptedSecretAesKey = str_replace(" ","+", 
	    	strip_tags(trim($_POST['requesterEncryptedSecretAesKey']))
	    );
	    $receiverEncryptedSecretAesKey = str_replace(" ","+", 
	    	strip_tags(trim($_POST['receiverEncryptedSecretAesKey']))
	    );

		// Get the private chat associated with the two users
		$table = "privateChat";
	    $cols = array("id", "acknowledged");
	    $where1 = array("idUser1", "idUser2");
	    $where2 = array($requesterUserId, $receiverUserId);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

	    // If the acknowledged flag is still 0, chat is to be initialized
	    if($dbResults[0][0] > 0 && $dbResults[0][1] == 0){
	    	$chatId = $dbResults[0][0];
	    	
	    	// Update privateChat table acknowledging chat establishment
	    	$table = "privateChat";
			$cols = array("acknowledged");
			$where1 = array("id");
			$where2 = array($chatId);
			$vals = array(1);
			$dbResults = dbUpdate($table, $cols, $vals, $where1, $where2, $dbc);

			if($dbResults == "success"){
				// Insert into the privateChatKeys table the requester's encrypted Aes key
				$table = "privateChatKeys";
				$cols = array("idUser", "idPrivateChat", "encryptedSecretKey");
				$vals = array($requesterUserId, $chatId, $requesterEncryptedSecretAesKey);
				$dbResults1 = dbInsert($table, $cols, $vals, $dbc);

				// Insert into the privateChatKeys table the receiver's encrypted Aes key
				$table = "privateChatKeys";
				$cols = array("idUser", "idPrivateChat", "encryptedSecretKey");
				$vals = array($receiverUserId, $chatId, $receiverEncryptedSecretAesKey);
				$dbResults2 = dbInsert($table, $cols, $vals, $dbc);

				if($dbResults1 == "success" && $dbResults2 == "success"){
					echo "success";
				} else echo "error 4";
			} else echo "error 3"; 
	    } else echo "error 2";
	} else echo "error 1";
?>