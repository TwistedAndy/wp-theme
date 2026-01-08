<?php

/**
 * Meta Library Integration Tests
 *
 * Verifies the lifecycle of metadata including batch fetching, chunking,
 * deduplication, and synchronization with the WordPress object cache.
 */
class MetaTest extends WP_UnitTestCase {

	/**
	 * Setup global test environment requirements.
	 */
	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();

		// Define constant required for custom logic
		if (!defined('TW_CACHE')) {
			define('TW_CACHE', true);
		}

		// Mock database helper if missing
		if (!function_exists('tw_app_database')) {
			function tw_app_database()
			{
				global $wpdb;

				return $wpdb;
			}
		}

		// Mock cache clearing helper if missing
		if (!function_exists('tw_app_clear')) {
			function tw_app_clear($group)
			{
				wp_cache_flush();
			}
		}
	}

	/**
	 * Reset the cache before every test instance.
	 */
	public function set_up(): void
	{
		parent::set_up();
		wp_cache_flush();
	}

	/**
	 * Test that tw_meta returns all meta values for a key.
	 */
	public function test_tw_meta_fetches_simple_data(): void
	{
		$post_id_1 = self::factory()->post->create();
		$post_id_2 = self::factory()->post->create();
		$meta_key = 'test_batch_key';

		add_post_meta($post_id_1, $meta_key, 'value_1');
		add_post_meta($post_id_2, $meta_key, 'value_2');

		$results = tw_meta('post', $meta_key);

		$this->assertIsArray($results);
		$this->assertArrayHasKey($post_id_1, $results);
		$this->assertEquals('value_1', $results[$post_id_1]);
		$this->assertEquals('value_2', $results[$post_id_2]);
	}

	/**
	 * Test that tw_meta decodes serialized data when requested.
	 */
	public function test_tw_meta_decodes_serialization(): void
	{
		$post_id = self::factory()->post->create();
		$meta_key = 'test_decode_key';
		$raw_data = ['foo' => 'bar'];

		update_post_meta($post_id, $meta_key, $raw_data);

		// 1. Fetch without decoding (default behavior)
		$raw_results = tw_meta('post', $meta_key, false);
		$this->assertTrue(is_serialized($raw_results[$post_id]));

		// 2. Fetch with decoding
		$decoded_results = tw_meta('post', $meta_key, true);
		$this->assertSame($raw_data, $decoded_results[$post_id]);
	}

	/**
	 * Test large dataset chunking logic (data > 100 items).
	 * Verifies split-key chunk creation.
	 */
	public function test_chunking_logic_creates_multiple_cache_keys(): void
	{
		global $wpdb;
		$meta_key = 'test_chunk_key';
		$item_count = 105;

		// Seed database directly for processing speed
		$bulk_values = [];
		for ($i = 1; $i <= $item_count; $i++) {
			$unique_id = 20000 + $i;
			$bulk_values[] = "($unique_id, '$meta_key', 'val_$i')";
		}
		$wpdb->query("INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(',', $bulk_values));

		// Trigger fetch to populate cache segments
		$results = tw_meta('post', $meta_key);
		$this->assertCount($item_count, $results);

		// Verify chunk map exists in cache
		$chunk_map = wp_cache_get($meta_key . '_chunks', 'twee_meta_post');
		$this->assertIsArray($chunk_map);
		$this->assertGreaterThan(1, count($chunk_map), 'Data should be segmented into multiple chunks.');

		// Verify first chunk contains exactly 100 items
		$first_chunk = wp_cache_get($meta_key . '_chunk_0', 'twee_meta_post');
		$this->assertIsArray($first_chunk);
		$this->assertCount(100, $first_chunk);
	}

	/**
	 * Test binary search logic in tw_meta_cache_key for chunk retrieval.
	 */
	public function test_cache_key_resolution_finds_correct_chunk(): void
	{
		$meta_key = 'chunked_key';
		// Simulate map: [Index => Start_ID (DESC)]
		$chunk_map = [0 => 500, 1 => 400, 2 => 300];
		wp_cache_set($meta_key . '_chunks', $chunk_map, 'twee_meta_post');

		// ID 450 should resolve to Chunk 0
		$this->assertEquals($meta_key . '_chunk_0', tw_meta_cache_key('post', 450, $meta_key));

		// ID 350 should resolve to Chunk 1
		$this->assertEquals($meta_key . '_chunk_1', tw_meta_cache_key('post', 350, $meta_key));

		// ID 50 should resolve to Chunk 2
		$this->assertEquals($meta_key . '_chunk_2', tw_meta_cache_key('post', 50, $meta_key));
	}

	/**
	 * Test that tw_meta_update returns false if value is unchanged.
	 */
	public function test_meta_update_ignores_identical_value(): void
	{
		$post_id = self::factory()->post->create();
		$meta_key = 'static_key';
		$value = 'same';
		add_post_meta($post_id, $meta_key, $value);

		$result = tw_meta_update('post', $post_id, $meta_key, $value);

		$this->assertFalse($result);
	}

	/**
	 * Test synchronization between native WP hooks and the custom cache.
	 */
	public function test_hooks_update_internal_cache(): void
	{
		$post_id = self::factory()->post->create();
		$meta_key = 'hook_key';
		$value = 'hook_val';

		// Warm up cache (Lazy Loading)
		tw_meta('post', $meta_key);

		// Trigger native WP add (fires 'added_post_meta')
		add_post_meta($post_id, $meta_key, $value);

		$cache_key = tw_meta_cache_key('post', $post_id, $meta_key);
		$cache_data = wp_cache_get($cache_key, 'twee_meta_post');

		$this->assertIsArray($cache_data);
		$this->assertArrayHasKey($post_id, $cache_data);
		$this->assertEquals($value, $cache_data[$post_id]);

		// Trigger native WP delete (fires 'deleted_post_meta')
		delete_post_meta($post_id, $meta_key);

		$updated_cache = wp_cache_get($cache_key, 'twee_meta_post');
		$this->assertArrayNotHasKey($post_id, $updated_cache);
	}

	/**
	 * Verify that update logic cleans up pre-existing duplicates.
	 */
	public function test_meta_update_removes_duplicates(): void
	{
		global $wpdb;
		$post_id = self::factory()->post->create();
		$meta_key = 'dupe_test_key';

		// Manually seed duplicates
		add_post_meta($post_id, $meta_key, 'value_1');
		add_post_meta($post_id, $meta_key, 'value_2');

		// Bypass native cache to trigger internal database fetch
		wp_cache_delete($post_id, 'post_meta');

		// Trigger deduplication update logic
		tw_meta_update('post', $post_id, $meta_key, 'final_value');

		$record_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key));

		$this->assertEquals(1, (int) $record_count, 'Deduplication failed to remove orphan rows.');
		$this->assertEquals('final_value', get_post_meta($post_id, $meta_key, true));
	}

	/**
	 * Test record deletion functionality.
	 */
	public function test_meta_delete_removes_record(): void
	{
		$post_id = self::factory()->post->create();
		$meta_key = 'del_key';
		add_post_meta($post_id, $meta_key, 'val');

		$result = tw_meta_delete('post', $post_id, $meta_key);

		$this->assertTrue($result);
		$this->assertEmpty(get_post_meta($post_id, $meta_key, true));
	}

	/**
	 * Ensure deletion returns false for non-existent keys.
	 */
	public function test_meta_delete_returns_false_on_failure(): void
	{
		$post_id = self::factory()->post->create();
		$result = tw_meta_delete('post', $post_id, 'fake_key');
		$this->assertFalse($result);

		// Test empty meta_key
		$this->assertFalse(tw_meta_delete('post', $post_id, ''));
	}

	/**
	 * Verify support for User and Term object types.
	 */
	public function test_supports_other_object_types(): void
	{
		$user_id = self::factory()->user->create();
		tw_meta_update('user', $user_id, 'bio', 'Hero');
		$this->assertEquals('Hero', tw_meta_get('user', $user_id, 'bio'));

		$term_id = self::factory()->term->create();
		tw_meta_update('term', $term_id, 'color', 'Red');
		$this->assertEquals('Red', tw_meta_get('term', $term_id, 'color'));
	}

	/**
	 * Verify cache key resolution exactly on chunk boundaries.
	 */
	public function test_cache_key_boundary_alignment(): void
	{
		$meta_key = 'boundary_key';
		$cache_group = 'twee_meta_post';

		// Map: Chunk 0 starts at 200, Chunk 1 starts at 100
		$boundary_map = [0 => 200, 1 => 100];
		wp_cache_set($meta_key . '_chunks', $boundary_map, $cache_group);

		$this->assertEquals($meta_key . '_chunk_0', tw_meta_cache_key('post', 200, $meta_key));
		$this->assertEquals($meta_key . '_chunk_1', tw_meta_cache_key('post', 100, $meta_key));
	}

	/**
	 * Test invalidation of native WP meta cache upon update.
	 */
	public function test_update_invalidates_standard_wp_cache(): void
	{
		$post_id = self::factory()->post->create();
		$meta_key = 'cache_inv_key';

		tw_meta_update('post', $post_id, $meta_key, 'v1');

		// Warm standard native WP cache
		get_post_meta($post_id, $meta_key, true);
		$this->assertNotEmpty(wp_cache_get($post_id, 'post_meta'));

		// Update via custom library
		tw_meta_update('post', $post_id, $meta_key, 'v2');

		$this->assertFalse(wp_cache_get($post_id, 'post_meta'));
	}

	/**
	 * Verify tw_meta handles missing cache segments gracefully.
	 */
	public function test_tw_meta_handles_partial_chunk_failure(): void
	{
		$meta_key = 'partial_chunk_key';
		$cache_group = 'twee_meta_post';

		$segment_map = [0 => 10, 1 => 20];
		wp_cache_set($meta_key . '_chunks', $segment_map, $cache_group);
		wp_cache_set($meta_key . '_chunk_0', [5 => 'exists'], $cache_group);

		$results = tw_meta('post', $meta_key);

		$this->assertCount(1, $results);
		$this->assertArrayHasKey(5, $results);
	}

	/**
	 * Verify decoding following a chunked database result fetch.
	 */
	public function test_tw_meta_decode_after_db_fetch(): void
	{
		global $wpdb;
		$meta_key = 'db_decode_test';
		$raw_data = ['id' => 1];
		$serialized_data = serialize($raw_data);

		// Insert into DB bypassing cache layers
		$wpdb->insert($wpdb->postmeta, [
			'post_id'    => 600,
			'meta_key'   => $meta_key,
			'meta_value' => $serialized_data
		]);

		wp_cache_delete($meta_key, 'twee_meta_post');

		$results = tw_meta('post', $meta_key, true);

		$this->assertIsArray($results[600]);
		$this->assertSame($raw_data, $results[600]);
	}

	/**
	 * Verify deduplication logic and orphan record cleanup.
	 */
	public function test_tw_meta_update_clean_orphans(): void
	{
		global $wpdb;
		$post_id = self::factory()->post->create();
		$meta_key = 'cleanup_logic_test';
		$target_val = 'match_me';

		// Seed initial duplicates
		$wpdb->insert($wpdb->postmeta, ['post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => 'old_val_1']);
		$wpdb->insert($wpdb->postmeta, ['post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => $target_val]);
		$wpdb->insert($wpdb->postmeta, ['post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => 'old_val_3']);

		// Clear cache to enter internal cleanup branch
		wp_cache_delete($post_id, 'post_meta');
		wp_cache_delete($meta_key, 'twee_meta_post');

		tw_meta_update('post', $post_id, $meta_key, $target_val);

		// Check database state after initial cleanup
		$rows_after_first = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key));
		$this->assertCount(2, $rows_after_first);

		tw_meta_update('post', $post_id, $meta_key, 'new_value');

		// Final single master record verification
		$rows_after_second = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key));
		$this->assertCount(1, $rows_after_second);
	}

	/**
	 * Verify serialization of array/object values for proper update comparison.
	 */
	public function test_tw_meta_serialize_comparison(): void
	{
		$post_id = self::factory()->post->create();
		$meta_key = 'serialization_compare_test';
		$raw_data = ['foo' => 'bar'];

		tw_meta_update('post', $post_id, $meta_key, $raw_data);

		// Attempt updating with identical array data
		$result = tw_meta_update('post', $post_id, $meta_key, $raw_data);

		$this->assertFalse($result, 'Failed to skip update for identical array data.');
	}

	/**
	 * Verify internal deduplication and collection logic in tw_meta_fetch_value().
	 */
	public function test_tw_meta_fetch_value_logic(): void
	{
		global $wpdb;
		$post_id = self::factory()->post->create();
		$meta_key = 'fetch_value_test_key';
		$target_val = 'match_me';

		// Seed duplicate rows
		$wpdb->insert($wpdb->postmeta, ['post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => 'old_1']);
		$wpdb->insert($wpdb->postmeta, ['post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => $target_val]);
		$wpdb->insert($wpdb->postmeta, ['post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => 'old_3']);

		// Execute internal fetch logic
		$returned_val = tw_meta_fetch_value('post', $post_id, $meta_key, $target_val);

		$this->assertEquals($target_val, $returned_val);

		// Verify database contains only the master record
		$remaining_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key));

		$this->assertCount(1, $remaining_rows);
		$this->assertEquals($target_val, $remaining_rows[0]->meta_value);

		// Test fallback behavior (picking first available record when no match exists)
		add_post_meta($post_id, $meta_key, 'temp_1');
		add_post_meta($post_id, $meta_key, 'temp_2');

		tw_meta_fetch_value('post', $post_id, $meta_key, 'non_existent_target');

		$final_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key));
		$this->assertEquals(1, (int) $final_count);
	}

}