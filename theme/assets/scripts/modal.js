Twee.addModule('modals', 'html', function($, wrapper) {

	$('[data-modal]').click(function(e) {

		var modal = $(this).data('modal');

		if (modal) {
			$('#modal_' + modal).trigger('open');
		}

	});

	$('a[href^="#modal_"]').click(function(e) {
		$($(this).attr('href')).trigger('open');
		e.preventDefault();
	});

	wrapper.on('close', '.modal_box', function() {
		$(this).removeClass('is_visible');
		Twee.unlockScroll();
	});

	wrapper.on('open', '.modal_box', function() {
		$(this).addClass('is_visible');
		Twee.lockScroll();
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

	wrapper.on('keyup', function(e) {
		if (e.which === 27) {
			$('.modal_box').trigger('close');
		}
	});

});