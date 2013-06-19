<?php

function smarty_function_errors($params, &$smarty)
{
	static $file = NULL;
	if (NULL == $file)
	{
		$path = MCWEB_Framework::getInstance()->template_path;
	}

	$arr = MCWEB_ValidationManager::get($params['name']);
	$str = '';
	foreach($arr as $value)
	{
		$str .= $value . '<br>';
	}
	return $str;
}
/*
	private static function loadfile($filename, $scene_name)
	{
		$arr['format'] = 'フォーマットに問題があります<br>';
		$arr['min'] = '入力が短すぎます<br>';
		$arr['max'] = '入力が長すぎます<br>';
		return $arr;
	}
	*/
?>