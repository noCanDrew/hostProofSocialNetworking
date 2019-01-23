<?php
	session_start();
	session_destroy();
	header("Location: https://collaber.org/harpocrates/login.php");
?>