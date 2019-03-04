<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");

    // Set session var array for user chat access control. This array is used for any future
    // chat related queries to authenticate the user submitting the request
    $_SESSION["accessibleChats"] = array();

    // Get existing groupChats
    // Generate html for display of these chats
    // Push each chatId from resulting query to the accessibleChats session var array for 
    // future access control checks.
    $chats = "";
    $oldChats = $dbc->prepare($oldChats = "SELECT 
            pgc.id, pgc.chatName, pgck.encryptedSecretKey
         FROM 
            privateGroupChat pgc
            LEFT JOIN privateGroupChatKeys pgck
                ON pgc.id = pgck.idPrivateGroupChat
         WHERE 
            pgck.idUserOwner = ?
        ");
        $oldChats->bind_param("i", $_SESSION["userId"]);
        $oldChats->execute();
        $oldChats->bind_result($pgcId,  $pgcChatName, $pgckEncryptedSecretKey);
        if($oldChats){  
            while($oldChats->fetch()){
                // Push chatId and key into session var array that can be used for chat 
                // access control when future chat related queries are performed. 
                // Build the return string to be echoed
                $_SESSION["accessibleChats"][$pgcId] = $pgckEncryptedSecretKey;
                $chats .= '
                    <a href = "groupChat.php?groupChatId=' . $pgcId . '">' . 
                    $pgcChatName . "</a><br>";
            }
        }
    $oldChats->close();

    // Echo contaner withh all associated chats
    echo '<div id = "groupChats" class = "indexSectionContainer"> 
            Group Chats:<br>' . $chats . '</div>';
?>