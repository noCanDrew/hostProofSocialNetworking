<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
	include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");

    // If the chatId is not set, return user to index.
    // Otherwise, clean the chatId and set a variable for it.
	if(!empty($_GET["groupChatId"])){
		$groupChatId = strip_tags(trim($_GET['groupChatId']));
	} else header("Location: https://collaber.org/harpocrates");

	// Check if user has permission to view this chat.
	// If they do, get their encryptedSecretKey for this chat.
    // If they do not, return user to index.
    $encryptedSecretKey = $_SESSION["accessibleChats"][$groupChatId];
    if($encryptedSecretKey == null)
    	header("Location: https://collaber.org/harpocrates");
?><!DOCTYPE HTML>

<html>
    <head>
        <title> Chat </title>

        <meta name="viewport" content="width=device-width, initial-scale=1">

    	<?php 
            // Set the aesSessionKey to be used in decrypting the locally stored, aes 
            // encrypted, rsa seed which is used for the rsa related functions. This 
            // decryption function can be found in harpocrates.js.
            echo '<script> aesSessionKey = "' . $_SESSION["aesSessionKey"] . '"; </script>';
        ?>
    	
    	<!-- https://github.com/wwwtyro/cryptico/blob/master/README.md -->
    	<!-- https://github.com/ricmoo/aes-js -->
        <script src="library/jsbn.js"></script>
        <script src="library/random.js"></script>
        <script src="library/hash.js"></script>
        <script src="library/rsa.js"></script>
        <script src="library/aes.js"></script>
        <script src="library/api.js"></script>
        <script src="library/aesRicemoo.js"></script>
        <script src="library/detectMobile.js"></script>

        <script src="library/harpocrates.js"></script>
        <link rel="stylesheet" type="text/css" href="css/chat.css">
        <script>
            // Set the chatId and chatAesKey variables to be utilized by functions in 
            // harpocrates.js
        	groupChatId = "<?php echo $groupChatId; ?>";
        	groupChatKeys = rsaDecrypt("<?php echo $encryptedSecretKey; ?>");

            // Get key component for each user in chat
            groupChatUsers = [];
            groupChatKeyMap = {};
            groupChatKeysArr = groupChatKeys.split(",");
            for(a = 0; a < groupChatKeysArr.length; a++){
                tmp = groupChatKeysArr[a].split(":");
                groupChatKeyMap[tmp[0]] = tmp[1];
                groupChatUsers[a] = tmp[0];
            }

            // Xor all the key compnents together to get chat's common seed
            keyXor = "xxxxxxxxxxxxxxxx";
            for(var k in groupChatKeyMap){
                keyXor = charLevelXor(keyXor, groupChatKeyMap[k]);
            }
            
            // Generate each user's unique ratchet
            groupChatRatchets = {};
            for(a = 0; a < groupChatUsers.length; a++){
                groupChatRatchets[groupChatUsers[a]] = new dRatchet(
                    keyXor, 
                    charLevelXor(keyXor, groupChatKeyMap[groupChatUsers[a]])
                );
            }

            // userId is defined in harpocrates.js
            // Its the userId of the acting client
            postingRatchet = new dRatchet(
                keyXor, 
                charLevelXor(keyXor, groupChatKeyMap[userId])
            );
        </script>
    </head>
    
    <body onload="updateGroupMessages(); buildEmotesList(); buildStickerList()">
        <div id = "messagesContainer" class = "messagesContainer">
            <div id = "output"></div>
            <div id = "tmpMessages"></div>
        </div>

        <div id = "userInputContainer" class = "userInputContainer">
            <div class = "messageBoxButtons">
                <button class = "button" id = "messageSubmit" onclick="postGroupMessage()">Submit</button>
                <button class = "button" onclick="displayEmotes()">Emoji</button>
                <button class = "button" onclick="displayStickers()">Stickers</button>
                <div id = "emotesList" class = "emotesList"></div>
                <div id = "stickerList" class = "emotesList"></div>
            </div>
            <div class = "messageBoxContainer">
                <textarea 
                    id = "messageBox" 
                    class = "messageBox" 
                    maxlength = "255" 
                    placeholder="Message..."
                ></textarea>
            </div>
        </div>
    </body>
    <script> if(mobileCheck()) document.body.style.fontSize = "1em";</script>
</html>