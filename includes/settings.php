<?php

/*
Описание: библиотека для работы с настройками
Автор: Тониевич Андрей
Версия: 1.0
Дата: 04.06.2016
*/

function tw_get_setting($group = false, $name = false) {

	global $tw_settings;

	if ($name and $group and isset($tw_settings[$group][$name])) {
		return $tw_settings[$group][$name];
	} elseif ($group and isset($tw_settings[$group])) {
		return $tw_settings[$group];
	} elseif ($name == false and $group == false) {
		return $tw_settings;
	} else {
		return false;
	}

};


function tw_set_setting($group, $name, $value) {

	global $tw_settings;

	if ($name and $group) {
		$tw_settings[$group][$name] = $value;
	} elseif ($name) {
		$tw_settings[$name] = $value;
	}

};
