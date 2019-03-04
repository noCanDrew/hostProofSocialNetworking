<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	include "library/betterHash.php";

	if(!empty($_POST["name"]) &&
		!empty($_POST["salt"]) &&
		!empty($_POST["hashword"])){
		$userName = strtolower(strip_tags(trim($_POST['name'])));
		$salt = strip_tags(trim($_POST['salt']));
		$hashword = strip_tags(trim($_POST['hashword']));
    	$hashword = betterHash($hashword, $salt);
    	
	    // Perform select on user table in DB to retrieve user's info.
		// Echo result back to requester.
	    $table = "user";
	    $cols = array("id", "encryptedRsaSeed", "publicKey");
	    $where1 = array("userName", "hashword");
	    $where2 = array($userName, $hashword);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

	    if(ctype_alnum($dbResults[0][1])){
	    	$_SESSION["userId"] = $dbResults[0][0];
		    $_SESSION["userName"] = $userName;

			echo $dbResults[0][1] . ";" . str_replace(" ","+", $dbResults[0][2])  . ";" . $dbResults[0][0];
	    } else echo "error 2";
	} else echo "error 3";
	$dbc->close();
?>