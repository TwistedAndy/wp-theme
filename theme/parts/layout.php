<?php if (!defined('ABSPATH') or empty($preview_id) or empty($block)) return; ?>
<!DOCTYPE html>
<html <?php echo get_language_attributes('html'); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php tw_asset_print(); ?>
</head>
<body <?php body_class(); ?> style="overflow: hidden;">
<main class="tw">
	<?php echo tw_block_render($block); ?>
</main>
<?php tw_asset_print(); ?>
<script>

	function resizeObserver(element, callback) {

		let last = element.getBoundingClientRect();

		const observer = new ResizeObserver(function() {
			const data = element.getBoundingClientRect();
			if (data.height !== last.height || data.width !== last.width) {
				callback();
				last = data;
			}
		});

		observer.observe(element);

	}

	function triggerResize() {
		window.parent.postMessage({
			type: 'resize',
			frame: '<?php echo $preview_id; ?>',
			height: document.body.scrollHeight
		}, '*');
	}

	window.addEventListener('load', triggerResize);
	window.addEventListener('resize', triggerResize);

	document.querySelectorAll('main').forEach(function(section) {
		resizeObserver(section, triggerResize);
	});

	triggerResize();

</script>
</body>
</html>