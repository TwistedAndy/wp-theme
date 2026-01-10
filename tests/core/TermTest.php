<?php

/**
 * Term Library Integration Tests
 */
class TermTest extends WP_UnitTestCase {

	/* -------------------------------------------------------------------------
	 * Setup & Mocking
	 * ------------------------------------------------------------------------- */

	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();

		// Mock database helper if missing
		if (!function_exists('tw_app_database')) {
			function tw_app_database()
			{
				global $wpdb;

				return $wpdb;
			}
		}

		// Mock app clear helper if missing
		if (!function_exists('tw_app_clear')) {
			function tw_app_clear($group)
			{
				wp_cache_flush();
			}
		}

		// Mock post terms helper (dependency for tw_term_links)
		if (!function_exists('tw_post_terms')) {
			function tw_post_terms($taxonomy)
			{
				return [1 => [2]]; // Simple mock: Post 1 -> Term 2
			}
		}
	}

	public function set_up(): void
	{
		parent::set_up();
		wp_cache_flush();
	}


	/* -------------------------------------------------------------------------
	 * Data Retrieval Tests (tw_term_data, tw_term_taxonomies)
	 * ------------------------------------------------------------------------- */

	/**
	 * Test tw_term_data: Single field, all fields, and cache hits.
	 */
	public function test_tw_term_data(): void
	{
		$term_id = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'Test Cat', 'description' => 'Desc']);

		// 1. Fetch Name (Default)
		$data_name = tw_term_data('term_id', 'name', 'category');
		$this->assertArrayHasKey($term_id, $data_name);
		$this->assertEquals('Test Cat', $data_name[$term_id]);

		// 2. Fetch All Fields
		$data_all = tw_term_data('term_id', 'all', 'category');
		$this->assertEquals('Desc', $data_all[$term_id]['description']);

		// 3. Cache Hit
		$data_cached = tw_term_data('term_id', 'name', 'category');
		$this->assertSame($data_name, $data_cached);
	}

	/**
	 * Test tw_term_taxonomies: Grouping by taxonomy and field selection.
	 */
	public function test_tw_term_taxonomies(): void
	{
		$cat_id = self::factory()->term->create(['taxonomy' => 'category']);
		$tag_id = self::factory()->term->create(['taxonomy' => 'post_tag']);

		// 1. Group all taxonomies
		$grouped = tw_term_taxonomies('', 'term_id');
		$this->assertArrayHasKey('category', $grouped);
		$this->assertArrayHasKey('post_tag', $grouped);
		$this->assertContains($cat_id, $grouped['category']);

		// 2. Filter by specific taxonomy
		$cat_only = tw_term_taxonomies('category', 'term_id');
		$this->assertContains($cat_id, $cat_only);
		$this->assertNotContains($tag_id, $cat_only);
	}

	/**
	 * Test tw_term_taxonomies logic branches: 'all' field, specific fields, and cache.
	 */
	public function test_term_taxonomies_logic_branches(): void
	{
		$cat_id = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'Cat Name', 'slug' => 'cat-slug']);

		// 1. Specific Taxonomy + Field 'all'
		// Covers: } elseif ($field == 'all') { ... }
		$res_all = tw_term_taxonomies('category', 'all');
		$this->assertEquals('Cat Name', $res_all[$cat_id]['name']);

		// 2. Specific Taxonomy + Arbitrary Field ('slug')
		// Covers: } elseif (isset($term[$field])) { ... }
		$res_slug = tw_term_taxonomies('category', 'slug');
		$this->assertEquals('cat-slug', $res_slug[$cat_id]);

		// 3. Grouped Taxonomies + Field 'all'
		// Covers: } elseif ($field == 'all') { ... }
		$res_grouped_all = tw_term_taxonomies('', 'all');
		$this->assertEquals('Cat Name', $res_grouped_all['category'][$cat_id]['name']);

		// 4. Grouped Taxonomies + Arbitrary Field ('slug')
		// Covers: } elseif (isset($term[$field])) { ... }
		$res_grouped_slug = tw_term_taxonomies('', 'slug');
		$this->assertEquals('cat-slug', $res_grouped_slug['category'][$cat_id]);

		// 5. Cache Hit
		$this->assertSame($res_grouped_slug, tw_term_taxonomies('', 'slug'));
	}


	/* -------------------------------------------------------------------------
	 * Link Generation Tests (tw_term_links, tw_term_link)
	 * ------------------------------------------------------------------------- */

	/**
	 * Test tw_term_links: Standard HTML generation and robustness.
	 */
	public function test_tw_term_links(): void
	{
		global $wpdb;

		// Setup Data to match Mock (Post 1 -> Term 2)
		$wpdb->delete($wpdb->terms, ['term_id' => 2]);
		$wpdb->delete($wpdb->term_taxonomy, ['term_taxonomy_id' => 2]);
		$wpdb->insert($wpdb->terms, ['term_id' => 2, 'name' => 'Term 2', 'slug' => 'term-2']);
		$wpdb->insert($wpdb->term_taxonomy, ['term_taxonomy_id' => 2, 'term_id' => 2, 'taxonomy' => 'category']);
		clean_term_cache(2, 'category');

		if (!get_post(1)) {
			$wpdb->insert($wpdb->posts, ['ID' => 1, 'post_title' => 'Mock Post', 'post_status' => 'publish']);
		}

		// Satisfy real DB query while matching Mock
		wp_set_object_terms(1, [2], 'category');
		tw_app_clear('twee_terms');
		tw_app_clear('twee_post_terms_category');

		// 1. Standard HTML Link
		$links = tw_term_links(1, 'category', 'my-class', true);
		$this->assertNotEmpty($links);
		$this->assertStringContainsString('<a href=', $links[0]);
		$this->assertStringContainsString('class="my-class"', $links[0]);

		// 2. Span Output
		$spans = tw_term_links(1, 'category', 'my-class', false);
		$this->assertNotEmpty($spans);
		$this->assertStringContainsString('<span', $spans[0]);
	}

	/**
	 * Test tw_term_links logic branches: Object input, Invalid IDs, Plain text.
	 */
	public function test_term_links_coverage(): void
	{
		global $wpdb;

		// Ensure Data Exists
		$wpdb->delete($wpdb->terms, ['term_id' => 2]);
		$wpdb->delete($wpdb->term_taxonomy, ['term_taxonomy_id' => 2]);
		$wpdb->insert($wpdb->terms, ['term_id' => 2, 'name' => 'Term 2', 'slug' => 'term-2']);
		$wpdb->insert($wpdb->term_taxonomy, ['term_taxonomy_id' => 2, 'term_id' => 2, 'taxonomy' => 'category']);
		clean_term_cache(2, 'category');

		if (!get_post(1)) {
			$wpdb->insert($wpdb->posts, ['ID' => 1, 'post_title' => 'Mock Post', 'post_status' => 'publish']);
		}
		wp_set_object_terms(1, [2], 'category');
		tw_app_clear('twee_terms');

		// 1. WP_Post Object Input
		// Covers: if ($post_id instanceof WP_Post) ...
		$links_obj = tw_term_links(new WP_Post((object) ['ID' => 1]), 'category');
		$this->assertStringContainsString('Term 2', $links_obj[0]);

		// 2. Non-numeric Input
		// Covers: if (!is_numeric($post_id)) ...
		$this->assertEmpty(tw_term_links('invalid', 'category'));

		// 3. Plain Text (No Link, No Class)
		// Covers: } else { $result[] = $labels[$term_id]; }
		$plain = tw_term_links(1, 'category', '', false);
		$this->assertEquals('Term 2', $plain[0]);
		$this->assertStringNotContainsString('<', $plain[0]);
	}

	/**
	 * Test tw_term_links: Invalid inputs (Array, Null).
	 */
	public function test_tw_term_links_non_numeric(): void
	{
		$this->assertEmpty(tw_term_links([], 'category'));
		$this->assertEmpty(tw_term_links(null, 'category'));
	}

	/**
	 * Test tw_term_link: Basic Permalinks.
	 */
	public function test_tw_term_link(): void
	{
		$term_id = self::factory()->term->create(['taxonomy' => 'category', 'slug' => 'link-test']);
		$this->assertStringContainsString('?cat=' . $term_id, tw_term_link($term_id, 'category'));
	}

	/**
	 * Test tw_term_link logic branches: Validations, fallbacks, and hierarchy.
	 */
	public function test_term_link_coverage(): void
	{
		// 1. Invalid/Non-Existent checks
		$this->assertEquals('', tw_term_link(123, 'non_existent_tax'));
		$this->assertEquals('', tw_term_link(99999, 'category'));

		// 2. Standard Category Fallback (?cat=)
		$cat_id = self::factory()->term->create(['taxonomy' => 'category']);
		tw_app_clear('twee_terms_category');
		$this->assertStringContainsString("?cat=$cat_id", tw_term_link($cat_id, 'category'));

		// 3. Custom Taxonomy Fallback (?taxonomy=...&term=...)
		register_taxonomy('query_tax', 'post', ['public' => true, 'rewrite' => ['slug' => 'qt']]);
		$q_term = self::factory()->term->create(['taxonomy' => 'query_tax', 'slug' => 'qs']);
		tw_app_clear('twee_terms_query_tax');
		$this->assertStringContainsString("?taxonomy=query_tax&term=qs", tw_term_link($q_term, 'query_tax'));

		// 4. Hierarchical Query Param Check
		$hier_tax = 'hier_tax';
		register_taxonomy($hier_tax, 'post', ['public' => true, 'hierarchical' => true, 'rewrite' => ['slug' => 'ht']]);
		$child = self::factory()->term->create(['taxonomy' => $hier_tax, 'slug' => 'c', 'parent' => self::factory()->term->create(['taxonomy' => $hier_tax])]);
		tw_app_clear("twee_terms_{$hier_tax}");

		$link = tw_term_link($child, $hier_tax);
		$this->assertStringContainsString("?taxonomy={$hier_tax}&term=c", $link);

		// 5. Cache Hit
		$this->assertEquals($link, tw_term_link($child, $hier_tax));
	}

	/**
	 * Test tw_term_link: Rewrite rules (Pretty Permalinks).
	 * Covers hierarchical vs non-hierarchical path building.
	 */
	public function test_term_link_rewrites(): void
	{
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure('/%postname%/');
		$orig_permastructs = $wp_rewrite->extra_permastructs;

		// 1. Hierarchical (Category)
		$wp_rewrite->add_permastruct('category', 'category/%category%', ['with_front' => false]);
		$parent = self::factory()->term->create(['taxonomy' => 'category', 'slug' => 'p']);
		$child = self::factory()->term->create(['taxonomy' => 'category', 'slug' => 'c', 'parent' => $parent]);

		tw_app_clear('twee_terms_category');
		clean_term_cache($child, 'category');
		clean_term_cache($parent, 'category');

		// A. Test Child (Triggers loop over parents)
		// Expected: /category/p/c/
		$this->assertStringContainsString('/category/p/c', tw_term_link($child, 'category'));

		// B. Test Parent (No ancestors, Hierarchical)
		// Covers: } else { $list[] = $slug; }
		$this->assertStringContainsString('/category/p', tw_term_link($parent, 'category'));
		$this->assertStringNotContainsString('/p/p', tw_term_link($parent, 'category'));

		// 2. Flat (Post Tag)
		$wp_rewrite->add_permastruct('post_tag', 'tag/%post_tag%', ['with_front' => false]);
		$tag = self::factory()->term->create(['taxonomy' => 'post_tag', 'slug' => 't']);

		tw_app_clear('twee_terms_post_tag');
		clean_term_cache($tag, 'post_tag');

		// Expected: /tag/t/
		$this->assertStringContainsString('/tag/t', tw_term_link($tag, 'post_tag'));

		// Restore
		$wp_rewrite->extra_permastructs = $orig_permastructs;
		$wp_rewrite->set_permalink_structure('');
	}


	/* -------------------------------------------------------------------------
	 * Hierarchy Tests (Parents, Ancestors, Children, Tree)
	 * ------------------------------------------------------------------------- */

	/**
	 * Test tw_term_parents: Retrieval of parent map.
	 */
	public function test_tw_term_parents(): void
	{
		$parent_id = self::factory()->term->create(['taxonomy' => 'category']);
		$child_id = self::factory()->term->create(['taxonomy' => 'category', 'parent' => $parent_id]);

		$parents = tw_term_parents('category');
		$this->assertEquals($parent_id, $parents[$child_id]);
	}

	/**
	 * Test tw_term_ancestors: Recursive walker logic.
	 */
	public function test_tw_term_ancestors(): void
	{
		$grand = self::factory()->term->create(['taxonomy' => 'category']);
		$parent = self::factory()->term->create(['taxonomy' => 'category', 'parent' => $grand]);
		$child = self::factory()->term->create(['taxonomy' => 'category', 'parent' => $parent]);

		$ancestors = tw_term_ancestors($child, 'category');
		$this->assertContains($parent, $ancestors);
		$this->assertContains($grand, $ancestors);
	}

	/**
	 * Test tw_term_children: Full tree and specific branches.
	 */
	public function test_tw_term_children(): void
	{
		$parent = self::factory()->term->create(['taxonomy' => 'category']);
		$child = self::factory()->term->create(['taxonomy' => 'category', 'parent' => $parent]);
		$grand = self::factory()->term->create(['taxonomy' => 'category', 'parent' => $child]);

		// 1. Full Tree
		$tree = tw_term_children(0, 'category');
		$this->assertArrayHasKey($parent, $tree);

		// 2. Specific Branch (passed parents array)
		$branch = tw_term_children($parent, 'category', tw_term_parents('category'));
		$this->assertContains($child, $branch);
		$this->assertContains($grand, $branch);
	}

	/**
	 * Test tw_term_children: Cache hit.
	 */
	public function test_term_children_cache(): void
	{
		$parent = self::factory()->term->create(['taxonomy' => 'category']);
		tw_app_clear('twee_terms_category');

		$tree_1 = tw_term_children(0, 'category');
		$tree_2 = tw_term_children(0, 'category'); // Cache hit

		$this->assertSame($tree_1, $tree_2);
	}

	/**
	 * Test tw_term_tree: Hierarchical structure and flattening.
	 */
	public function test_tw_term_tree(): void
	{
		$parent = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'A Parent']);
		$child = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'B Child', 'parent' => $parent]);

		// 1. Hierarchical Tree
		$tree = tw_term_tree('category', false);

		// Verify structure
		$parent_node = null;
		foreach ($tree as $node) {
			if ($node['id'] == $parent) $parent_node = $node;
		}
		$this->assertNotEmpty($parent_node['children']);
		$this->assertEquals($child, current($parent_node['children'])['id']);

		// 2. Flattened Tree
		$flat = tw_term_tree('category', true);
		$found_child = false;
		foreach ($flat as $item) {
			if ($item['id'] == $child) {
				$found_child = true;
				$this->assertEmpty($item['children']);
			}
		}
		$this->assertTrue($found_child);
	}

	/**
	 * Test tw_term_tree logic branches: Invalid tax, Flat sorting, Cache.
	 */
	public function test_term_tree_coverage(): void
	{
		// 1. Invalid Taxonomy
		$this->assertEmpty(tw_term_tree('invalid'));

		// 2. Flat Taxonomy Logic (Sorting & Parent 0)
		$flat_tax = 'flat_tax';
		register_taxonomy($flat_tax, 'post', ['hierarchical' => false, 'public' => true]);

		$t2 = self::factory()->term->create(['taxonomy' => $flat_tax, 'name' => 'Zebra']);
		$t1 = self::factory()->term->create(['taxonomy' => $flat_tax, 'name' => 'Apple']);

		tw_app_clear("twee_terms_{$flat_tax}");
		$tree = tw_term_tree($flat_tax, false);

		// Verify sorting (Apple first)
		$this->assertEquals('Apple', $tree[0]['name']);
		$this->assertEquals(0, $tree[0]['parent']);

		// 3. Flattened Cache Hit
		$this->assertSame(tw_term_tree($flat_tax, true), tw_term_tree($flat_tax, true));
	}


	/* -------------------------------------------------------------------------
	 * Ordering & Post Association Tests (tw_term_order, tw_term_posts)
	 * ------------------------------------------------------------------------- */

	/**
	 * Test tw_term_order: Custom meta ordering.
	 */
	public function test_tw_term_order(): void
	{
		$t1 = self::factory()->term->create(['taxonomy' => 'category']);
		$t2 = self::factory()->term->create(['taxonomy' => 'category']);

		update_term_meta($t1, 'custom_order', 10);
		update_term_meta($t2, 'custom_order', 5);

		tw_app_clear('twee_terms');
		clean_term_cache($t1, 'category');
		clean_term_cache($t2, 'category');

		$order = tw_term_order('term_id', 'category', 'custom_order');
		$this->assertEquals(5, $order[$t2]);
		$this->assertEquals(10, $order[$t1]);
	}

	/**
	 * Test tw_term_order logic branches: Global fetch, defaults, cache.
	 */
	public function test_term_order_coverage(): void
	{
		$t1 = self::factory()->term->create(['taxonomy' => 'category', 'name' => 'T1']);
		$tag = self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Tag']);

		update_term_meta($t1, 'order', 99);
		update_term_meta($tag, 'custom', 20);

		tw_app_clear('twee_terms');

		// 1. Global Fetch (Empty Tax)
		// Covers: } else { $where = ''; }
		$global = tw_term_order('term_id', '', 'custom');
		$this->assertEquals(20, $global[$tag]);

		// 2. Default Meta Key ('order')
		// Covers: } else { $meta_key = 'order'; }
		$default = tw_term_order('term_id', 'category', '');
		$this->assertEquals(99, $default[$t1]);

		// 3. Cache Hit
		$this->assertSame($default, tw_term_order('term_id', 'category', ''));
	}

	/**
	 * Test tw_term_posts: Basic and Hierarchical Fetch.
	 */
	public function test_tw_term_posts(): void
	{
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create(['taxonomy' => 'category']);
		wp_set_object_terms($post_id, [$term_id], 'category');

		// 1. Basic Fetch
		$map = tw_term_posts('category', 'post', '', false);
		$this->assertContains($post_id, $map[$term_id]);

		// 2. Hierarchical Fetch
		$map_children = tw_term_posts('category', 'post', '', true);
		$this->assertArrayHasKey($term_id, $map_children);
	}

	/**
	 * Test tw_term_posts logic branches: Merging, Fallbacks, Comma-lists.
	 */
	public function test_term_posts_coverage(): void
	{
		$parent = self::factory()->term->create(['taxonomy' => 'category']);
		$child = self::factory()->term->create(['taxonomy' => 'category', 'parent' => $parent]);
		$tag = self::factory()->term->create(['taxonomy' => 'post_tag']);

		$p1 = self::factory()->post->create(['post_type' => 'post', 'post_status' => 'publish']);
		$p2 = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'draft']);

		wp_set_object_terms($p1, [$child], 'category');
		wp_set_object_terms($p2, [$tag], 'post_tag');

		tw_app_clear('twee_post_terms_category');
		tw_app_clear('twee_post_terms_post_tag');
		tw_app_clear('twee_terms_category');

		// 1. Hierarchy Merge
		// Covers: if ($term_ids) { array_merge... }
		$res_hier = tw_term_posts('category', '', '', true);
		$this->assertContains($p1, $res_hier[$parent]);

		// 2. Non-Hierarchical / Flat
		// Covers: if ($object instanceof WP_Taxonomy and empty($object->hierarchical))
		$res_flat = tw_term_posts('post_tag', 'page', '', true);
		$this->assertContains($p2, $res_flat[$tag]);

		// 3. Comma-Separated Logic
		// Covers: if (strpos($status, ',')) ...
		$res_multi = tw_term_posts('category', 'post,page', 'publish,draft', false);
		$this->assertIsArray($res_multi);

		// 4. Single Status Logic (No Comma)
		// Covers: } else { $statuses = [esc_sql($status)]; }
		// We use children=false to force SQL generation path.
		$res_single = tw_term_posts('category', 'post', 'publish', false);
		$this->assertContains($p1, $res_single[$child]);
	}

	/**
	 * Test hook registration.
	 */
	public function test_term_cache_clearing_hooks(): void
	{
		$this->assertEquals(10, has_action('clean_taxonomy_cache', 'tw_term_clear_taxonomy'));
		$this->assertEquals(10, has_action('edited_terms', 'tw_term_clear_ids'));
	}

}