<?php

/**
 * Content Tests
 */
class ContentTest extends WP_UnitTestCase {

	protected static int $post_id;

	protected static int $user_id;

	protected static int $term_id;

	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();

		self::$post_id = self::factory()->post->create([
			'post_title'   => 'Hello World Real Post',
			'post_content' => 'This is the full content body. It has enough words to calculate reading time.',
			'post_excerpt' => 'This is the summary.',
			'post_date'    => '2023-01-01 12:00:00',
			'post_author'  => 1
		]);

		self::$user_id = self::factory()->user->create([
			'display_name' => 'Jane Doe',
			'role'         => 'author',
		]);

		self::$term_id = self::factory()->term->create([
			'name'        => 'Test Category',
			'description' => 'Category Description',
			'taxonomy'    => 'category',
		]);
	}

	/**
	 * Test title retrieval for various objects and truncation.
	 */
	public function test_title(): void
	{
		global $wp_post_types;

		// 1. Standard Objects
		$this->assertSame('Hello World Real Post', tw_content_title(get_post(self::$post_id)));
		$this->assertSame('Jane Doe', tw_content_title(get_userdata(self::$user_id)));
		$this->assertSame('Test Category', tw_content_title(get_term(self::$term_id)));
		$this->assertSame($wp_post_types['post']->label, tw_content_title($wp_post_types['post']));
		$this->assertEmpty(tw_content_title(new stdClass()));

		// 2. Truncation
		$long_post = self::factory()->post->create_and_get(['post_title' => 'A very long title that needs stripping']);
		$title = tw_content_title($long_post, 6);
		$this->assertStringContainsString('A very', $title);
		$this->assertStringNotContainsString('long', $title);
	}

	/**
	 * Test text retrieval, excerpt priority, and "more" tag logic.
	 */
	public function test_text(): void
	{
		// 1. WP_Term (Description)
		$term = get_term(self::$term_id);
		$this->assertSame('Category Description', tw_content_text($term));

		// 2. String Input
		$this->assertSame('Just a string', tw_content_text('Just a string'));

		// 3. Empty Length (Return full text/excerpt)
		$post = get_post(self::$post_id);
		$this->assertSame('This is the summary.', tw_content_text($post, 0));

		// 4. "More" Tag Logic
		$intro = str_repeat('Content ', 50);
		$raw_content = $intro . '<!--more-->Hidden';
		$cut_length = 100;

		$text_cut = tw_content_text($raw_content, $cut_length, false, ' ', true);

		$this->assertStringContainsString('Content', $text_cut);
		$this->assertLessThanOrEqual(106, strlen($text_cut), 'Text should be cut to length');
		$this->assertStringNotContainsString('Hidden', $text_cut);

		$text_full = tw_content_text($raw_content, $cut_length, false, ' ', false);

		$this->assertGreaterThan($cut_length, strlen($text_full), 'Should return full intro text ignoring length limit');
		$this->assertStringNotContainsString('Hidden', $text_full);
		$this->assertStringStartsWith('Content Content', $text_full);

		// 5. Long Excerpt with force_cut = false
		$long_excerpt_id = self::factory()->post->create([
			'post_excerpt' => 'A very long excerpt that should technically be returned fully if force cut is false.',
			'post_content' => ''
		]);

		$long_post = get_post($long_excerpt_id);

		$this->assertStringContainsString('returned fully', tw_content_text($long_post, 50, false, ' ', false));
	}

	/**
	 * Test heading generation for global state and specific queries.
	 */
	public function test_heading(): void
	{
		// 1. Global State
		$this->go_to(home_url('/'));
		$this->assertSame(get_bloginfo('name', 'display'), tw_content_heading());

		$this->go_to('/?p=999999');
		$this->assertSame('Page not found', tw_content_heading());

		$this->go_to('/?s=searchquery');
		$this->assertStringContainsString('searchquery', strtolower(tw_content_heading()));

		$this->go_to(get_term_link(self::$term_id));
		$this->assertSame('Test Category', tw_content_heading());

		// 2. Invalid Query Input (Covers !($query instanceof WP_Query))
		$this->assertEmpty(tw_content_heading('invalid_string'));

		// 3. Specific Query Mocks
		$q = new WP_Query();
		$q->is_front_page = false;
		$q->is_home = false;
		$q->post = get_post(self::$post_id);

		// Post Object (Covers $title = $object->post_title)
		$q->queried_object = get_post(self::$post_id);
		$this->assertSame('Hello World Real Post', tw_content_heading($q));

		// Dates
		// IMPORTANT: Reset queried_object to null so the function doesn't stop at ($object instanceof WP_Post)
		$q->queried_object = null;

		$q->is_day = true;
		$this->assertStringContainsString('January 1, 2023', tw_content_heading($q));
		$q->is_day = false;

		$q->is_month = true;
		$this->assertStringContainsString('January 2023', tw_content_heading($q));
		$q->is_month = false;

		$q->is_year = true;
		$this->assertStringContainsString('2023', tw_content_heading($q));

		// User
		$q = new WP_Query();
		$q->queried_object = get_userdata(self::$user_id);
		$this->assertStringContainsString('Jane Doe', tw_content_heading($q));

		// Pagination
		$q = new WP_Query();
		$q->queried_object = get_post_type_object('post');
		$q->set('paged', 2);
		$res = tw_content_heading($q, '<span>', '</span>', true);
		$this->assertStringContainsString('Page 2', $res);
		$this->assertStringContainsString('<span>', $res);
	}

	/**
	 * Test link generation attributes.
	 */
	public function test_link(): void
	{
		$link = ['url' => 'http://test.com', 'title' => 'Click', 'target' => '_blank'];

		// 1. Standard (Class provided, Target provided)
		$out = tw_content_link($link, 'btn', 'Hidden');
		$this->assertStringContainsString('href="http://test.com"', $out);
		$this->assertStringContainsString('class="btn"', $out);
		$this->assertStringContainsString('target="_blank"', $out);
		$this->assertStringContainsString('sr-hidden">Hidden', $out);

		// 2. Empty Class & No Target (Covers else branches)
		$simple_link = ['url' => 'http://simple.com', 'title' => 'Simple'];
		// Passing '' as the second argument forces the $class = '' else block
		$out_simple = tw_content_link($simple_link, '');

		$this->assertStringNotContainsString('class="', $out_simple); // Verifies class was set to empty string
		$this->assertStringNotContainsString('target="', $out_simple); // Verifies target was set to empty string
		$this->assertStringContainsString('>Simple</a>', $out_simple);

		// 3. Invalid
		$this->assertEmpty(tw_content_link([]));
		$this->assertEmpty(tw_content_link(['title' => 'No URL']));
	}

	/**
	 * Test text stripping and sanitization.
	 */
	public function test_strip(): void
	{
		// 1. Basic Truncation
		$text = "This is a long sentence used for testing.";
		$this->assertStringContainsString('This', tw_content_strip($text, 4));

		// 2. Allowed Tags (+tag syntax)
		// Covers: if ($allowed_tags) ...
		$html = "<b>Bold</b> <i>Italic</i> <u>Underline</u>";
		$this->assertStringContainsString('<u>', tw_content_strip($html, 100, '+u'));

		// 3. Dangerous Tags
		$bad = "<h1>Title</h1><script>alert(1)</script>Content";
		$clean = tw_content_strip($bad, 100);
		$this->assertStringNotContainsString('<script>', $clean);
		$this->assertStringNotContainsString('<h1>', $clean);
		$this->assertStringContainsString('Content', $clean);

		// -------------------------------------------------------------
		// NEW COVERAGE SCENARIOS
		// -------------------------------------------------------------

		// 4. Empty Allowed Tags (String)
		// Covers: } else { $allowed_tags_list = ''; }
		$html_input = "<b>Bold</b> <i>Italic</i>";
		$stripped = tw_content_strip($html_input, 100, '');
		$this->assertSame('Bold Italic', $stripped);

		// 5. Cut Logic: No space found after cut point
		// Covers: if ($pos < $length ...)
		$no_space_text = 'Start12345' . str_repeat('a', 50);
		$res_no_space = tw_content_strip($no_space_text, 10);
		$this->assertSame('Start12345...', $res_no_space);

		// 6. Cut Logic: Space is too far away
		// Covers: ... or $pos > ($length + 20)) { $pos = $length; }
		$far_space_text = 'Start' . str_repeat('x', 30) . ' End';
		$res_far_space = tw_content_strip($far_space_text, 5);
		$this->assertSame('Start...', $res_far_space);

		// 7. Broken Closing Tag Regex
		// Covers: if (!empty($matches[1])) { $text = $matches[1]; }
		// FIX: We increase length to 9 to result in "Check </b".
		// The regex requires at least one char after "</" to match via `[^>]+`.
		$broken_tag_text = 'Check </b>';
		$res_broken = tw_content_strip($broken_tag_text, 9, 'b');
		$this->assertSame('Check ...', $res_broken);

		// 8. Open Link Stripping
		// Covers: if ($link_end === false) { ... }
		$link_html = "Link <a href='#'>Click</a>";
		// Cut at 18 gives: "Link <a href='#'>C"
		// The function detects the open <a> but no </a>, so it strips back to "Link ..."
		$res_link = tw_content_strip($link_html, 18, '<a>');
		$this->assertSame('Link ...', $res_link);
	}

	/**
	 * Test formatting helpers: Date, Time, Phone.
	 */
	public function test_formatting(): void
	{
		$post = get_post(self::$post_id);

		// Date
		update_option('date_format', 'F j, Y');
		$this->assertSame('January 1, 2023', tw_content_date($post));
		$this->assertSame('2023-01-01', tw_content_date($post, 'Y-m-d'));

		// Time
		$this->assertSame('3 min read', tw_content_time(self::factory()->post->create_and_get(['post_content' => 'Short'])));
		$long_post = self::factory()->post->create_and_get(['post_content' => str_repeat('word ', 600)]);
		$this->assertStringContainsString('3 min', tw_content_time($long_post));

		// Phone
		$this->assertSame('tel:+1555', tw_content_phone('+1 (555)'));
		$this->assertSame('#', tw_content_phone('abc'));
	}

	/**
	 * Test video embed generation for native types and WP attachment fallback.
	 * Runs before ACF mocks to cover the native attachment resolution branch.
	 */
	public function test_video(): void
	{
		// Handle empty input
		$this->assertEmpty(tw_content_video(''));

		// YouTube (Standard & Shorts)
		$youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
		$youtube_html = tw_content_video($youtube_url);

		$this->assertStringContainsString('<iframe', $youtube_html);
		$this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $youtube_html);
		$this->assertStringContainsString('autoplay=1', $youtube_html);
		$this->assertStringContainsString('pointer-events: none', $youtube_html);

		$shorts_url = 'https://www.youtube.com/shorts/abcdefghijk';
		$shorts_html = tw_content_video($shorts_url, ['controls' => true, 'loop' => false]);

		$this->assertStringContainsString('embed/abcdefghijk', $shorts_html);
		$this->assertStringContainsString('controls=1', $shorts_html);

		// Vimeo (Standard & Groups/Channels)
		$vimeo_url = 'https://vimeo.com/123456789';
		$vimeo_html = tw_content_video($vimeo_url);

		$this->assertStringContainsString('player.vimeo.com/video/123456789', $vimeo_html);

		$vimeo_group_url = 'https://vimeo.com/groups/animation/videos/987654321';
		$vimeo_group_html = tw_content_video($vimeo_group_url, ['controls' => true, 'autoplay' => false]);

		$this->assertStringContainsString('player.vimeo.com/video/987654321', $vimeo_group_html);
		$this->assertStringContainsString('autoplay=0', $vimeo_group_html);

		// Native HTML5 (webm)
		$webm_url = 'https://example.com/movie.webm';
		$webm_html = tw_content_video($webm_url, ['poster' => 'https://example.com/poster.jpg']);

		$this->assertStringContainsString('type="video/webm"', $webm_html);

		// Native WP Attachment Fallback
		$attachment_id = self::factory()->attachment->create_object([
			'file'           => 'test-video.mp4',
			'post_mime_type' => 'video/mp4',
			'post_type'      => 'attachment'
		]);
		$attachment_html = tw_content_video($attachment_id);

		$this->assertStringContainsString('test-video.mp4', $attachment_html);
		$this->assertStringContainsString('type="video/mp4"', $attachment_html);
	}

	/**
	 * Test ACF attachment logic and empty URL fallbacks.
	 * * @depends test_video
	 */
	public function test_video_acf_and_empty_returns(): void
	{
		// Mock ACF function to hit the function_exists() branch
		if (!function_exists('acf_get_attachment')) {
			function acf_get_attachment($id)
			{
				if ($id === 888) {
					return [
						'url'       => 'https://example.com/acf-mock.mp4',
						'mime_type' => 'video/mp4',
						'width'     => 1920,
						'height'    => 1080
					];
				}

				return false;
			}
		}

		// ACF logic path
		$acf_html = tw_content_video(888);

		$this->assertStringContainsString('acf-mock.mp4', $acf_html);
		$this->assertStringContainsString('width="1920"', $acf_html);

		// Empty returns
		$this->assertEmpty(tw_content_video(999999)); // ACF returns false
		$this->assertEmpty(tw_content_video(['mime_type' => 'video/mp4'])); // Missing 'url' array key
		$this->assertEmpty(tw_content_video((object) ['url' => 'https://example.com/test.mp4'])); // Unhandled object type
	}

	/**
	 * Test specific HTML5 video attributes: controls and poster ID resolution.
	 */
	public function test_video_html5_attributes(): void
	{
		// Mock image link function for poster resolution
		if (!function_exists('tw_image_link')) {
			function tw_image_link($id, $size)
			{
				return 'https://example.com/resolved-poster-' . $id . '.jpg';
			}
		}

		$video_url = 'https://example.com/test-video.mp4';

		// Controls attribute
		$controls_html = tw_content_video($video_url, ['controls' => true]);

		$this->assertStringContainsString('controls', $controls_html);
		$this->assertStringContainsString('<video', $controls_html);

		// Numeric poster resolution
		$poster_html = tw_content_video($video_url, ['poster' => 777]);

		$this->assertStringContainsString('poster="https://example.com/resolved-poster-777.jpg"', $poster_html);
	}

}