<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";

	if(!empty($_POST["name"]) &&
		!empty($_POST["hashword"])){
		$userName = strip_tags(trim($_POST['name']));
		$hashword = strip_tags(trim($_POST['hashword']));
		
		// Set session variables userName and userId
		$table = "user";
	    $cols = array("id");
	    $where1 = array("userName");
	    $where2 = array($userName);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

	    // Check if dbResult returned a userId
	    if(is_int($dbResults[0][0])){
		    $_SESSION["userId"] = $dbResults[0][0];
		    $_SESSION["userName"] = $userName;
		}

		// Perform select on user table in DB to retrieve user's salt
		// echo result back to requester
	    $table = "user";
	    $cols = array("encryptedRsaSeed", "publicKey");
	    $where1 = array("userName", "hashword");
	    $where2 = array($userName, $hashword);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
		echo $dbResults[0][0] . ";" . str_replace(" ","+", $dbResults[0][1]);
	} else {
		echo "error";
	}
?>