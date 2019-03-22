<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	if(!isset($_SESSION["aesSessionKey"])) 
		header("Location: login.php");	
	else{
		if(!empty($_POST["groupChatId"])){
			$groupChatId = strip_tags(trim($_POST['groupChatId']));
			
			// Get groupChatRequest responses from given groupChatId.
			// Also get the responders' public key.
		    $groupChatConfirmations = $dbc->prepare($groupChatConfirmations = "SELECT 
		            pgc.id,
		            pgcr.idUserReceiver,
		            pgcr.acknowledge,
		            u.publicKey
		         FROM 
		            privateGroupChat pgc
		            LEFT JOIN privateGroupChatRequest pgcr
		            	ON pgc.id = pgcr.idPrivateGroupChat
		            LEFT JOIN user u
		            	ON pgcr.idUserReceiver = u.id
		         WHERE 
		            pgc.idUserCreator = ? AND  pgc.id = ?
		        ");
		        $groupChatConfirmations->bind_param("ii", $_SESSION["userId"], $groupChatId);
		        $groupChatConfirmations->execute();
		        $groupChatConfirmations->bind_result(
		            $pgcId,
		            $pgcrIdUserReceiver,
		            $pgcrAcknowledge,
		            $uPublicKey
		        );
		        
		        if($groupChatConfirmations){
		        	$count = 0;
		        	$groupChat = array();
		            while($groupChatConfirmations->fetch()){
		            	$count++;
		                if($pgcrAcknowledge != ""){
			                array_push(
			                	$groupChat, 
			                    array($pgcId, $pgcrIdUserReceiver, $pgcrAcknowledge, $uPublicKey)
			                ); 
			            }
		            }

		            // If every user has replied to the initial request, echo info.
		            if($count == count($groupChat)){
		            	foreach($groupChat as $response){
		            		echo $response[0] . "," . $response[1] . "," . $response[2] . "," . $response[3] . "|";
		            	}
		            } else echo "error 2";
		        }
		    $groupChatConfirmations->close();
		} else echo "error 1";
		$dbc->close();
	}
?>
