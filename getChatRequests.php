<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");

    // Get chat requests
    // Join privateGroupChat with privateGroupChatRequests to get userId of chat creator
    // Join with user table to get creator's name and public key.
    // Echo each result with link to funciton respondChatRequest() with appropriate vars
    $groupChatRequests = "";
    $newGroupChatRequests = $dbc->prepare($newGroupChatRequests = "SELECT 
            pgc.id,
            pgc.idUserCreator,
            pgc.chatName,
            pgcr.id, 
            pgcr.time_stamp,
            u.userName,
            u.publicKey
         FROM 
            privateGroupChat pgc
            LEFT JOIN privateGroupChatRequest pgcr
                ON pgc.id = pgcr.idPrivateGroupChat
            LEFT JOIN user u
                ON pgc.idUserCreator = u.id
         WHERE 
            pgcr.idUserReceiver = ?
            AND
            pgcr.acknowledge = ''
        ");
        $newGroupChatRequests->bind_param("i", $_SESSION["userId"]);
        $newGroupChatRequests->execute();
        $newGroupChatRequests->bind_result(
            $privateGroupChatId,
            $privateGroupChatIdUserCreator,
            $privateGroupChatChatName,
            $privateGroupChatRequestId, 
            $privateGroupChatRequestTime_stamp,
            $creatorUserName,
            $creatorPublicKey
        );
        if($newGroupChatRequests){  
            while($newGroupChatRequests->fetch()){
                $groupChatRequests .= '
                    <div id = "groupChatRequest' . $privateGroupChatId . '">' . 
                    $creatorUserName . " has invited you to join " . 
                    $privateGroupChatChatName . '.<br>' . 
                    '<a class = "" onclick="respondGroupChatRequest(\'' . '1' .'\', \'' .
                     $privateGroupChatId  . '\', \'' . $privateGroupChatRequestId  .
                      '\', \'' .  $creatorPublicKey. '\')">' .  
                            'Accept' .
                        "</a> | " .
                    '<a class = "" onclick="respondGroupChatRequest(\'' . '0' .'\', \'' .
                     $privateGroupChatId  . '\', \'' . $privateGroupChatRequestId  .
                      '\', \'' .  $creatorPublicKey. '\')">' .  
                            'Decline' .
                        "</a><br><br></div>"
                ;
            }
        }
    $newGroupChatRequests->close();

    // Echo contaner withh all associated chat requests
    echo '<div id = "groupChatRequests" class = "indexSectionContainer">
            Group Chat Requests:<br>' .  $groupChatRequests . '</div>';
?>