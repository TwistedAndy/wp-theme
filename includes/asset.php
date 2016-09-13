<?php

/*
Описание: библиотека для работы с наборами ресурсов
Автор: Тониевич Андрей
Версия: 1.1
Дата: 26.08.2016
*/

add_action('after_setup_theme', 'tw_register_assets');

function tw_register_assets() {

	$predefined_assets = array(
		'template' => array(
			'style' => 'css/style.css',
			'display' => true
		),
		'likes' => array(
			'style' => 'scripts/social-likes.css',
			'script' => 'scripts/social-likes.min.js',
		),
		'colorbox' => array(
			'style' => 'scripts/colorbox/colorbox.css',
			'script' => 'scripts/jquery.colorbox-min.js',
		),
		'styler' => array(
			'style' => 'scripts/jquery.formstyler.css',
			'script' => 'scripts/jquery.formstyler.min.js',
		),
		'jcarousel' => array(
			'script' => 'scripts/jquery.jcarousel.min.js',
		),
		'nivo' => array(
			'style' => 'scripts/nivo-slider.css',
			'script' => 'scripts/jquery.nivo.slider.pack.js',
		)
	);

	$assets = tw_get_setting('assets');

	if (!empty($assets) and is_array($assets)) {

		foreach ($assets as $name => $asset) {

			if (is_array($asset)) {
				$asset = wp_parse_args($asset, $predefined_assets[$name]);
			} elseif (is_bool($asset) and $asset) {
				$asset = $predefined_assets[$name];
				$asset['display'] = true;
			} else {
				$asset = $predefined_assets[$name];
			}

			tw_register_asset($name, $asset);

			tw_set_setting('registred_assets', $name, $asset);

		}

	}

}


function tw_register_asset($name, $asset) {

	if (is_array($asset)) {

		$dir = get_template_directory_uri() . '/assets/';

		$defaults = array(
			'deps' => array('jquery'),
			'style' => '',
			'script' => '',
			'footer' => false,
			'display' => false,
			'localize' => array()
		);

		$asset = wp_parse_args($asset, $defaults);

		if (!empty($asset['script']) and is_string($asset['script'])) {

			wp_register_script($name, $dir . $asset['script'], $asset['deps'], null, $asset['footer']);

			if ($asset['localize']) {
				wp_localize_script($name, $name, $asset['localize']);
			}

		}

		if (!empty($asset['style']) and is_string($asset['style'])) {
			wp_register_style($name, $dir . $asset['style'], array(), null);
		}

	}

	return false;

}


add_action('wp_enqueue_scripts', 'tw_enqueue_assets');

function tw_enqueue_assets() {

	$registred_assets = tw_get_setting('registred_assets');

	if ($registred_assets) {

		foreach ($registred_assets as $name => $asset) {

			if ((is_array($asset) and !empty($asset['display'])) or (is_bool($asset) and $asset)) {
				tw_enqueue_asset($name);
			}

		}

	}

}


function tw_enqueue_asset($name) {

	if (wp_script_is($name, 'registered')) {
		wp_enqueue_script($name);
	}

	if (wp_style_is($name, 'registered')) {
		wp_enqueue_style($name);
	}

	return false;

}







