<?php
/**
 * Widget with latest or most populat posts
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

class Twisted_Widget_Posts extends Twisted_Widget {

	public $fields = array(
		'title' => array(
			'name' => 'Заголовок',
			'value' => 'Последние записи',
			'type' => 'text',
			'filter' => 'widget_title'
		),
		'number' => array(
			'name' => 'Количество записей',
			'value' => 5,
			'type' => 'number'
		),
		'chars' => array(
			'name' => 'Количество символов',
			'value' => 150,
			'type' => 'number'
		),
		'category' => array(
			'name' => 'Рубрика',
			'value' => 0,
			'type' => 'select',
		),
		'categories' => array(
			'name' => 'Строка с ID-категорий',
			'value' => '',
			'type' => 'text'
		),
		'sort' => array(
			'name' => 'Сортировка',
			'value' => 0,
			'values' => array(
				0 => 'Последние',
				1 => 'Случайные',
				2 => 'Популярные'
			),
			'type' => 'select'
		)
	);

	function __construct() {
		parent::__construct('twisted_widget_posts', 'Записи на сайте', array('description' => 'Виджет для вывода записей с дополнительными настройками'));
	}

	public function widget($args, $instance) {

		$instance = $this->fields_load($instance, false);

		echo $args['before_widget'];

		if ($instance['title']) echo $args['before_title'] . $instance['title'] . $args['after_title'];

		$array = array(
			'numberposts' => $instance['number'],
			'cat' => $instance['categories'],
			'category' => $instance['category']
		);

		if ($instance['sort'] == 1) {
			$array['orderby'] = 'rand';
		} elseif ($instance['sort'] == 2) {
			$array['meta_key'] = 'post_views_count';
			$array['orderby'] = 'meta_value_num';
		}

		if ($items = get_posts($array)) { ?>

			<?php foreach ($items as $item) { ?>

				<div class="news">
					<div class="date"><?php echo tw_date($item, 'd.m.Y'); ?></div>
					<a href="<?php echo get_permalink($item->ID); ?>" class="news_title"><?php echo tw_title($item); ?></a>
					<p><?php echo tw_text($item, $instance['chars']); ?></p>
				</div>

			<?php } ?>

		<?php }

		echo $args['after_widget'];

	}

	public function form($instance) {

		$this->fields['category']['values'][0] = 'Все рубрики';

		if ($cats = get_categories()) {
			foreach ($cats as $cat) {
				$this->fields['category']['values'][$cat->cat_ID] = $cat->cat_name;
			}
		}

		$this->fields_render($instance);

	}

}