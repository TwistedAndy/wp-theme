document.addEventListener('rocket-DOMContentLoaded', initApp);
document.addEventListener('DOMContentLoaded', initApp);

window.addEventListener('rocket-load', initApp);
window.addEventListener('load', initApp);

function initApp() {

	if (typeof jQuery === 'function') {
		jQuery('[class*="_box"]').trigger('tw_init', [jQuery]);
	}

	let scrollbarWidth = parseInt(window.innerWidth - document.documentElement.clientWidth);

	if (isNaN(scrollbarWidth) || scrollbarWidth < 0) {
		scrollbarWidth = 0;
	}

	document.body.style.setProperty('--width-scrollbar', scrollbarWidth + 'px');

}

function runOnce(element, slug, timeout) {

	slug = slug || 'element';

	let key = 'tw_' + slug + '_loaded';

	if (timeout > 0) {
		setTimeout(function() {
			element[key] = false;
		}, timeout);
	}

	if (element[key]) {
		return true;
	} else {
		element[key] = true;
		return false;
	}

}

function runLater(fn, bufferInterval) {

	var timeout;

	return function() {
		clearTimeout(timeout);
		timeout = setTimeout(fn.apply.bind(fn, this, arguments), bufferInterval);
	};

}

function smoothScrollTo(element, speed) {

	var $ = jQuery,
		wrapper = $('html, body');

	speed = parseInt(speed) || 1000;

	element = $(element);

	if (element.length > 0) {

		var offset = element.offset().top - scrollOffset() - 20;

		if (element.attr('id')) {
			var scroll = wrapper.scrollTop();
			window.location.hash = element.attr('id');
			wrapper.scrollTop(scroll);
		}

		wrapper.stop().animate({
			'scrollTop': offset
		}, speed);

	}

}

function scrollOffset() {

	var header = jQuery('.header_box .header'),
		offset = header.height();

	if (document.body.classList.contains('admin-bar')) {

		var width = window.innerWidth;

		if (width <= 782 && width >= 600) {
			offset += 46;
		} else if (width > 782) {
			offset += 32;
		}

	}

	return offset;

}

function lockScroll() {
	document.body.classList.add('is_locked');
}

function unlockScroll() {
	document.body.classList.remove('is_locked');
}

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