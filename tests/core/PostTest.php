<?php

/**
 * Post Library Integration Tests
 */
class PostTest extends WP_UnitTestCase {

	/**
	 * Setup mocks for external dependencies.
	 */
	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();

		if (!function_exists('tw_app_database')) {
			function tw_app_database()
			{
				global $wpdb;

				return $wpdb;
			}
		}

		if (!function_exists('tw_app_clear')) {
			function tw_app_clear($group)
			{
				wp_cache_flush();
			}
		}

		// Mock ancestor retrieval (dependency of tw_post_term_thread)
		if (!function_exists('tw_term_ancestors')) {
			function tw_term_ancestors($term_id, $taxonomy)
			{
				return ($term_id > 1) ? [$term_id - 1] : [];
			}
		}

		// Mock term data (dependency of tw_post_term_thread)
		if (!function_exists('tw_term_data')) {
			function tw_term_data($key, $field, $taxonomy)
			{
				return [1 => 'Term 1', 2 => 'Term 2', 3 => 'Term 3'];
			}
		}
	}

	/**
	 * Reset cache before each test.
	 */
	public function set_up(): void
	{
		parent::set_up();
		wp_cache_flush();
	}

	/**
	 * Test fetching post data with default and custom fields.
	 */
	public function test_post_data_fetch(): void
	{
		$id1 = self::factory()->post->create(['post_title' => 'Post A', 'post_status' => 'publish']);
		$id2 = self::factory()->post->create(['post_title' => 'Post B', 'post_status' => 'draft']);

		// 1. Default fetch
		$res_def = tw_post_data('post');
		$this->assertArrayHasKey($id1, $res_def);
		$this->assertEquals('Post A', $res_def[$id1]);

		// 2. Fetch specific fields
		$res_cols = tw_post_data('post', 'ID', ['post_name', 'post_status']);
		$this->assertIsArray($res_cols[$id1]);
		$this->assertArrayHasKey('post_name', $res_cols[$id1]);
		$this->assertArrayNotHasKey('post_title', $res_cols[$id1]);

		// 3. Filter by status
		$res_pub = tw_post_data('post', 'ID', 'post_title', 'publish');
		$this->assertArrayHasKey($id1, $res_pub);
		$this->assertArrayNotHasKey($id2, $res_pub);
	}

	/**
	 * Test retrieval of raw term relationships.
	 */
	public function test_post_terms_get(): void
	{
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create(['taxonomy' => 'category']);

		wp_set_object_terms($post_id, [$term_id], 'category');

		$map = tw_post_terms('category');

		$this->assertArrayHasKey($post_id, $map);
		$this->assertContains($term_id, $map[$post_id]);
	}

	/**
	 * Test threading logic (ancestors + term).
	 */
	public function test_post_term_thread_logic(): void
	{
		global $wpdb;
		$post_id = self::factory()->post->create();

		// Force Term ID 3 to match static mock data
		$wpdb->delete($wpdb->terms, ['term_id' => 3]);
		$wpdb->delete($wpdb->term_taxonomy, ['term_taxonomy_id' => 3]);

		$wpdb->insert($wpdb->terms, ['term_id' => 3, 'name' => 'Term 3', 'slug' => 'term-3']);
		$wpdb->insert($wpdb->term_taxonomy, ['term_taxonomy_id' => 3, 'term_id' => 3, 'taxonomy' => 'category']);
		clean_term_cache(3, 'category');

		wp_set_object_terms($post_id, [3], 'category');

		// 1. Single thread (Reversed longest path)
		$thread = tw_post_term_thread($post_id, 'category', true);
		$this->assertArrayHasKey(3, $thread);
		$this->assertEquals('Term 3', $thread[3]);

		// 2. Multi thread
		$threads = tw_post_term_thread($post_id, 'category', false);
		$this->assertNotEmpty($threads);
		$this->assertArrayHasKey(3, $threads[0]);
	}

	/**
	 * Test query argument builder.
	 */
	public function test_post_query_builder(): void
	{
		// 1. Basic Arguments
		$args = tw_post_query('post', ['number' => 10]);
		$this->assertEquals('post', $args['post_type']);
		$this->assertEquals(10, $args['posts_per_page']);

		// 2. Custom Order via ID list
		$ids = [1, 2, 3];
		$args_custom = tw_post_query('post', ['order' => 'custom', 'items' => $ids]);
		$this->assertEquals($ids, $args_custom['post__in']);
		$this->assertEquals('post__in', $args_custom['orderby']);

		// 3. Taxonomy Query
		register_taxonomy('genre', 'post');
		$args_tax = tw_post_query('post', ['genre' => [5, 10]]);
		$this->assertEquals('genre', $args_tax['tax_query'][0]['taxonomy']);
		$this->assertEquals([5, 10], $args_tax['tax_query'][0]['terms']);
	}

	/**
	 * Test 'related' order query logic.
	 */
	public function test_post_query_related(): void
	{
		global $wp_query;
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create(['taxonomy' => 'category']);

		wp_set_object_terms($post_id, [$term_id], 'category');
		$wp_query->queried_object = get_post($post_id);

		$args = tw_post_query('post', ['order' => 'related']);

		$this->assertContains($post_id, $args['post__not_in']);
		$this->assertEquals('category', $args['tax_query'][0]['taxonomy']);
		$this->assertContains($term_id, $args['tax_query'][0]['terms']);
	}

	/**
	 * Test post cache clearing hook.
	 */
	public function test_post_cache_clearing(): void
	{
		$id = self::factory()->post->create();
		tw_post_data('post', 'ID', ''); // Warm 'posts_ID' cache

		$key = 'posts_ID';
		$grp = 'twee_posts_post';

		$this->assertNotEmpty(wp_cache_get($key, $grp));

		wp_update_post(['ID' => $id, 'post_title' => 'New']); // Triggers save_post -> clear

		$this->assertFalse(wp_cache_get($key, $grp));
	}

	/**
	 * Test term cache clearing hook.
	 */
	public function test_post_terms_cache_clearing(): void
	{
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create(['taxonomy' => 'category']);

		tw_post_terms('category'); // Warm cache

		$key = 'post_terms';
		$grp = 'twee_post_terms_category';

		$this->assertNotEmpty(wp_cache_get($key, $grp));

		wp_set_object_terms($post_id, [$term_id], 'category'); // Triggers set_object_terms -> clear

		$this->assertFalse(wp_cache_get($key, $grp));
	}

	/**
	 * Test specific logic branches: Comma-sep fields, Multi-status, Custom order.
	 */
	public function test_post_data_logic_branches(): void
	{
		$id1 = self::factory()->post->create(['post_title' => 'A', 'post_status' => 'publish', 'menu_order' => 1]);
		$id2 = self::factory()->post->create(['post_title' => 'B', 'post_status' => 'draft', 'menu_order' => 2]);

		// 1. Comma-separated fields (Explode logic)
		$res_comma = tw_post_data('post', 'ID', 'post_title, post_status');
		$this->assertIsArray($res_comma[$id1]);
		$this->assertArrayHasKey('post_title', $res_comma[$id1]);

		// 2. Multi-status (IN clause)
		$res_multi = tw_post_data('post', 'ID', 'post_title', 'publish, draft');
		$this->assertArrayHasKey($id1, $res_multi);
		$this->assertArrayHasKey($id2, $res_multi);

		// 3. Complex order (crc32 key)
		$res_order = tw_post_data('post', 'ID', 'post_title', '', 'p.menu_order DESC');
		$this->assertNotEmpty($res_order);
		$this->assertEquals($id2, array_key_first($res_order)); // Descending order
	}

	/**
	 * Test caching defaults and fallback logic.
	 */
	public function test_post_data_caching_and_defaults(): void
	{
		$id = self::factory()->post->create(['post_title' => 'Cache Test']);

		// 1. Empty order -> Default 'p.ID ASC'
		$res_def = tw_post_data('post', 'ID', 'post_title', '', '');
		$this->assertArrayHasKey($id, $res_def);

		// 2. Cache Hit -> Early return
		tw_post_data('post', 'ID', 'post_title', 'publish'); // Warm cache
		$res_cache = tw_post_data('post', 'ID', 'post_title', 'publish'); // Hit cache
		$this->assertArrayHasKey($id, $res_cache);

		// 3. Missing field fallback
		$res_fallback = tw_post_data('post', 'ID', ['post_title', 'comment_count']);
		$this->assertArrayHasKey('comment_count', $res_fallback[$id]);
	}

	/**
	 * Test thread logic branches: Empty map, Reversal, Cache.
	 */
	public function test_post_term_thread_logic_branches(): void
	{
		$post_id = self::factory()->post->create();
		$taxonomy = 'category';

		// 1. Empty Terms Map Fallback
		wp_set_object_terms($post_id, [], $taxonomy); // Clear default category
		$empty_thread = tw_post_term_thread($post_id, $taxonomy);
		$this->assertEmpty($empty_thread);

		// 2. Ancestor Reversal & Cache
		global $wpdb;
		$wpdb->delete($wpdb->terms, ['term_id' => 3]);
		$wpdb->delete($wpdb->term_taxonomy, ['term_taxonomy_id' => 3]);
		$wpdb->insert($wpdb->terms, ['term_id' => 3, 'name' => 'T3', 'slug' => 't3']);
		$wpdb->insert($wpdb->term_taxonomy, ['term_taxonomy_id' => 3, 'term_id' => 3, 'taxonomy' => $taxonomy]);
		clean_term_cache(3, $taxonomy);

		wp_set_object_terms($post_id, [3], $taxonomy);

		$thread_init = tw_post_term_thread($post_id, $taxonomy);
		$this->assertArrayHasKey(3, $thread_init);

		$thread_cached = tw_post_term_thread($post_id, $taxonomy);
		$this->assertSame($thread_init, $thread_cached);
	}

	/**
	 * Test ancestor traversal and reversal logic.
	 */
	public function test_post_term_thread_ancestors(): void
	{
		$post_id = self::factory()->post->create();
		$taxonomy = 'category';

		// Create hierarchy: Parent -> Child
		$parent_id = self::factory()->term->create(['taxonomy' => $taxonomy, 'name' => 'Parent']);
		$child_id = self::factory()->term->create(['taxonomy' => $taxonomy, 'name' => 'Child', 'parent' => $parent_id]);

		wp_set_object_terms($post_id, [$child_id], $taxonomy);
		tw_app_clear('twee_post_terms_' . $taxonomy);

		$thread = tw_post_term_thread($post_id, $taxonomy);

		$this->assertArrayHasKey($child_id, $thread);
		$this->assertNotEmpty($thread);
	}

	/**
	 * Test advanced query logic: Excludes, Offsets, Sorting, Meta Query.
	 */
	public function test_post_query_coverage(): void
	{
		// 1. Exclude & Offset
		$args_basic = tw_post_query('post', ['exclude' => [1, 2], 'offset' => 5]);
		$this->assertEquals([1, 2], $args_basic['post__not_in']);
		$this->assertEquals(5, $args_basic['offset']);

		// 2. Related Order (Append to exclude)
		global $wp_query;
		$curr_id = self::factory()->post->create();
		$wp_query->queried_object = get_post($curr_id);

		$args_rel = tw_post_query('post', ['order' => 'related', 'exclude' => [99]]);
		$this->assertContains(99, $args_rel['post__not_in']);
		$this->assertContains($curr_id, $args_rel['post__not_in']);

		// 3. Sorting (Date vs Title)
		$this->assertEquals('DESC', tw_post_query('post', ['order' => 'date'])['order']);
		$this->assertEquals('ASC', tw_post_query('post', ['order' => 'title'])['order']);

		// 4. Views Order (Meta Query)
		$args_view = tw_post_query('post', ['order' => 'views']);
		$this->assertArrayHasKey('meta_query', $args_view);
		$this->assertEquals(['views' => 'DESC', 'date' => 'DESC'], $args_view['orderby']);
	}

}