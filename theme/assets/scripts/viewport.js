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

function setViewportVariables() {

	var styles = document.documentElement.style,
		footer = document.querySelector('.footer_box .footer');

	styles.setProperty('--vh', (window.innerHeight / 100) + 'px');
	styles.setProperty('--width-scrollbar', getScrollbarWidth() + 'px');

	if (footer) {
		styles.setProperty('--width-regular', footer.clientWidth + 'px');
	}

}

window.addEventListener('DOMContentLoaded', setViewportVariables);
window.addEventListener('orientationchange', setViewportVariables);
window.addEventListener('resize', setViewportVariables);
window.addEventListener('load', setViewportVariables);