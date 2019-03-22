<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: login.php");

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
                $groupChatRequests .= 
                    '<div id = "groupChatRequest' . $privateGroupChatId . '" class = "indexSubsectionContainer">'.
                        '<p>' . $creatorUserName . 
                        ' <span style = "font-size:.66em; color:rgb(178,121,40)">has invited you to join</span> ' . 
                        $privateGroupChatChatName . '</p>' . 
                        ''.
                        '<button class = "" onclick="respondGroupChatRequest(\'' . '1' .'\', \'' .
                            $privateGroupChatId  . '\', \'' . $privateGroupChatRequestId  .
                            '\', \'' .  $creatorPublicKey. '\')">' .  
                            'Accept' .
                        '</button>' .
                        '&nbsp;&nbsp;' . 
                        '<button class = "" onclick="respondGroupChatRequest(\'' . '0' .'\', \'' .
                            $privateGroupChatId  . '\', \'' . $privateGroupChatRequestId  .
                            '\', \'' .  $creatorPublicKey. '\')">' .  
                            'Decline' .
                        '</button>' .
                    '</div>
                    <div class = "rowSeparator"></div>'
                ;
            }
        }
    $newGroupChatRequests->close();

    // Echo contaner withh all associated chat requests
    // Remove last line break from string for uniformity
    echo substr($groupChatRequests, 0, -strlen('<div class = "rowSeparator"></div>'));
?>