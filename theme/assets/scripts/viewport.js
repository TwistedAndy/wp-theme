function lockScroll() {
	document.body.classList.add('is_locked');
	document.body.style.paddingRight = getScrollbarWidth() + 'px';
}

function unlockScroll() {
	document.body.classList.remove('is_locked');
	document.body.style.paddingRight = '0px';
}

function getScrollbarWidth() {

	var outer = document.createElement('div');
	var inner = document.createElement('div');

	outer.style.visibility = 'hidden';
	outer.style.overflow = 'scroll';
	outer.style.msOverflowStyle = 'scrollbar';
	outer.appendChild(inner);

	document.body.appendChild(outer);

	var scrollbarWidth = (outer.offsetWidth - inner.offsetWidth);

	outer.parentNode.removeChild(outer);

	return scrollbarWidth;

}

function fixViewportHeight() {
	document.documentElement.style.setProperty('--vh', (window.innerHeight / 100) + 'px');
}

window.addEventListener('DOMContentLoaded', fixViewportHeight);
window.addEventListener('orientationchange', fixViewportHeight);
window.addEventListener('resize', fixViewportHeight);
window.addEventListener('load', fixViewportHeight);