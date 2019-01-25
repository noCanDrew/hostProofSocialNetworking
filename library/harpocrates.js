/*
    Welcome one and all...
*/



/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// General purpose variables.
var date = "";                  // Used to mark day separator in chat
var numUpdates = 0;             // Used for showing number of new messages in <title>
var userActive = true;          // Boolean for marking user as active/inactive
var maxMessageLength = 255;     // Max length any new message can be.  
var intervalExtension = 30;     // Multiplier for interval mod on chat update.

// User specific global variables.
var userName = removeTags(window.localStorage.userName);
var publicKey = removeTags(window.localStorage.publicKey);
var encryptedRsaSeed = removeTags(window.localStorage.encryptedSeed);

// Decrypt rsa seed using aesSessionKey (provided by given php pages).
// Using this seed, generate user's RSA keys.
var seed = aesDecrypt(encryptedRsaSeed, aesSessionKey);
var rsaKeyObject = cryptico.generateRSAKey(seed, 1024);


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Encrypt and decrypt wrappers for convenience.
// Uses the cryptico.js file in library.
function rsaEncrypt(plainText, key){
    return cryptico.encrypt(plainText, key).cipher;
}

function rsaDecrypt(cipherText){
    return cryptico.decrypt(cipherText, rsaKeyObject).plaintext;
}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Uses aesRicemoo.js in library for ARS encrypt and decrypt.
// Encrypt and decrypt using an initialization vector utilizes the cipher block 
// chaining mode of AES. 
// Messages have a high likelihood of recurrence and therefor necessitate an 
// initialization vector in order to avoid encrypted results leaking info. 
// Encrypt and decrypt without using initialization vectors are also implemented 
// below and utilize the counter mode of AES. 
// These functions should only be used for unique strings that never repeat and do not
// contain natural language readable text; such as the unique rsa seeds of users. 
function aesEncryptWithIv(text, aesKey, initializationVector){
    var utf8 = unescape(encodeURIComponent(aesKey));
    var key = [];
    for(var i = 0; i < utf8.length; i++){
        key.push(utf8.charCodeAt(i));
    }
    var utf8 = unescape(encodeURIComponent(initializationVector));
    var iv = [];
    for(var i = 0; i < utf8.length; i++){
        iv.push(utf8.charCodeAt(i));
    }
    var textBytes = aesjs.utils.utf8.toBytes(text);
    var aesCbc = new aesjs.ModeOfOperation.cbc(key, iv);
    var encryptedBytes = aesCbc.encrypt(textBytes);
    return aesjs.utils.hex.fromBytes(encryptedBytes);
}

function aesDecryptWithIv(text, aesKey, initializationVector){
    var utf8 = unescape(encodeURIComponent(aesKey));
    var key = [];
    for(i = 0; i < utf8.length; i++){
        key.push(utf8.charCodeAt(i));
    }
    var utf8 = unescape(encodeURIComponent(initializationVector));
    var iv = [];
    for(var i = 0; i < utf8.length; i++){
        iv.push(utf8.charCodeAt(i));
    }
    var encryptedBytes = aesjs.utils.hex.toBytes(text);
    var aesCbc = new aesjs.ModeOfOperation.cbc(key, iv);
    var decryptedBytes = aesCbc.decrypt(encryptedBytes);
    return aesjs.utils.utf8.fromBytes(decryptedBytes);
}

// Defaults an "iv" for the counter to 5.
function aesEncrypt(text, aesKey){
    var utf8 = unescape(encodeURIComponent(aesKey));
    var key = [];
    for(var i = 0; i < utf8.length; i++){
        key.push(utf8.charCodeAt(i));
    }
    var textBytes = aesjs.utils.utf8.toBytes(text);
    var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5));
    var encryptedBytes = aesCtr.encrypt(textBytes);
    return aesjs.utils.hex.fromBytes(encryptedBytes);
}

// Assumes a default "iv" for the counter of 5.
function aesDecrypt(text, aesKey){
    var utf8 = unescape(encodeURIComponent(aesKey));
    var key = [];
    for(i = 0; i < utf8.length; i++){
        key.push(utf8.charCodeAt(i));
    }
    var encryptedBytes = aesjs.utils.hex.toBytes(text);
    var aesCtr = new aesjs.ModeOfOperation.ctr(key, new aesjs.Counter(5));
    var decryptedBytes = aesCtr.decrypt(encryptedBytes);
    return aesjs.utils.utf8.fromBytes(decryptedBytes);
}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Pads a user typed message out to length chars in such a way as to allow easy pad 
// removal later. Messages are padded so that encrypted messages all appear the same 
// length despite the plain text of said messages varying in size.
function padMessage(text, length){
    if(text.length < length){
        return text + " " + randStr(length - 1 - text.length);
    } else return null;
}

// Depads a user message by removing random pad string, independent of text length.
// Assumes pad string is tail of text and a space (" ") seperator exists between the 
// meaningful text and the random pad string. 
function depadMessage(text){
    tmp = text.split(" ");
    return text.split(tmp[tmp.length - 1])[0].trim();
}

// https://medium.com/@dazcyril/generating-cryptographic-random-state-in-javascript-in-
// the-browser-c538b3daae50
// Generate a random string of given length from the alphabet validChars
function randStr(length){
    const validChars = 
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let array = new Uint8Array(length);
    window.crypto.getRandomValues(array);
    array = array.map(x => validChars.charCodeAt(x % validChars.length));
    const randomState = String.fromCharCode.apply(null, array);
    return randomState;
}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


emojis = [0x1F601, 0x1F602, 0x1F603, 0x1F604, 0x1F605, 0x1F606, 
        0x1F609, 0x1F60A, 0x1F60B, 0x1F60C, 0x1F60D, 0x1F60F, 0x1F612,
        0x1F613, 0x1F614, 0x1F616, 0x1F618, 0x1F61A, 0x1F61C, 0x1F61D, 
        0x1F61E, 0x1F620, 0x1F621, 0x1F622, 0x1F623, 0x1F624, 0x1F625, 
        0x1F628, 0x1F629, 0x1F62A, 0x1F62B, 0x1F62D, 0x1F630, 0x1F631, 
        0x1F632, 0x1F633, 0x1F635, 0x1F637, 0x1F638, 0x1F639, 0x1F63A, 
        0x1F63B, 0x1F63C, 0x1F63D, 0x1F63E, 0x1F63F, 0x1F640, 0x1F645, 
        0x1F646, 0x1F647, 0x1F648, 0x1F649, 0x1F64A, 0x1F64B, 0x1F64C, 
        0x1F64D, 0x1F64E, 0x1F64F
];

emotes = [":grin:", ":tears", ":smile:", ":smile-open:", ":smile-sweat:", 
    ":smile-closed-eyes:", ":wink:", ":smiile-blush:", ":delicious:", 
    ":relieved:", ":smile-heart-eyes:", ":smirk:", ":unamused:", 
    ":cold-sweat:", ":pensive:", ":confounded:", ":kiss:", ":kiss-face:", 
    ":wink-toung:", ":closed-eyes-toung:", ":disappointed:", ":angry:", 
    ":pounting:", ":crying:", ":persevering:", ":triumph:", 
    ":disappointed-tear:", ":fearful:", ":weary:", ":sleepy:", ":tired:",
    ":crying-loud:", ":cold-sweat2:", ":scream:", ":astonished:", 
    ":flushed:", ":dizzy:", ":medical-mask:", ":grin-cat:",
    ":tear-joy-cat:", ":smil-cat:", ":smile-hearts-cat:", ":smirk-cat:", 
    ":kiss-cat:", ":pout-cat:", ":cry-cat:", ":weary-cat:",
    ":face-no:", ":face-ok:", ":bowing:", ":see-no-evil:", ":hear-no-evil:", 
    ":speak-no-evil:", ":raising-hand:", ":raising-two-hands:", ":frown:", 
    ":pout:", ":folded-hands:"
];

// Given a string of text, replace all occurances of the strings defined in emotes with 
// the corisponding unicode chars defined in emojis. 
function emoteDecoder(text){
    for(var a = 0; a < emotes.length; a++){
        text = text.split(emotes[a]).join(String.fromCodePoint(emojis[a]));
    }
    return text;
}

// This function should be called on page load for chat.php. 
// Generates the clickable elements in page that allows user to add emojis to the text 
// area in chat through a GUI menu. 
function buildEmotesList(){
    for(var a = 0; a < emotes.length; a++){
        document.getElementById('emotesList').innerHTML += 
           '<a style = "cursor: pointer;" onclick = "addEmoteToTextBox(\'' + 
            emotes[a] + '\')">' + String.fromCodePoint(emojis[a]) + '</a>'; 
    }
}

// Toggles the visibility of the emoji menu in chat.
function displayEmotes(){
    if(displayEmoteCheck()){
        document.getElementById('emotesList').style.display = "block";
        if(window.getComputedStyle(document.getElementById('stickerList')).display === "block"){
            displayStickers();
        }
    } else document.getElementById('emotesList').style.display = "none";
}

// If enough remaining room exists in the text area for a valid message, adds emote code 
// to the text area in chat. 
function addEmoteToTextBox(emote){
    tmp = document.getElementById('messageBox').value;
    if(maxMessageLength - tmp.length - emote.length > 0){
        document.getElementById('messageBox').value += emote;
        displayEmotes();
    }
}

// Closure for emoji display toggle
var displayEmoteCheck = (function(){
    var check = false;
    return function(){
        if(check) check = false; 
        else check = true;
        return check;
    }
})();

sticker = ["tester"];

// Sticker messages are of the format: :sticker-_name_of_sticker
// Given a string of text, replace it with an image tag and corrisponding sticker if the
// string is formated to be an encoded sticker.
function stickerDecoder(text){
    if(/^:sticker-/.test(messageText)){
        imageSrc = text.split(":sticker-");
        return '<img ' + 
            'src = "library/stickers/' + imageSrc[1] + '.jpg"' +  
            'class = "sticker"' + 
        '>';
    } else return text;
}

function buildStickerList(){
    for(var a = 0; a < sticker.length; a++){
        document.getElementById('stickerList').innerHTML += 
           '<a style = "cursor: pointer;" onclick = "sendSticker(\'' + 
            sticker[a] + '\')"><img src="library/stickers/' + sticker[a] + '.jpg"></a>'; 
    }
}

// Toggles the visibility of the sticker menu in chat.
function displayStickers(){
    if(displayStickerCheck()){
        document.getElementById('stickerList').style.display = "block";
        if(window.getComputedStyle(document.getElementById('emotesList')).display === "block"){
            displayEmotes();
        }
    } else document.getElementById('stickerList').style.display = "none";
}

// Post "message" of the format ":sticker-_name_of_sticker" 
function sendSticker(sticker){
    iv = randStr(16);
    message = ":sticker-" + sticker;
    paddedMessage = padMessage(message, maxMessageLength + 1);
    aesEncryptedMessage = aesEncryptWithIv(paddedMessage, chatAesKey, iv);

    // Post encrypted message to server
    var http = new XMLHttpRequest();
    var url = 'submitMessage.php';
    var params = 'aesEncryptedMessage=' + aesEncryptedMessage + 
                 '&iv=' + iv +
                 '&chatId=' + chatId;           
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            if(http.responseText == "success"){
            } else document.getElementById('output').innerHTML += 
                removeTags(http.responseText);
        }
    }
    http.send(params);
    displayStickers();
}

// Closure for sticker display toggle
var displayStickerCheck = (function(){
    var check = false;
    return function(){
        if(check) check = false; 
        else check = true;
        return check;
    }
})();




/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// stackoverflow.com/questions/295566/sanitize-rewrite-html-on-the-client-side
// Removes tags from text that mnay contain HTML.
// Used as a post process for text display client side. Once client side has decrypted 
// text from server, the text is then stripped of all tags so that malicious injections 
// are prevented prior to text laoding on page.
var tagBody = '(?:[^"\'>]|"[^"]*"|\'[^\']*\')*';
var tagOrComment = new RegExp(
    '<(?:'
    // Comment body.
    + '!--(?:(?:-*[^->])*--+|-?)'
    // Special "raw text" elements whose content should be elided.
    + '|script\\b' + tagBody + '>[\\s\\S]*?</script\\s*'
    + '|style\\b' + tagBody + '>[\\s\\S]*?</style\\s*'
    // Regular name
    + '|/?[a-z]'
    + tagBody
    + ')>',
    'gi'
);

function removeTags(html){
    var oldHtml;
    do{
        oldHtml = html;
        html = html.replace(tagOrComment, '');
    } while (html !== oldHtml);
    return html.replace(/</g, '&lt;');
}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// This function should be called on page load for chat.php.   
// Call getMessages(), then call getMessages() every 10 seconds for 
// chat updates.
// Include the url of the target php file for post
function updateMessages(){
    getMessages();
    setInterval(getMessages, 10000);
}

// stackoverflow.com/questions/667555/how-to-detect-idle-time-in-javascript-elegantly
// Detect if user is somewhat active in browser. 
// If user is idle for 60 seconds, set userActive boolean to false.
// Set userActive to true and reset numUpdates if user has interacted with window.
function idleChecker(){
    var t;
    window.onload = resetTimer;
    window.onmousemove = resetTimer;
    window.onmousedown = resetTimer;       
    window.ontouchstart = resetTimer;  
    window.onclick = resetTimer;      
    window.onkeypress = resetTimer;   
    window.addEventListener('scroll', resetTimer, true); 

    function updateActivity() {
        userActive = false;
    }

    function resetTimer(){
        clearTimeout(t);
        numUpdates = 0;
        userActive = true;
        t = setTimeout(updateActivity, 60000);
    }
}
idleChecker();


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Closure for modifier variable that adjusts the polling rate for http requests for 
// chat updates when user is idle.
var intervalMod = (function(){
    var counter = 0;
    return function(){
        counter++; 
        return counter;
    }
})();

// Returns a random color seeded by the first char of input text.
// Used for user icon generation in chat based on user names. 
function randColor(myText){
    x = myText.charCodeAt(0) % 3;
    if(x == 0) return "gradientColorOrange";
    else if(x == 1) return "gradientColorGreen";
    else return "gradientColorBlue";
}

// This function should be called on page load for chat.php thorugh the 
// updateMessages() function.
function getMessages(){
    // Check if user is still active on the page. 
    // If not, only call server once every intervalExtension times getNewMessages()
    // is called. This simply decreases traffic to/from client/server when user is
    // relatively inactive. 
    if(userActive || intervalMod() % intervalExtension == 0){
        if(userActive) document.title = "Chat";
    
        // Note url is requestNewChatMessages.php and not requestChatMessages.php
        var http = new XMLHttpRequest();
        if(document.getElementById('output').innerHTML == "") 
            var url = 'requestChatMessages.php';
        else var url = 'requestNewChatMessages.php';
        var params = 'chatId=' + chatId; 
        http.open('POST', url, true);
        http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        http.onreadystatechange = function() {
            if(http.readyState == 4 && http.status == 200){
                chatOutputArray = http.responseText.split(",");

                // Update page title with number of unread messages
                if(!userActive){
                    if(chatOutputArray.length > 1){
                        numUpdates += chatOutputArray.length - 1;
                        document.title = "Chat (" + numUpdates + ")";
                    }
                } else document.title = "Chat";
                
                // For each message, decrypt text and format the meta display info.
                // Tags must always be removed from this text... and unfortunately 
                // must be done client side as to maintain privacy. 
                // To anyone attempting to edit their client side page code, do not 
                // disable this text sanitizer. If you do your entire account may 
                // become compromised.
                newText = "";
                for(a = 0; a < chatOutputArray.length - 1; a++){
                    message = removeTags(chatOutputArray[a]);
                    message = message.split("|");

                    if(date != message[2].substring(0, 10)){
                        date = message[2].substring(0, 10);
                        newText += '<div class = "dateSeparator">' + date + '</div>';
                    }

                    messageText = aesDecryptWithIv(message[1], chatAesKey, message[3]);
                    messageText = emoteDecoder(depadMessage(messageText));
                    messageText = stickerDecoder(messageText);
                    timeStamp = message[2].substring(10, 16);
                        
                    newText += getMessageHtml(a, message[0], messageText, timeStamp);
                }
                document.getElementById('output').innerHTML += newText;
                var objDiv = document.getElementById("messagesContainer");
                objDiv.scrollTop = objDiv.scrollHeight;
            }
        }
        http.send(params);
    }
}

// Generates HTML out to be appended to the output div of chat
function getMessageHtml(a, displayName, messageText, timeStamp){
    icon = displayName.substring(0, 1);
    color = randColor(icon);

    // CSS for user messages custimized for who in chat wrote the message
    if(displayName.trim() == userName.trim()){
        side = "float: right;";
        borderRadius = "border-radius: .5em 0px .5em .5em;";
    } else {
        side = "float: left;";
        borderRadius = "border-radius: 0px .5em .5em .5em;";
    }

    // The message div and all of its contents
    return '' + 
    '<div id="messageContainer'+a+'" class="messageContainer" style="'+side+'">'+ 
        '<div class="userIdCard '+color+'" style="'+side+'">'+icon+'</div>'+ 
        '<div class = "messageBody" style = "'+side+' '+borderRadius+'">'+ 
            '<p class = "letters">'+messageText+'</p>'+ 
            //'<div class = "timeStamp">' + timeStamp + '</div>' + 
        '</div>'+ 
    "</div>";
}

// Post a message to private chat.
function postMessage(){
    // Get plaintext message from textbox.
    // Enforce maxMessageLength char limit in message.
    // Pad message with random string.
    // Encrypt plaintext using the chat's established secret aes key.
    message = document.getElementById('messageBox').value;
    if(message.length > 0){
        iv = randStr(16);
        message = message.substring(0, maxMessageLength - 1);
        paddedMessage = padMessage(message, maxMessageLength + 1);
        aesEncryptedMessage = aesEncryptWithIv(paddedMessage, chatAesKey, iv);

        // Post encrypted message to server
        var http = new XMLHttpRequest();
        var url = 'submitMessage.php';
        var params = 'aesEncryptedMessage=' + aesEncryptedMessage + 
                     '&iv=' + iv +
                     '&chatId=' + chatId;           
        http.open('POST', url, true);
        http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        http.onreadystatechange = function() {
            if(http.readyState == 4 && http.status == 200) {
                if(http.responseText == "success"){
                    document.getElementById('messageBox').value = "";
                } else document.getElementById('output').innerHTML += 
                    removeTags(http.responseText);
            }
        }
        http.send(params);
    }
}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Used for establishing an initial connection between two users.
// User1 triggers establishPrivateChat() targeting user2.
// DB is updated and user2 is notified user1 is attempting to establish a connection.
function establishPrivateChat(){
    var http = new XMLHttpRequest();
    var url = 'requestUserPublicKey.php';
    var params = 'name=' + document.getElementById('userReceiver').value;
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    http.onreadystatechange = function(){
        if(http.readyState == 4 && http.status == 200){
            // Get target user's public key for use in asymetricly encrypting partial
            // AES key. Check if chat between target user and current user already 
            // exists or if current user is attempting to establish connection with 
            // them selves. In either case, report error. Else, proceed. 
            result = http.responseText.split("$");
            if(result[0] == "Error, chat already exists between users." || 
                result[0] == "Stop talking to yourself."){
                document.getElementById('output').innerHTML = result[0];
            } else if(result[0] != "error" && result[0] != ""){
                receiverUserId = result[0];
                receiverPublicKey = result[1];

                // Establish first half of private AES key to be used in private chat.
                // Encrypt partial key using the public rsa key of the targeted user.
                requestMessage = randStr(8) + "$" + userName;
                encryptedRequestMessage = rsaEncrypt(requestMessage, receiverPublicKey);

                // Submit the chat request for server logging.
                // Target user will now see a chat request in their inbox and current 
                // user will see a success message if succesful, else current user will
                // see an error thrown.
                var http2 = new XMLHttpRequest();
                var url2 = 'submitPrivateChatRequest.php';
                var params2 =  'userIdReceiver=' + receiverUserId + 
                                '&encryptedRequestMessage=' + encryptedRequestMessage;
                http2.open('POST', url2, true);
                http2.setRequestHeader('Content-type','application/x-www-form-urlencoded');
                http2.onreadystatechange = function(){
                    if(http2.readyState == 4 && http2.status == 200){
                        if(http2.responseText == "success") 
                            document.getElementById('output').innerHTML = 
                                "Chat invite sent.";
                        else document.getElementById('output').innerHTML = 
                            removeTags(http2.responseText);
                    }
                }
                http2.send(params2);
            } else document.getElementById('output').innerHTML = 
                "Error: User does not exist.";
        }
    }
    http.send(params);
}

// Continuing from establishPrivateChat(), user2 may trigger acceptPrivateChat() to 
// establish a chat with user1. If they do, DB is updated once more, allowing user1 and
// user2 to chat privately. This process is necessary in order to restrict server from 
// ever seeing the encryption keys being used by user1 and user2, thus making their chat 
// truely private. 
function acceptPrivateChat(requesterUserName, senderPublicKey, privateChatRequestMessage){
    // Decrypt requestMessage with private key
    secretMessage = rsaDecrypt(privateChatRequestMessage);

    // Check for sender authentication.
    // The initial sender included their identity. This claimed identity is then compared 
    // to the server's account of who sent this message. If these values are unequal, 
    // something funky is going on...
    split = secretMessage.split("$");
    if(requesterUserName == split[1]){
        // Generate secret key.
        // Both user1 and user2 have input into the final AES key so that neither may try
        // some specific shenanigans. In general, this input should be random, but because 
        // the key generation occures cleint side, a user may attempt to hack the key
        // generation process. This step ensures no single user can influence the entirity
        // of the key generation.
        secretAesKey = split[0] + randStr(8);

        // Encrypt secret key with requester's and receiver's public key
        requesterEncryptedSecretAesKey = rsaEncrypt(secretAesKey, senderPublicKey);
        receiverEncryptedSecretAesKey = rsaEncrypt(secretAesKey, publicKey);

        // Send acceptance message to server and log appropriate encrypted data.
        // User will see success or error message depending on server and DB result 
        var http = new XMLHttpRequest();
        var url = 'submitAcknowledgeChatRequest.php';
        var params = 'requesterUserName=' + requesterUserName + 
                     '&receiverEncryptedSecretAesKey=' + receiverEncryptedSecretAesKey +
                     '&requesterEncryptedSecretAesKey=' + requesterEncryptedSecretAesKey;          
        http.open('POST', url, true);
        http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        http.onreadystatechange = function() {//Call a function when the state changes.
            if(http.readyState == 4 && http.status == 200){
                document.getElementById('output').innerHTML = 
                    removeTags(http.responseText);
            }
        }
        http.send(params);
    } else {
        document.getElementById('output').innerHTML = "The user who sent this request " +
        "is not who they say they are. This request and its acceptance will be discarded.";
    }
}













