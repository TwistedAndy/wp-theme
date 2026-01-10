<?php

/**
 * Asset Tests using WP Integration Suite
 * * Verifies the lifecycle of assets including registration, normalization,
 * enqueuing, and the output buffer injection process.
 */
class AssetTest extends WP_UnitTestCase {

	/**
	 * Setup: Clear asset state before each test to ensure isolation.
	 */
	public function set_up(): void
	{
		parent::set_up();

		tw_app_set('registered', [], 'assets');
		tw_app_set('enqueued', [], 'assets');
		tw_app_set('printed', [], 'assets');
		tw_app_set('localized', [], 'assets');
	}

	/**
	 * Helper to run initialization and clean up the buffer if the override constant is active.
	 * Prevents "Risky Test" warnings due to unclosed buffers.
	 */
	private function init_twee(): void
	{
		$level = ob_get_level();
		tw_asset_init();

		// If init started a buffer (due to TW_TEST_BUFFER_OVERRIDE), close it immediately.
		if (ob_get_level() > $level) {
			ob_end_clean();
		}
	}

	/**
	 * Verify actions are hooked with correct priorities.
	 */
	public function test_actions_hooks(): void
	{
		tw_asset_actions();

		$this->assertEquals(0, has_action('wp_head', 'tw_asset_init'));
		$this->assertEquals(5, has_action('wp_head', 'tw_asset_print'));
		$this->assertEquals(6, has_action('wp_head', 'tw_asset_placeholder'));
		$this->assertEquals(100, has_action('wp_footer', 'tw_asset_print'));
		$this->assertEquals(0, has_action('admin_enqueue_scripts', 'tw_asset_init'));
	}

	/**
	 * Test scanning of blocks/plugins and internal dependency resolution.
	 */
	public function test_init_scanning(): void
	{
		$block_dir = TW_ROOT . 'assets/build/blocks';
		$plugin_dir = TW_ROOT . 'assets/plugins/test-dependant';

		if (!is_dir($block_dir)) {
			mkdir($block_dir, 0777, true);
		}

		if (!is_dir($plugin_dir)) {
			mkdir($plugin_dir, 0777, true);
		}

		// Mock Block Asset
		file_put_contents($block_dir . '/mock-feature.css', '/* Block CSS */');

		// Mock Plugin Asset
		$config = "<?php return ['script' => 'app.js', 'deps' => ['style' => ['mock-feature_box']]];";
		file_put_contents($plugin_dir . '/index.php', $config);
		file_put_contents($plugin_dir . '/app.js', 'console.log("hi");');

		$this->init_twee();

		$registered = tw_app_get('registered', 'assets', []);

		// Assert Block registration logic
		$this->assertArrayHasKey('mock-feature_box', $registered);
		$this->assertEquals('tw_block_', $registered['mock-feature_box']['prefix']);

		// Assert Plugin & Dependency Resolution
		$deps = $registered['test-dependant']['deps']['style'];
		$this->assertContains('tw_block_mock-feature_box', $deps, 'Dependency should be prefixed automatically');

		// Assert WP Registration state
		$this->assertTrue(wp_style_is('tw_block_mock-feature_box', 'registered'));
		$this->assertTrue(wp_script_is('tw_test-dependant', 'registered'));

		// Cleanup
		unlink($block_dir . '/mock-feature.css');
		unlink($plugin_dir . '/index.php');
		unlink($plugin_dir . '/app.js');
		rmdir($plugin_dir);
	}

	/**
	 * Test validation logic: removal of integer keys or empty assets.
	 */
	public function test_init_validation(): void
	{
		$invalid_data = [
			0             => ['style' => 'http://example.com/int.css'],
			'empty-asset' => [],
			'valid-asset' => ['style' => 'http://example.com/valid.css']
		];

		tw_app_set('registered', $invalid_data, 'assets');

		$this->init_twee();

		$registered = tw_app_get('registered', 'assets', []);

		$this->assertArrayNotHasKey(0, $registered);
		$this->assertArrayNotHasKey('empty-asset', $registered);
		$this->assertArrayHasKey('valid-asset', $registered);
	}

	/**
	 * Test early return when no assets exist (requires FS manipulation).
	 */
	public function test_init_empty_return(): void
	{
		$real_plugins = TW_ROOT . 'assets/plugins';
		$temp_plugins = TW_ROOT . 'assets/plugins_backup_' . uniqid('', false);
		$moved_plugins = false;

		$real_blocks = TW_ROOT . 'assets/build/blocks';
		$temp_blocks = TW_ROOT . 'assets/build/blocks_backup_' . uniqid('', false);
		$moved_blocks = false;

		try {
			if (is_dir($real_plugins)) {
				rename($real_plugins, $temp_plugins);
				$moved_plugins = true;
			}
			mkdir($real_plugins, 0777, true);

			if (is_dir($real_blocks)) {
				rename($real_blocks, $temp_blocks);
				$moved_blocks = true;
			}
			mkdir($real_blocks, 0777, true);

			tw_app_set('registered', [], 'assets');

			// Use raw init call to strictly test the return logic
			tw_asset_init();

			$this->assertEmpty(tw_app_get('registered', 'assets', []));

		} finally {
			if (is_dir($real_plugins)) {
				@rmdir($real_plugins);
			}
			if (is_dir($real_blocks)) {
				@rmdir($real_blocks);
			}
			if ($moved_plugins) {
				rename($temp_plugins, $real_plugins);
			}
			if ($moved_blocks) {
				rename($temp_blocks, $real_blocks);
			}
		}
	}

	/**
	 * Test output buffer start logic using override constant.
	 */
	public function test_init_buffer(): void
	{
		if (!defined('TW_TEST_BUFFER_OVERRIDE')) {
			define('TW_TEST_BUFFER_OVERRIDE', true);
		}

		tw_asset_register(['dummy' => ['style' => 'dummy.css']]);
		set_current_screen('front');

		$level = ob_get_level();
		tw_asset_init();

		$this->assertGreaterThan($level, ob_get_level());
		ob_end_clean(); // Cleanup manually
	}

	/**
	 * Test merging logic during scanning: boolean/callable display and array overrides.
	 * Covers logic where pre-registered configuration (via tw_asset_register or direct setting)
	 * merges with discovered assets from plugins and blocks.
	 */
	public function test_init_merging_strategies(): void
	{
		$plugin_path = TW_ROOT . 'assets/plugins';
		$block_path = TW_ROOT . 'assets/build/blocks';

		// 1. Setup Plugin Fixtures
		$plugin_bool_dir = $plugin_path . '/merge-bool';
		$plugin_arr_dir = $plugin_path . '/merge-arr';

		if (!is_dir($plugin_bool_dir)) {
			mkdir($plugin_bool_dir, 0777, true);
		}

		if (!is_dir($plugin_arr_dir)) {
			mkdir($plugin_arr_dir, 0777, true);
		}

		// Plugin 1: Returns basic config
		file_put_contents($plugin_bool_dir . '/index.php', "<?php return ['script' => 'bool.js'];");

		// Plugin 2: Returns config with version 1.0
		file_put_contents($plugin_arr_dir . '/index.php', "<?php return ['script' => 'arr.js', 'version' => '1.0'];");

		// 2. Setup Block Fixtures
		if (!is_dir($block_path)) {
			mkdir($block_path, 0777, true);
		}

		file_put_contents($block_path . '/merge-block-call.css', '/* CSS */');
		file_put_contents($block_path . '/merge-block-arr.css', '/* CSS */');

		// 3. Pre-register Overrides
		$overrides = [
			'merge-bool'           => true,
			'merge-arr'            => ['version' => '2.0'],
			'merge-block-call_box' => function() {
				return true;
			},
			'merge-block-arr_box'  => ['footer' => true]
		];

		tw_app_set('registered', $overrides, 'assets');

		// 4. Run Initialization
		$this->init_twee();

		$registered = tw_app_get('registered', 'assets', []);

		// 5. Assertions

		// Assert Plugin Boolean Merge
		$this->assertArrayHasKey('merge-bool', $registered);
		$this->assertTrue($registered['merge-bool']['display']);

		// Assert Plugin Array Merge
		$this->assertArrayHasKey('merge-arr', $registered);
		$this->assertEquals('2.0', $registered['merge-arr']['version']);

		// Assert Block Callable Merge
		$this->assertArrayHasKey('merge-block-call_box', $registered);
		$this->assertIsCallable($registered['merge-block-call_box']['display']);

		// Assert Block Array Merge
		$this->assertArrayHasKey('merge-block-arr_box', $registered);
		$this->assertTrue($registered['merge-block-arr_box']['footer']);

		// Cleanup
		unlink($plugin_bool_dir . '/index.php');
		rmdir($plugin_bool_dir);

		unlink($plugin_arr_dir . '/index.php');
		rmdir($plugin_arr_dir);

		unlink($block_path . '/merge-block-call.css');
		unlink($block_path . '/merge-block-arr.css');
	}

	/**
	 * Test manual asset registration.
	 */
	public function test_register_manual(): void
	{
		tw_asset_register([
			'test-style'  => ['style' => 'style.css', 'directory' => 'build'],
			'test-script' => ['script' => 'app.js', 'directory' => 'build'],
		]);

		$registered = tw_app_get('registered', 'assets', []);
		$this->assertArrayHasKey('test-style', $registered);
		$this->assertEquals('build', $registered['test-style']['directory']);
	}

	/**
	 * Test updating the 'display' property of an existing asset.
	 */
	public function test_register_update_display(): void
	{
		tw_asset_register(['existing-asset' => ['script' => 'http://example.com/app.js']]);

		// Update with boolean
		tw_asset_register(['existing-asset' => true]);
		$registered = tw_app_get('registered', 'assets', []);
		$this->assertTrue($registered['existing-asset']['display']);

		// Update with callable
		$callback = function() {
			return true;
		};

		tw_asset_register(['existing-asset' => $callback]);
		$registered = tw_app_get('registered', 'assets', []);
		$this->assertSame($callback, $registered['existing-asset']['display']);
	}

	/**
	 * Test asset normalization defaults.
	 */
	public function test_normalize_basic(): void
	{
		$normalized = tw_asset_normalize(['style' => 'simple.css']);

		$this->assertEquals('tw_', $normalized['prefix']);
		$this->assertTrue($normalized['footer']);
		$this->assertIsArray($normalized['deps']);
	}

	/**
	 * Test normalization when 'deps' is a single string.
	 */
	public function test_normalize_string_deps(): void
	{
		$normalized = tw_asset_normalize(['deps' => 'common-dependency']);

		$this->assertEquals('common-dependency', $normalized['deps']['script']);
		$this->assertEquals('common-dependency', $normalized['deps']['style']);
	}

	/**
	 * Test basic single asset enqueue.
	 */
	public function test_enqueue_basic(): void
	{
		tw_asset_register(['my-asset' => ['style' => 'http://example.com/main.css']]);
		tw_asset_enqueue('my-asset');
		$this->assertContains('my-asset', tw_app_get('enqueued', 'assets', []));
	}

	/**
	 * Test array enqueue and invalid input handling.
	 */
	public function test_enqueue_array(): void
	{
		tw_asset_register([
			'asset-1' => ['script' => 'http://example.com/1.js'],
			'asset-2' => ['script' => 'http://example.com/2.js'],
		]);

		tw_asset_enqueue(['asset-1', 'asset-2']);
		tw_asset_enqueue('non-existent');

		$enqueued = tw_app_get('enqueued', 'assets', []);
		$this->assertContains('asset-1', $enqueued);
		$this->assertContains('asset-2', $enqueued);
		$this->assertCount(2, $enqueued);
	}

	/**
	 * Test instant enqueue prefixes.
	 */
	public function test_enqueue_instant(): void
	{
		tw_asset_register([
			'prefixed' => ['script' => 'http://example.com/p.js', 'prefix' => 'tw_'],
			'raw'      => ['script' => 'http://example.com/r.js', 'prefix' => ''],
		]);

		$this->init_twee();

		tw_asset_enqueue('prefixed', true);
		$this->assertTrue(wp_script_is('tw_prefixed', 'enqueued'));

		tw_asset_enqueue('raw', true);
		$this->assertTrue(wp_script_is('raw', 'enqueued'));
	}

	/**
	 * Test instant enqueue of styles and collections.
	 */
	public function test_enqueue_collections(): void
	{
		wp_register_script('dep-1', 'http://example.com/d1.js');
		wp_register_style('dep-1', 'http://example.com/d1.css');

		tw_asset_register([
			'my-style'    => ['style' => 'http://example.com/style.css'],
			'coll-script' => ['deps' => ['script' => ['dep-1']]],
			'coll-style'  => ['deps' => ['style' => ['dep-1']]]
		]);

		$this->init_twee();

		tw_asset_enqueue('my-style', true);
		$this->assertTrue(wp_style_is('tw_my-style', 'enqueued'));

		tw_asset_enqueue('coll-script', true);
		$this->assertTrue(wp_script_is('dep-1', 'enqueued'));

		tw_asset_enqueue('coll-style', true);
		$this->assertTrue(wp_style_is('dep-1', 'enqueued'));
	}

	/**
	 * Test dependency auto-enqueue.
	 */
	public function test_enqueue_dependencies(): void
	{
		tw_asset_register([
			'parent' => ['script' => 'http://example.com/p.js', 'deps' => ['child']],
			'child'  => ['script' => 'http://example.com/c.js'],
		]);

		$this->init_twee();

		tw_asset_enqueue('parent', true);
		$this->assertTrue(wp_script_is('tw_child', 'enqueued'));
	}

	/**
	 * Test basic script localization.
	 */
	public function test_localize_basic(): void
	{
		tw_asset_register([
			'loc-script' => [
				'script'   => 'http://example.com/l.js',
				'object'   => 'obj',
				'localize' => function() {
					return ['key' => 'val'];
				},
			],
		]);

		$this->init_twee();

		tw_asset_localize('loc-script');
		$this->assertContains('loc-script', tw_app_get('localized', 'assets', []));
	}

	/**
	 * Test localization branches: map generation, inline styles, fallbacks.
	 */
	public function test_localize_advanced(): void
	{
		tw_app_set('map', null, 'assets');

		tw_asset_register([
			'no-prefix' => [
				'script'   => 'http://example.com/np.js',
				'style'    => 'http://example.com/np.css',
				'prefix'   => '',
				'localize' => ['k' => 'v'],
				'inline'   => '.np { color: red; }',
			],
			'callable'  => [
				'style'  => 'http://example.com/c.css',
				'inline' => function() {
					return '.c { color: blue; }';
				},
			]
		]);

		$this->init_twee();

		// Test No Prefix & Inline String
		tw_asset_localize('no-prefix');
		global $wp_scripts, $wp_styles;

		$this->assertStringContainsString('"k":"v"', $wp_scripts->get_data('no-prefix', 'data'));
		$this->assertContains('.np { color: red; }', $wp_styles->get_data('no-prefix', 'after'));

		// Test Callable Inline
		tw_asset_localize('callable');
		$this->assertContains('.c { color: blue; }', $wp_styles->get_data('tw_callable', 'after'));

		// Test Early Return
		tw_asset_localize('no-prefix');
	}

	/**
	 * Test header/footer separation and auto-display (including callable display).
	 */
	public function test_print_logic(): void
	{
		tw_asset_register([
			'header'   => ['script' => 'http://example.com/h.js', 'footer' => false, 'display' => true],
			'footer'   => ['script' => 'http://example.com/f.js', 'footer' => true, 'display' => true],
			'callable' => [
				'script'  => 'http://example.com/c.js',
				'footer'  => false,
				'display' => function() {
					return true;
				}
			],
		]);

		$this->init_twee();

		remove_action('wp_head', 'tw_asset_init', 0);

		add_action('wp_head', 'tw_asset_print', 5);
		add_action('wp_footer', 'tw_asset_print', 100);

		ob_start();
		do_action('wp_head');
		ob_end_clean();
		$printed = tw_app_get('printed', 'assets', []);

		$this->assertContains('header', $printed);
		$this->assertContains('callable', $printed);
		$this->assertNotContains('footer', $printed);

		ob_start();
		do_action('wp_footer');
		ob_end_clean();
		$this->assertContains('footer', tw_app_get('printed', 'assets', []));
	}

	/**
	 * Test logic branches: Empty prefix, style/script merging, standard WP styles.
	 */
	public function test_print_advanced(): void
	{
		tw_asset_register([
			'noprefix'    => ['style' => 'http://example.com/np.css', 'prefix' => '', 'display' => true, 'footer' => false],
			'collection'  => ['deps' => ['style' => ['dep']], 'display' => true, 'footer' => false],
			'dep'         => ['style' => 'http://example.com/d.css'],
			'script-coll' => ['deps' => ['script' => ['dep-js']], 'display' => true, 'footer' => false],
			'dep-js'      => ['script' => 'http://example.com/d.js'],
		]);

		wp_register_style('std-wp', 'http://example.com/std.css');
		wp_register_script('tw_dep-js', 'http://example.com/d.js');

		$this->init_twee();

		remove_action('wp_head', 'tw_asset_init', 0);

		add_filter('twee_asset_enqueue', function($l) {
			$l[] = 'std-wp';

			return $l;
		});
		add_action('wp_head', 'tw_asset_print', 5);

		ob_start();
		do_action('wp_head');
		ob_end_clean();

		$printed = tw_app_get('printed', 'assets', []);

		$this->assertContains('noprefix', $printed);
		$this->assertContains('collection', $printed);
		$this->assertContains('script-coll', $printed);
		$this->assertContains('std-wp', $printed);

		$this->assertTrue(wp_style_is('noprefix', 'done'));
		$this->assertTrue(wp_style_is('tw_dep', 'done'));
		$this->assertTrue(wp_script_is('tw_dep-js', 'done'));
		$this->assertTrue(wp_style_is('std-wp', 'done'));

		remove_all_filters('twee_asset_enqueue');
		remove_action('wp_head', 'tw_asset_print', 5);
		wp_dequeue_style('noprefix');
		wp_dequeue_style('tw_dep');
		wp_dequeue_style('std-wp');
		wp_dequeue_script('tw_dep-js');
	}

	/**
	 * Test basic injection with critical CSS.
	 */
	public function test_inject_basic(): void
	{
		tw_asset_register([
			'hero_box' => [
				'style'   => ['https://example.com/hero.css'],
				'version' => '1.0',
			],
		]);
		$ph = tw_asset_placeholder(true);

		$content = "<html><head>{$ph}</head><body><div class='hero_box'></div></body></html>";
		$output = tw_asset_inject($content);

		$this->assertStringContainsString('href="https://example.com/hero.css?v=1.0"', $output);
		$this->assertStringNotContainsString('TWEE_ASSET_PLACEHOLDER', $output);
	}

	/**
	 * Test injection when regex matches but no missing assets found.
	 */
	public function test_inject_no_missing(): void
	{
		tw_asset_register(['dummy' => ['style' => 'd.css']]);
		$ph = tw_asset_placeholder(true);

		$content = "<html><head>{$ph}</head><body><div class='tw_block_test_box'></div></body></html>";
		$output = tw_asset_inject($content);

		$this->assertStringNotContainsString($ph, $output);
		$this->assertStringNotContainsString('<link', $output);
	}

	/**
	 * Test injection when regex finds no matches.
	 */
	public function test_inject_no_matches(): void
	{
		tw_asset_register(['dummy' => ['style' => 'd.css']]);
		$ph = tw_asset_placeholder(true);

		$content = "<html><head>{$ph}</head><body><div class='simple-class'></div></body></html>";
		$output = tw_asset_inject($content);

		$this->assertStringNotContainsString($ph, $output);
		$this->assertStringNotContainsString('<link', $output);
	}

	/**
	 * Test complex injection: limit handling, fonts, preload/noscript.
	 */
	public function test_inject_complex(): void
	{
		$this->assertSame('NoPH', tw_asset_inject('NoPH'));

		$font_file = TW_ROOT . 'assets/styles/base/fonts.scss';

		if (!is_dir(dirname($font_file))) {
			mkdir(dirname($font_file), 0777, true);
		}

		file_put_contents($font_file, "src: url('../fonts/f.woff');");

		add_filter('twee_asset_limit', function() {
			return 1;
		});

		tw_asset_register([
			'header_box'  => ['style' => ['h.css']],
			'box_one_box' => ['style' => ['1.css']],
			'box_two_box' => ['style' => ['2.css']],
			'ex_box'      => ['style' => ['e.css']],
		]);

		$ph = tw_asset_placeholder(true);
		$content = "<html><head>{$ph}</head><body>
			<div class='tw_block_ex_box'></div>
			<div class='header_box'></div>
			<div class='box_one_box box_two_box'></div>
		</body></html>";

		$out = tw_asset_inject($content);

		$this->assertStringNotContainsString('e.css', $out);
		$this->assertStringContainsString('h.css', $out);
		$this->assertStringContainsString('f.woff', $out);

		$this->assertStringContainsString('<link rel="preload" as="style"', $out);
		$this->assertStringContainsString('<noscript>', $out);

		// Cleanup
		unlink($font_file);
		remove_all_filters('twee_asset_limit');
	}

}