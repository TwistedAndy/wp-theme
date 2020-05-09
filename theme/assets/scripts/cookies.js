function setCookie(name, value, exdays) {

	var date = new Date();

	exdays = parseInt(exdays) || 365;

	date.setTime(date.getTime() + (exdays * 24 * 60 * 60 * 1000));

	var expires = 'expires=' + date.toUTCString();

	document.cookie = name + '=' + value + ';' + expires + ';path=/';

}

function getCookie(name) {

	name = name + '=';

	var decodedCookie = decodeURIComponent(document.cookie);

	var parts = decodedCookie.split(';');

	for (var i = 0; i < parts.length; i++) {

		var part = parts[i];

		while (part.charAt(0) === ' ') {
			part = part.substring(1);
		}

		if (part.indexOf(name) === 0) {
			return part.substring(name.length, part.length);
		}

	}

	return '';

}