<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: https://collaber.org/harpocrates/login.php");

	if(!empty($_POST["userIdReceiver"]) &&
		!empty($_POST["encryptedRequestMessage"])){
		$userIdSender = $_SESSION["userId"];
		$userIdReceiver = strip_tags(trim($_POST['userIdReceiver']));
		$encryptedRequestMessage = str_replace(" ","+", 
			strip_tags(trim($_POST['encryptedRequestMessage']))
		);

		if($userIdReceiver == $userIdSender) echo "Stop talking to yourself.";
		else{
			// Should update the 2 queries that check for chat existance with a single 
			// traditional query... these dbInterface ones are placeholders for now.

			// Check if these two users already have a priavte chat established
			// If they do, return "Error, chat already exists between users."
			$table = "privateChat";
		    $cols = array("id");
		    $where1 = array("idUser1", "idUser2");
		    $where2 = array($userIdSender, $userIdReceiver);
		    $limit = "1";
		    $orderBy = "";
		    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
			if(sizeof($dbResults) == 0){
				// Perform same check again but with flipped user ids. 
				$table = "privateChat";
			    $cols = array("id");
			    $where1 = array("idUser2", "idUser1");
			    $where2 = array($userIdSender, $userIdReceiver);
			    $limit = "1";
			    $orderBy = "";
			    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
			    if(sizeof($dbResults) == 0){
					// If chat hasnt been established previously, insert row into private 
					// chat table for associated users.
					$table = "privateChat";
					$cols = array("idUser1", "idUser2", "encryptedRequestMessage");
					$vals = array($userIdSender, $userIdReceiver, $encryptedRequestMessage);
					$dbResults = dbInsert($table, $cols, $vals, $dbc);
					echo "success"; //$userIdSender . "<br>" . $userIdReceiver;
			    } else echo "Error, chat already exists between users.";
			} else echo "Error, chat already exists between users.";
		}
	} else {
		echo "error";
	}
?>