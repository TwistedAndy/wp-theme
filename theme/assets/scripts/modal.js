/* Modal windows */

jQuery(function($) {

	$('[data-modal]').click(function(e) {

		var modal = $(this).data('modal');

		if (modal) {
			$('#modal_' + modal).trigger('show');
		}

	});

	$('.modal_box').each(function() {

		var wrapper = $(document.body);

		var modal = $(this);

		modal.on('show', function() {
			modal.addClass('is_visible');
			wrapper.addClass('is_locked');
		});

		modal.on('close', function() {
			modal.removeClass('is_visible');
			wrapper.removeClass('is_locked');
		});

		modal.click(function(e) {
			modal.trigger('close');
		});

		modal.find('.modal').click(function(e) {
			e.stopPropagation();
		});

		$('.close', modal).click(function() {
			modal.trigger('close');
		});

		$(document).keyup(function(e) {
			if (e.which === 27) {
				modal.trigger('close');
			}
		});

	});


});