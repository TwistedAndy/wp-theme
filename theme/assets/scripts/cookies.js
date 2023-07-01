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

function getCookieValue(name) {
	return getCookie(name).split('|') || [];
}

function setCookieValue(name, value) {

	if (!Array.isArray(value)) {
		value = [value];
	}

	value = value.filter(function(item) {
		return item;
	});

	return setCookie(name, value.join('|'), 365);

}

function addCookieValue(name, value) {

	var array = getCookieValue(name);

	array.push(value.toString());

	setCookieValue(name, array);

}

function removeCookieValue(name, value) {

	var array = getCookieValue(name);

	array = array.filter(function(item) {
		return item !== value;
	});

	setCookieValue(name, array);

}

function hasCookieValue(name, value) {

	var array = getCookieValue(name);

	return (array.indexOf(value) !== -1);

}