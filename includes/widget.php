<?php

/*
Описание: класс для упрощения создания виджетов
Автор: Тониевич Андрей
Версия: 1.6
Дата: 04.06.2016
*/

class Twisted_Widget extends WP_Widget {

	public function __construct($id_base, $name, $widget_options = array(), $control_options = array()) {

		parent::__construct($id_base, $name, $widget_options, $control_options);

	}

	public function fields_load($instance, $skip_filter = true) {

		if (!$instance) $instance = array();

		if (!empty($this->fields) and is_array($this->fields)) {

			foreach ($this->fields as $name => $field) {

				if (!isset($instance[$name])) {

					$instance[$name] = $field['value'];

				}

				if (isset($field['filter']) and !$skip_filter) {

					$instance[$name] = apply_filters($field['filter'], $instance[$name]);

				}

				if (isset($field['type']) and ($field['type'] == 'number' or ($field['type'] == 'checkbox' and !isset($field['values'])))) {

					$instance[$name] = intval($instance[$name]);

				}

			}

		}

		return $instance;

	}

	public function fields_render($instance) {

		$instance = $this->fields_load($instance);

		if (!empty($this->fields) and is_array($this->fields)) {

			foreach ($this->fields as $name => $field) { ?>

				<p><label for="<?php echo $this->get_field_id($name); ?>"><?php echo $field['name']; ?>:</label>

				<?php if ($field['type'] == 'textarea') { ?>

					<textarea class="widefat" rows="8" cols="20" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>"><?php echo esc_attr($instance[$name]); ?></textarea>

				<?php } elseif (isset($field['values']) and $field['values']) { ?>

					<?php if ($field['type'] == 'select') { ?>

						<select class="widefat" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>">
							<?php foreach($field['values'] as $key => $value) { ?>
							<option value="<?php echo $key; ?>"<?php if ($instance[$name] == $key) {?> selected="selected"<?php } ?>><?php echo $value; ?></option>
							<?php } ?>
						</select>

					<?php } elseif ($field['type'] == 'radio') { ?>

						<?php foreach($field['values'] as $key => $value) { ?>
						<br />
						<input id="<?php echo $this->get_field_id($name . $key); ?>" type="radio" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo $key; ?>" <?php if ($instance[$name] == $key) {?> checked="checked"<?php } ?> /><label for="<?php echo $this->get_field_id($name . $key); ?>"><?php echo $value; ?></label>
						<?php } ?>

					<?php } elseif ($field['type'] == 'checkbox') { ?>

						<?php foreach($field['values'] as $key => $value) { ?>
						<br />
						<input id="<?php echo $this->get_field_id($name . $key); ?>" type="checkbox" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo $key; ?>" <?php if ($instance[$name] == $key) {?> checked="checked"<?php } ?> /><label for="<?php echo $this->get_field_id($name . $key); ?>"><?php echo $value; ?></label>
						<?php } ?>

					<?php } ?>

				<?php } elseif ($field['type'] == 'checkbox') { ?>

					<input id="<?php echo $this->get_field_id($name); ?>" type="checkbox" class="checkbox" name="<?php echo $this->get_field_name($name); ?>" value="1"<?php if ($instance[$name] == '1') {?> checked="checked"<?php } ?> />

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