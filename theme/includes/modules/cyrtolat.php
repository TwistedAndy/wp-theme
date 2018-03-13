<?php
/**
 * Cyrtolat library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Convert cyrillic symbols in urls to latin
 *
 * @param string $text
 *
 * @return string
 */

function tw_convert_to_translit($text) {

	$iso = array(
		'Є' => 'YE', 'І' => 'I',  'Ѓ' => 'G', 'і' => 'i',  '№' => '#', 'є' => 'ye', 'ѓ' => 'g',
		'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH',
		'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'X', 'Ц' => 'C',
		'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SHH', 'Ъ' => "'", 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU',
		'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
		'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'x',
		'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
		'ю' => 'yu', 'я' => 'ya', '—' => '-', '«' => '', '»' => '', '…' => ''
	);

	return strtr($text, $iso);

}

add_action('sanitize_title', 'tw_convert_to_translit', 0);
