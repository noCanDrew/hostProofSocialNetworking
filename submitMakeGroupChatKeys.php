<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: login.php");

	if(!empty($_POST["encryptedBatch"])){
	   	$userId = $_SESSION["userId"];
		$encryptedBatch = str_replace(" ","+",
			strip_tags(trim($_POST['encryptedBatch']))
		);
		
		// Seperate components from posted string
		$explodeBatch = explode("|", $encryptedBatch);
		$chatId = $explodeBatch[0];
		$encryptedKeys = $explodeBatch[1];

		// check if user is responsible for given chat id
		$table = "privateGroupChat";
	    $cols = array("id");
	    $where1 = array("id", "idUserCreator");
	    $where2 = array($chatId, $_SESSION["userId"]);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
	    if(count($dbResults) > 0){
	    	// Update privateGroupChat table for chatId: make status 1.
	    	$table = "privateGroupChat";
			$cols = array("status");
			$where1 = array("id");
			$where2 = array($chatId);
			$vals = array(1);
			$dbResults = dbUpdate($table, $cols, $vals, $where1, $where2, $dbc);
			if($dbResults == "success"){
				// For each user, insert their portion of $encrypedBatch into privateGroupChatKeys table
				$userEncryptedKeys = explode(",", $encryptedKeys);
				array_pop($userEncryptedKeys); // remove last (empty) element

				$placeholders = implode(', ', array_fill(0, count($userEncryptedKeys), "(?, ?, ?)"));
				$sql = "INSERT IGNORE INTO privateGroupChatKeys (idUserOwner, " .
					"encryptedSecretKey, idPrivateGroupChat) VALUES $placeholders";

		    	$stmt = $dbc->prepare($sql);
				$type = str_repeat('isi', count($userEncryptedKeys));
				foreach($userEncryptedKeys as $tuple){
					$tmp = explode(":", $tuple);
				    $values[] = $tmp[0];
				    $values[] = $tmp[1];
				    $values[] = $chatId;
				}
				foreach($values as $key => $value){
				    $value_references[$key] = &$values[$key];
				}
				call_user_func_array(
					'mysqli_stmt_bind_param', 
				    array_merge(array($stmt, $type), $value_references)
				);
				$stmt->execute(); 
				$stmt->close();

				// Should include check to verify all entries were succesful...
			} else echo "error 1";
	    } else echo "error 2";
	} else echo "error 3";
	$dbc->close();
?>