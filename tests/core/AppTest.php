<?php

/**
 * App Tests using WP Integration Suite
 */
class AppTest extends WP_UnitTestCase {

	/**
	 * Reset the app settings cache before each test.
	 */
	public function setUp(): void
	{
		parent::setUp();
		tw_app_clear('tw_app_settings');
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void
	{
		parent::tearDown();
		tw_app_clear('tw_app_settings');
	}

	/**
	 * Test that theme setup registers features and menus.
	 */
	public function test_action_setup_theme()
	{
		// 1. Setup Data
		tw_app_set('tw_app_settings', [
			'support' => [
				'post-thumbnails' => true,
				'html5'           => ['comment-list', 'comment-form'],
			],
			'menus'   => ['main' => 'Main Menu'],
		]);

		// 2. Run the function
		tw_app_action_setup_theme();

		// 3. Assertions
		$this->assertTrue(current_theme_supports('post-thumbnails'));
		$this->assertTrue(current_theme_supports('html5'));

		$locations = get_registered_nav_menus();
		$this->assertArrayHasKey('main', $locations);
		$this->assertEquals('Main Menu', $locations['main']);
	}

	/**
	 * Test widget initialization and sidebar registration.
	 */
	public function test_action_widgets_init()
	{
		global $wp_registered_sidebars;

		// 1. Setup Data
		tw_app_set('tw_app_settings', [
			'sidebars' => [['id' => 'sidebar-1', 'name' => 'Sidebar 1']],
		]);

		// 2. Run function
		tw_app_action_widgets_init();

		// 3. Assert Sidebar exists
		$this->assertArrayHasKey('sidebar-1', $wp_registered_sidebars);
	}

	/**
	 * Test init hook for Post Types & Taxonomies.
	 */
	public function test_action_init()
	{
		// 1. Setup Data
		tw_app_set('tw_app_settings', [
			'types'      => ['book' => ['public' => true, 'label' => 'Books']],
			'taxonomies' => ['genre' => ['types' => 'book', 'label' => 'Genres']],
		]);

		// 2. Run function
		tw_app_action_init();

		// 3. Assertions
		$this->assertTrue(post_type_exists('book'));
		$pt = get_post_type_object('book');
		$this->assertEquals('Books', $pt->label);

		$this->assertTrue(taxonomy_exists('genre'));
	}

	/**
	 * Test template part rendering.
	 */
	public function test_template_rendering()
	{
		$folder = 'parts';
		$file = TW_ROOT . $folder . '/test-part.php';

		// Ensure directory exists
		if (!is_dir(dirname($file))) {
			mkdir(dirname($file), 0777, true);
		}

		// Create dummy template
		file_put_contents($file, '<h1><?php echo $title; ?></h1>');

		// Run
		$output = tw_app_template('test-part', ['title' => 'Real WP'], $folder);

		// Assert
		$this->assertStringContainsString('<h1>Real WP</h1>', $output);

		// Cleanup
		if (file_exists($file)) {
			unlink($file);
		}
	}

	/**
	 * Test dynamic file inclusion.
	 */
	public function test_app_include()
	{
		$folder = TW_ROOT . 'includes_test';
		$file = $folder . '/helper.php';

		// Create dummy file
		if (!is_dir($folder)) {
			mkdir($folder);
		}
		file_put_contents($file, '<?php function my_test_fn(){} ?>');

		// Run
		tw_app_include($folder);

		// Assert
		$this->assertTrue(function_exists('my_test_fn'));

		// Cleanup
		if (file_exists($file)) {
			unlink($file);
		}
		if (is_dir($folder)) {
			rmdir($folder);
		}
	}

}