<?php
	include "library/sessionStart.php";

	if(!empty($_POST["groupChatId"])){
		$groupChatId = strip_tags(trim($_POST["groupChatId"]));
		$_SESSION[$groupChatId . "exponentialBackff"] = 5;
	} else {
		echo "error";
	}
?>