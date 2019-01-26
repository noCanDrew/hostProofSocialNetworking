<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");

    // Get new chat requests
    // Generate html for display of these new contact notices
    $chatRequests = "";
    $newChatRequests = $dbc->prepare($newChatRequests = "SELECT 
            privateChat.id, 
            privateChat.idUser1, 
            privateChat.idUser2, 
            privateChat.encryptedRequestMessage, 
            privateChat.acknowledged, 
            privateChat.time_stamp, 
            user.userName,
            user.id,
            user.publicKey
         FROM 
            privateChat, user
         WHERE 
            user.id = privateChat.idUser1 AND acknowledged = 0 AND privateChat.idUser2 = ?
        ");
        $newChatRequests->bind_param("i", $_SESSION["userId"]);
        $newChatRequests->execute();
        $newChatRequests->bind_result(
            $privateChatId, 
            $privateChatIdUser1, 
            $privateChatIdUser2, 
            $privateChatEncryptedRequestMessage, 
            $privateChatAcknowledge, 
            $privateChatTime_stamp, 
            $userUserName,
            $userName,
            $userPublicKey);
        if($newChatRequests){  
            while($newChatRequests->fetch()){
                $chatRequests .=
                    '<a class = "" onclick="acceptPrivateChat(\'' . $userUserName .
                    '\', \'' . $userPublicKey . '\', \'' . 
                    $privateChatEncryptedRequestMessage . '\')">' .  
                        "New chat request from: " . 
                        $userUserName .
                    "</a><br><br>";
            }
        }
    $newChatRequests->close();

    // Get existing chats
    // Generate html for display of these chats
    $chats = "";
    $oldChats = $dbc->prepare($oldChats = "SELECT 
            id, 
            idUser1, 
            idUser2, 
            encryptedRequestMessage, 
            acknowledged, 
            time_stamp
         FROM 
            privateChat
         WHERE 
            (idUser1 = ? OR idUser2 = ?) AND acknowledged = 1
        ");
        $oldChats->bind_param("ii", $_SESSION["userId"], $_SESSION["userId"]);
        $oldChats->execute();
        $oldChats->bind_result(
            $privateChatId, 
            $privateChatIdUser1, 
            $privateChatIdUser2, 
            $privateChatEncryptedRequestMessage, 
            $privateChatAcknowledge, 
            $privateChatTime_stamp
        );
        if($oldChats){  
            while($oldChats->fetch()){
                $chats .= 
                    '<a href = "chat.php?chatId=' . $privateChatId . '">Chat: ' . 
                    $privateChatIdUser1 . " and " . $privateChatIdUser2 . "</a><br>";
            }
        }
    $oldChats->close();
?><!DOCTYPE HTML>

<html>
    <head>
        <script> 
            // Set the aesSessionKey to be used in decrypting the locally stored, aes 
            // encrypted, rsa seed which is used for the rsa related functions. This 
            // decryption function can be found in harpocrates.js.
            aesSessionKey = "<?php echo $_SESSION["aesSessionKey"]; ?>"; 
        </script>

    	<!-- https://github.com/wwwtyro/cryptico/blob/master/README.md -->
        <!-- https://github.com/ricmoo/aes-js -->
        <script src="library/jsbn.js"></script>
        <script src="library/random.js"></script>
        <script src="library/hash.js"></script>
        <script src="library/rsa.js"></script>
        <script src="library/aes.js"></script>
        <script src="library/api.js"></script>
        <script src="library/aesRicemoo.js"></script>

        <script src="library/harpocrates.js"></script>
        <link rel="stylesheet" type="text/css" href="css/index.css">
    </head>
    
    <body>
        <div class = "indexSectionContainer">
            Establish connection with user: <input type="text" id = "userReceiver"><br>
            <button onclick="establishPrivateChat()">
                Submit
            </button>
        </div>

        <div id = "output"></div>
        
        <div id = "chatRequests" class = "indexSectionContainer">
            Chat Requests:<br><?php echo $chatRequests; ?>
        </div>

        <div id = "chats" class = "indexSectionContainer">
            Chats:<br><?php echo $chats; ?>
        </div>

        <form action="submitLogout.php">
            <input type="submit" value="Logout" />
        </form>
    </body>
</html>