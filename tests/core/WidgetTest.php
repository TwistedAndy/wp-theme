<?php

use Twee\Widget;

/**
 * Widget Tests using WP Integration Suite
 */
class WidgetTest extends \WP_UnitTestCase {

	/**
	 * Instance of the concrete test widget.
	 *
	 * @var Twee_Test_Widget
	 */
	protected Twee_Test_Widget $widget;

	/**
	 * Set up the test fixture.
	 *
	 * @return void
	 */
	public function set_up(): void
	{
		parent::set_up();

		if (!class_exists('Twee\Widget')) {
			require_once TW_ROOT . 'includes/core/widget.php';
		}

		$this->widget = new Twee_Test_Widget();
	}

	/**
	 * Test that fields_load correctly applies default values when instance is empty.
	 *
	 * @return void
	 */
	public function test_default_values_loading(): void
	{
		$instance = $this->widget->fields_load([]);

		$this->assertSame('default_text', $instance['test_text']);
		$this->assertSame(10, $instance['test_number']);
		$this->assertSame(0, $instance['test_single_cb']);
		$this->assertSame('opt1', $instance['test_select']);
	}

	/**
	 * Test sanitization logic specifically for numbers and checkboxes.
	 *
	 * @return void
	 */
	public function test_sanitization_logic(): void
	{
		$input = [
			'test_number'    => '99',    // String input
			'test_single_cb' => '1',     // String input
			'test_text'      => 'hello', // String input
		];

		$instance = $this->widget->fields_load($input);

		// Number type should cast to integer
		$this->assertIsInt($instance['test_number']);
		$this->assertSame(99, $instance['test_number']);

		// Checkbox type (without 'values' array) should cast to integer
		$this->assertIsInt($instance['test_single_cb']);
		$this->assertSame(1, $instance['test_single_cb']);

		// Standard text should remain string
		$this->assertSame('hello', $instance['test_text']);
	}

	/**
	 * Test that filters are applied via apply_filters when valid.
	 *
	 * @return void
	 */
	public function test_fields_load_with_filter(): void
	{
		// Hook into the filter defined in Twee_Test_Widget constructor
		add_filter('twee_test_filter_hook', function($val) {
			return $val . '_modified';
		});

		// Pass 'false' to 2nd arg to allow filtering
		$instance = $this->widget->fields_load(['test_filtered' => 'original'], false);

		$this->assertSame('original_modified', $instance['test_filtered']);
	}

	/**
	 * Test the update method wrapper functionality.
	 *
	 * @return void
	 */
	public function test_update_method(): void
	{
		$new = ['test_text' => 'Updated'];
		$old = ['test_text' => 'Old'];

		$result = $this->widget->update($new, $old);

		$this->assertSame('Updated', $result['test_text']);
		// Should still merge defaults
		$this->assertSame(10, $result['test_number']);
	}

	/**
	 * Test HTML output for standard Text inputs and Textareas.
	 *
	 * @return void
	 */
	public function test_render_basics(): void
	{
		$instance = [
			'test_text'     => 'My Value',
			'test_textarea' => 'Content here',
		];

		ob_start();
		$this->widget->form($instance);
		$output = ob_get_clean();

		// Text Field assertions
		$this->assertStringContainsString('Text Field:', $output);
		$this->assertStringContainsString('type="text"', $output);
		$this->assertStringContainsString('value="My Value"', $output);

		// Textarea assertions
		$this->assertStringContainsString('<textarea', $output);
		$this->assertStringContainsString('>Content here</textarea>', $output);
	}

	/**
	 * Test HTML output for Selects (Single and Multi).
	 *
	 * @return void
	 */
	public function test_render_selects(): void
	{
		$instance = [
			'test_select'    => 'opt2',
			'test_multi_sel' => ['a', 'b'],
		];

		ob_start();
		$this->widget->form($instance);
		$output = ob_get_clean();

		// Single Select
		$this->assertStringContainsString('value="opt2" selected="selected"', $output);
		$this->assertStringNotContainsString('value="opt1" selected="selected"', $output);

		// Multi Select
		$this->assertStringContainsString('multiple', $output);
		$this->assertStringContainsString('value="a" selected="selected"', $output);
		$this->assertStringContainsString('value="b" selected="selected"', $output);
	}

	/**
	 * Test HTML output for Radio buttons.
	 *
	 * @return void
	 */
	public function test_render_radio(): void
	{
		$instance = ['test_radio' => 'r2'];

		ob_start();
		$this->widget->form($instance);
		$output = ob_get_clean();

		$this->assertStringContainsString('type="radio"', $output);
		// Note: Source code results in two spaces before 'checked' due to PHP close/open tags
		$this->assertStringContainsString('value="r2"  checked="checked"', $output);

		// r1 should exist but not be checked
		$this->assertStringContainsString('value="r1" ', $output);
		$this->assertStringNotContainsString('value="r1"  checked="checked"', $output);
	}

	/**
	 * Test HTML output for Checkboxes (Single and Multi-Group).
	 *
	 * @return void
	 */
	public function test_render_checkboxes(): void
	{
		$instance = [
			'test_single_cb' => 1,
			'test_multi_cb'  => 'c1',
		];

		ob_start();
		$this->widget->form($instance);
		$output = ob_get_clean();

		// Single Checkbox (Boolean style)
		$this->assertStringContainsString('type="checkbox" class="checkbox"', $output);
		$this->assertStringContainsString('value="1" checked="checked"', $output);

		// Multi Checkbox Group (Values array style)
		$this->assertStringContainsString('value="c1"  checked="checked"', $output);
		$this->assertStringContainsString('value="c2" ', $output);
	}

	/**
	 * Test a widget with no fields to ensure 100% code coverage.
	 * Covers early returns in fields_load and fields_render.
	 *
	 * @return void
	 */
	public function test_widget_with_no_fields(): void
	{
		$empty_widget = new Twee_Test_Empty_Widget();

		// Test fields_load early return
		$instance = $empty_widget->fields_load(['some' => 'data']);
		// It should return the instance as-is because there are no fields to process/map
		$this->assertSame('data', $instance['some']);

		// Test fields_render early return
		ob_start();
		$result = $empty_widget->form([]);
		$output = ob_get_clean();

		// Output should be empty
		$this->assertEmpty($output);
		// Result should be the instance array
		$this->assertIsArray($result);
	}

}

/**
 * Concrete implementation of Twee\Widget for testing purposes.
 * Covers all input types supported by the parent class.
 */
class Twee_Test_Widget extends Widget {

	public function __construct()
	{
		// Define fields covering all formatting switch cases
		$this->fields = [
			'test_text'      => [
				'name'  => 'Text Field',
				'type'  => 'text',
				'value' => 'default_text',
			],
			'test_filtered'  => [
				'name'   => 'Filtered Field',
				'type'   => 'text',
				'value'  => 'raw',
				'filter' => 'twee_test_filter_hook',
			],
			'test_number'    => [
				'name'  => 'Number Field',
				'type'  => 'number',
				'value' => 10,
			],
			'test_single_cb' => [
				'name'  => 'Single Checkbox',
				'type'  => 'checkbox',
				'value' => 0,
			],
			'test_select'    => [
				'name'   => 'Select Box',
				'type'   => 'select',
				'value'  => 'opt1',
				'values' => ['opt1' => 'Option 1', 'opt2' => 'Option 2'],
			],
			'test_multi_sel' => [
				'name'     => 'Multi Select',
				'type'     => 'select',
				'multiple' => true,
				'value'    => [],
				'values'   => ['a' => 'A', 'b' => 'B'],
			],
			'test_radio'     => [
				'name'   => 'Radio Group',
				'type'   => 'radio',
				'value'  => 'r1',
				'values' => ['r1' => 'Radio 1', 'r2' => 'Radio 2'],
			],
			'test_multi_cb'  => [
				'name'   => 'Checkbox Group',
				'type'   => 'checkbox',
				'value'  => 'c1',
				'values' => ['c1' => 'Check 1', 'c2' => 'Check 2'],
			],
			'test_textarea'  => [
				'name'  => 'Text Area',
				'type'  => 'textarea',
				'value' => 'default area',
			],
		];

		parent::__construct('twee_test_widget', 'Twee Test Widget');
	}

}

/**
 * Empty Widget for testing logic when valid $fields are missing.
 */
class Twee_Test_Empty_Widget extends Widget {

	public function __construct()
	{
		$this->fields = [];
		parent::__construct('twee_empty_widget', 'Empty Widget');
	}

}