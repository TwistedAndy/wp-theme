jQuery(document.body).on('tw_init', function(e, $) {

	var wrapper = $(document.body);

	$('[data-modal]').click(function(e) {

		var modal = $(this).data('modal');

		if (modal) {
			$('#modal_' + modal).trigger('show');
		}

	});

	$('a[href^="#modal_"]').click(function(e) {
		$($(this).attr('href')).trigger('show');
		e.preventDefault();
	});

	wrapper.on('close', '.modal_box', function() {
		$(this).removeClass('is_visible');
		unlockScroll();
	});

	wrapper.on('show', '.modal_box', function() {
		$(this).addClass('is_visible');
		lockScroll();
	});

	wrapper.on('click', '.modal_box', function() {
		$(this).trigger('close');
	});

	wrapper.on('click', '.modal_box .modal', function(e) {
		if ($(e.target).is('.close, [data-close]')) {
			$(this).trigger('close');
		} else {
			e.stopPropagation();
		}
	});

	$(document).keyup(function(e) {
		if (e.which === 27) {
			$('.modal_box').trigger('close');
		}
	});

});