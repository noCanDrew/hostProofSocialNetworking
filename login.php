<?php

/*
	The theory:
	- Generate a random AES key when user logs in. 
	- Storing the rsaSeed locally, encrypted through AES and then stroing the aesKey as
	a session variable disallows the server from ever seeing the unencrypted rsa seed.
	- This also makes it difficult for some malicious actor to retrieve the unencrypted 
	seed from the cleint.
	- This also means the cleint does not need to store the user's real plaintext 
	password locally in order to decrypt the seed on every page they visit which would 
	also be vulnerable. And this also does not require the storage of their password 
	server side which would enable the server to decrypt the rsaSeed. 
	- The returned encryptedRsaSeed client gets from server is decrypted one time on
	initial login (on the client side) using the users actual password (which the server
	never sees). This unencrypted seed is then re-encrypted with some throw away AES
	key and that key is now stored on the server to be retrieved whenever the client 
	needs it. Note, the server has the key but does not have the encryptedRsaSeed the
	key decrypts. So the server still cant reconstruct the user's seed.  
*/


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

	// Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
	include "library/sessionStart.php";
    include "library/dbInterface.php";
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
        <script src="library/detectMobile.js"></script>
        <script src="library/javascriptRandomStringGenerator.js"></script>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

        <script src="library/alerts.js"></script>
        <script src="library/betterHash.js"></script>
        <link rel="stylesheet" type="text/css" href="css/index.css">
    </head>
    
    <body>
    	<script>
    		function serializeRSAKey(key) {
			    return JSON.stringify({
					coeff: key.coeff.toString(16),
					d: key.d.toString(16),
					dmp1: key.dmp1.toString(16),
					dmq1: key.dmq1.toString(16),
					e: key.e.toString(16),
					n: key.n.toString(16),
					p: key.p.toString(16),
					q: key.q.toString(16)
			    });
			}

    		function login(){
    			var aesSessionKey = randStr(16);
				var userName = document.getElementById('userName').value.toLowerCase();
        		var userPassword = document.getElementById('userPassword').value;

        		if(userPassword.length == 16 && userName.length > 0){
        			hashword = "";
        			ui_displayAlert(["Fetching login context."], false, "loading");


        			http = new XMLHttpRequest();
					url = 'requestUserPasswordSalt.php';
					params = 'name=' + userName;
					http.open('POST', url, true);
					http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					http.onreadystatechange = function() {
					    if(http.readyState == 4 && http.status == 200){
					        salt = http.responseText;
					        ui_closeAlert();
					        if(salt == "error")
					        	ui_displayAlert(["Error: invalid password."], true, "");
					        else{
					        	ui_displayAlert(["Validating login.", "Building encryption state."], false, "loading");

					        	// Hash password prior to sending it to server so that server never sees the plaintext
			        			// password of the user. Note, additional salt+hashing is done server side for reasons
			        			// explained in submitLogin.php.
					        	hashword = betterHash(userPassword, salt);

								http2 = new XMLHttpRequest();
								url2 = 'submitLogin.php';
								params2 = 'name=' + userName + 
										 '&hashword=' + hashword + 
										 '&salt=' + salt;
								http2.open('POST', url2, true);
								http2.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
								http2.onreadystatechange = function(){
								    if(http2.readyState == 4 && http2.status == 200){
								        result = http2.responseText;
								        ui_closeAlert();
								        
										// If user has been verified...
										if(result.length > 0 && result != "error"){
											ui_displayAlert(["Establishing encryption state."], false, "loading");

											results = result.split(";");
											encryptedRsaSeed = results[0];
											publicKeyReturned = results[1];
											userId = results[2];
											
											// Decrypt rsa seed using userPassword
											var utf8 = unescape(encodeURIComponent(userPassword));
											var key = [];
											for(var i = 0; i < utf8.length; i++){
											    key.push(utf8.charCodeAt(i));
											}

											try{
												cont = true;
												var encryptedBytes = aesjs.utils.hex.toBytes(encryptedRsaSeed);
												var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5));
												var decryptedBytes = aesCtr.decrypt(encryptedBytes);
												var seed = aesjs.utils.utf8.fromBytes(decryptedBytes);

												// Using seed, generate RSA keys
												newRSAkey = cryptico.generateRSAKey(seed, 2048);
					        					newPublicKeyString = cryptico.publicKeyString(newRSAkey);

					        					serialRsaKey = serializeRSAKey(newRSAkey);

											} catch(e){
												cont = false;
												newPublicKeyString = "";
												ui_closeAlert();
				        						ui_displayAlert(["Error: Wrong username/password combination."], true, "");
											}

				        					// If valid return...
				        					if(newPublicKeyString == publicKeyReturned){
				        						ui_closeAlert();
				        						ui_displayAlert(["Establishing account login state."], false, "loading");

				        						// Create random session key and send it to server for server side storage
				        						// Note, the server does not have the thing the key decrypts because the
				        						// server is to remain untrusted.
				        						// On every page load post login, this key is to be insterted into the
				        						// javascript of the page so that the client can use it to decrypt the
				        						// the locally stored encryptedSeed.
				        						var http3 = new XMLHttpRequest();
												var url3 = 'setAesSessionKey.php';
												var params3 = 'aesSessionKey=' + aesSessionKey;
												http3.open('POST', url3, true);
												http3.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
												http3.onreadystatechange = function() {
												    if(http3.readyState == 4 && http3.status == 200){
												        // Encrypt seed with AES using session key
						        						var utf8 = unescape(encodeURIComponent(aesSessionKey));
														var key = [];
														for(var i = 0; i < utf8.length; i++){
														    key.push(utf8.charCodeAt(i));
														}
														/*var textBytes = aesjs.utils.utf8.toBytes(seed);
														var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5)); 
														var encryptedBytes = aesCtr.encrypt(textBytes);
														var encryptedSeed = aesjs.utils.hex.fromBytes(encryptedBytes);*/

														var textBytes = aesjs.utils.utf8.toBytes(serialRsaKey);
														var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5)); 
														var encryptedBytes = aesCtr.encrypt(textBytes);
														var encryptedSerialRsaKey = aesjs.utils.hex.fromBytes(encryptedBytes);
														//encryptedSerialRsaKey = serialRsaKey;


														// Store name, public key, and encrypted seed in client's local storage
														window.localStorage.setItem("userId", userId);
														window.localStorage.setItem("userName", userName.toLowerCase());
														window.localStorage.setItem("publicKey", newPublicKeyString);
														//window.localStorage.setItem("encryptedSeed", encryptedSeed);
														window.localStorage.setItem("encryptedSerialRsaKey", encryptedSerialRsaKey);
														
														location.href = "index.php";
												    }
												} 
												http3.send(params3);
				        					} else if(cont){
				        						ui_closeAlert();
				        						ui_displayAlert(["Error: Server connectivity/storage issue. Please try again later."], true, "");
											}
										} else {
											ui_closeAlert();
											ui_displayAlert(["Error: Wrong username/password combination."], true, "");
								    	}
								    } 
								} 
								http2.send(params2);
					        }
					    } 
					}
					http.send(params);    			
        		} else {
        			ui_closeAlert();
        			ui_displayAlert(["Error: invalid password."], true, "");
        		}
        	}
        </script>

        <div class = "indexSectionContainerFixed shadow">
            <div class = "indexSectionContainerHeader">
                <i class="fa fa-sign-in" aria-hidden="true"></i>
                &nbsp;Sign in
                <div style = "float: right"> 
                    <a onclick="ui_displayAlert(loginAlert, true)"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class = "indexSubsectionContainer">
            	<input type="text" class = "inputField" placeholder="Username" maxlength = "16" id="userName" autocomplete="off" required>
            </div>
            <div class = "indexSubsectionContainer">
            	<input type="password" class = "inputField" placeholder="Password" minlength = "16" maxlength = "16" id="userPassword" required>
            </div>
            <div class = "indexSubsectionContainer">
                <button onclick="login()">
                	Sign in
                </button>
                &nbsp; &nbsp; 
                <a href = "signUp.php"> Sign up </a>
            </div>
        </div>
    </body>
</html>