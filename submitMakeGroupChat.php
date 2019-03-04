<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: https://collaber.org/harpocrates/login.php");

	if(!empty($_POST["users"]) && !empty($_POST["chatName"])){
		$chatName = strip_tags(trim($_POST['chatName']));
		$userArr = explode("-", $_POST['users']);
		$userId = $_SESSION["userId"];

		// Make sure the number of invites is reasonable
		if(count($userArr) > 0 && count($userArr < 100)){
	    	$types = "";
			$dneArr = array();

	    	// Clean user input 
			// Remove all instances where userName is an empty string
			// Remove any instance where user is attempting to invite themselves
	    	for($a = 0; $a < count($userArr); $a++){
	    		$userArr[$a] = strip_tags(trim($userArr[$a]));
	    		if($userArr[$a] == "" || $userArr[$a] == $_SESSION["userName"]){
	    			unset($userArr[$a]);
	    			$userArr = array_values($userArr);
	    			$a--;
	    		}
	    		else $types .= "s";
	    	}

	    	// Check if each user (by name) exists and get their userId, userName
			$query = $dbc->prepare($query = "SELECT 
					id, userName
				FROM 
					user 
				WHERE
					" . stringify(array_fill(0, count($userArr), "userName"), " = ? OR ", 4) . "
				LIMIT
					100
			");
			$query->bind_param($types, ...$userArr);
			$a = 0; 
			$data = array();
			$query->execute();
			$meta = $query->result_metadata();
			while ($field = $meta->fetch_field()){ 
				$var = $a++;
				$$var = null; 
				$data[$var] = &$$var;   
			}
			call_user_func_array(array($query,'bind_result'), $data);

			// Insert each row returned from DB into hashmap ret.
			// This removes duplicates if user entered the same target user more than once.
			// The specific code below assumes the user.id is the first column of the returned row.
			// The hashmap key index is this user.id.
			$ret = array();
			$retUserNames = array();
			while ($query->fetch()){
			    $tmp = array();
			    for($a = 0; $a < count($data); $a++){ 
			    	array_push($tmp, $data[$a]);
			  	}
			  	$ret[$tmp[0]] = $tmp;
			  	array_push($retUserNames, $tmp[1]);
			} 
			$query->close();

			// Compare userArr to retUserNames to make sure each user originally targeted exists and is accounted for.
			// Record all instances of such cases where the above is not true in dneArr
			for($a = 0; $a < count($userArr); $a++){
				if(!in_array($userArr[$a], $retUserNames)) 
					array_push($dneArr, $userArr[$a]);
			}

			// Only make chat if all users who were invted actually exist
			// Otherwise, report to creator some entries were wrong/ may have been mistyped
			if(count($dneArr) == 0){
				// Create private group chat
				$table = "privateGroupChat";
				$cols = array("idUserCreator", "chatName");
				$vals = array($userId, $chatName);
				$dbResult = dbInsert($table, $cols, $vals, $dbc);
				
				if($dbResult == "success"){
					// Get chatId of private group chat established above
					$table = "privateGroupChat";
				    $cols = array("id");
				    $where1 = array("idUserCreator");
				    $where2 = array($userId);
				    $limit = "1";
				    $orderBy = "id DESC";
				    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
				    $chatId = $dbResults[0][0];

				    if(is_int($chatId)){
						// https://stackoverflow.com/questions/31573997/mysql-prepared-statement-with-bulk-insert
						// Batch inserts requests into privatGroupChatRequests table
						$placeholders = implode(', ', array_fill(0, count($ret), "(?, ?)"));
						$sql = "INSERT IGNORE INTO privateGroupChatRequest (idUserReceiver, " .
							"idPrivateGroupChat) VALUES $placeholders";

				    	$stmt = $dbc->prepare($sql);
						$type = str_repeat('ii', count($ret));
						foreach ($ret as $user) {
						    $values[] = $user[0];
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

						echo "Chat invites have been sent succesfully. ";
				    } else echo "error 1";
				} else echo "error 2";
			} else {
				echo "The following users do not exist: <br>";
				print_r($dneArr);
			}
		} else echo "error 3";
	} else echo "error 4";
	$dbc->close();
?>