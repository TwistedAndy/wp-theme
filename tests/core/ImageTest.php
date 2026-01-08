<?php

/**
 * Image Library Integration Tests
 */
class ImageTest extends WP_UnitTestCase {

	protected static int $image_id;

	protected static string $upload_dir;

	/**
	 * Class setup: Create a real test image.
	 */
	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();

		$uploaddir = wp_upload_dir();
		self::$upload_dir = $uploaddir['basedir'];

		$filename = 'test-image.jpg';
		$file = self::$upload_dir . '/' . $filename;

		// Create 1000x800 image for resizing tests
		if (function_exists('imagecreatetruecolor')) {
			$im = imagecreatetruecolor(1000, 800);
			imagejpeg($im, $file);
			imagedestroy($im);
		} else {
			file_put_contents($file, 'dummy content');
		}

		self::$image_id = self::factory()->attachment->create_upload_object($file);
	}

	/**
	 * Class teardown: Clean up attachment and cache directory.
	 */
	public static function tear_down_after_class(): void
	{
		wp_delete_attachment(self::$image_id, true);

		$cache_dir = self::$upload_dir . '/cache/';

		if (is_dir($cache_dir)) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

			foreach ($files as $fileinfo) {
				$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
				@$todo($fileinfo->getRealPath());
			}

			@rmdir($cache_dir);
		}

		parent::tear_down_after_class();
	}

	/**
	 * Test setup: Define constants and load library.
	 */
	public function set_up(): void
	{
		parent::set_up();

		if (!defined('TW_CACHE')) {
			define('TW_CACHE', false);
		}
		if (!defined('TW_THEME_WIDTH')) {
			define('TW_THEME_WIDTH', 1420);
		}
		if (!defined('TW_THEME_GAP')) {
			define('TW_THEME_GAP', 20);
		}
		if (!defined('TW_HOME')) {
			define('TW_HOME', get_site_url());
		}
		if (!defined('TW_FOLDER')) {
			$url = parse_url(TW_HOME);
			define('TW_FOLDER', (is_array($url) && !empty($url['path'])) ? $url['path'] : '');
		}

		if (!function_exists('tw_image')) {
			require_once TW_ROOT . 'includes/core/image.php';
		}

		// Reset internal cache
		tw_app_set('tw_image_sizes', null);

		// Clean global sizes for isolation
		global $_wp_additional_image_sizes;
		$keys = [
			'test-size',
			'test-thumb',
			'test-hidden',
			'custom-tiny',
			'custom-medium',
			'custom-large',
			'srcset-small',
			'srcset-medium',
			'explicit-size',
			'mock-size',
			'mock'
		];
		foreach ($keys as $key) {
			if (isset($_wp_additional_image_sizes[$key])) {
				unset($_wp_additional_image_sizes[$key]);
			}
		}
	}

	/**
	 * Test auto-size fallback when keys are missing.
	 */
	public function test_auto_size_fallback_default(): void
	{
		update_option('medium_size_w', 300);
		update_option('medium_size_h', 300);

		// Fallback to 'medium'
		$attr = ['sizes' => []];
		$output = tw_image(self::$image_id, 'auto', '', '', $attr);

		$this->assertStringContainsString('width="300"', $output);
	}

	/**
	 * Test breakpoint logic: explicit width, padding, and pixel values.
	 */
	public function test_breakpoint_logic_and_pixel_values(): void
	{
		// 1. Explicit cards_width, padding, and value > 100
		tw_image_sizes(['explicit-size' => ['width' => 500, 'height' => 500]]);

		$attr_explicit = [
			'cards_width'   => 2000,
			'cards_padding' => 50,
			'sizes'         => ['dt' => 500]
		];

		$out_explicit = tw_image(self::$image_id, 'auto', '', '', $attr_explicit);
		$this->assertStringContainsString('<img', $out_explicit);

		// 2. VW calculation logic
		$attr_vw = [
			'cards_width' => 1000,
			'sizes'       => ['dt' => '50vw']
		];
		$out_vw = tw_image(self::$image_id, 'auto', '', '', $attr_vw);
		$this->assertStringContainsString('<img', $out_vw);
	}

	/**
	 * Test breakpoint logic: units (px, %, vw) and columns.
	 */
	public function test_breakpoint_calculation_logic(): void
	{
		$cards_width = 1420;
		$cards_gap = 20;

		// 1. Numeric > 100 (Pixels)
		$attr_pixels = ['sizes' => ['dt' => 500]];
		$out_pixels = tw_image(self::$image_id, 'auto', '', '', $attr_pixels);
		$this->assertStringContainsString('width="', $out_pixels);

		// 2. Numeric <= 12 (Columns)
		$attr_cols = ['sizes' => ['dt' => 4], 'cards_width' => $cards_width, 'cards_gap' => $cards_gap];
		$out_cols = tw_image(self::$image_id, 'auto', '', '', $attr_cols);
		$this->assertStringContainsString('<img', $out_cols);

		// 3. Numeric 13-100 (Percentage)
		$attr_num_pct = ['sizes' => ['dt' => 50], 'cards_width' => $cards_width];
		$out_num_pct = tw_image(self::$image_id, 'auto', '', '', $attr_num_pct);
		$this->assertStringContainsString('<img', $out_num_pct);

		// 4. String 'px'
		$attr_str_px = ['sizes' => ['dt' => '600px']];
		$out_str_px = tw_image(self::$image_id, 'auto', '', '', $attr_str_px);
		$this->assertStringContainsString('<img', $out_str_px);

		// 5. String '%'
		$attr_str_pct = ['sizes' => ['dt' => '25%'], 'cards_width' => $cards_width];
		$out_str_pct = tw_image(self::$image_id, 'auto', '', '', $attr_str_pct);
		$this->assertStringContainsString('<img', $out_str_pct);

		// 6. String 'vw'
		$attr_str_vw = ['sizes' => ['dt' => '100vw'], 'cards_width' => $cards_width];
		$out_str_vw = tw_image(self::$image_id, 'auto', '', '', $attr_str_vw);
		$this->assertStringContainsString('<img', $out_str_vw);
	}

	/**
	 * Test link generation: permalinks, titles, and relative paths.
	 */
	public function test_image_link_generation_logic(): void
	{
		// Permalink logic
		$post = get_post(self::$image_id);
		$attr_link = ['link' => 'url'];
		$out_link = tw_image($post, 'thumbnail', '', '', $attr_link);

		$permalink = get_permalink(self::$image_id);
		$relative_link = str_replace(TW_HOME, '', $permalink);

		$this->assertStringContainsString('href="' . $relative_link . '"', $out_link);

		// Alt Tags
		$attr_alt_title = ['link' => 'full', 'alt' => 'Fallback Title'];
		$out_alt_title = tw_image(self::$image_id, 'thumbnail', '', '', $attr_alt_title);
		$this->assertStringContainsString('title="Fallback Title"', $out_alt_title);

		// Link Attributes
		$attr_class_title = ['link' => 'full', 'link_class' => 'link_test_class', 'link_title' => 'Link Title', 'link_target' => '_blank'];
		$out_class_title = tw_image(self::$image_id, 'thumbnail', '', '', $attr_class_title);
		$this->assertStringContainsString('class="link_test_class"', $out_class_title);
		$this->assertStringContainsString('title="Link Title"', $out_class_title);
		$this->assertStringContainsString('target="_blank"', $out_class_title);
	}

	/**
	 * Test tw_image_link inputs: Objects, Arrays, IDs, External URLs.
	 */
	public function test_tw_image_link_variations(): void
	{
		$post = get_post(self::$image_id);

		// 1. Attachment Object
		$link_obj = tw_image_link($post, 'full');
		$this->assertStringContainsString('test-image.jpg', $link_obj);

		// 2. Array with 'ID'
		$array_input_no_sizes = ['ID' => self::$image_id];
		$link_arr = tw_image_link($array_input_no_sizes, 'full');
		$this->assertStringContainsString('test-image.jpg', $link_arr);

		$array_input_no_sizes = ['id' => self::$image_id];
		$link_arr = tw_image_link($array_input_no_sizes, 'full');
		$this->assertStringContainsString('test-image.jpg', $link_arr);

		// 3. Empty/Null
		$this->assertEmpty(tw_image_link(0));
		$this->assertEmpty(tw_image_link(null));

		// 4. Array Size (On-the-fly resizing)
		if (function_exists('imagecreatetruecolor')) {
			$link = tw_image_link(self::$image_id, [155, 155]);
			$this->assertStringContainsString('/cache/', $link);
			$this->assertStringContainsString('155x155', $link);
		}

		// 5. Invalid Array
		$array_input_no_sizes = ['random_key' => self::$image_id];
		$link_arr = tw_image_link($array_input_no_sizes, 'full');
		$this->assertEmpty($link_arr);

		// 6. Post Object (Thumbnail retrieval)
		$parent_post_id = self::factory()->post->create();
		update_post_meta($parent_post_id, '_thumbnail_id', self::$image_id);
		$parent_post = get_post($parent_post_id);

		$link_from_post = tw_image_link($parent_post, 'full');
		$this->assertStringContainsString('test-image.jpg', $link_from_post);

		wp_delete_post($parent_post_id, true);

		// 7. External URLs
		$external_url = 'https://external-cdn.com/remote-image.jpg';
		$original_file = get_post_meta(self::$image_id, '_wp_attached_file', true);
		update_post_meta(self::$image_id, '_wp_attached_file', $external_url);

		$remote_link = tw_image_link(self::$image_id, 'full');
		$this->assertSame($external_url, $remote_link);
		update_post_meta(self::$image_id, '_wp_attached_file', $original_file);

		// 8. Test string URLs (HTTP and Protocol-relative)
		$http_url = 'https://example.com/image.jpg';
		$this->assertSame($http_url, tw_image_link($http_url, 'full'));

		$proto_url = '//example.com/image.jpg';
		$this->assertSame($proto_url, tw_image_link($proto_url, 'full'));

		// 9. Cover fallback for invalid non-string/non-array size
		$this->assertStringContainsString('test-image.jpg', tw_image_link(self::$image_id, false));

	}

	/**
	 * Test image link generation when the file path is stored as an absolute path.
	 */
	public function test_tw_image_link_absolute_path(): void
	{
		// 1. Hook into upload_dir to force consistent Unix-style paths, avoiding Windows slash issues
		$filter_upload_dir = function($uploads) {
			$uploads['basedir'] = '/tmp/wp-content/uploads';
			$uploads['baseurl'] = 'http://example.org/wp-content/uploads';

			return $uploads;
		};

		add_filter('upload_dir', $filter_upload_dir);

		// 2. Define the absolute path and expected URL based on our mocked dir
		$mock_basedir = '/tmp/wp-content/uploads';
		$mock_baseurl = 'http://example.org/wp-content/uploads';
		$absolute_file = $mock_basedir . '/absolute-image.jpg';
		$expected_link = $mock_baseurl . '/absolute-image.jpg';

		// 3. Set the meta to match the mocked basedir exactly
		update_post_meta(self::$image_id, '_wp_attached_file', $absolute_file);

		// 4. Run the function (it will call wp_upload_dir() and trigger our filter)
		$link = tw_image_link(self::$image_id, 'full', true);

		// 5. Assertions
		$this->assertEquals($expected_link, $link);

		// 6. Cleanup
		remove_filter('upload_dir', $filter_upload_dir);
		update_post_meta(self::$image_id, '_wp_attached_file', 'test-image.jpg');
	}

	/**
	 * Test input normalization: ACF arrays and Post Objects.
	 */
	public function test_input_normalization_coverage(): void
	{
		// 1. ACF Array (ID/id)
		$acf_input = ['ID' => self::$image_id];
		$out_acf = tw_image($acf_input, 'full');
		$this->assertStringContainsString('test-image.jpg', $out_acf);

		$acf_input = ['id' => self::$image_id];
		$out_acf = tw_image($acf_input, 'full');
		$this->assertStringContainsString('test-image.jpg', $out_acf);

		// 2. Post Object (Thumbnail ID)
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, '_thumbnail_id', self::$image_id);
		$post = get_post($post_id);

		$out_post = tw_image($post, 'full');

		$this->assertStringContainsString('test-image.jpg', $out_post);
	}

	/**
	 * Test size registration.
	 */
	public function test_tw_image_sizes_registration(): void
	{
		$sizes = [
			'test-thumb' => [
				'width'  => 100,
				'height' => 100,
				'crop'   => true,
				'thumb'  => true
			],
			'test-width' => [
				'height' => 100
			]
		];
		$registered = tw_image_sizes($sizes);

		$this->assertArrayHasKey('test-thumb', $registered);
		$this->assertEquals(100, $registered['test-thumb']['width']);

		$size = tw_image_sizes('test-thumb');
		$this->assertEquals(100, $size['width']);
		$this->assertEquals(100, $size['height']);
		$this->assertNotEmpty($size['crop']);

		// Test set_post_thumbnail_size trigger
		global $_wp_additional_image_sizes;

		$this->assertEquals(100, $_wp_additional_image_sizes['post-thumbnail']['width']);
		$this->assertEquals(100, $_wp_additional_image_sizes['post-thumbnail']['height']);

		// Test empty width processin
		$sizes_missing_width = [];
		$registered_missing = tw_image_sizes($sizes_missing_width);

		$this->assertEquals(0, $registered_missing['test-width']['width']);
	}

	/**
	 * Test retrieval of size configuration.
	 */
	public function test_tw_image_size_retrieval(): void
	{
		tw_image_sizes(['test-size' => ['width' => 500, 'height' => 300]]);

		$size = tw_image_size('test-size');
		$this->assertEquals(500, $size['width']);
		$this->assertEquals(300, $size['height']);

		// Non-existing size
		$size = tw_image_size('non-existing', -1);
		$this->assertEquals(0, $size['width']);
		$this->assertEquals(0, $size['height']);
	}

	/**
	 * Test basic HTML output.
	 */
	public function test_tw_image_basic_output(): void
	{
		$output = tw_image(self::$image_id, 'full');
		$this->assertStringContainsString('width="1000"', $output);
		$this->assertStringContainsString('src=', $output);
	}

	/**
	 * Test auto calculation for columns.
	 */
	public function test_tw_image_auto_calculation(): void
	{
		tw_image_sizes([
			'custom-tiny'   => ['width' => 100, 'height' => 100],
			'custom-medium' => ['width' => 600, 'height' => 600],
			'custom-large'  => ['width' => 1000, 'height' => 1000],
		]);

		$attr_col = ['sizes' => ['dt' => 4], 'cards_gap' => 20];
		$out_col = tw_image(self::$image_id, 'auto', '', '', $attr_col);
		$this->assertStringContainsString('<img', $out_col);
	}

	/**
	 * Test meta injection (Alt) and container wrapping.
	 */
	public function test_tw_image_meta_and_injection(): void
	{
		update_post_meta(self::$image_id, '_wp_attachment_image_alt', 'Custom Meta Alt');

		$attr = ['before' => '<div>', 'after' => '</div>', 'link' => 'http://ex.com'];
		$output = tw_image(self::$image_id, 'full', '', '', $attr);

		$this->assertStringContainsString('alt="Custom Meta Alt"', $output);
		$this->assertStringContainsString('<a href="http://ex.com"', $output);

		// Verify wrapper structure: <a...><div><img.../></div></a>
		$linkPos = strpos($output, '<a href=');
		$divPos = strpos($output, '<div>');
		$this->assertLessThan($divPos, $linkPos, 'The <a> tag should open before the <div> tag.');

		$linkClosePos = strpos($output, '</a>');
		$divClosePos = strpos($output, '</div>');
		$this->assertLessThan($linkClosePos, $divClosePos, 'The </div> tag should close before the </a> tag.');
	}

	/**
	 * Test invalid inputs return empty string.
	 */
	public function test_tw_image_invalid_input(): void
	{
		$this->assertEmpty(tw_image(999999));
	}

	/**
	 * Test external link logic in tw_image_link.
	 */
	public function test_tw_image_link_external(): void
	{
		if (!function_exists('imagecreatetruecolor')) {
			$this->markTestSkipped('GD missing');
		}

		$acf = ['ID' => self::$image_id, 'sizes' => ['custom' => 'http://img.jpg']];
		$this->assertEquals('http://img.jpg', tw_image_link($acf, 'custom'));
	}

	/**
	 * Test helper: tw_image_attribute (background/mask).
	 */
	public function test_tw_image_attribute(): void
	{
		$attr = tw_image_attribute(self::$image_id, 'full', false);
		$this->assertStringContainsString('style="background-image: url(', $attr);

		$attr = tw_image_attribute(self::$image_id, 'full', '--mask-property');
		$this->assertStringContainsString('--mask-property', $attr);

		$attr = tw_image_attribute([], 'full', false);
		$this->assertEmpty('', $attr);
	}

	/**
	 * Test custom HTML attributes.
	 */
	public function test_tw_image_custom_attributes(): void
	{
		$attr = ['class' => 'cls', 'data-id' => '123'];
		$out = tw_image(self::$image_id, 'full', '', '', $attr);

		$this->assertStringContainsString('class="cls"', $out);
		$this->assertStringContainsString('data-id="123"', $out);
	}

	/**
	 * Test loading attribute (lazy/eager).
	 */
	public function test_tw_image_loading_logic(): void
	{
		// Default: lazy
		$out = tw_image(self::$image_id, 'full');
		$this->assertStringContainsString('loading="lazy"', $out);

		// Explicit: false
		$out_e = tw_image(self::$image_id, 'full', '', '', ['loading' => false]);
		$this->assertStringNotContainsString('loading=', $out_e);
	}

	/**
	 * Test dimension calculations (tw_image_calculate).
	 */
	public function test_tw_image_calculate(): void
	{
		$calc = tw_image_calculate(1000, 800, 500, 400, true);
		$this->assertEquals(500, $calc['width']);
		$this->assertEquals(400, $calc['height']);
	}

	/**
	 * Test hard cropping (exact ratio and square).
	 */
	public function test_calculate_basic_crop(): void
	{
		// Exact ratio
		$result = tw_image_calculate(1000, 800, 500, 400, true);
		$this->assertSame(500, $result['width']);
		$this->assertSame(400, $result['height']);

		// Square
		$result_sq = tw_image_calculate(1000, 800, 500, 500, true);
		$this->assertSame(500, $result_sq['width']);
		$this->assertSame(500, $result_sq['height']);
	}

	/**
	 * Test cropping upscale protection.
	 */
	public function test_calculate_crop_upscale_protection(): void
	{
		// Target > Source: Cap at source
		$result = tw_image_calculate(100, 100, 200, 200, true);
		$this->assertEquals(100, $result['width']);
		$this->assertEquals(100, $result['height']);

		// One dimension > Source: Cap height, keep width
		$result_mixed = tw_image_calculate(100, 100, 50, 200, true);
		$this->assertEquals(50, $result_mixed['width']);
		$this->assertEquals(100, $result_mixed['height']);
	}

	/**
	 * Test cropping with aspect ratio maintenance.
	 */
	public function test_calculate_crop_with_aspect_maintenance(): void
	{
		// Width clips to 100. Height becomes 50 to maintain 2:1 ratio.
		$result = tw_image_calculate(100, 100, 200, 100, true, true);
		$this->assertEquals(100, $result['width']);
		$this->assertEquals(50, $result['height']);

		$result = tw_image_calculate(100, 100, 100, 200, true, true);
		$this->assertEquals(50, $result['width']);
		$this->assertEquals(100, $result['height']);
	}

	/**
	 * Test soft resizing: Landscape.
	 */
	public function test_calculate_soft_resize_landscape(): void
	{
		// 2:1 > 1:1. Fits by width.
		$result = tw_image_calculate(1000, 500, 200, 200, false);
		$this->assertEquals(200, $result['width']);
		$this->assertEquals(100, $result['height']);
	}

	/**
	 * Test soft resizing: Portrait.
	 */
	public function test_calculate_soft_resize_portrait(): void
	{
		// 0.5 < 1:1. Fits by height.
		$result = tw_image_calculate(500, 1000, 200, 200, false);
		$this->assertEquals(100, $result['width']);
		$this->assertEquals(200, $result['height']);
	}

	/**
	 * Test soft resizing: No upscale.
	 */
	public function test_calculate_soft_resize_no_upscale(): void
	{
		// Source fits in target. Return source.
		$result = tw_image_calculate(100, 50, 200, 200, false);
		$this->assertEquals(100, $result['width']);
		$this->assertEquals(50, $result['height']);
	}

	/**
	 * Test sanitization (zero/negative dims).
	 */
	public function test_calculate_sanitization(): void
	{
		$result = tw_image_calculate(0, 0, 100, 100, true);
		$this->assertEquals(100, $result['width']);
		$this->assertEquals(100, $result['height']);
	}

	/**
	 * Test soft resizing: Missing dimensions.
	 */
	public function test_calculate_soft_resize_missing_dimensions(): void
	{
		// Missing height
		$result_no_h = tw_image_calculate(1000, 800, 500, 0, false);
		$this->assertEquals(500, $result_no_h['width']);
		$this->assertEquals(400, $result_no_h['height']);

		// Missing width
		$result_no_w = tw_image_calculate(1000, 800, 0, 400, false);
		$this->assertEquals(500, $result_no_w['width']);
		$this->assertEquals(400, $result_no_w['height']);

		// Both missing
		$result_none = tw_image_calculate(1000, 800, 0, 0, false);
		$this->assertEquals(1000, $result_none['width']);
		$this->assertEquals(800, $result_none['height']);
	}

	/**
	 * Test soft resizing: Boundary checks.
	 */
	public function test_calculate_soft_resize_upscale_protection(): void
	{
		$result = tw_image_calculate(1000, 500, 1200, 800, false);
		$this->assertEquals(500, $result['height']);
		$this->assertEquals(1000, $result['width']);
	}

	/**
	 * Test cache clearing.
	 */
	public function test_tw_image_clear_cache(): void
	{
		$path = static::$upload_dir . '/cache/thumbs_temp/';

		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}

		$file = $path . self::$image_id . '_dummy.webp';
		file_put_contents($file, 't');

		tw_image_clear(self::$image_id);

		$this->assertFileDoesNotExist($file);
		@rmdir($path);
	}

	/**
	 * Test cache clearing: Missing directory.
	 */
	public function test_tw_image_clear_no_directory(): void
	{
		$dir = wp_upload_dir();
		$base = $dir['basedir'] . '/cache/';

		// Ensure dir missing
		if (is_dir($base)) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($files as $fileinfo) {
				$fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
			}
			rmdir($base);
		}

		tw_image_clear(99999);
		$this->assertDirectoryDoesNotExist($base);
	}

	/**
	 * Test srcset generation.
	 */
	public function test_tw_image_srcset_generation(): void
	{
		if (!function_exists('imagecreatetruecolor')) {
			$this->markTestSkipped('GD missing');
		}

		tw_image_sizes(['srcset-small' => ['width' => 400], 'srcset-medium' => ['width' => 800]]);

		// Basic srcset
		$attr = ['sizes' => ['dt' => '100%'], 'srcset' => ['srcset-small', 'srcset-medium']];
		$out = tw_image(self::$image_id, 'full', '', '', $attr);
		$this->assertStringContainsString('400w', $out);
		$this->assertStringContainsString('800w', $out);

		// Media Query
		$attr = ['sizes' => ['dl' => '100%'], 'srcset' => ['srcset-small', 'srcset-medium']];
		$out = tw_image(self::$image_id, 'full', '', '', $attr);
		$this->assertStringContainsString('(min-width: ' . TW_THEME_WIDTH . 'px) 100%', $out);

		// Invalid sizes fallback
		$attributes = tw_image_srcset(self::$image_id, ['sizes' => ['none']]);
		$this->assertArrayHasKey('sizes', $attributes);

		// Merge breakpoints
		$attributes = tw_image_srcset(self::$image_id, ['sizes' => ['tl' => 800, 'ds' => 1000, 'dl' => 1000, 'dt' => TW_THEME_WIDTH]]);
		$this->assertStringContainsString('800px, (max-width: ' . (TW_THEME_WIDTH - 1) . 'px) 1000px, (min-width: ' . TW_THEME_WIDTH . 'px) ' . TW_THEME_WIDTH . 'px', $attributes['sizes']);
	}

	/**
	 * Test system option update on size registration.
	 */
	public function test_tw_image_sizes_system_update(): void
	{
		update_option('medium_size_w', 300);

		$sizes = ['medium' => ['width' => 450, 'height' => 450, 'crop' => true]];
		tw_image_sizes($sizes);

		$this->assertEquals(450, get_option('medium_size_w'));
	}

	/**
	 * Test metadata fallback.
	 */
	public function test_tw_image_link_existing_metadata(): void
	{
		$meta = wp_get_attachment_metadata(self::$image_id);
		$meta['sizes']['mock'] = ['file' => 'mock.jpg', 'width' => 500, 'height' => 500];
		wp_update_attachment_metadata(self::$image_id, $meta);

		tw_image_sizes(['mock' => ['width' => 500, 'height' => 500]]);

		$link = tw_image_link(self::$image_id, 'mock');
		$this->assertStringContainsString('mock.jpg', $link);
	}

	/**
	 * Test local assets logic.
	 */
	public function test_tw_image_link_assets(): void
	{
		$path = TW_ROOT . 'assets/images/test.png';

		if (!is_dir(dirname($path))) {
			mkdir(dirname($path), 0777, true);
		}

		file_put_contents($path, 't');

		$link = tw_image_link('test.png', 'thumbnail');
		$this->assertNotEmpty($link);
		$this->assertStringContainsString('test.png', $link);

		// Resize edge cases
		$resize_link = tw_image_resize('', 'full');
		$this->assertEmpty($resize_link);

		$full_link = tw_image_resize($link, 'non-existing-size', 0);
		$this->assertStringContainsString('assets/images/test.png', $full_link);

		$svg_link = tw_image_resize(TW_ROOT . 'assets/images/test.svg', 'full', 0);
		$this->assertStringContainsString('assets/images/test.svg', $svg_link);

		unlink($path);
	}

	/**
	 * Test hook registration: delete_attachment -> tw_image_clear.
	 */
	public function test_delete_attachment_hook_triggers_clear(): void
	{
		$dir = wp_get_upload_dir();
		$cache_path = $dir['basedir'] . '/cache/thumbs_999x999/';

		// Create temp attachment
		$filename = self::$upload_dir . '/hook-test.jpg';

		if (function_exists('imagecreatetruecolor')) {
			$im = imagecreatetruecolor(10, 10);
			imagejpeg($im, $filename);
			imagedestroy($im);
		} else {
			file_put_contents($filename, 'dummy');
		}

		$temp_attachment_id = self::factory()->attachment->create_upload_object($filename);

		// Create mock cache file
		if (!is_dir($cache_path)) {
			mkdir($cache_path, 0777, true);
		}

		$mock_cache_file = $cache_path . $temp_attachment_id . '_test_thumb.webp';

		file_put_contents($mock_cache_file, 'cache content');

		$this->assertFileExists($mock_cache_file);

		// Trigger hook
		wp_delete_attachment($temp_attachment_id, true);

		// Verify deletion
		$this->assertFileDoesNotExist($mock_cache_file, 'The cache file should be deleted via the delete_attachment hook.');

		@rmdir($cache_path);
	}

}