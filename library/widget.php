<?php

/*
Описание: дополнительные виджеты
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

class Twisted_Widget extends WP_Widget {
	
	public function __construct($id_base, $name, $widget_options = array(), $control_options = array()) {

		parent::__construct($id_base, $name, $widget_options, $control_options);

	}
	
	public function fields_load($instance, $skip_filter = true) {
		
		if (!$instance) $instance = array();
		
		if (isset($this->fields) and $this->fields) {
			
			foreach ($this->fields as $name => $field) {
				
				if (!isset($instance[$name])) {
					
					$instance[$name] = $field['value'];
					
				}
				
				if (isset($field['filter']) and !$skip_filter) {
					
					$instance[$name] = apply_filters($field['filter'], $instance[$name]);
					
				}
				
				if (isset($field['type']) and in_array($field['type'], array('number', 'checkbox'))) {
					
					$instance[$name] = intval($instance[$name]);
					
				}
				
			}
			
		}
		
		return $instance;
		
	}
	
	public function fields_render($instance) {

		$instance = $this->fields_load($instance);

		if (isset($this->fields) and $this->fields) {

			foreach ($this->fields as $name => $field) { ?>
				
				<p><label for="<?php echo $this->get_field_id($name); ?>"><?php echo $field['name']; ?>:</label> 
					
				<?php if ($field['type'] == 'textarea') { ?>
					
					<textarea class="widefat" rows="8" cols="20" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>"><?php echo esc_attr($instance[$name]); ?></textarea>
					
				<?php } elseif (isset($field['values']) and $field['values']) { ?>
					
					<?php if ($field['type'] == 'select') { ?>
						
						<select id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>">
							<?php foreach($field['values'] as $key => $value) { ?>
							<option value="<?php echo $key; ?>"<?php if ($instance[$name] == $key) {?> selected="selected"<?php } ?>><?php echo $value; ?></option>
							<?php } ?>
						</select>
						
					<?php } elseif ($field['type'] == 'radio') { ?>
						
						<?php foreach($field['values'] as $key => $value) { ?>
						<br />
						<input id="<?php echo $this->get_field_id($name . $key); ?>" type="radio" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo $key; ?>" <?php if ($instance[$name] == $key) {?> checked="checked"<?php } ?> /><label for="<?php echo $this->get_field_id($name . $key); ?>"><?php echo $value; ?></label>
						<?php } ?>
						
					<?php } ?>
			
				<?php } elseif ($field['type'] == 'checkbox') { ?>
				
					<input id="<?php echo $this->get_field_id($name); ?>" type="checkbox" class="checkbox" name="<?php echo $this->get_field_name($name); ?>" value="1"<?php if ($instance[$name] == $value) {?> checked="checked"<?php } ?> /><label for="<?php echo $this->get_field_id($name); ?>"><?php echo $value; ?></label>
					
				<?php } else { ?>
					
					<input class="widefat" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>" type="text" value="<?php echo esc_attr($instance[$name]); ?>" />
				
				<?php } ?>
				
				</p>
					
			<?php }
			
		}

		return $instance;

	}

	public function update($new_instance, $old_instance) {
		
		return $this->fields_load($new_instance);
		
	}

	public function form($instance) {
		
		$this->fields_render($instance);
		
	}
	
}


class widget_super_posts extends Twisted_Widget {

	function __construct() {
		parent::__construct('widget_super_posts', 'Записи на сайте', array('description' => 'Записи на сайте в разном виде'));
	}

	public $fields = array(
		'title' => array(
			'name'	 => 'Заголовок',
			'value'  => 'Последние записи',
			'type'	 => 'text',
			'filter' => 'widget_title'
		), 
		'number' => array(
			'name'	 => 'Количество записей',
			'value'  => 5,
			'type'	 => 'number'
		), 
		'chars' => array(
			'name'	 => 'Количество символов',
			'value'  => 150,
			'type'	 => 'number'
		), 
		'category' => array(
			'name'	 => 'Рубрика',
			'value'  => 0,
			'type'	 => 'select',
		), 
		'categories' => array(
			'name'	 => 'Строка с ID-категорий',
			'value'  => '',
			'type'	 => 'text'
		), 
		'text' => array(
			'name'	 => 'Текст внизу',
			'value'  => '',
			'type'	 => 'textarea'
		), 
		'sort' => array(
			'name'	 => 'Сортировка',
			'value'  => 0,
			'values' => array(
				0 => 'Последние',
				1 => 'Случайные',
				2 => 'Популярные'
			),
			'type'	 => 'select'
		)
	);
	
	public function widget($args, $instance) {

		$instance = $this->fields_load($instance, false);
		
		echo $args['before_widget'];
		
		if ($instance['title']) echo $args['before_title'] . $instance['title'] . $args['after_title'];

		$array = array('numberposts' => $instance['number'], 'cat' => $instance['categories'], 'category' => $instance['category']);
		
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


class widget_recent_comments extends Twisted_Widget {

	function __construct() {
		parent::__construct('widget_recent_comments', 'Последние комментарии', array('description' => 'Последние комментарии с аватарками'));
	}
	
	public $fields = array(
		'title' => array(
			'name'	 => 'Заголовок',
			'value'  => 'Последние комментарии',
			'type'	 => 'text',
			'filter' => 'widget_title'
		), 
		'number' => array(
			'name'	 => 'Количество комментариев',
			'value'  => 5,
			'type'	 => 'number'
		), 
		'chars' => array(
			'name'	 => 'Количество символов',
			'value'  => 150,
			'type'	 => 'number'
		)
	);

	public function widget($args, $instance) {

		$instance = $this->fields_load($instance, false);

		echo $args['before_widget'];
		
		if ($instance['title']) echo $args['before_title'] . $instance['title'] . $args['after_title'];
		
		if ($items = get_comments(array('status' => 'approve', 'number' => $instance['number']))) {
			foreach ($items as $item) {
				$post = get_post($item->comment_post_ID);
				?>
			
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
					<p><?php echo tw_strip($item->comment_content, $instance['chars']); ?></p>
				</div>
			
			<?php }
		
		}

		echo $args['after_widget'];

	}
	
} 


add_action('widgets_init', 'tw_load_widget');

function tw_load_widget() {
	
	if (tw_settings('init', 'widget_posts')) register_widget('widget_super_posts');

	if (tw_settings('init', 'widget_comments')) register_widget('widget_recent_comments');

}

?>