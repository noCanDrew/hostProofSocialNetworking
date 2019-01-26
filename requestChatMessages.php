<?php
	include "library/sessionStart.php";
	if(!isset($_SESSION["aesSessionKey"])) var_dump(http_response_code(403));	
	else{
		include "library/dbInterface.php";

		if(!empty($_POST["chatId"])){
			$userId = $_SESSION["userId"];
			$chatId = strip_tags(trim($_POST['chatId']));
			
			// Check if user has permission to view this chat
			// If they do, get their encryptedSecretKey for this chat
			$table = "privateChatKeys";
		    $cols = array("encryptedSecretKey");
		    $where1 = array("idUser", "idPrivateChat");
		    $where2 = array($userId, $chatId);
		    $limit = "1";
		    $orderBy = "";
		    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

		    // User has permission to view this chat
		    if(count($dbResults) > 0){ 
				// Retrieve all messages associated with chat and who posted them
		    	$table = "user,privateMessage";
			    $cols = array(
			    	"privateMessage.id", 
			    	"user.userName", 
			    	"privateMessage.initializationVector", 
			    	"privateMessage.encryptedMessage", 
			    	"privateMessage.time_stamp"
			    );
			    $where1 = array("privateMessage.idPrivateChat");
			    $where2 = array($chatId);
			    $limit = "";
			    $orderBy = "id";
			    $messages = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

			    /*
			    // Retrieve all images associated with chat and who posted them
			    $table = "user,privateMessageImage";
			    $cols = array(
			    	"privateMessageImage.id", 
			    	"user.userName", 
			    	"privateMessageImage.initializationVector", 
			    	"privateMessageImage.encryptedMessage", 
			    	"privateMessageImage.time_stamp"
			    );
			    $where1 = array("privateMessageImage.idPrivateChat");
			    $where2 = array($chatId);
			    $limit = "";
			    $orderBy = "id";
			    $images = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
			    */

			    //$dbResults = array_merge($messages, $images);
			    $dbResults = $messages;
			    foreach ($dbResults as $key => $post) {
				   $timestamps[$key] = $post[4];
				}
				array_multisort($timestamps, SORT_ASC, $dbResults);

			    $chatOutputArray = "";
				foreach($dbResults as $message){
					$chatOutputArray .= $message[1] . '|' . $message[3] . '|' . 
						$message[4] . '|' . $message[2] . ',';
					$_SESSION["newestMessageTime"] = "" . $message[4];
				}

				echo $chatOutputArray;
			} else var_dump(http_response_code(403));
		} else var_dump(http_response_code(403));
		$dbc->close();	
	}
?>