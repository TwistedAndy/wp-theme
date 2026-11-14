<?php
/**
 * ACF Flexible Content Preview Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Add a thumbnail for a layout
 */
function tw_preview_filter_thumbnail(string $thumbnail, array $field, array $layout): string
{
	$file = 'assets/preview/' . $layout['name'] . '.webp';

	$preview = TW_ROOT . $file;

	if (is_readable($preview)) {
		return TW_URL . $file . '?ver=' . filemtime($preview);
	}

	return $thumbnail;
}

add_filter('acfe/flexible/thumbnail', 'tw_preview_filter_thumbnail', 10, 3);

/**
 * Run the code below only on the local server
 */
$host = (string) parse_url(TW_HOME, PHP_URL_HOST);

if (!str_ends_with($host, '.test') and !str_ends_with($host, '.localhost') and wp_get_environment_type() !== 'local') {
	return;
}


/**
 * Add an icon to generate a preview
 */
function tw_preview_filter_icons(array $icons): array
{
	$new = [
		'thumbnail' => '<a class="acf-icon small light acfe-flexible-icon acf-js-tooltip dashicons dashicons-camera-alt" href="#" data-name="generate-preview" title="' . __('Generate a preview', 'acf') . '"></a>'
	];

	return array_merge($new, $icons);
}

add_filter('acfe/flexible/layouts/icons', 'tw_preview_filter_icons', 50);


/**
 * Save a preview that the browser made from the block preview
 */
function tw_preview_action_preview(): void
{
	if (!current_user_can('edit_posts') or !check_ajax_referer('twee_generate_preview', 'nonce', false)) {
		wp_send_json(['success' => 0, 'message' => 'You are not allowed to generate previews']);
	}

	$layout = isset($_POST['layout']) ? (string) wp_unslash($_POST['layout']) : '';

	if (!preg_match('/^[a-z0-9_]+$/', $layout)) {
		wp_send_json(['success' => 0, 'message' => 'The layout name is not correct']);
	}

	if (empty($_FILES['file']['tmp_name']) or $_FILES['file']['error'] !== UPLOAD_ERR_OK or !is_uploaded_file($_FILES['file']['tmp_name'])) {
		wp_send_json(['success' => 0, 'message' => 'The image is not received']);
	}

	$image = getimagesize($_FILES['file']['tmp_name']);

	if (($image[2] ?? 0) !== IMAGETYPE_WEBP) {
		wp_send_json(['success' => 0, 'message' => 'The image is not a WebP file']);
	}

	$folder = TW_ROOT . 'assets/preview';

	if (!is_dir($folder)) {
		mkdir($folder, 0755, true);
	}

	$file = 'assets/preview/' . $layout . '.webp';

	if (!move_uploaded_file($_FILES['file']['tmp_name'], TW_ROOT . $file)) {
		wp_send_json(['success' => 0, 'message' => 'Unable to save ' . $file]);
	}

	wp_send_json(['success' => 1, 'message' => 'Saved: ' . $file . ' (' . $image[0] . 'x' . $image[1] . ')']);
}

add_action('wp_ajax_twee_generate_preview', 'tw_preview_action_preview');


/**
 * Include admin scripts
 *
 * The browser renders the block preview in a hidden frame 1280 pixels wide,
 * makes a WebP image of the block, and sends it to the server.
 */
function tw_preview_action_scripts(): void
{
	tw_asset_enqueue('screen', true);

	?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const $ = jQuery;
			const size = 1280;

			function wait(time) {
				return new Promise(function(resolve) {
					setTimeout(resolve, time);
				});
			}

			function readFile(blob) {
				return new Promise(function(resolve, reject) {
					const reader = new FileReader();

					reader.onload = function() {
						resolve(reader.result);
					};

					reader.onerror = reject;
					reader.readAsDataURL(blob);
				});
			}

			// The library does not embed the mask images of the pseudo elements, such as the icons,
			// so the stylesheet rules get the images as data URLs
			async function embedMasks(contents) {
				const rules = [];
				const images = {};

				function collect(list) {
					for (const rule of list) {
						if (rule.cssRules) {
							collect(rule.cssRules);
						}

						if (rule.style) {
							rules.push(rule);
						}
					}
				}

				for (const sheet of contents.styleSheets) {
					try {
						collect(sheet.cssRules);
					} catch (error) {
						// The browser does not give the rules of a stylesheet from another domain
					}
				}

				for (const rule of rules) {
					for (const name of ['-webkit-mask-image', 'mask-image', '--mask-image']) {
						const value = rule.style.getPropertyValue(name);

						if (!value.includes('url(') || value.includes('data:')) {
							continue;
						}

						let result = value;

						for (const match of value.matchAll(/url\(\s*['"]?([^'")]+)['"]?\s*\)/g)) {
							const url = new URL(match[1], rule.parentStyleSheet.href || contents.baseURI).href;

							images[url] = images[url] || fetch(url).then(function(response) {
								return response.blob();
							}).then(readFile).catch(function() {
								return '';
							});

							const image = await images[url];

							if (image) {
								result = result.replace(match[0], 'url("' + image + '")');
							}
						}

						rule.style.setProperty(name, result, rule.style.getPropertyPriority(name));
					}
				}
			}

			async function makePreview(source, previewId) {
				const frame = document.createElement('iframe');
				const field = document.createElement('textarea');

				frame.style.cssText = 'position: fixed; top: 0; left: -' + (size * 2) + 'px; width: ' + size + 'px; height: ' + size + 'px; border: 0;';
				document.body.appendChild(frame);

				try {
					field.innerHTML = source.text();

					// A new frame ID stops the resize messages for the visible preview
					const html = field.value.split(previewId).join(previewId + '_capture');
					const contents = frame.contentWindow.document;

					contents.open();
					contents.write(html);
					contents.close();

					// Wait for the images and the styles, but not more than 10 seconds
					for (let time = 0; contents.readyState !== 'complete' && time < 10000; time += 100) {
						await wait(100);
					}

					await contents.fonts.ready;
					await embedMasks(contents);

					// The block scripts, such as carousels, start after the page loads
					await wait(1000);

					const main = contents.querySelector('main') || contents.body;
					const node = (main.children.length === 1) ? main.firstElementChild : main;
					const canvas = await modernScreenshot.domToCanvas(node, {
						scale: 1,
						backgroundColor: getComputedStyle(contents.body).backgroundColor
					});

					const result = document.createElement('canvas');

					result.width = canvas.width;
					result.height = Math.min(canvas.height, size);
					result.getContext('2d').drawImage(canvas, 0, 0);

					return await new Promise(function(resolve) {
						result.toBlob(resolve, 'image/webp', 0.8);
					});
				} finally {
					frame.remove();
				}
			}

			$(document.body).on('click', '[data-name="generate-preview"]', async function(event) {
				event.preventDefault();

				const button = $(this);
				const layout = button.closest('.layout');
				const preview = layout.children('.acfe-fc-placeholder').find('iframe').first();
				const source = preview.prev();

				if (!source.length) {
					alert('The preview of this block is not available');
					return;
				}

				button.removeClass('dashicons-camera-alt').addClass('dashicons-update');

				try {
					const image = await makePreview(source, preview.attr('id'));

					if (!image) {
						alert('Preview generation failed: the browser did not make a WebP image');
						return;
					}

					const data = new FormData();

					data.append('action', 'twee_generate_preview');
					data.append('nonce', '<?php echo esc_js(wp_create_nonce('twee_generate_preview')); ?>');
					data.append('layout', layout.data('layout'));
					data.append('file', image, layout.data('layout') + '.webp');

					const response = await fetch(ajaxurl, {
						method: 'POST',
						body: data,
						credentials: 'same-origin'
					}).then(function(response) {
						return response.json();
					});

					alert(response.message);
				} catch (error) {
					alert('Preview generation failed: ' + (error instanceof Error ? error.message : error));
				} finally {
					button.addClass('dashicons-camera-alt').removeClass('dashicons-update');
				}
			});
		});
	</script>
	<style>
		div.acfe-flexible-layout-thumbnail {
			height: auto;
			background-size: contain;
			background-color: #ffffff;
		}

		div.acfe-flexible-layout-thumbnail:before {
			content: '';
			display: block;
			position: relative;
			padding-bottom: 62.5%;
		}
	</style>
<?php }

add_action('acf/input/admin_enqueue_scripts', 'tw_preview_action_scripts', 20);
