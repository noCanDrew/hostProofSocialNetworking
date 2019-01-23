<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";

	if(!empty($_POST["name"]) &&
		!empty($_POST["hashword"])){
		$userName = strip_tags(trim($_POST['name']));
		$hashword = strip_tags(trim($_POST['hashword']));
		
		$table = "user";
	    $cols = array("salt2");
	    $where1 = array("userName");
	    $where2 = array($userName);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);

	    if(ctype_alnum($dbResults[0][0])){
	    	$hashword = sha1($hashword . $dbResults[0][0]);

		    // Perform select on user table in DB to retrieve user's info
			// Echo result back to requester
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

				echo $dbResults[0][1] . ";" . str_replace(" ","+", $dbResults[0][2]);
		    } else echo "error 1";
	    } else echo "error 2";
	} else echo "error 3";
	$dbc->close();
?>