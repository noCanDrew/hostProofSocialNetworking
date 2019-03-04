<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	include "library/betterHash.php";
	
	if(!empty($_POST["name"]) &&
	!empty($_POST["salt"]) &&
	!empty($_POST["hashword"]) &&
	!empty($_POST["publicKey"]) &&
	!empty($_POST["encryptedRsaSeed"])){
		$userName = strtolower(strip_tags(trim($_POST['name'])));
		$salt = strip_tags(trim($_POST['salt']));
		$hashword = strip_tags(trim($_POST['hashword']));
		$publicKey = strip_tags(trim($_POST['publicKey']));
		$publicKey = str_replace(" ","+", $publicKey);
		$encryptedRsaSeed = strip_tags(trim($_POST['encryptedRsaSeed']));

		// Ensure user inputs meet initial submission criteria
		if(strlen($salt) == 16 &&
			strlen($userName) > 0 &&
			strlen($userName) <= 16 &&
			strlen($hashword) == 64 && 
			strlen($publicKey) == 172 &&
			strlen($encryptedRsaSeed) == 256 &&
			ctype_alnum($userName) &&
			ctype_alnum($hashword) && 
			ctype_alnum($salt)){

			// Check if userName is already in use. 
			$table = "user";
		    $cols = array("id");
		    $where1 = array("userName");
		    $where2 = array(strtolower($userName));
		    $limit = "1";
		    $orderBy = "";
		    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
			if(sizeof($dbResults) == 0){
				// Salt hashword and then hash the result.
				// This disallows attacker with access to DB table from faking sign in.
				// Note, password had to be hashed prior to being sent to server in order to
				// preserve "host-proof" nature. But to the server, what it receieves is effectively
				// a plain text password which means this value itself cant be stored. Thus, it
				// is salted and hashed prior to storage as well. 
				$hashword = betterHash($hashword, $salt);

				// Use dbInsert() from dbInterface.php store article data
				$table = "user";
				$cols = array("userName", "salt", "hashword", "publicKey", "encryptedRsaSeed");
				$vals = array($userName, $salt, $hashword, $publicKey, $encryptedRsaSeed);
				$dbResult = dbInsert($table, $cols, $vals, $dbc);

				echo $dbResult;
			} else echo "Error: User name already in use.";
		} else echo "Error: username must be between 1 and 16 characters and can only contain alphanumerics." .
					" Password must be exactly 16 characters";
	} else echo "Error: missing informatio.n";
	$dbc->close();
?>