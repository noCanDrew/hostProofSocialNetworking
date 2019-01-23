<?php
	include "library/sessionStart.php";
	include "library/dbInterface.php";
	include "library/phpRandomStringGenerator.php";

	if(!empty($_POST["name"]) &&
	!empty($_POST["salt"]) &&
	!empty($_POST["hashword"]) &&
	!empty($_POST["publicKey"]) &&
	!empty($_POST["encryptedRsaSeed"])){
		$userName = strip_tags(trim($_POST['name']));
		$salt = strip_tags(trim($_POST['salt']));
		$hashword = strip_tags(trim($_POST['hashword']));
		$publicKey = strip_tags(trim($_POST['publicKey']));
		$publicKey = str_replace(" ","+", $publicKey);
		$encryptedRsaSeed = strip_tags(trim($_POST['encryptedRsaSeed']));

		// Ensure user inputs meet initial submission criteria
		if(strlen($salt) == 16 &&
			strlen($userName) > 0 &&
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
		    $where2 = array($userName);
		    $limit = "1";
		    $orderBy = "";
		    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
			if(sizeof($dbResults) == 0){

				// Salt and hash again so the hashword stored in DB can not be directly used
				// for login in the event of a comprimised DB. 
				$salt2 = randStr(16);
				$hashword = sha1($hashword . $salt2);

				// Use dbInsert() from dbInterface.php store article data
				$table = "user";
				$cols = array("userName", "salt", "salt2", "hashword", "publicKey", "encryptedRsaSeed");
				$vals = array($userName, $salt, $salt2, $hashword, $publicKey, $encryptedRsaSeed);
				$dbResult = dbInsert($table, $cols, $vals, $dbc);

				echo $dbResult;
			} else echo "Error: User name already in use.";
		} else echo "Error: username must be between 1 and 16 characters." . 
					" Password must be exactly 16 characters";
	} else echo "Error: missing informatio.n";
?>