function lockScroll() {
	document.body.classList.add('is_locked');
}

function unlockScroll() {
	document.body.classList.remove('is_locked');
}

function getScrollbarWidth() {

	var outer = document.createElement('div'),
		inner = document.createElement('div');

	outer.style.visibility = 'hidden';
	outer.style.overflow = 'scroll';
	outer.style.msOverflowStyle = 'scrollbar';
	outer.appendChild(inner);

	document.body.appendChild(outer);

	var scrollbarWidth = outer.offsetWidth - inner.offsetWidth;

	outer.parentNode.removeChild(outer);

	return scrollbarWidth;

}

function fixViewportHeight() {
	document.documentElement.style.setProperty('--vh', (window.innerHeight / 100) + 'px');
	document.documentElement.style.setProperty('--width-scrollbar', getScrollbarWidth() + 'px');
}

window.addEventListener('DOMContentLoaded', fixViewportHeight);
window.addEventListener('orientationchange', fixViewportHeight);
window.addEventListener('resize', fixViewportHeight);
window.addEventListener('load', fixViewportHeight);