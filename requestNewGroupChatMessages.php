<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: login.php");	
	else{
		// To prevent spamming of query, log each visit in session array.
		// Delete vists older than MAX_VISITS_INTERVAL seconds
		// If there are more than MAX_VISITS recent vists (MAX_VISITS times in under 
		// MAX_VISITS_INTERVAL seconds), return 403
		$MAX_VISITS = 5;
		$MAX_INTERVAL_TIMER = 10;
		if(empty($_SESSION["recentVists"])) $_SESSION["recentVists"] = array();
		$recentVists = array();
		$time = intval(time());
		for($a = 0; $a < count($_SESSION["recentVists"]); $a++){
			if($_SESSION["recentVists"][$a] > $time - $MAX_INTERVAL_TIMER)
				array_push($recentVists, $_SESSION["recentVists"][$a]);
		}
		array_push($recentVists, $time);
		$_SESSION["recentVists"] = $recentVists;

		if(count($recentVists) < $MAX_VISITS && !empty($_POST["groupChatId"])){
			$groupChatId = strip_tags(trim($_POST['groupChatId']));

			// If user has access to this chat
			// Retrieve all messages associated with chat and who posted them.
			// Push them to a json array and return the json formated response
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
			    if(count($dbResults) > 0) echo json_encode($dbResults);
			    else var_dump(http_response_code(304));
			} else var_dump(http_response_code(403));	
		} else var_dump(http_response_code(403));	
		$dbc->close();
	}
?>