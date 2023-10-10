<?php
/**
 * Widget with the latest posts
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

namespace Twee\Widgets;

class Posts extends \Twee\Widget {

	public $fields = [
		'title' => [
			'name' => 'Title',
			'value' => 'Recent Posts',
			'type' => 'text',
			'filter' => 'widget_title'
		],
		'number' => [
			'name' => 'Number',
			'value' => 5,
			'type' => 'number'
		],
		'chars' => [
			'name' => 'Text Length',
			'value' => 150,
			'type' => 'number'
		],
		'category' => [
			'name' => 'Category',
			'value' => 0,
			'type' => 'select',
		],
		'sort' => [
			'name' => 'Order',
			'value' => 0,
			'values' => [
				0 => 'Date',
				1 => 'Random',
			],
			'type' => 'select'
		]
	];

	function __construct() {
		parent::__construct(get_class($this), 'Custom Posts', ['description' => 'Widget with posts']);
	}

	public function widget($args, $instance) {

		$instance = $this->fields_load($instance, false);

		echo $args['before_widget'];

		if ($instance['title']) echo $args['before_title'] . $instance['title'] . $args['after_title'];

		$array = [
			'numberposts' => $instance['number'],
			'category' => $instance['category']
		];

		if ($instance['sort'] == 1) {
			$array['orderby'] = 'rand';
		}

		if ($items = get_posts($array)) { ?>

			<?php foreach ($items as $item) { ?>

				<div class="item">
					<div class="date"><?php echo tw_date($item, 'd.m.Y'); ?></div>
					<a href="<?php echo get_permalink($item->ID); ?>" class="title"><?php echo tw_title($item); ?></a>
					<div class="text"><?php echo tw_text($item, $instance['chars']); ?></div>
				</div>

			<?php } ?>

		<?php }

		echo $args['after_widget'];

	}

	public function form($instance) {

		$this->fields['category']['values'][0] = 'All Categories';

		if ($cats = get_categories()) {
			foreach ($cats as $cat) {
				$this->fields['category']['values'][$cat->cat_ID] = $cat->cat_name;
			}
		}

		$this->fields_render($instance);

	}

}