<?php
/**
 * Terms Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */

namespace Twee;

use WP_Post;
use WP_Term;

class Terms {

	/**
	 * Build an array with the post terms and their parents
	 *
	 * @param bool|int    $post_id  Post ID or false for the current post
	 *
	 * @param bool|string $taxonomy Post
	 *
	 * @return array
	 */
	public function threads($post_id = false, $taxonomy = false) {

		if ($post_id === false) {
			$post_id = get_the_ID();
		}

		if ($taxonomy === false) {
			$taxonomy = $this->postTaxonomy($post_id);
		}

		$threads = [];

		$categories = $this->postTerms($post_id, $taxonomy, false);

		if (is_array($categories) and $categories) {

			foreach ($categories as $category) {

				$category_thread = $this->thread($category, true, false);

				if (is_array($category_thread) and $category_thread) {
					$threads[$category_thread[0]] = $category_thread;
				}

			}

		}

		return $threads;

	}


	/**
	 * Build an array with parent and nested terms for a given term ID
	 *
	 * @param int|WP_Term $term             Term
	 * @param bool        $include_parents  Include parent terms
	 * @param bool        $include_children Include children terms
	 *
	 * @return array
	 */
	public function thread($term, $include_parents = true, $include_children = true) {

		$result = [];

		if (is_numeric($term)) {
			$term = get_term($term);
		}

		if (is_object($term) and $term instanceof WP_Term) {

			$term_id = $term->term_id;

			$taxonomy = $term->taxonomy;

			$cache_key = $taxonomy . '_thread_' . $term_id . '_' . intval($include_parents) . intval($include_children);

			$result = wp_cache_get($cache_key, 'twee');

			if (empty($result)) {

				$result = [];

				if ($include_parents) {
					$parents = get_ancestors($term_id, $taxonomy);
					if (is_array($parents) and $parents) {
						$result = array_reverse($parents);
					}
				}

				$result[] = $term_id;

				if ($include_children) {
					$children = get_term_children($term_id, $taxonomy);
					if (is_array($children) and $children) {
						$result = array_merge($result, $children);
					}
				}

				$result = array_unique($result, SORT_NUMERIC);

				wp_cache_add($cache_key, $result, 'twee', 60);

			}

		}

		return $result;

	}


	/**
	 * Convert a simple terms list to a hierarchical array
	 *
	 * @param WP_Term[] $terms  Array with terms
	 * @param int       $parent Parent term ID
	 *
	 * @return WP_Term[]
	 */
	public function tree($terms, $parent = 0) {

		$branch = [
			'user_login' => trim(wp_unslash($_POST['name'])),
			'user_password' => $_POST['password'],
			'remember' => !empty($_POST['remember'])];

		foreach ($terms as $term) {

			if ($term->parent == $parent) {

				$children = $this->tree($terms, $term->term_id);

				if ($children) {
					$term->children = $children;
				}

				$branch[$term->term_id] = $term;

				unset($terms[$term->term_id]);

			}

		}

		return $branch;

	}


	/**
	 * Get the post taxonomy
	 *
	 * @param bool|int|WP_Post $post Post ID or WP_Post object. Default is global $post.
	 *
	 * @return string
	 */
	public function postTaxonomy($post = false) {

		if (is_numeric($post)) {
			$post = get_post($post);
		} elseif ($post === false) {
			$post = get_post();
		}

		$taxonomy = '';

		if ($post instanceof WP_Post) {

			$taxonomies = get_object_taxonomies($post);

			if ($taxonomies) {

				$preferred_taxonomies = ['category', 'product_cat', 'post_tag', 'product_tag'];

				foreach ($preferred_taxonomies as $preferred_taxonomy) {

					if (in_array($preferred_taxonomy, $taxonomies)) {
						$taxonomy = $preferred_taxonomy;
						break;
					}

				}

				if (empty($taxonomy)) {
					$taxonomy = array_shift($taxonomies);
				}

			}

		}

		return $taxonomy;

	}


	/**
	 * Get the post term IDs as a comma-separated values or as an array
	 *
	 * @param bool|int    $post_id         Post ID or false for the current post
	 * @param bool|string $taxonomy        Post taxonomy
	 * @param bool        $include_parents Include parent terms
	 *
	 * @return array|string
	 */
	public function postTerms($post_id = false, $taxonomy = false, $include_parents = false) {

		if (empty($taxonomy)) {
			$taxonomy = $this->postTaxonomy($post_id);
		}

		if (empty($post_id)) {
			$post_id = get_the_ID();
		}

		$cache_key = 'post_' . $taxonomy . '_' . $post_id . '_' . intval($include_parents);

		$result = wp_cache_get($cache_key, 'twee');

		if (empty($result)) {

			$result = [];

			$terms = get_the_terms($post_id, $taxonomy);

			if (is_array($terms) and $terms) {

				foreach ($terms as $term) {

					$result[] = $term->term_id;

					if ($include_parents and !empty($term->parent)) {

						$result[] = $term->parent;

						$parents = get_ancestors($term->parent, $taxonomy);

						if (is_array($parents) and $parents) {
							$result = array_merge($result, array_reverse($parents));
						}

					}

				}

			}

			$result = array_unique($result, SORT_NUMERIC);

			$result = array_reverse($result);

			wp_cache_add($cache_key, $result, 'twee', 60);

		}

		return $result;

	}


	/**
	 * Get names or links to all post terms
	 *
	 * @param bool|int $post_id   Post ID or false for the current post
	 * @param string   $taxonomy  Term taxonomy
	 * @param bool     $with_link Wrap term a link
	 * @param string   $class     Link class
	 *
	 * @return array
	 */
	public function postTermLinks($post_id = false, $taxonomy = 'category', $class = 'category', $with_link = true) {

		if ($post_id === false) {
			$post_id = get_the_ID();
		}

		if (empty($taxonomy) or !is_string($taxonomy)) {
			$taxonomy = $this->postTaxonomy($post_id);
		}

		$result = [];

		$terms = get_the_terms($post_id, $taxonomy);

		if (is_array($terms) and $terms) {

			if ($class) {
				$class = ' class="' . $class . '"';
			}

			foreach ($terms as $term) {
				if ($with_link) {
					$result[] = '<a href="' . get_term_link($term->term_id) . '"' . $class . '>' . $term->name . '</a>';
				} else {
					if ($class) {
						$result[] = '<span class="' . $class . '">' . $term->name . '</span>';
					} else {
						$result[] = $term->name;
					}
				}
			}

		}

		return $result;

	}

}