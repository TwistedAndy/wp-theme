<?php
/**
 * Widget with latest comments
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

class Twisted_Widget_Comments extends Twisted_Widget {

	public $fields = array(
		'title' => array(
			'name' => 'Заголовок',
			'value' => 'Последние комментарии',
			'type' => 'text',
			'filter' => 'widget_title'
		),
		'number' => array(
			'name' => 'Количество комментариев',
			'value' => 5,
			'type' => 'number'
		),
		'chars' => array(
			'name' => 'Количество символов',
			'value' => 150,
			'type' => 'number'
		)
	);

	function __construct() {
		parent::__construct('twisted_widget_comments', 'Последние комментарии', array('description' => 'Последние комментарии с аватарками'));
	}

	public function widget($args, $instance) {

		$instance = $this->fields_load($instance, false);

		echo $args['before_widget'];

		if ($instance['title']) echo $args['before_title'] . $instance['title'] . $args['after_title'];

		if ($items = get_comments(array('status' => 'approve', 'number' => $instance['number']))) {
			foreach ($items as $item) { $post = get_post($item->comment_post_ID); ?>

				<div class="comment">
					<div class="comment_head">
						<div class="comment_avatar"><?php echo get_avatar($item, 50); ?><span></span></div>
						<div class="comment_info">
							<span class="com_date"><?php echo get_comment_date('d.m.Y', $item); ?></span>
							<span class="author"><?php echo get_comment_author_link($item); ?> оставил(-а)</span>
							<span class="com_title">Комментарий к записи:</span>
						</div>
					</div>
					<h4><a href="<?php echo get_comment_link($item); ?>"><?php echo tw_title($post); ?></a></h4>
					<p><?php echo tw_text($item->comment_content, $instance['chars']); ?></p>
				</div>

			<?php }

		}

		echo $args['after_widget'];

	}

}