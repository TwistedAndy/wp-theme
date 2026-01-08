<?php

/**
 * App Library Integration Tests
 */
class AppTest extends WP_UnitTestCase {

	protected string $test_folder;

	protected string $test_file;

	public function set_up(): void
	{
		parent::set_up();

		tw_app_clear('tw_app_settings');
		$this->test_folder = TW_ROOT . 'includes_test';
		$this->test_file = TW_ROOT . 'parts/test-part.php';
	}

	public function tear_down(): void
	{
		if (file_exists($this->test_file)) {
			unlink($this->test_file);
		}

		if (is_dir($this->test_folder)) {
			array_map('unlink', glob("$this->test_folder/*.*"));
			rmdir($this->test_folder);
		}

		tw_app_clear('tw_app_settings');

		parent::tear_down();
	}

	/**
	 * Test global init and basic hook registration
	 */
	public function test_initialization_and_hooks(): void
	{
		global $twee_cache;
		$this->assertIsArray($twee_cache);
		$this->assertNotFalse(has_action('after_setup_theme', 'tw_app_action_setup_theme'));
		$this->assertNotFalse(has_action('widgets_init', 'tw_app_action_widgets_init'));
	}

	/**
	 * Test Theme Setup: features and menus
	 */
	public function test_action_setup_theme(): void
	{
		tw_app_features('custom-header');
		tw_app_features(['post-thumbnails', 'html5' => ['comment-list']]);
		tw_app_menus(['main' => 'Main Menu']);
		tw_app_action_setup_theme();

		$this->assertTrue(current_theme_supports('custom-header'));
		$this->assertTrue(current_theme_supports('post-thumbnails'));
		$this->assertTrue(current_theme_supports('html5'));
		$this->assertEquals('Main Menu', get_registered_nav_menus()['main']);
	}

	/**
	 * Test Sidebars and Widgets registration
	 */
	public function test_widgets_and_sidebars(): void
	{
		global $wp_registered_sidebars, $wp_widget_factory;

		tw_app_sidebar(['id' => 'sb-1', 'name' => 'Sidebar 1']);
		tw_app_widget('WP_Widget_Archives');

		// Mock custom widget class
		$class = 'Twee\\Widgets\\TestWidget';
		if (!class_exists($class)) {
			eval('namespace Twee\\Widgets; class TestWidget extends \WP_Widget { 
				public function __construct() { parent::__construct("tw_test", "Test"); } 
			}');
		}
		tw_app_widget('TestWidget');

		tw_app_action_widgets_init();

		$this->assertArrayHasKey('sb-1', $wp_registered_sidebars);
		$this->assertArrayHasKey('WP_Widget_Archives', $wp_widget_factory->widgets);

		$found = false;
		foreach ($wp_widget_factory->widgets as $w) {
			if ($w instanceof $class) $found = true;
		}
		$this->assertTrue($found);
	}

	/**
	 * Test Init: Post Types, Taxonomies, and Editor Styles
	 */
	public function test_action_init_logic(): void
	{
		tw_app_type('book', ['public' => true, 'label' => 'Books']);
		tw_app_taxonomy('genre', 'book', ['label' => 'Genres']);

		$css = TW_ROOT . 'editor-style.css';
		file_put_contents($css, 'body{}');

		tw_app_action_init();

		$this->assertTrue(post_type_exists('book'));
		$this->assertTrue(taxonomy_exists('genre'));
		$this->assertTrue(current_theme_supports('editor-style'));

		unlink($css);
	}

	/**
	 * Test Runtime Cache: set, get, unset, and clear (including flush fallback)
	 */
	public function test_cache_logic(): void
	{
		global $twee_cache;

		tw_app_set('k1', 'v1', 'grp');
		$this->assertEquals('v1', tw_app_get('k1', 'grp'));
		$this->assertEquals('def', tw_app_get('none', 'grp', 'def'));

		tw_app_set('k1', null, 'grp'); // Unset
		$this->assertNull(tw_app_get('k1', 'grp'));

		tw_app_set('k2', 'v2', 'grp');
		tw_app_clear('grp'); // Group clear
		$this->assertArrayNotHasKey('grp', $twee_cache);
	}

	/**
	 * Test Settings: retrieval and array updates
	 */
	public function test_app_settings(): void
	{
		$data = ['k' => 'v'];
		tw_app_settings('custom', $data);
		$this->assertEquals($data, tw_app_settings('custom'));
		$this->assertEquals([], tw_app_settings('invalid'));
	}

	/**
	 * Test Database: singleton check and new instance creation
	 */
	public function test_app_database(): void
	{
		global $wpdb;
		$this->assertInstanceOf(\wpdb::class, tw_app_database());

		$orig = $wpdb;
		$wpdb = null;
		$this->assertInstanceOf(\wpdb::class, tw_app_database());
		$wpdb = $orig;
	}

	/**
	 * Test Inclusion: non-existent folder, file lists, and auto-scan
	 */
	public function test_app_include_logic(): void
	{
		tw_app_include(TW_ROOT . 'missing_dir'); // Guard check

		if (!is_dir($this->test_folder)) {
			mkdir($this->test_folder, 0777, true);
		}

		file_put_contents($this->test_folder . '/a.php', '<?php function fn_a(){}');
		file_put_contents($this->test_folder . '/b.php', '<?php function fn_b(){}');

		tw_app_include($this->test_folder); // Auto-scan

		$this->assertTrue(function_exists('fn_a'));
		$this->assertTrue(function_exists('fn_b'));
	}

	/**
	 * Test Template: rendering, extraction, and buffer cleanup
	 */
	public function test_template_logic(): void
	{
		if (!is_dir(dirname($this->test_file))) {
			mkdir(dirname($this->test_file), 0777, true);
		}

		// Normal rendering + extraction
		file_put_contents($this->test_file, '<?php echo $val; ?>');
		$this->assertEquals('hello', tw_app_template('test-part', ['val' => 'hello']));

		// Buffer leak cleanup
		file_put_contents($this->test_file, '<?php ob_start(); echo "leak";');
		$lv = ob_get_level();
		$out = tw_app_template('test-part');
		$this->assertEquals('leak', $out);
		$this->assertEquals($lv, ob_get_level());
	}

	/**
	 * Test Object Filter Removal: class/method matching
	 */
	public function test_remove_object_filter(): void
	{
		$obj = new class {

			public function hook($v)
			{
				return $v;
			}

		};
		$cls = get_class($obj);

		add_filter('tw_hook', [$obj, 'hook'], 20);
		$this->assertTrue(tw_app_remove_filter('tw_hook', $cls, 'hook', 20));
		$this->assertFalse(has_filter('tw_hook', [$obj, 'hook']));

		// Safety check
		$this->assertFalse(tw_app_remove_filter('tw_hook', 'Missing', 'none'));
	}

}