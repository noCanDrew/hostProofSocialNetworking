<?php
    // Start session and determin if user is logged in by checking their aesSessionKey.
    // Include library for database interfacing
    include "library/sessionStart.php";
    include "library/dbInterface.php";
    if(!isset($_SESSION["aesSessionKey"])) 
        header("Location: https://collaber.org/harpocrates/login.php");   
?><!DOCTYPE HTML>

<html>
    <head>
        <title> Harpocrates </title>

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
        <div class = "indexSectionContainer">
            Establish connection with group of users: <br>
            <input type="text" id = "userReceivers"><br>
            <input type="text" id = "groupChatName"><br>
            <button onclick="establishPrivateGroupChat()">Submit</button>
        </div>
        
        <?php 
            // Get container element for all chat requests.
            include "getChatRequests.php";

            // Get container element for all existing chats useer is a member of.
            include "getExistingChats.php";
        ?>

        <form action="submitLogout.php">
            <input type="submit" value="Logout" />
        </form>
    </body>
</html>

<?php $dbc->close(); ?>