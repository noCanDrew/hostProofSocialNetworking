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
	- ...
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
    		function login(){
    			var aesSessionKey = randStr(16);

				userName = document.getElementById('userName').value;
        		userPassword = document.getElementById('userPassword').value;

        		if(userPassword.length == 16 && userName.length > 0){

        			// Request salt for userName's password
        			salt = "";
					http = new XMLHttpRequest();
					url = 'requestUserPasswordSalt.php';
					params = 'name=' + userName;
					http.open('POST', url, true);
					http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					http.onreadystatechange = function(){//Call a function when the state changes.
					    if(http.readyState == 4 && http.status == 200){
					        salt = http.responseText;
					        // If the user exists, salt will be equal to that user's password salt
							// Hash their password with the salt and send to server for verification
							if(salt.length == 16){
								result = "";
								userPasswordHash = SHA256(userPassword + salt);
								http2 = new XMLHttpRequest();
								url2 = 'submitLogin.php';
								params2 = 'name=' + userName + 
										 '&hashword=' + userPasswordHash;
								http2.open('POST', url2, true);
								http2.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
								http2.onreadystatechange = function() {//Call a function when the state changes.
								    if(http2.readyState == 4 && http.status == 200){
								        result = http2.responseText;
								        
										// If user has been verified...
										if(result.length > 0 && result != "error"){
											results = result.split(";");
											encryptedRsaSeed = results[0];
											publicKeyReturned = results[1];
											
											// decrypt rsa seed using userPassword
											var utf8 = unescape(encodeURIComponent(userPassword));
											var key = [];
											for(var i = 0; i < utf8.length; i++){
											    key.push(utf8.charCodeAt(i));
											}
											var encryptedBytes = aesjs.utils.hex.toBytes(encryptedRsaSeed);
											var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5));
											var decryptedBytes = aesCtr.decrypt(encryptedBytes);
											var seed = aesjs.utils.utf8.fromBytes(decryptedBytes);
											
											// using seed, generate RSA keys
											newRSAkey = cryptico.generateRSAKey(seed, 1024);
				        					newPublicKeyString = cryptico.publicKeyString(newRSAkey);
				        					
				        					// If valid return...
				        					if(newPublicKeyString == publicKeyReturned){
				        						// create random session key and send it to server for server side storage
				        						// Note, the server does not have the thing the key decrypts because the
				        						// server is to remain untrusted.
				        						// On every page load post login, this key is to be insterted into the
				        						// javascript of the page so that the client can use it to decrypt the
				        						// the locally stored encryptedSeed 
				        						var http3 = new XMLHttpRequest();
												var url3 = 'setAesSessionKey.php';
												var params3 = 'aesSessionKey=' + aesSessionKey;
												http3.open('POST', url3, true);
												http3.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
												http3.onreadystatechange = function() {//Call a function when the state changes.
												    if(http3.readyState == 4 && http3.status == 200){
												        // Encrypt seed with AES using session key
						        						var utf8 = unescape(encodeURIComponent(aesSessionKey));
														var key = [];
														for(var i = 0; i < utf8.length; i++){
														    key.push(utf8.charCodeAt(i));
														}
														var textBytes = aesjs.utils.utf8.toBytes(seed);
														var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5)); // need to randomize the counter seed and save it as well
														var encryptedBytes = aesCtr.encrypt(textBytes);
														var encryptedSeed = aesjs.utils.hex.fromBytes(encryptedBytes);

														// Store name, public key, and encrypted seed in client's local storage
														window.localStorage.setItem("userName", userName);
														window.localStorage.setItem("publicKey", newPublicKeyString);
														window.localStorage.setItem("encryptedSeed", encryptedSeed);
														
														location.href = "https://collaber.org/harpocrates";
												    }
												} 
												http3.send(params3);
				        					} else document.getElementById('output').innerHTML += "Error: Server connectivity/storage issue .<br>";
										} else document.getElementById('output').innerHTML += "Error: Wrong username/password combination.<br>";
								    } 
								} 
								http2.send(params2);
							} else document.getElementById('output').innerHTML += "Error: Return invalid.<br>";
					    } 
					} 
					http.send(params);
        		} else document.getElementById('output').innerHTML += "Error: invalid password.<br>";
        	}
        </script>

        <input type="text" placeholder="Username" class = "" maxlength = "16" id="userName" required>
        <br>
        <br>
        <input type="password" placeholder="Password" class = "" minlength = "16" maxlength = "16" id="userPassword" required>
        <br>
        <button onclick="login()">
            Login
        </button>

        <div id = "output"></div>

    </body>
</html>