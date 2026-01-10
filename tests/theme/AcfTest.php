<?php

/**
 * ACF Storage Engine Tests
 */
class AcfTest extends WP_UnitTestCase {

	public static array $mock_fields = [];

	public static array $acf_data = [];

	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();

		// Dynamic mock for acf_get_field to inject test definitions
		if (!function_exists('acf_get_field')) {
			function acf_get_field($key)
			{
				if (isset(AcfTest::$mock_fields[$key])) {
					return AcfTest::$mock_fields[$key];
				}

				// Fallback for tests relying on this specific hardcoded value
				if ($key === 'field_grp') {
					return ['type' => 'group'];
				}

				return false;
			}
		}

		// Mock ACF helpers
		if (!function_exists('acf_get_data')) {
			function acf_get_data($name)
			{
				return AcfTest::$acf_data[$name] ?? false;
			}
		}

		if (!function_exists('acf_set_data')) {
			function acf_set_data($name, $value)
			{
				AcfTest::$acf_data[$name] = $value;
			}
		}

		if (!function_exists('acf_flush_value_cache')) {
			function acf_flush_value_cache($post_id, $field_name)
			{
				return;
			}
		}
	}

	public function set_up(): void
	{
		parent::set_up();
		global $wpdb;
		$wpdb->suppress_errors();
		wp_cache_flush();
	}

	public function tear_down(): void
	{
		parent::tear_down();
		self::$acf_data = [];
		self::$mock_fields = [];
		unset($_POST['paged'], $_GET['action']);
	}

	/**
	 * Test hook registration.
	 */
	public function test_init_filters(): void
	{
		tw_acf_init_filters();

		// ACF Filters
		$this->assertEquals(20, has_filter('acf/pre_load_value', 'tw_acf_load_value'));
		$this->assertEquals(20, has_filter('acf/pre_update_value', 'tw_acf_save_value'));
		$this->assertEquals(10, has_filter('acf/pre_load_reference', 'tw_acf_load_reference'));
		$this->assertEquals(5, has_action('acf/pre_render_field', 'tw_acf_pre_render_field'));

		// Revision Hooks
		$this->assertEquals(10, has_action('wp_restore_post_revision', 'tw_acf_revision_restore'));
		$this->assertEquals(20, has_action('_wp_put_post_revision', 'tw_acf_revision_create'));
		$this->assertEquals(15, has_filter('_wp_post_revision_fields', 'tw_acf_revision_fields'));

		// Compression Hooks
		$this->assertEquals(5, has_action('edit_comment'));
		$this->assertEquals(5, has_action('profile_update'));
		$this->assertEquals(5, has_action('edit_term'));
		$this->assertEquals(5, has_action('save_post'));
	}

	/**
	 * Test basic ID parsing.
	 */
	public function test_decode_post_id(): void
	{
		$this->assertEquals(['type' => 'post', 'id' => 123], tw_acf_decode_post_id(123));
		$this->assertEquals(['type' => 'post', 'id' => 123], tw_acf_decode_post_id('123'));
		$this->assertEquals(['type' => 'term', 'id' => 45], tw_acf_decode_post_id('term_45'));
		$this->assertEquals(['type' => 'user', 'id' => 1], tw_acf_decode_post_id('user_1'));
		$this->assertEquals(['type' => 'option', 'id' => 'options'], tw_acf_decode_post_id('options'));
		$this->assertEquals(['type' => 'option', 'id' => 'my_option'], tw_acf_decode_post_id('my_option'));

		$post = self::factory()->post->create_and_get();
		$this->assertEquals(['type' => 'post', 'id' => $post->ID], tw_acf_decode_post_id($post));
	}

	/**
	 * Test ID decoding coverage for specific prefixes and objects.
	 */
	public function test_decode_post_id_coverage(): void
	{
		// Prefixes
		$this->assertEquals(['type' => 'post', 'id' => 5], tw_acf_decode_post_id('post_5'));
		$this->assertEquals(['type' => 'post', 'id' => 10], tw_acf_decode_post_id('attachment_10'));
		$this->assertEquals(['type' => 'blog', 'id' => 1], tw_acf_decode_post_id('blog_1'));
		$this->assertEquals(['type' => 'blog', 'id' => 2], tw_acf_decode_post_id('site_2'));
		$this->assertEquals(['type' => 'block', 'id' => 'block_abc'], tw_acf_decode_post_id('block_abc'));
		$this->assertEquals(['type' => 'option', 'id' => 'options'], tw_acf_decode_post_id('option'));

		// Objects
		$term = new WP_Term((object) ['term_id' => 55]);
		$this->assertEquals(['type' => 'term', 'id' => 55], tw_acf_decode_post_id($term));

		$user = new WP_User((object) ['ID' => 99]);
		$this->assertEquals(['type' => 'user', 'id' => 99], tw_acf_decode_post_id($user));

		$comment = new WP_Comment((object) ['comment_ID' => 101]);
		$this->assertEquals(['type' => 'comment', 'id' => 101], tw_acf_decode_post_id($comment));
	}

	/**
	 * Test encoding a Repeater field to indexed array.
	 */
	public function test_encode_repeater_data(): void
	{
		$field = [
			'type'       => 'repeater',
			'name'       => 'my_repeater',
			'sub_fields' => [
				['key' => 'field_sub_1', 'name' => 'sub_text', 'type' => 'text'],
				['key' => 'field_sub_2', 'name' => 'sub_num', 'type' => 'number'],
			]
		];

		$raw_values = [
			'row-0' => ['field_sub_1' => 'Row 1 Text', 'field_sub_2' => '10'],
			'row-1' => ['field_sub_1' => 'Row 2 Text', 'field_sub_2' => '20', 'acf_deleted' => 0],
			'row-2' => ['acf_deleted' => 1]
		];

		$encoded = tw_acf_encode_data($raw_values, $field);

		$this->assertIsArray($encoded);
		$this->assertCount(2, $encoded);
		$this->assertEquals('Row 1 Text', $encoded[0]['sub_text']);
		$this->assertEquals('Row 2 Text', $encoded[1]['sub_text']);
	}

	/**
	 * Test encoding Flexible Content.
	 */
	public function test_encode_flexible_content(): void
	{
		$field = [
			'type'    => 'flexible_content',
			'name'    => 'my_flex',
			'layouts' => [
				['name' => 'layout_a', 'sub_fields' => [['key' => 'field_a_1', 'name' => 'title', 'type' => 'text']]],
				['name' => 'layout_b', 'sub_fields' => [['key' => 'field_b_1', 'name' => 'image', 'type' => 'image']]]
			]
		];

		$raw_values = [
			['acf_fc_layout' => 'layout_a', 'field_a_1' => 'Hello World'],
			['acf_fc_layout' => 'layout_b', 'field_b_1' => 555]
		];

		$encoded = tw_acf_encode_data($raw_values, $field);

		$this->assertCount(2, $encoded);
		$this->assertEquals('layout_a', $encoded[0]['acf_fc_layout']);
		$this->assertEquals('Hello World', $encoded[0]['title']);
		$this->assertEquals(555, $encoded[1]['image']);
	}

	/**
	 * Test decoding repeater data back to ACF keys.
	 */
	public function test_decode_repeater_data(): void
	{
		$field = [
			'type'       => 'repeater',
			'name'       => 'my_repeater',
			'sub_fields' => [['key' => 'field_sub_1', 'name' => 'sub_text', 'type' => 'text']]
		];

		$stored_data = [['sub_text' => 'Stored Value']];
		$decoded = tw_acf_decode_data($stored_data, $field);

		$this->assertArrayHasKey('field_sub_1', $decoded[0]);
		$this->assertEquals('Stored Value', $decoded[0]['field_sub_1']);
	}

	/**
	 * Test decoding Flexible Content fields (Recursion & Layout mapping).
	 */
	public function test_decode_flexible_content_logic(): void
	{
		$field = [
			'type'    => 'flexible_content',
			'layouts' => [
				[
					'name'       => 'text_layout',
					'sub_fields' => [['key' => 'field_txt', 'name' => 'txt', 'type' => 'text']]
				],
				[
					'name'       => 'img_layout',
					'sub_fields' => [['key' => 'field_img', 'name' => 'img', 'type' => 'image']]
				]
			]
		];

		$values = [
			0 => ['acf_fc_layout' => 'text_layout', 'txt' => 'Hello'],
			1 => ['acf_fc_layout' => 'img_layout', 'img' => 123],
			2 => ['txt' => 'Skip me'],
			3 => ['acf_fc_layout' => 'non_existent']
		];

		$decoded = tw_acf_decode_data($values, $field);

		$this->assertCount(2, $decoded);
		$this->assertEquals('text_layout', $decoded[0]['acf_fc_layout']);
		$this->assertEquals('Hello', $decoded[0]['field_txt']);
		$this->assertEquals('img_layout', $decoded[1]['acf_fc_layout']);
		$this->assertEquals(123, $decoded[1]['field_img']);
	}

	/**
	 * Test decoding Group fields.
	 */
	public function test_decode_group_field_logic(): void
	{
		$field = [
			'type'       => 'group',
			'sub_fields' => [
				['key' => 'field_exist', 'name' => 'exist', 'type' => 'text'],
				['key' => 'field_miss', 'name' => 'miss', 'type' => 'text'],
				['key' => 'field_nested', 'name' => 'nested', 'type' => 'group', 'sub_fields' => [['key' => 'field_inner', 'name' => 'inner']]]
			]
		];

		$values = ['exist' => 'I am here', 'nested' => ['inner' => 'Deep']];

		$decoded = tw_acf_decode_data($values, $field);

		$this->assertEquals('I am here', $decoded['field_exist']);
		$this->assertSame('', $decoded['field_miss']);
		$this->assertEquals('Deep', $decoded['field_nested']['field_inner']);
	}

	/**
	 * Test decoding Repeater fields with missing sub-fields.
	 */
	public function test_decode_repeater_missing_subfield(): void
	{
		$field = [
			'type'       => 'repeater',
			'sub_fields' => [
				['key' => 'field_alpha', 'name' => 'alpha', 'type' => 'text'],
				['key' => 'field_beta', 'name' => 'beta', 'type' => 'text']
			]
		];

		$values = [0 => ['alpha' => 'Present']];
		$decoded = tw_acf_decode_data($values, $field);

		$this->assertEquals('Present', $decoded[0]['field_alpha']);
		$this->assertSame('', $decoded[0]['field_beta']);
	}

	/**
	 * Test decompress walker logic for Groups and Invalid Fields.
	 */
	public function test_decompress_walker_coverage(): void
	{
		$post_id = self::factory()->post->create();

		self::$mock_fields = [
			'field_grp'      => [
				'key'        => 'field_grp',
				'name'       => 'my_group',
				'type'       => 'group',
				'label'      => 'My Group',
				'sub_fields' => [['key' => 'field_sub_1', 'name' => 'known_sub', 'type' => 'text', 'label' => 'Known']]
			],
			'field_nameless' => ['key' => 'field_nameless', 'name' => '', 'type' => 'text']
		];

		update_post_meta($post_id, 'my_group', ['known_sub' => 'Found', 'unknown_sub' => 'Skipped']);
		update_post_meta($post_id, 'nameless_key', 'Some Value');
		update_post_meta($post_id, '_acf_map', ['my_group' => 'grp', 'nameless_key' => 'nameless']);

		$result = tw_acf_decompress_fields('post', $post_id);

		$group_key = 'my_group_my_group';
		$this->assertArrayHasKey($group_key, $result);
		$this->assertSame('', $result[$group_key]['value']);
		$this->assertEquals('Found', $result[$group_key . '_known_sub']['value']);
		$this->assertArrayNotHasKey($group_key . '_unknown_sub', $result);
		$this->assertArrayNotHasKey('nameless_key', $result);
	}

	/**
	 * Integration Test: Load Value.
	 */
	public function test_load_value(): void
	{
		$post_id = self::factory()->post->create();
		$field = ['key' => 'field_test', 'name' => 'test_field', 'type' => 'text'];

		update_post_meta($post_id, 'test_field', 'Loaded Value');

		$this->assertEquals('Loaded Value', tw_acf_load_value(null, $post_id, $field));
	}

	/**
	 * Test loading a 'clone' field type.
	 */
	public function test_load_value_clone(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, 'sub_field_a', 'Value A');
		update_post_meta($post_id, 'sub_field_b', 'Value B');

		$field = [
			'type'       => 'clone',
			'name'       => 'my_clone',
			'sub_fields' => [
				['key' => 'field_1', 'name' => 'sub_field_a', 'type' => 'text'],
				['key' => 'field_2', 'name' => 'sub_field_b', 'type' => 'text'],
			]
		];

		$result = tw_acf_load_value(null, $post_id, $field);

		$this->assertEquals('Value A', $result['field_1']);
		$this->assertEquals('Value B', $result['field_2']);
	}

	/**
	 * Test default value fallback.
	 */
	public function test_load_value_default(): void
	{
		$post_id = self::factory()->post->create();
		$field = ['type' => 'text', 'name' => 'missing_field', 'default_value' => 'Default Fallback'];

		$this->assertEquals('Default Fallback', tw_acf_load_value(null, $post_id, $field));
	}

	/**
	 * Test legacy flexible content check.
	 */
	public function test_load_value_legacy_flex(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, 'legacy_flex', ['layout_1', 'layout_2']);

		$field = ['type' => 'flexible_content', 'name' => 'legacy_flex', 'layouts' => [['name' => 'layout_1']]];

		$this->assertNull(tw_acf_load_value(null, $post_id, $field));
	}

	/**
	 * Test Google Map JSON decoding.
	 */
	public function test_load_value_google_map(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, 'my_map', json_encode(['lat' => 40.7128, 'address' => 'New York']));

		$field = ['type' => 'google_map', 'name' => 'my_map'];
		$result = tw_acf_load_value(null, $post_id, $field);

		$this->assertEquals('New York', $result['address']);
	}

	/**
	 * Test early returns in tw_acf_load_value.
	 */
	public function test_load_value_early_returns(): void
	{
		$field = ['name' => 'test_field', 'type' => 'text'];

		$this->assertEquals('bypass', tw_acf_load_value('bypass', 1, $field));
		$this->assertNull(tw_acf_load_value(null, 'field_123456', $field));
		$this->assertNull(tw_acf_load_value(null, null, $field));
	}

	/**
	 * Test loading option values.
	 */
	public function test_load_value_option_entity(): void
	{
		$field = ['name' => 'footer_text', 'type' => 'text'];
		update_option('options_footer_text', 'Copyright 2024');

		$this->assertEquals('Copyright 2024', tw_acf_load_value(null, 'options', $field));
	}

	/**
	 * Test validation when decoding fails.
	 */
	public function test_load_value_decode_validation(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, 'my_repeater', 'invalid_string_data');

		$field = ['name' => 'my_repeater', 'type' => 'repeater', 'sub_fields' => [['key' => 'sub_1', 'name' => 'sub']]];

		$this->assertNull(tw_acf_load_value(null, $post_id, $field));
	}

	/**
	 * Test repeater pagination logic.
	 */
	public function test_load_value_pagination(): void
	{
		$post_id = self::factory()->post->create();
		$data = [['val' => 1], ['val' => 2], ['val' => 3], ['val' => 4], ['val' => 5]];
		update_post_meta($post_id, 'my_paged_repeater', $data);

		$field = [
			'name'          => 'my_paged_repeater',
			'type'          => 'repeater',
			'pagination'    => true,
			'rows_per_page' => 2,
			'sub_fields'    => [['key' => 'sub_val', 'name' => 'val']]
		];

		// 1. Normal Load
		self::$acf_data = [];
		$this->assertCount(5, tw_acf_load_value(null, $post_id, $field));

		// 2. Rendering Active
		self::$acf_data['acf_is_rendering'] = true;
		$this->assertCount(2, tw_acf_load_value(null, $post_id, $field));

		// 3. AJAX Pagination
		self::$acf_data['acf_is_rendering'] = false;
		$_POST['paged'] = 2;

		$callback = function() use ($post_id, $field) {
			$page = tw_acf_load_value(null, $post_id, $field);
			$this->assertEquals(3, $page[0]['sub_val']);
		};
		add_action('wp_ajax_acf/ajax/query_repeater', $callback);
		do_action('wp_ajax_acf/ajax/query_repeater');
		remove_action('wp_ajax_acf/ajax/query_repeater', $callback);

		// 4. REST Call Bypass
		self::$acf_data['acf_is_rendering'] = true;
		self::$acf_data['acf_inside_rest_call'] = true;
		$this->assertCount(5, tw_acf_load_value(null, $post_id, $field));
	}

	/**
	 * Integration Test: Save a simple value.
	 */
	public function test_save_value_simple(): void
	{
		$post_id = self::factory()->post->create();
		$field = ['key' => 'field_123456', 'name' => 'simple_text', 'type' => 'text'];

		tw_acf_save_value(null, 'My Text Value', $post_id, $field);

		$this->assertEquals('My Text Value', get_post_meta($post_id, 'simple_text', true));
		$map = get_post_meta($post_id, '_acf_map', true);
		$this->assertEquals('123456', $map['simple_text']);
	}

	/**
	 * Integration Test: Save a repeater.
	 */
	public function test_save_value_complex(): void
	{
		$post_id = self::factory()->post->create();
		$field = [
			'key'        => 'field_rep_1',
			'name'       => 'my_repeater',
			'type'       => 'repeater',
			'sub_fields' => [['key' => 'field_sub_1', 'name' => 'title', 'type' => 'text']]
		];

		$values = ['row-0' => ['field_sub_1' => 'Item A'], 'row-1' => ['field_sub_1' => 'Item B']];
		tw_acf_save_value(null, $values, $post_id, $field);

		$stored = get_post_meta($post_id, 'my_repeater', true);
		$this->assertEquals('Item A', $stored[0]['title']);
	}

	/**
	 * Test Option Page saving.
	 */
	public function test_save_option_value(): void
	{
		$field = ['key' => 'field_opt_1', 'name' => 'site_color', 'type' => 'text'];
		tw_acf_save_value(null, 'Red', 'options', $field);

		$this->assertEquals('Red', get_option('options_site_color'));
		$this->assertArrayHasKey('site_color', get_option('options_acf_map'));
	}

	/**
	 * Test early returns in tw_acf_save_value.
	 */
	public function test_save_value_early_returns(): void
	{
		$field = ['name' => 'test', 'type' => 'text'];

		$this->assertEquals('bypass', tw_acf_save_value('bypass', 'val', 1, $field));
		$this->assertNull(tw_acf_save_value(null, 'val', 1, ['name' => 'test']));
		$this->assertNull(tw_acf_save_value(null, 'val', 'field_123', $field));
		$this->assertNull(tw_acf_save_value(null, 'val', null, $field));
	}

	/**
	 * Test saving a clone field (Recursive save).
	 */
	public function test_save_value_clone_field(): void
	{
		$post_id = self::factory()->post->create();
		$field = [
			'type'       => 'clone',
			'name'       => 'my_clone',
			'sub_fields' => [['key' => 'sub_1', 'name' => 'inner_text', 'type' => 'text']]
		];

		tw_acf_save_value(null, ['sub_1' => 'Cloned Value'], $post_id, $field);
		$this->assertEquals('Cloned Value', get_post_meta($post_id, 'inner_text', true));
	}

	/**
	 * Test repeater pagination saving (Chunk merging).
	 */
	public function test_save_value_repeater_pagination(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, 'paged_rep', [['val' => 10], ['val' => 20]]);

		$field = [
			'type'          => 'repeater',
			'name'          => 'paged_rep',
			'key'           => 'field_rep',
			'pagination'    => true,
			'rows_per_page' => 2,
			'sub_fields'    => [['key' => 'sub_val', 'name' => 'val', 'type' => 'text']]
		];

		do_action('acf/save_post');
		unset($_POST['_acf_form']);
		$_POST['paged'] = 2;

		tw_acf_save_value(null, [['sub_val' => 30], ['sub_val' => 40]], $post_id, $field);

		$merged = get_post_meta($post_id, 'paged_rep', true);
		$this->assertCount(4, $merged);
		$this->assertEquals(30, $merged[2]['val']);
	}

	/**
	 * Test repeater pagination saving for Options.
	 */
	public function test_save_value_repeater_pagination_options(): void
	{
		$field = [
			'type'          => 'repeater',
			'name'          => 'opt_rep',
			'key'           => 'field_opt_rep',
			'pagination'    => true,
			'rows_per_page' => 2,
			'sub_fields'    => [['key' => 'sub_val', 'name' => 'val', 'type' => 'text']]
		];

		do_action('acf/save_post');
		unset($_POST['_acf_form']);

		// Scenario A: Update Chunk
		update_option('options_opt_rep', [['val' => 'Old 1'], ['val' => 'Old 2']]);
		$_POST['paged'] = 1;

		tw_acf_save_value(null, [['sub_val' => 'New 1'], ['sub_val' => 'New 2']], 'options', $field);
		$this->assertEquals('New 1', get_option('options_opt_rep')[0]['val']);

		// Scenario B: Non-Array Fallback
		update_option('options_opt_rep', 'invalid');
		tw_acf_save_value(null, [['sub_val' => 'Fresh']], 'options', $field);
		$this->assertEquals('Fresh', get_option('options_opt_rep')[0]['val']);
	}

	/**
	 * Test updating an existing repeater page (Chunk overwrite).
	 */
	public function test_save_value_repeater_update_chunk(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, 'rep_field', [['val' => 'Original 1'], ['val' => 'Original 2']]);

		$field = [
			'type'          => 'repeater',
			'name'          => 'rep_field',
			'key'           => 'field_rep',
			'pagination'    => true,
			'rows_per_page' => 2,
			'sub_fields'    => [['key' => 'sub_val', 'name' => 'val', 'type' => 'text']]
		];

		do_action('acf/save_post');
		unset($_POST['_acf_form']);
		$_POST['paged'] = 1;

		tw_acf_save_value(null, [['sub_val' => 'Updated 1'], ['sub_val' => 'Updated 2']], $post_id, $field);

		$result = get_post_meta($post_id, 'rep_field', true);
		$this->assertEquals('Updated 1', $result[0]['val']);
	}

	/**
	 * Test deletion logic when saving empty values.
	 */
	public function test_save_value_deletion_logic(): void
	{
		$post_id = self::factory()->post->create();

		// Post Deletion
		update_post_meta($post_id, 'to_delete', 'val');
		update_post_meta($post_id, '_acf_map', ['to_delete' => 'key123']);
		tw_acf_save_value(null, '', $post_id, ['key' => 'field_key123', 'name' => 'to_delete', 'type' => 'text']);
		$this->assertEmpty(get_post_meta($post_id, 'to_delete', true));

		// Option Deletion
		update_option('options_opt_delete', 'val');
		update_option('options_acf_map', ['opt_delete' => 'key']);
		tw_acf_save_value(null, '', 'options', ['key' => 'field_opt', 'name' => 'opt_delete', 'type' => 'text']);
		$this->assertFalse(get_option('options_opt_delete'));
	}

	/**
	 * Test loading a reference (Key lookup).
	 */
	public function test_load_reference(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, '_acf_map', ['my_field' => 'ABC1234']);

		$this->assertEquals('field_ABC1234', tw_acf_load_reference(null, 'my_field', $post_id));
	}

	/**
	 * Test reference loading coverage.
	 */
	public function test_load_reference_coverage(): void
	{
		// Invalid Entity
		$this->assertEquals('original', tw_acf_load_reference('original', 'field', null));

		// Option Page
		update_option('options_acf_map', ['opt_field' => 'key_opt']);
		$this->assertEquals('field_key_opt', tw_acf_load_reference(null, 'opt_field', 'options'));

		// Invalid Map
		update_option('options_acf_map', 'invalid');
		$this->assertEquals('default', tw_acf_load_reference('default', 'opt_field', 'options'));
	}

	/**
	 * Test pre-render logic for repeaters.
	 */
	public function test_pre_render_field(): void
	{
		tw_acf_pre_render_field(['type' => 'text', 'name' => 'txt']);
		$this->assertFalse(has_filter('acf/pre_load_metadata', 'tw_acf_total_rows'));

		tw_acf_pre_render_field(['type' => 'repeater', 'name' => 'rep']);
		$this->assertTrue(acf_get_data('acf_is_rendering'));

		remove_filter('acf/pre_load_metadata', 'tw_acf_total_rows');
		self::$acf_data = [];
	}

	/**
	 * Test total rows calculation logic.
	 */
	public function test_total_rows_calculation(): void
	{
		$post_id = self::factory()->post->create();

		$this->assertEquals('default', tw_acf_total_rows('default', null, 'field'));

		// Array Count
		update_post_meta($post_id, 'my_repeater', [1, 2, 3]);
		add_filter('acf/pre_load_metadata', 'tw_acf_total_rows', 10, 3);
		$this->assertEquals(3, tw_acf_total_rows(null, $post_id, 'my_repeater'));

		// Option Numeric
		update_option('optionsopt_rep', '5');
		add_filter('acf/pre_load_metadata', 'tw_acf_total_rows', 10, 3);
		$this->assertEquals(5, tw_acf_total_rows(null, 'options', 'opt_rep'));
	}

	/* -------------------------------------------------------------------------
	 * Compression
	 * ------------------------------------------------------------------------- */

	/**
	 * Test compression early returns.
	 */
	public function test_compress_meta_early_returns(): void
	{
		$post_id = self::factory()->post->create();

		// Restore Action
		$_GET['action'] = 'restore';
		tw_acf_compress_meta('post', $post_id);
		unset($_GET['action']);

		// Empty Meta
		tw_acf_compress_meta('post', $post_id);

		// No ACF Fields
		update_post_meta($post_id, 'regular_meta', 'some_val');
		tw_acf_compress_meta('post', $post_id);
		$this->assertEmpty(get_post_meta($post_id, '_acf_map', true));
	}

	/**
	 * Test full meta compression logic.
	 * Covers:
	 * - Identification of ACF fields (_key => field_...)
	 * - Orphaned reference key check
	 * - Walker invocation (Recursion for Repeater/Group)
	 * - Cleanup of old keys ($acf_remove)
	 * - Map creation and update
	 * - Empty value cleanup
	 * - Usage of existing map ($metadata['_acf_map'])
	 * - Deletion of map if empty
	 */
	public function test_compress_meta_logic(): void
	{
		$post_id = self::factory()->post->create();

		// --- Setup Raw ACF Data (Old Format) ---

		// 1. Simple Field
		update_post_meta($post_id, '_simple_text', 'field_simple123');
		update_post_meta($post_id, 'simple_text', 'Hello World');

		// 2. Repeater Field
		update_post_meta($post_id, '_my_repeater', 'field_rep456');
		update_post_meta($post_id, 'my_repeater', '1'); // Count = 1

		// Row 0
		update_post_meta($post_id, '_my_repeater_0_sub_val', 'field_sub789');
		update_post_meta($post_id, 'my_repeater_0_sub_val', 'Row 1 Value');

		// 3. Orphaned Key
		update_post_meta($post_id, '_orphan_field', 'field_orphan');

		// 4. Empty Value
		update_post_meta($post_id, '_empty_field', 'field_empty');
		update_post_meta($post_id, 'empty_field', '');

		// We seed an existing map with a key that is NOT in the current compression batch.
		// This ensures the function reads and merges with the existing map.
		update_post_meta($post_id, '_acf_map', ['existing_key' => 'old_map_val']);

		// --- Execute Compression ---
		tw_acf_compress_meta('post', $post_id);

		// 1. Map Verification
		$map = get_post_meta($post_id, '_acf_map', true);
		$this->assertIsArray($map);
		$this->assertEquals('simple123', $map['simple_text']); // Simple field mapped
		$this->assertEquals('rep456', $map['my_repeater']); // Repeater mapped
		$this->assertArrayNotHasKey('orphan_field', $map); // Orphan ignored

		// 2. Simple Field Verification
		$this->assertEquals('Hello World', get_post_meta($post_id, 'simple_text', true));

		// 3. Repeater Compression Verification
		$this->assertEmpty(get_post_meta($post_id, 'my_repeater_0_sub_val', true));
		$rep_data = get_post_meta($post_id, 'my_repeater', true);
		$this->assertIsArray($rep_data);
		$this->assertEquals('Row 1 Value', $rep_data[0]['sub_val']);

		// 4. Empty Value Verification
		$this->assertEmpty(get_post_meta($post_id, 'empty_field', true));
		$this->assertArrayNotHasKey('empty_field', $map);

		// We create a scenario where all fields are effectively empty or removed, resulting in an empty map.
		$post_delete_id = self::factory()->post->create();

		// Setup a field that will be deleted because it is empty
		update_post_meta($post_delete_id, '_to_delete', 'field_del');
		update_post_meta($post_delete_id, 'to_delete', ''); // Empty value triggers removal

		// Setup an existing map that only contains this key (so it becomes empty after removal)
		update_post_meta($post_delete_id, '_acf_map', ['to_delete' => 'del']);

		// Execute
		tw_acf_compress_meta('post', $post_delete_id);

		// Assert Map is Deleted
		$this->assertEmpty(get_post_meta($post_delete_id, '_acf_map', true));
	}

	/**
	 * Test Group compression via Walker.
	 */
	public function test_compress_walker_group(): void
	{
		$post_id = self::factory()->post->create();
		update_post_meta($post_id, '_my_group', 'field_grp');
		update_post_meta($post_id, 'my_group', '');
		update_post_meta($post_id, '_my_group_inner', 'field_inner');
		update_post_meta($post_id, 'my_group_inner', 'Inner Value');

		// Mock acf_get_field handled by set_up_before_class (field_grp)
		tw_acf_compress_meta('post', $post_id);

		$data = get_post_meta($post_id, 'my_group', true);
		$this->assertEquals('Inner Value', $data['inner']);
	}

	/**
	 * Test compression hooks for non-post objects.
	 */
	public function test_compression_hooks_integration(): void
	{
		tw_acf_init_filters();

		// Comment
		$comment_id = self::factory()->comment->create();
		update_metadata('comment', $comment_id, '_c_field', 'field_c');
		update_metadata('comment', $comment_id, 'c_field', 'Val');
		do_action('edit_comment', $comment_id);
		$this->assertEquals('c', get_metadata('comment', $comment_id, '_acf_map', true)['c_field']);

		// User
		$user_id = self::factory()->user->create();
		$user = get_user_by('id', $user_id);
		update_metadata('user', $user_id, '_u_field', 'field_u');
		update_metadata('user', $user_id, 'u_field', 'Val');
		do_action('profile_update', $user_id, $user);
		$this->assertEquals('u', get_metadata('user', $user_id, '_acf_map', true)['u_field']);

		// Term
		$term_id = self::factory()->term->create();
		update_metadata('term', $term_id, '_t_field', 'field_t');
		update_metadata('term', $term_id, 't_field', 'Val');
		do_action('edit_term', $term_id);
		$this->assertEquals('t', get_metadata('term', $term_id, '_acf_map', true)['t_field']);
	}

	/**
	 * Test revision field generation and merging.
	 */
	public function test_revision_fields(): void
	{
		$this->assertEquals([], tw_acf_revision_fields([], ['ID' => 0]));

		$post_id = self::factory()->post->create();
		wp_update_post(['ID' => $post_id, 'post_title' => 'Updated']); // Force revision

		self::$mock_fields = [
			'field_flex' => [
				'key'     => 'field_flex',
				'name'    => 'flex',
				'type'    => 'flexible_content',
				'label'   => 'Flex',
				'layouts' => [
					[
						'key'        => 'l_1',
						'name'       => 'l_1',
						'label'      => 'Hero',
						'sub_fields' => [['key' => 'f_sub', 'name' => 'sub', 'type' => 'text', 'label' => 'Sub']]
					]
				]
			],
			'field_rep'  => [
				'key'        => 'field_rep',
				'name'       => 'rep',
				'type'       => 'repeater',
				'label'      => 'Rep',
				'sub_fields' => [['key' => 'f_rep_sub', 'name' => 'val', 'type' => 'text', 'label' => 'Val']]
			]
		];

		update_post_meta($post_id, '_acf_map', ['flex' => 'flex', 'rep' => 'rep']);
		update_post_meta($post_id, 'flex', [['acf_fc_layout' => 'l_1', 'sub' => 'Hero Text']]);
		update_post_meta($post_id, 'rep', [['val' => 'Rep Item']]);

		$fields = tw_acf_revision_fields([], ['ID' => $post_id]);

		// Flex Check
		$key_flex = 'twee_acf_flex_0_sub';
		$this->assertEquals('Hero - Sub', $fields[$key_flex]);
		$this->assertEquals('Hero Text', apply_filters("_wp_post_revision_field_$key_flex", '', $key_flex, get_post($post_id)));

		// Repeater Check
		$key_repeater = 'twee_acf_rep_rep_0_val';
		$this->assertEquals('Rep Item', apply_filters("_wp_post_revision_field_$key_repeater", '', $key_repeater, get_post($post_id)));
	}

	/**
	 * Test restoring ACF data from a revision.
	 */
	public function test_revision_restore(): void
	{
		$post_id = self::factory()->post->create();
		$revision_id = wp_insert_post(['post_type' => 'revision', 'post_parent' => $post_id]);

		$map = ['restored_field' => 'key_123'];
		update_metadata('post', $revision_id, '_acf_map', $map);
		update_metadata('post', $revision_id, 'restored_field', 'Restored Value');

		// 1. Success Scenario
		tw_acf_revision_restore($post_id, $revision_id);

		$this->assertEquals('Restored Value', get_post_meta($post_id, 'restored_field', true));
		$this->assertEquals($map, get_post_meta($post_id, '_acf_map', true));

		// Reset State for Failure Checks
		update_post_meta($post_id, 'restored_field', 'Reset');

		// 2. Failure: Empty Map
		update_metadata('post', $revision_id, '_acf_map', []); // Empty map
		tw_acf_revision_restore($post_id, $revision_id);
		$this->assertEquals('Reset', get_post_meta($post_id, 'restored_field', true));

		// 3. Failure: Post Type Mismatch
		// We pass a standard post ID (post_type='post') instead of a revision.
		// Even if it has a map, it should fail validation.
		$standard_post_id = self::factory()->post->create();
		update_metadata('post', $standard_post_id, '_acf_map', $map);

		tw_acf_revision_restore($post_id, $standard_post_id);
		$this->assertEquals('Reset', get_post_meta($post_id, 'restored_field', true));

		// 4. Failure: Parent Mismatch
		// Create a revision belonging to a DIFFERENT post
		$other_post_id = self::factory()->post->create();
		$other_revision_id = wp_insert_post(['post_type' => 'revision', 'post_parent' => $other_post_id]);
		update_metadata('post', $other_revision_id, '_acf_map', $map);

		tw_acf_revision_restore($post_id, $other_revision_id);
		$this->assertEquals('Reset', get_post_meta($post_id, 'restored_field', true));
	}

	/**
	 * Test creating a revision (copy from parent).
	 */
	public function test_revision_create(): void
	{
		$post_id = self::factory()->post->create();
		$map = ['field_copy' => 'key_copy'];
		update_post_meta($post_id, '_acf_map', $map);
		update_post_meta($post_id, 'field_copy', 'Parent Value');

		$revision_id = wp_insert_post(['post_type' => 'revision', 'post_parent' => $post_id]);
		tw_acf_revision_create($revision_id);

		$this->assertEquals($map, get_metadata('post', $revision_id, '_acf_map', true));
		$this->assertEquals('Parent Value', get_metadata('post', $revision_id, 'field_copy', true));

		// Check Failures
		$not_revision_id = self::factory()->post->create();
		tw_acf_revision_create($not_revision_id);
		$this->assertEmpty(get_metadata('post', $not_revision_id, '_acf_map', true));
	}

}