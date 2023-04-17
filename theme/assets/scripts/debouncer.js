function debouncer(fn, bufferInterval) {

	var timeout;

	return function() {
		clearTimeout(timeout);
		timeout = setTimeout(fn.apply.bind(fn, this, arguments), bufferInterval);
	};

}