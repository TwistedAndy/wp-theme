<?php
/**
 * Additional modules
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */


/**
 * Fix the russian dates
 */

if (tw_get_setting('init', 'module_russian_date')) {

	function tw_russian_date($date = '') {

		$replace_ru = array (
			'Январь' => 'января',
			'Февраль' => 'февраля',
			'Март' => 'марта',
			'Апрель' => 'апреля',
			'Май' => 'мая',
			'Июнь' => 'июня',
			'Июль' => 'июля',
			'Август' => 'августа',
			'Сентябрь' => 'сентября',
			'Октябрь' => 'октября',
			'Ноябрь' => 'ноября',
			'Декабрь' => 'декабря',
		);

		$replace_en = array(
			'January' => 'января',
			'February' => 'февраля',
			'March' => 'марта',
			'April' => 'апреля',
			'May' => 'мая',
			'June' => 'июня',
			'July' => 'июля',
			'August' => 'августа',
			'September' => 'сентября',
			'October' => 'октября',
			'November' => 'ноября',
			'December' => 'декабря',
			'Monday' => 'понедельник',
			'Tuesday' => 'вторник',
			'Wednesday' => 'среда',
			'Thursday' => 'четверг',
			'Friday' => 'пятница',
			'Saturday' => 'суббота',
			'Sunday' => 'воскресенье',
			'Mon' => 'понедельник',
			'Tue' => 'вторник',
			'Wed' => 'среда',
			'Thu' => 'четверг',
			'Fri' => 'пятница',
			'Sat' => 'суббота',
			'Sun' => 'воскресенье',
		);

		if (tw_get_setting('init', 'module_english_date')) {
			$replace_ru = array_merge($replace_ru, $replace_en);
		}

		return strtr($date, $replace_ru);

	}

	add_filter('the_time', 'tw_russian_date');
	add_filter('the_modified_time', 'tw_russian_date');
	add_filter('get_the_date', 'tw_russian_date');
	add_filter('get_comment_date', 'tw_russian_date');

}


/**
 * Convert cyrillic symbols in urls to latin
 */

if (tw_get_setting('init', 'module_cyrtolat')) {

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

}