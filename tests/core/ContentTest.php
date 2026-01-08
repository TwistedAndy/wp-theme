<?php

/**
 * Content Tests using WP Integration Suite
 */
class ContentTest extends WP_UnitTestCase {

	/**
	 * Setup method.
	 */
	public function setUp(): void
	{
		parent::setUp();
		// Files are now loaded globally via bootstrap.php, so no manual require needed here.
	}

	/**
	 * Test that title retrieval works for WP_Post objects.
	 */
	public function test_content_title_post()
	{
		// Create a real post
		$post_id = self::factory()->post->create([
			'post_title' => 'Hello World Real Post',
		]);
		$post = get_post($post_id);

		// Run
		$title = tw_content_title($post);

		// Assert
		$this->assertSame('Hello World Real Post', $title);
	}

	/**
	 * Test that title retrieval works for WP_User objects.
	 */
	public function test_content_title_user()
	{
		// Create a real user
		$user_id = self::factory()->user->create([
			'display_name' => 'Jane Doe',
		]);
		$user = get_userdata($user_id);

		// Run
		$this->assertSame('Jane Doe', tw_content_title($user));
	}

	/**
	 * Test text extraction logic (Excerpt vs Content).
	 */
	public function test_content_text_logic()
	{
		// Create post with distinct content and excerpt
		$post_id = self::factory()->post->create([
			'post_content' => 'This is the full content body.',
			'post_excerpt' => 'This is the summary.',
		]);
		$post = get_post($post_id);

		// Should prefer excerpt if it exists
		$this->assertSame('This is the summary.', tw_content_text($post));
	}

	/**
	 * Test the ACF link field helper.
	 */
	public function test_content_link()
	{
		$link = ['url' => 'http://example.com', 'title' => 'Go'];

		// Run
		$output = tw_content_link($link);

		// Assert
		$this->assertStringContainsString('href="http://example.com"', $output);
		$this->assertStringContainsString('>Go</a>', $output);
	}

	/**
	 * Test 404 heading generation using global WP_Query state.
	 */
	public function test_content_heading_404()
	{
		// Force a 404 state by requesting a non-existent post ID
		$this->go_to('/?p=99999999');

		global $wp_query;
		$this->assertTrue($wp_query->is_404());

		// Assert
		$this->assertSame('Page not found', tw_content_heading());
	}

	/**
	 * Test reading time calculation.
	 */
	public function test_content_time()
	{
		$content = str_repeat('word ', 600); // 600 words
		$post_id = self::factory()->post->create(['post_content' => $content]);
		$post = get_post($post_id);

		// Calculation: 600 / 200 = 3 minutes
		$this->assertSame('3 min read', tw_content_time($post));
	}

	/**
	 * Test string truncation logic.
	 */
	public function test_content_strip()
	{
		$text = "This is a long sentence used for testing.";

		// Strip to 4 chars -> "This" + "..."
		$this->assertSame('This...', tw_content_strip($text, 4));
	}

	/**
	 * Test phone link formatting.
	 */
	public function test_content_phone()
	{
		$this->assertSame('tel:+1555', tw_content_phone('+1 (555)'));
	}

	/**
	 * Test date formatting.
	 */
	public function test_content_date()
	{
		$post_id = self::factory()->post->create([
			'post_date' => '2023-01-01 12:00:00',
		]);
		$post = get_post($post_id);

		// Test using custom format Y-m-d
		$this->assertSame('2023-01-01', tw_content_date($post, 'Y-m-d'));
	}

}