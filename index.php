<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: login.php");   
?><!DOCTYPE HTML>

<html>
    <head>
        <title> IM </title>

        <meta charset="UTF-8">
        <meta name="theme-color" content="rgb(45,45,45)">
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
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

        <script src="library/alerts.js"></script>
        <script src="library/harpocrates.js"></script>

        <?php 
            // Get chats user is responsible for creating and their associated
            // confirmations. Generate javascript calls for final chat submissions
            // for any chat which has received all reponses from all targeted users.
            // Then execute java script calls for creating privateGroupChats.
            include "getChatConfirmations.php";
            if(strpos($activateGroupChat, "makeGroupChatKeys")) echo $activateGroupChat; 
        ?>

        <link rel="stylesheet" type="text/css" href="css/index.css">
    </head>
    
    <body>
        <div class = "indexSectionContainer shadow">
            <div class = "indexSectionContainerHeader">
                <i class="fa fa-envelope" aria-hidden="true"></i>
                &nbsp;Invite users to chat
                <div style = "float: right"> 
                    <a onclick="ui_displayAlert(makeNewInvitesAlert, true, '')"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class = "indexSubsectionContainer">
                <input type="text" class = "inputField" id = "userReceivers" placeholder = "Users" maxlength="1000">
            </div>
            <div class = "indexSubsectionContainer">
                <input type="text" class = "inputField" id = "groupChatName" placeholder = "Chat name" maxlength="32">
            </div>
            <div class = "indexSubsectionContainer">
                <button onclick="establishPrivateGroupChat()" class = "">Submit</button>
            </div>
        </div>
        
        <div id = "groupChatRequests" class = "indexSectionContainer shadow">
            <div class = "indexSectionContainerHeader"> 
                <i class="fa fa-envelope-open" aria-hidden="true"></i> 
                &nbsp;Chat invites 
                <div style = "float: right"> 
                    <a onclick="ui_displayAlert(chatInviteAlert, true, '')"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>
                </div>
            </div>
            <?php
                // Get container element for all chat requests.
                include "getChatRequests.php";
            ?>
        </div>

        <div id = "groupChatRequests" class = "indexSectionContainer shadow">
            <div class = "indexSectionContainerHeader">
                <i class="fa fa-comments" aria-hidden="true"></i> 
                &nbsp;Chats 
                <div style = "float: right"> 
                    <a onclick="ui_displayAlert(chatsAlert, true, '')"><i class="fa fa-question-circle-o" aria-hidden="true"></i></a>
                </div>
            </div>
            <?php
                // Get container element for all existing chats useer is a member of.
                include "getExistingChats.php";
            ?>
        </div>

        <form action="submitLogout.php">
            <button type="submit" class = "">Sign out</button>
        </form>
    </body>
</html>

<?php $dbc->close(); ?>