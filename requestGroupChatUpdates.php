<?php
	// Set up event stream to push updates to client of 5 second intervals
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache');

	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: https://collaber.org/harpocrates/login.php");	
	else{
		if(!empty($_GET["groupChatId"])){
			$userId = $_SESSION["userId"];
			$groupChatId = strip_tags(trim($_GET['groupChatId']));

			// Established backoff variable to slow polling rate if chat is inactive
			// set in setExponentialBackoffByChatId.php.
			// Sleep the query so it is delayed by this variable. This is to prevent
			// over querying in the event a chat is inactive but users have it open.
			if(isset($_SESSION[$groupChatId . "exponentialBackff"])){
				sleep($_SESSION[$groupChatId . "exponentialBackff"]);
				
			    // User has permission to view this chat
			    if($_SESSION["accessibleChats"][$groupChatId] != null){ 
			    	$newMessages = $dbc->prepare($newMessages = "SELECT 
					        user.id,
					    	user.userName, 
					        privateGroupMessage.id, 
					    	privateGroupMessage.time_stamp, 
					    	privateGroupMessage.encryptedMessage, 
					    	privateGroupMessage.initializationVector
				         FROM 
				            privateGroupMessage, user
				         WHERE 
				            privateGroupMessage.idPrivateGroupChat = ? 
				            AND 
				            user.id = privateGroupMessage.idUser 
				            AND 
				            privateGroupMessage.time_stamp 
				            	> 
				            STR_TO_DATE(?, '%Y-%m-%d %H:%i:%s')
				         ORDER BY 
				         	privateGroupMessage.id
				    ");
			        $newMessages->bind_param("is", 
			        	$groupChatId, 
			        	$_SESSION["newestMessageTime"]
			        );
			        $newMessages->execute();
			        $newMessages->bind_result(
			            $userUserId,
				    	$userUserName, 
		    			$privateMessageId, 
				    	$privateMessageTime_stamp, 
				    	$privateMessageEncryptedMessage, 
				    	$privateMessageInitializationVector
			        );

					$dbResults = array();
			        if($newMessages){  
			            while($newMessages->fetch()){
			            	array_push(
			            		$dbResults, 
			            		array(
			            			"userId" => $userUserId,
							    	"userName" => $userUserName, 
			            			"messageId" => $privateMessageId, 
							    	"timeStamp" => $privateMessageTime_stamp, 
							    	"encryptedMessage" => $privateMessageEncryptedMessage, 
							    	"iv" => $privateMessageInitializationVector
			            		)
			            	); 
			            	$_SESSION["newestMessageTime"] = $privateMessageTime_stamp;
			            }
			        }
				    $newMessages->close();

				    if(count($dbResults) > 0){
				    	//$_SESSION[$groupChatId . "exponentialBackff"] = 5;
						echo "data: " . json_encode($dbResults) . "\n\n";
				    } else {
				    	/*if($_SESSION[$groupChatId . "exponentialBackff"] < 300){
				    		$_SESSION[$groupChatId . "exponentialBackff"] = pow($_SESSION[$groupChatId . "exponentialBackff"], 1.1);
				    	} *///else header("Location: https://collaber.org/harpocrates");	
				    	var_dump(http_response_code(200));
				    }
					flush();
					ob_flush();
				} else {
					$dbc->close();
					header("Location: https://collaber.org/harpocrates");	
				}
			} else {
				$dbc->close();
				header("Location: https://collaber.org/harpocrates");	
			}
		} else {
			$dbc->close();
			header("Location: https://collaber.org/harpocrates");	
		}	
	}
	$dbc->close();	
?>