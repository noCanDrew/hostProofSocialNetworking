<?php
	include "library/sessionStart.php";
	if(isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates");
?><!DOCTYPE HTML>

<html>
    <head>
    	<!-- https://github.com/wwwtyro/cryptico/blob/master/README.md -->
    	<!-- https://github.com/ricmoo/aes-js -->
        <script src="library/jsbn.js"></script>
        <script src="library/random.js"></script>
        <script src="library/hash.js"></script>
        <script src="library/rsa.js"></script>
        <script src="library/aes.js"></script>
        <script src="library/api.js"></script>
        <script src="library/aesRicemoo.js"></script>
        <script src="library/javascriptRandomStringGenerator.js"></script>
    </head>
    
    <body>
    	<script>
        	function generate(){
        		userName = document.getElementById('userName').value;
        		userPassword = document.getElementById('userPassword').value;
        		userPasswordCheck = document.getElementById('userPasswordCheck').value;

        		if(userPassword == userPasswordCheck && userName.length > 0){
        			// Salt and hash userPassword
        			// Must store result on server
        			salt = randStr(16);
        			userPasswordHash = SHA256(userPassword + salt);

        			// Generate a new deterministic RSA Key using random seed
        			// Must store new public key on server
        			randSeed = randStr(128);
        			newRSAkey = cryptico.generateRSAKey(randSeed, 1024);
        			newPublicKeyString = cryptico.publicKeyString(newRSAkey);

        			// Used ctr example from https://github.com/ricmoo/aes-js
        			// Encrypt randSeed using AES with userPassword as key
        			// Must store encrypted randSeed on server
					var utf8 = unescape(encodeURIComponent(userPassword));
					var key = [];
					for(var i = 0; i < utf8.length; i++){
					    key.push(utf8.charCodeAt(i));
					}
					var textBytes = aesjs.utils.utf8.toBytes(randSeed);
					var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5));
					var encryptedBytes = aesCtr.encrypt(textBytes);
					var encryptedHex = aesjs.utils.hex.fromBytes(encryptedBytes);

					// Send name, salt, hashword, publickey, and encryptedSeed to server 
                    // for storage
					var http = new XMLHttpRequest();
					var url = 'submitSignUp.php';
					var params = 'name=' + userName + 
								 '&salt=' + salt + 
								 '&hashword=' + userPasswordHash + 
								 '&publicKey=' + newPublicKeyString + 
								 '&encryptedRsaSeed=' + encryptedHex;
					http.open('POST', url, true);
					http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					http.onreadystatechange = function() {
					    if(http.readyState == 4 && http.status == 200) {
					        alert(http.responseText);
					    }
					}
					http.send(params);
        		}
        	}
        </script>

        <input type="text" placeholder="Username" class = "" maxlength = "16" id="userName" required>
        <br>
        <input type="password" placeholder="Password" class = "" minlength = "16" maxlength = "16" id="userPassword" required>
        <br>
        <input type="password" placeholder="Retype Password" class = "" minlength = "16" maxlength = "16" id="userPasswordCheck" required>
        <br>
        <button onclick="generate()">
            Sign Up
        </button>

        <div id="output"></div>

    </body>
</html>