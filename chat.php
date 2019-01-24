<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
	include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");

    // If the chatId is not set, return user to index.
    // Otherwise, clean the chatId and set a variable for it.
	if(!empty($_GET["chatId"])){
		$chatId = strip_tags(trim($_GET['chatId']));
	} else header("Location: https://collaber.org/harpocrates");

	// Check if user has permission to view this chat.
	// If they do, get their encryptedSecretKey for this chat.
    // If they do not, return user to index.
	$table = "privateChatKeys";
    $cols = array("encryptedSecretKey");
    $where1 = array("idUser", "idPrivateChat");
    $where2 = array($_SESSION["userId"], $chatId);
    $limit = "1";
    $orderBy = "";
    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
    if(count($dbResults) > 0){ 
    	$encryptedSecretKey = $dbResults[0][0];
    } else header("Location: https://collaber.org/harpocrates");
?><!DOCTYPE HTML>

<html>
    <head>
        <title> Chat </title>

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
        <link rel="stylesheet" type="text/css" href="css/chat.css">

        <script>
            // Set the chatId and chatAesKey variables to be utilized by functions in 
            // harpocrates.js
        	chatId = "<?php echo $chatId; ?>";
        	chatAesKey = rsaDecrypt("<?php echo $encryptedSecretKey; ?>");

            // closure for emoji display toggle
            var displayEmoteCheck = (function(){
                var check = false;
                return function(){
                    if(check) check = false; 
                    else check = true;
                    return check;
                }
            })();

            function displayEmotes(){
                if(displayEmoteCheck()) 
                    document.getElementById('emotesList').style.display = "inherit";
                else document.getElementById('emotesList').style.display = "none";
            }
        </script>
    </head>
    
    <body onload="updateMessages(); buildEmotesList(); buildStickerList()">
        <div id = "messagesContainer" class = "messagesContainer">
            <div id = "output"></div>
        </div>

        <div id = "userInputContainer" class = "userInputContainer">
            <div class = "messageBoxButtons">
                <button class = "button" onclick="postMessage()">Submit</button>
                <button class = "button" onclick="displayEmotes()">Emoji</button>
                <button class = "button" onclick="displayStickers()">Stickers</button>
                <!--<input type="file" onchange="postImage(this)"/>-->
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
</html>