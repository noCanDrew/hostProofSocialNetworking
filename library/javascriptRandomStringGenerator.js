function randStr(length){
	/*var text = "";
	var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	for (var i = 0; i < length; i++)
		text += possible.charAt(Math.floor(Math.random() * possible.length));
	return text;*/
	const validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	let array = new Uint8Array(length);
	window.crypto.getRandomValues(array);
	array = array.map(x => validChars.charCodeAt(x % validChars.length));
	const randomState = String.fromCharCode.apply(null, array);
	return randomState;
}