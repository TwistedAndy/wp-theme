<?php
/**
 * Date processing library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Fix russian month names
 *
 * @param string $date
 *
 * @return string
 */

function tw_russian_date($date = '') {

	$replace_ru = array(
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

	if (tw_get_setting('modules','cyrdate', 'english_convert')) {
		$replace_ru = array_merge($replace_ru, $replace_en);
	}

	return strtr($date, $replace_ru);

}

add_filter('the_time', 'tw_russian_date');
add_filter('the_modified_time', 'tw_russian_date');
add_filter('get_the_date', 'tw_russian_date');
add_filter('get_comment_date', 'tw_russian_date');