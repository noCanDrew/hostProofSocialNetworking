<?php
	include "library/sessionStart.php";
	if(!isset($_SESSION["aesSessionKey"])) var_dump(http_response_code(403));	
	else{
		// Include the database interface file
		// If the newest message sen by requester is not yet set, assume a date from well
		// before this site ever went live to ensure all messages are retrieved. 
		include "library/dbInterface.php";
		if(!isset($_SESSION["newestMessageTime"])) 
			$_SESSION["newestMessageTime"] = "2000-01-20 04:00:44";

		// Check if polling is being abused by user
		// Hard coded value 5 seconds with corresponding hard coded value 10 seconds in 
		// updateMessages() in harpocrates.js. If polling is occuring more often than once 
		// per 5 seconds, the client is abusing polling
		$time = intval(time());
		$test = $time - $_SESSION["time"];
		if($test > 5){
			$_SESSION["time"] = $time;

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
			    $dbResults = dbSelect(
			    	$table, $cols, $where1, $where2, $limit, $orderBy, $dbc
			    );

			    // User has permission to view this chat
			    if(count($dbResults) > 0){ 
			    	$dbResults = array();

					// Retrieve all messages associated with chat and who posted them.
					// Sql is not my strong suit. There is probably a better way to do
					// this and also incorporate sorting by datetime... and probably
					// even drag the authentication query above into this single query
					// ... but this is just a proof of concept
				    $newMessages = $dbc->prepare($newMessages = "SELECT 
					        privateMessage.id, 
					    	user.userName, 
					    	privateMessage.initializationVector, 
					    	privateMessage.encryptedMessage, 
					    	privateMessage.time_stamp
				         FROM 
				            privateMessage, user
				         WHERE 
				            privateMessage.idPrivateChat = ? 
				            AND 
				            user.id = privateMessage.idUser 
				            AND 
				            privateMessage.time_stamp 
				            	> 
				            STR_TO_DATE(?, '%Y-%m-%d %H:%i:%s')
				         
				         UNION ALL 

				         SELECT 
					        privateMessageImage.id, 
					    	user.userName, 
					    	privateMessageImage.initializationVector, 
					    	privateMessageImage.encryptedMessage, 
					    	privateMessageImage.time_stamp
				         FROM 
				            privateMessageImage, user
				         WHERE 
				            privateMessageImage.idPrivateChat = ? 
				            AND 
				            user.id = privateMessageImage.idUser 
				            AND 
				            privateMessageImage.time_stamp 
				            	> 
				            STR_TO_DATE(?, '%Y-%m-%d %H:%i:%s')

				        ");
				        $newMessages->bind_param("isis", 
				        	$chatId, 
				        	$_SESSION["newestMessageTime"], 
				        	$chatId, 
				        	$_SESSION["newestMessageTime"]
				        );
				        $newMessages->execute();
				        $newMessages->bind_result(
				            $privateMessageId, 
					    	$userUserName, 
					    	$privateMessageInitializationVector, 
					    	$privateMessageEncryptedMessage, 
					    	$privateMessageTime_stamp
				        );
				        if($newMessages){  
				            while($newMessages->fetch()){
				            	array_push(
				            		$dbResults, 
				            		array(
				            			$privateMessageId, 
								    	$userUserName, 
								    	$privateMessageInitializationVector, 
								    	$privateMessageEncryptedMessage, 
								    	$privateMessageTime_stamp
				            		)
				            	); 
				            }
				        }
				    $newMessages->close();

				    if(count($dbResults) > 0){
				    	// Sort the results of the query...
				    	// I dont know how to sort by datetime in query on a join
				    	// union such that the rows are interlaced between the
				    	// two queried tables... ya, i know
				    	foreach ($dbResults as $key => $post) {
							$timestamps[$key] = $post[4];
						}
						array_multisort($timestamps, SORT_ASC, $dbResults);

						$chatOutputArray = "";
						foreach($dbResults as $message){
							// The return value $chatOutputArray is essentially a 2d 
							// array where commas (,) segment coulmns and veretical
							// bars (|) segment rows. 
							$chatOutputArray .= $message[1] . '|' . $message[3] . 
							'|' . $message[4] . '|' . $message[2] . ',';

							// Update the most recent message posted var for future 
							// checking of updates
							$_SESSION["newestMessageTime"] = "" . $message[4];
						}
						echo $chatOutputArray;
				    } else var_dump(http_response_code(304));
				} else var_dump(http_response_code(403));
			} else var_dump(http_response_code(403));
		} else var_dump(http_response_code(403));	
		$dbc->close();
	}
?>