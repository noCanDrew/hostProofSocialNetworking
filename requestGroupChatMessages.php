<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: https://collaber.org/harpocrates/login.php");	
	else{
		if(!empty($_POST["groupChatId"])){
			$userId = $_SESSION["userId"];
			$groupChatId = strip_tags(trim($_POST['groupChatId']));
			
		    // User has permission to view this chat
		    if($_SESSION["accessibleChats"][$groupChatId] != null){ 
				// Retrieve all messages associated with chat and who posted them.
				// Need to include authentication in this query so no results are returned
				// if the user does not have access to this specific chat (not member of group)
		    	$table = "user,privateGroupMessage";
			    $cols = array(
			    	"user.id",
			    	"user.userName", 
			    	"privateGroupMessage.id", 
			    	"privateGroupMessage.time_stamp",
			    	"privateGroupMessage.encryptedMessage", 
			    	"privateGroupMessage.initializationVector"
			    );
			    $where1 = array("privateGroupMessage.idPrivateGroupChat");
			    $where2 = array($groupChatId);
			    $limit = "";
			    $orderBy = "privateGroupMessage.id";
			    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

				$chatOutputArray = array();
				foreach($dbResults as $message){
					array_push(
						$chatOutputArray, 
						array(
							"userId" => $message[0],
							"userName" => $message[1],
							"messageId" => $message[2],
							"timeStamp" => $message[3],
							"encryptedMessage" => $message[4],
							"iv" => $message[5],
						)
					);

					// Update the most recent message posted var for future checking of updates
					$_SESSION["newestMessageTime"] = "" . $message[3];
				}
				echo json_encode($chatOutputArray);
			} else var_dump(http_response_code(403));
		} else var_dump(http_response_code(403));
		$dbc->close();	
	}
?>