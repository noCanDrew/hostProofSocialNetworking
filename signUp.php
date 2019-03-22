<?php
	include "library/sessionStart.php";
	if(isset($_SESSION["aesSessionKey"])) 
        header("Location: index.php");
?><!DOCTYPE HTML>

<html>
    <head>
        <meta charset="UTF-8">
        <meta name="theme-color" content="rgb(45,45,45)">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
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
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

        <script src="library/alerts.js"></script>
        <script src="library/removeTags.js"></script>
        <script src="library/betterHash.js"></script>
        <link rel="stylesheet" type="text/css" href="css/index.css">
    </head>
    
    <body>
    	<script>
        	function generate(){
        		userName = document.getElementById('userName').value;
        		userPassword = document.getElementById('userPassword').value;
        		userPasswordCheck = document.getElementById('userPasswordCheck').value;

        		if(userPassword == userPasswordCheck && userName.length > 0 && userName.length <= 16){
                    ui_closeAlert();
                    ui_displayAlert(["Generating new account keys."], false, "loading");

        			// Hash userPassword
        			// Must store result(ish) on server
        			salt = randStr(16);
        			userPasswordHash = betterHash(userPassword, salt);

        			// Generate a new deterministic RSA Key using random seed
        			// Must store new public key on server
        			randSeed = randStr(128);
        			newRSAkey = cryptico.generateRSAKey(randSeed, 2048);
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
					var params = 'name=' + userName.toLowerCase() + 
								 '&salt=' + salt + 
								 '&hashword=' + userPasswordHash + 
								 '&publicKey=' + newPublicKeyString + 
								 '&encryptedRsaSeed=' + encryptedHex;
					http.open('POST', url, true);
					http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					http.onreadystatechange = function(){
					    if(http.readyState == 4 && http.status == 200){
					        if(http.responseText == "success"){
                                ui_closeAlert();
                                ui_displayAlert(["New account established! You may now sign in to your new account."], true, "notice");
                            } else {
                                ui_closeAlert();
                                ui_displayAlert([removeTags(http.responseText)], true, "notice");
                            }
					    }
					}
					http.send(params);
        		} else {
                    ui_closeAlert();
                    ui_displayAlert(["Passwords entered were not the same or username was invalid."], true, "notice");
                }
        	}
        </script>

        <div class = "indexSectionContainerFixed shadow">
            <div class = "indexSectionContainerHeader">
                <i class="fa fa-user-plus" aria-hidden="true"></i>
                &nbsp;Sign Up
                <div style = "float: right"> 
                    <a onclick="ui_displayAlert(signUpAlert, true, '')"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class = "indexSubsectionContainer">
                <input type="text" class = "inputField" placeholder="Username" maxlength = "16" id="userName" autocomplete="off" required>
            </div>
            <div class = "indexSubsectionContainer">
                <input type="password" class = "inputField" placeholder="Password" minlength = "16" maxlength = "16" id="userPassword" required>
            </div>
            <div class = "indexSubsectionContainer">
                <input type="password" class = "inputField" placeholder="Retype Password" minlength = "16" maxlength = "16" id="userPasswordCheck" required>
            </div>
            <div class = "indexSubsectionContainer">
                <button onclick="generate()">
                    Sign up
                </button>
                &nbsp; &nbsp; 
                <a href = "login.php"> Sign in </a>
            </div>
        </div>
    </body>
</html>