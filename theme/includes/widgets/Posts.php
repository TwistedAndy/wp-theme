<?php
/**
 * Widget with the latest posts
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

namespace Twee\widgets;

class Posts extends \Twee\Widget {

	public array $fields = [
		'title'    => [
			'name'   => 'Title',
			'value'  => 'Recent Posts',
			'type'   => 'text',
			'filter' => 'widget_title'
		],
		'number'   => [
			'name'  => 'Number',
			'value' => 5,
			'type'  => 'number'
		],
		'category' => [
			'name'  => 'Category',
			'value' => 0,
			'type'  => 'select',
		],
		'sort'     => [
			'name'   => 'Order',
			'value'  => 0,
			'values' => [
				0 => 'Date',
				1 => 'Random',
			],
			'type'   => 'select'
		]
	];

	public function __construct()
	{
		parent::__construct(get_class($this), 'Custom Posts', ['description' => 'Widget with posts']);
	}

	public function widget($args, $instance): void
	{
		$instance = $this->fields_load($instance, false);

		echo $args['before_widget'] ?? '';

		if ($instance['title']) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}

		$args = [
			'post_status' => 'publish',
			'post_type'   => 'post',
			'numberposts' => $instance['number'],
			'category'    => $instance['category']
		];

		if ($instance['sort'] == 1) {
			$args['orderby'] = 'rand';
		}

		$object = get_queried_object();

		if ($object instanceof \WP_Post) {
			$args['post__not_in'] = [$object->ID];
		}

		$items = get_posts($args);

		if ($items) { ?>
			<div class="posts">
				<?php foreach ($items as $item) { ?>
					<div class="post">
						<?php echo tw_image($item, 'thumbnail', '', '', ['link' => 'url', 'link_class' => 'image']); ?>
						<div class="details">
							<a href="<?php echo get_permalink($item); ?>" class="title"><?php echo $item->post_title; ?></a>
							<div class="info">
								<?php if ($links = tw_term_links($item->ID, 'category')) { ?>
									<?php echo reset($links); ?>
								<?php } ?>
								<span class="date"><?php echo tw_content_date($item, 'm/d/Y'); ?></span>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		<?php }

		echo $args['after_widget'] ?? '';

	}

	public function form($instance): array
	{
		$this->fields['category']['values'][0] = 'All Categories';

		if ($cats = get_categories()) {
			foreach ($cats as $cat) {
				$this->fields['category']['values'][$cat->cat_ID] = $cat->cat_name;
			}
		}

		$this->fields_render($instance);

		return $instance;
	}

}