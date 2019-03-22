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
var ratchetLock = false;        // Used for serializing ratchet usage

// User specific global variables.
var userId = removeTags(window.localStorage.userId);
var userName = removeTags(window.localStorage.userName);
var publicKey = removeTags(window.localStorage.publicKey);
var encryptedSerialRsaKey = removeTags(window.localStorage.encryptedSerialRsaKey);

// Decrypt serial rsa object using aesSessionKey (provided by given php pages).
// Using this object, generate user's RSA keys.
// Note, only generate this rsa object if its needed. It is not performant to generate 
// and is only used when creating group chat keys or using group chat keys
var rsaKeyObject = null;
function generateRsaKeyObject(){
    serialRsaKey = aesDecrypt(encryptedSerialRsaKey, aesSessionKey);
    rsaKeyObject = deserializeRSAKey(serialRsaKey); 
}

function deserializeRSAKey(key) {
    let json = JSON.parse(key);
    let rsa = new RSAKey();
    rsa.setPrivateEx(
        json.n, json.e, json.d, json.p, json.q, json.dmp1, json.dmq1, json.coeff
    );
    return rsa;
}


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
    if(rsaKeyObject == null) generateRsaKeyObject();
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
    return html;
}

/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Request messages on page load
// Request new messages every 6 seconds
function updateGroupMessages(){
    getGroupMessages();
    setInterval(getGroupMessages, 6000);
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
    if(x == 0) return "gradientColorGreen";
    else if(x == 1) return "gradientColorOrange";
    else return "gradientColorBlue";
}

// Generates HTML out to be appended to the output div of chat
function getMessageHtml(id, displayName, messageText, timeStamp){
    if(checkImageURL(messageText) && id != "tmp"){
        testImage(messageText, id);
        innerMessage = '<p id = "innerMessage'+id+'" class = "letters"></p>';
    } else {
        innerMessage = '<p id="innerMessage'+id+'" class="letters">'+messageText+'</p>';
    }

    icon = displayName.substring(0, 1);
    color = randColor(icon);

    // CSS for user messages custimized for who in chat wrote the message
    if(displayName.trim() == userName.trim()){
        side = "float: right;";
        timeSide = "left: -3.5em;";
        borderRadius = "border-radius: .5em 0px .5em .5em;";
    } else {
        side = "float: left;";
        timeSide = "right: -3.5em;";
        borderRadius = "border-radius: 0px .5em .5em .5em;";
    }

    // The message div and all of its contents
    return '' + 
    '<div id="messageContainer'+id+'" class="messageContainer" style="'+side+'">'+ 
        '<div class="userIdCard '+color+'" style="'+side+'">'+icon+'</div>'+ 
        '<div id="message'+id+'" class="messageBody" style="'+side+' '+borderRadius+'">'+
            innerMessage+ 
            '<div class="displayNameStamp">'+displayName+'</div>'+ 
            '<div class="timeStamp" style="'+timeSide+'">'+timeStamp+'</div>'+ 
        '</div>'+ 
    "</div>";
}

/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


// Standard response for declining chat invite
ackNoThanks = "no thanks";

// Send request to server to make a group chat and send requests to designated users.
// "users" is string with user names seperated by ","
function establishPrivateGroupChat(){
    users = document.getElementById('userReceivers').value;
    chatName = document.getElementById('groupChatName').value;

    if(users.length > 0 && chatName.length > 0){
        var http = new XMLHttpRequest();
        var url = 'submitMakeGroupChat.php';
        var params = 'users=' + users + '&chatName=' + encodeURIComponent(chatName);          
        http.open('POST', url, true);
        http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        http.onreadystatechange = function(){
            if(http.readyState == 4 && http.status == 200){
                document.getElementById('userReceivers').value = "";
                document.getElementById('groupChatName').value = "";

                ui_closeAlert();
                ui_displayAlert([removeTags(http.responseText)], true, "notice");
            }
        }
        http.send(params);
    } else {
        ui_closeAlert();
        ui_displayAlert(["Empty fields found."], true, "notice");
    }
}

function respondGroupChatRequest(ack, chatId, requestId, creatorPublicKey){
    if(ack == 1) encryptedKey = rsaEncrypt(randStr(16), creatorPublicKey);
    else encryptedKey = ackNoThanks;
    
    var http = new XMLHttpRequest();
    var url = 'submitResponseGroupChatRequest.php';
    var params = 'ack=' + encryptedKey + 
        '&chatId=' + chatId +
        '&requestId=' + requestId
    ;
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    http.onreadystatechange = function(){
        if(http.readyState == 4 && http.status == 200){
            document.getElementById("groupChatRequest" + chatId).innerHTML = "";
            ui_closeAlert();
            ui_displayAlert([removeTags(http.responseText)], true, "notice");
        }
    }
    http.send(params);
}

// Once all users have responded to invites, original creator must construct final
// keys, ecnrypt them each once with each user's public key, then post to DB.
function makeGroupChatKeys(groupChatId){
    // Call php script to perform $groupChatConfirmations for specific groupChatId
    var http = new XMLHttpRequest();
    var url = 'requestConfirmationsByChatId.php';
    var params = 'groupChatId=' + groupChatId;
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    http.onreadystatechange = function(){
        if(http.readyState == 4 && http.status == 200){
            result = removeTags(http.responseText);
            result = result.split("|"); // split return by response

            // Decrypt each response
            // Response order: $pgcId, $pgcrIdUserReceiver, $pgcrAcknowledge, $uPublicKey
            // result.length - 1 because there is a trailing comma in the response
            // Build encryptedKeys string that will be encrypted for each user in chat
            publicKeys = [];
            encryptedKeys = "";
            for(a = 0; a < result.length - 1; a++){
                tmp = result[a].split(","); // split response by element
                if(tmp[2] != ackNoThanks){
                    publicKeys[a] = [tmp[1], tmp[3]];
                    encryptedKeys += tmp[1] + ":" + rsaDecrypt(tmp[2]) + ",";
                }
            }

            if(publicKeys.length > 0){
                // Add this user's portion
                encryptedKeys += userId + ":" + randStr(16);
                publicKeys.push([userId, publicKey]);
                
                // Encrypt the encryptedKeys with each user's public key who accepted invite
                encryptedBatch = "";
                for(a = 0; a < publicKeys.length; a++){
                    encryptedBatch += publicKeys[a][0] + ":" + 
                        rsaEncrypt(encryptedKeys, publicKeys[a][1]) + ",";
                }

                // Post encryptedBatch to server to store groupChatKeys on DB
                var http2 = new XMLHttpRequest();
                var url2 = 'submitMakeGroupChatKeys.php';
                var params2 = 'encryptedBatch=' + groupChatId + "|" + encryptedBatch;
                http2.open('POST', url2, true);
                http2.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                http2.onreadystatechange = function(){
                    if(http2.readyState == 4 && http2.status == 200){
                        //alert(removeTags(http2.responseText));
                    }
                }
                http2.send(params2);
            } else {
                // Deactivate all chat creation vars because everyone invited has declined
                var http2 = new XMLHttpRequest();
                var url2 = 'submitDeactivateChatCreation.php';
                var params2 = 'groupChatId=' + groupChatId;
                http2.open('POST', url2, true);
                http2.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                http2.onreadystatechange = function(){
                    if(http2.readyState == 4 && http2.status == 200){
                        //alert(removeTags(http2.responseText));
                    }
                }
                http2.send(params2);
            }
        }
    }
    http.send(params);
}

// Post a message to private group chat.
function postGroupMessage(){
    // Get plaintext message from textbox.
    // Enforce maxMessageLength char limit in message.
    // Pad message with random string.
    // Encrypt plaintext using this users ratchet state.
    message = document.getElementById('messageBox').value.trim();
    if(message.length > 0 && !ratchetLock){
        ratchetLock = true;
        document.getElementById("messageSubmit").disabled = true;

        iv = randStr(16);
        message = message.substring(0, maxMessageLength - 1);
        paddedMessage = padMessage(message, maxMessageLength + 1);
        postingRatchet.updateState();
        aesEncryptedMessage = aesEncryptWithIv(
            paddedMessage, 
            postingRatchet.xorOut, 
            iv
        );

        // Post encrypted message to server
        var http = new XMLHttpRequest();
        var url = 'submitGroupMessage.php';
        var params = 'aesEncryptedMessage=' + aesEncryptedMessage + 
                     '&iv=' + iv +
                     '&groupChatId=' + groupChatId;           
        http.open('POST', url, true);
        http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        http.onreadystatechange = function() {
            if(http.readyState == 4 && http.status == 200) {
                if(http.responseText == "success"){
                    document.getElementById('messageBox').value = "";
                    
                    message = removeTags(message);
                    message = emoteDecoder(message);
                    msg = getMessageHtml("tmp", userName, message, "");
                    document.getElementById('tmpMessages').innerHTML += msg;
                    var objDiv = document.getElementById("messagesContainer");
                    objDiv.scrollTop = objDiv.scrollHeight;

                    ratchetLock = false;
                    reEnablePosting("messageSubmit", 3000);
                } else {
                    // step back posting ratchet by 1

                    ratchetLock = false;
                    reEnablePosting("messageSubmit", 3000);
                }
            }
        }
        http.send(params);
    }
}

function reEnablePosting(id, time){
    setTimeout(
        function(){
            document.getElementById(id).disabled = false;
        },
        time
    );
}

// Return true if ratchet's state is properly known.
// Return false if ratchet update is uncertain.
function getGroupMessages(){
    newMessageCheck = false;

    // Check if user is still active on the page. 
    // If not, only call server once every intervalExtension times getNewMessages()
    // is called. This simply decreases traffic to/from client/server when user is
    // relatively inactive. 
    if(userActive || intervalMod() % intervalExtension == 0){
        if(userActive) document.title = "Chat";
    
        // Note url is requestNewChatMessages.php and not requestChatMessages.php
        var http = new XMLHttpRequest();
        if(document.getElementById('output').innerHTML == "") 
            var url = 'requestGroupChatMessages.php';
        else var url = 'requestNewGroupChatMessages.php';
        var params = 'groupChatId=' + groupChatId; 
        http.open('POST', url, true);
        http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        http.onreadystatechange = function(){
            if(http.readyState == 4 && http.status == 200){
                chatOutputArray = JSON.parse(removeTags(http.responseText));

                // Update page title with number of unread messages
                if(!userActive){
                    if(chatOutputArray.length > 0){
                        numUpdates += chatOutputArray.length;
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
                for(a = 0; a < chatOutputArray.length; a++){
                    message = chatOutputArray[a];
                    if(date != message["timeStamp"].substring(0, 10)){
                        date = message["timeStamp"].substring(0, 10);
                        newText += '<div class = "dateSeparator">' + date + '</div>';
                    }

                    // Set boolean to true to cause device vibration notice of new message
                    if(url == 'requestNewGroupChatMessages.php' &&
                        message["userId"] != userId) newMessageCheck = true;

                    groupChatRatchets[message["userId"]].updateState();
                    messageText = aesDecryptWithIv(
                        message["encryptedMessage"], 
                        groupChatRatchets[message["userId"]].xorOut, 
                        message["iv"]
                    );

                    messageText = emoteDecoder(depadMessage(messageText));
                    //messageText = stickerDecoder(messageText);
                    messageText = removeTags(messageText);

                    timeStamp = message["timeStamp"].substring(10, 16);
                    newText += getMessageHtml(
                        message["messageId"], 
                        message["userName"], 
                        messageText, 
                        timeStamp
                    );
                }

                // Move the postingRatchet to the current state of the this user's 
                // decrypting ratchet
                postingRatchet.setState(
                    groupChatRatchets[userId].xorOut, 
                    groupChatRatchets[userId].state1, 
                    groupChatRatchets[userId].state2
                );

                document.getElementById('tmpMessages').innerHTML = "";
                document.getElementById('output').innerHTML += newText;
                var objDiv = document.getElementById("messagesContainer");

                // For each message containing a valid image url, display image instead
                // of url. The delete the item in the hashmap so it doesnt load everytime 
                // there is a pole response from server
                setTimeout(function(){
                    for(id in imageMessages){
                        document.getElementById("innerMessage" + id).
                            appendChild(imageMessages[id]);
                    }

                    imageMessages = Object();
                    objDiv.scrollTop = objDiv.scrollHeight;
                }, 500);

                if(newMessageCheck) notificationEvent();
                objDiv.scrollTop = objDiv.scrollHeight;
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

// Make object class ratchet
// Object stores internally state of each user's ratchet
// At time of groupChat page load, create object and initialize seeds for ratchets
// When decrypting, check against ratchet state...
// Note: Need to instantiate object in groupChat.php after groupChatKeys has been 
// established

class dRatchet{
    // bootleg double ratchet
    updateState(){
        this.state1 = this.betterHash(this.state1, this.commonSeed);
        this.state2 = this.betterHash(this.state2, this.personalSeed);
        this.xorOut = charLevelXor(this.state1, this.state2).substring(0,16);
    };

    // Copiedish from betterHash.js... ya I know... just use it for now
    betterHash(str, salt){
        str = str.substring(0,16);
        salt = salt.substring(0,16);
        var iterationFactor = 10;
        var hashword = str + salt + str + salt;
        for(var a = 0; a < iterationFactor; a++){
            tmp = SHA256(hashword);
            hashword = charLevelXor(hashword, tmp);
        }
        return hashword;
    }

    setState(newXorOut, newState1, newState2){
        this.xorOut = newXorOut;
        this.state1 = newState1;
        this.state2 = newState2;
    }

    constructor(seed1, seed2){
        this.commonSeed = seed1;
        this.personalSeed = seed2;
        this.state1 = "xxxxxxxxxxxxxxxx";
        this.state2 = "xxxxxxxxxxxxxxxx";
        this.xorOut = "";
    }
}

// Performs a logic xor at the character level.
// Note the randomized alphabet though... just cuz
function charLevelXor(str1, str2){
    if(str1.length == str2.length){
        var ret = "";
        var alphabet = 'B0fnrIMF6d9av31hSjimyVo2Z48XKxGQgCTWulUNtsRbApeYwD5cPzOE7JqLkH';
        for(var a = 0; a < str1.length; a++){
            pos = (alphabet.indexOf(str1.charAt(a)) + 
                alphabet.indexOf(str2.charAt(a))) % alphabet.length;
            ret += alphabet.charAt(pos);
        }
        return ret;
    } else return null;
}

/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

function notificationEvent(){
    /*var audio = new Audio("library/soundFiles/notification.mp3");
    audio.play();


    window.navigator.vibrate([100,30,100,30,100,30,200,30,200,30,200,30,100,30,100,30,100]);
    /*
    filename = "library/soundFiles/notification";
    var mp3Source = '<source src="' + filename + '.mp3" type="audio/mpeg">';
    var oggSource = '<source src="' + filename + '.ogg" type="audio/ogg">';
    var embedSource = '<embed hidden=
        "true" autostart="true" loop="false" src="' + filename +'.mp3">';
    document.getElementById("sound").innerHTML=
        '<audio autoplay="autoplay">' + mp3Source + oggSource + embedSource + '</audio>';
    */
}

/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

// https://stackoverflow.com/questions/9714525/javascript-image-url-verify

imageMessages = Object();

function checkImageURL(url){
    if(url.substring(0,5) == "https")
        return(url.match(/\.(jpeg|jpg|gif|png|JPEG|JPG|GIF|PNG)$/) != null);
}

function testImage(url, id){
    if(url.substring(0,5) == "https"){ 
        url = url.replace("javascript", "");
        url = url.replace("'", "");
        url = url.replace("\"", "");
        //url = encodeURIComponent(url);

        timeout =  1000;
        var timedOut = false, timer;
        var img = new Image();

        img.onerror = img.onabort = function() {
            if (!timedOut) {
                clearTimeout(timer);
            }
        };
        img.onload = function() {
            if (!timedOut) {
                clearTimeout(timer);
                imageMessages[id] = img;
            }
        };
        img.src = url;
        timer = setTimeout(function() {
            timedOut = true;
        }, timeout); 
    }
}

