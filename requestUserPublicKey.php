<?php
	include "library/dbInterface.php";

	if(!empty($_POST["name"])){
		$userName = strip_tags(trim($_POST['name']));
		
		// Perform select on user table in DB to retrieve user's public key.
		// Echo result back to requester.
	    $table = "user";
	    $cols = array("id", "publicKey");
	    $where1 = array("userName");
	    $where2 = array($userName);
	    $limit = "1";
	    $orderBy = "";
	    $dbResults = dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc);
		echo $dbResults[0][0] . "$" .  $dbResults[0][1];
	} else {
		echo "error";
	}
?>