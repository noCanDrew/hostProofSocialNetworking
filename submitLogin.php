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

		    $indicesServer = array(
		    	'PHP_SELF', 'argv', 'argc', 'GATEWAY_INTERFACE', 'SERVER_ADDR', 
		    	'SERVER_NAME', 'SERVER_SOFTWARE', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 
				'REQUEST_TIME', 'REQUEST_TIME_FLOAT', 'QUERY_STRING', 'DOCUMENT_ROOT', 
				'HTTP_ACCEPT', 'HTTP_ACCEPT_CHARSET', 'HTTP_ACCEPT_ENCODING', 
				'HTTP_ACCEPT_LANGUAGE', 'HTTP_CONNECTION', 'HTTP_HOST', 'HTTP_REFERER', 
				'HTTP_USER_AGENT', 'HTTPS', 'REMOTE_ADDR', 'REMOTE_HOST', 'REMOTE_PORT', 
				'REMOTE_USER', 'REDIRECT_REMOTE_USER', 'SCRIPT_FILENAME', 'SERVER_ADMIN', 
				'SERVER_PORT', 'SERVER_SIGNATURE', 'PATH_TRANSLATED', 'SCRIPT_NAME', 
				'REQUEST_URI', 'PHP_AUTH_DIGEST', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 
				'AUTH_TYPE', 'PATH_INFO', 'ORIG_PATH_INFO'
			); 
			$info = array();
			foreach ($indicesServer as $arg) { 
			    if(isset($_SERVER[$arg])) array_push($info, $_SERVER[$arg]);
			}
		    $meta = json_encode($info);

		    $table = "login";
			$cols = array("idUser", "meta");
			$vals = array($_SESSION["userId"], $meta);
			$dbResult = dbInsert($table, $cols, $vals, $dbc);

			echo $dbResults[0][1] . ";" . str_replace(" ","+", $dbResults[0][2])  . ";" . $dbResults[0][0];
	    } else echo "error 2";
	} else echo "error 3";
	$dbc->close();
?>