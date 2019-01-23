<?php
	include "library/sessionStart.php";

	if(!empty($_POST["aesSessionKey"])){
		$_SESSION["aesSessionKey"] = strip_tags(trim($_POST['aesSessionKey']));
	} else {
		echo "error";
	}
?>