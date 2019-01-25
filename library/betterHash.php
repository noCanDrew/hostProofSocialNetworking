<?php
	// Performs a logic xor at the character level.
	// Input must only contain chars contained in the alphabet.
	function charLevelXor($str1, $str2){
		if(strlen($str1) == strlen($str2)){
			$ret = "";
			$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			for($a = 0; $a < strlen($str1); $a++){
				$pos = (strpos($alphabet, substr($str1, $a, 1)) + strpos($alphabet, substr($str2, $a, 1))) % strlen($alphabet);
				$ret .= substr($alphabet, $pos, 1);
			}
			return $ret;
		} else return null;
	}

	// Takes in a string and some salt and produces a 64 char long string that is a result of chained salting/hashing
	// with sha256.
	function betterHash($str, $salt){
		if(strlen($str) == 64 && strlen($salt) == 16){
			$iterationFactor = 100;
			$hashword = $str;
			$salt = $salt . $salt . $salt . $salt;

			for($a = 0; $a < $iterationFactor; $a++){
				$tmp = sha1($hashword);
				$hashword = substr($hashword, 0, strlen($tmp));
				$hashword = charLevelXor($hashword, $tmp);
				if($hashword == null) return null;
			}
			return $hashword;
		} else return null;
	}
?>