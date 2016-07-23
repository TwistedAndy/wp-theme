<?php

/*
Описание: библиотека для работы с наборами ресурсов
Автор: Тониевич Андрей
Версия: 1.0
Дата: 23.07.2016
*/

add_action('wp_enqueue_scripts', 'tw_process_assets');

function tw_process_assets() {

	$predefined_assets = array(
		'template' => array(
			'style' => 'css/style.css',
			'display' => true
		),
		'likes' => array(
			'style' => 'assets/social-likes.css',
			'script' => 'assets/social-likes.min.js',
		),
		'colorbox' => array(
			'style' => 'assets/colorbox/colorbox.css',
			'script' => 'assets/jquery.colorbox-min.js',
		),
		'styler' => array(
			'style' => 'assets/jquery.formstyler.css',
			'script' => 'assets/jquery.formstyler.min.js',
		),
		'jcarousel' => array(
			'script' => 'assets/jquery.jcarousel.min.js',
		),
		'nivo' => array(
			'style' => 'assets/nivo-slider.css',
			'script' => 'assets/jquery.nivo.slider.pack.js',
		)
	);

	$assets = tw_get_setting('assets');

	if (!empty($assets) and is_array($assets)) {

		foreach ($assets as $name => $asset) {

			if (is_bool($asset) and $asset) {

				if (wp_script_is($name, 'registered')) {
					wp_enqueue_script($name);
				}

				if (wp_style_is($name, 'registered')) {
					wp_enqueue_style($name);
				}

				$asset = array();

				$asset['display'] = true;

			}

			if (!empty($predefined_assets[$name])) {
				$asset = wp_parse_args($asset, $predefined_assets[$name]);
			}

			if (is_array($asset)) {
				tw_register_asset($name, $asset);
				if (!empty($asset['display'])) {
					tw_load_asset($name);
				}
			}

		}

	}

}


function tw_register_asset($name, $asset) {

	if (is_array($asset)) {

		$dir = get_template_directory_uri() . '/';

		$defaults = array(
			'deps' => array('jquery'),
			'style' => '',
			'script' => '',
			'footer' => true,
			'display' => false,
			'localize' => array()
		);

		$asset = wp_parse_args($asset, $defaults);

		if (!empty($asset['script']) and is_string($asset['script'])) {

			wp_register_script($name, $dir . $asset['script'], $asset['deps'], null, $asset['footer']);

			if ($asset['localize']) {
				wp_localize_script($name, $name, $asset['localize']);
			}

			if ($asset['display']) {
				wp_enqueue_script($name);
			}

		}

		if (!empty($asset['style']) and is_string($asset['style'])) {

			wp_register_style($name, $dir . $asset['style'], array(), null);

			if ($asset['display']) {
				wp_enqueue_style($name);
			}

		}

	}

	return false;

}


function tw_load_asset($name) {

	if (wp_script_is($name, 'registered')) {
		wp_enqueue_script($name);
	}

	if (wp_style_is($name, 'registered')) {
		wp_enqueue_style($name);


	}

	return false;

}


