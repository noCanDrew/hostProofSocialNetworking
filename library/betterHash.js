
// Takes in a string and some salt and produces a 64 char long string that is a result of chained salting/hashing
// with sha256.
function betterHash(str, salt){
	// Performs a logic xor at the character level.
	// Input must only contain chars contained in the alphabet.
	function charLevelXor(str1, str2){
		if(str1.length == str2.length){
			var ret = "";
			var alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			for(var a = 0; a < str1.length; a++){
				pos = (alphabet.indexOf(str1.charAt(a)) + alphabet.indexOf(str2.charAt(a))) % alphabet.length;
				ret += alphabet.charAt(pos);
			}
			return ret;
		} else return null;
	}

	if(str.length == 16 && salt.length == 16){
		var iterationFactor = 1000;
		var hashword = str + salt + str + salt;

		for(var a = 0; a < iterationFactor; a++){
			tmp = SHA256(hashword);
			hashword = charLevelXor(hashword, tmp);
		}
		return hashword;
	} else return null;
}