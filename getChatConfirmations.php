<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");



    // Get privatGroupChat's this user is reponsible for creating.
    // If all users invited have replied to invites, add javascript function call
    // to make/post keys to server.
    $activateGroupChat = "<script>";
    $groupChatConfirmations = $dbc->prepare($groupChatConfirmations = "SELECT 
            pgc.id,
            pgcr.idUserReceiver,
            pgcr.acknowledge
         FROM 
            privateGroupChat pgc, privateGroupChatRequest pgcr
         WHERE 
            pgc.idUserCreator = ? 
            AND 
            pgc.id = pgcr.idPrivateGroupChat
            AND
            pgc.status = 0
        ");
        $groupChatConfirmations->bind_param("i", $_SESSION["userId"]);
        $groupChatConfirmations->execute();
        $groupChatConfirmations->bind_result(
            $pgcId,
            $pgcrIdUserReceiver,
            $pgcrAcknowledge
        );
        
        if($groupChatConfirmations){
            // Insert each return into a hashmap $groupChats where key is groupChatId and value is the row return.
            $groupChats = array();
            while($groupChatConfirmations->fetch()){
                if(!is_array($groupChats[$pgcId])) $groupChats[$pgcId] = array();
                array_push(
                    $groupChats[$pgcId], 
                    array($pgcId, $pgcrIdUserReceiver, $pgcrAcknowledge)
                ); 
            }

            // Check if all requests have been replied to and make chat if they have.
            foreach($groupChats as $groupChat){
                $numReplies = 0;
                foreach($groupChat as $response){
                    if($response[2] != "") $numReplies++;
                    else break;
                }

                // If all reponses are accounted for, append createChat script
                // $groupChat[0][0] is the groupChatId
                if($numReplies == count($groupChat)){
                    $activateGroupChat .= "makeGroupChatKeys(" . $groupChat[0][0] . "); ";
                }
            }
        }

        $activateGroupChat .= "</script>";
    $groupChatConfirmations->close();
?>