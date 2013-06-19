<?php

function smarty_prefilter_mcweb_rewrite($tpl_source, &$smarty)
{
	require_once(DIR_MCWEB . '/simple_html_dom/simple_html_dom.php');
	$dom = str_get_dom($tpl_source);

	//	テンプレートのパスを取得
	$path = $smarty->_current_file;
	if (0 == strncmp('file:', $path, 5))	$path = substr($path, 5);
	$realpath = realpath($path);
	if (FALSE === $realpath)
	{
		$path = DIR_HTML . '/' . $path;
		$realpath = realpath($path);
		if (FALSE === $realpath)	throw new MCWEB_TemplateNotFoundException();
	}
	$path = str_replace('\\', '/', $realpath);

	//	DIR_HTMLフォルダの中身以外のテンプレートの参照は許さないが、
	//	DIR_MCWEBフォルダの中のものは例外として許可します
	if (0 !== strncmp(DIR_MCWEB, $path, strlen(DIR_MCWEB)))
	{
		if (0 !== strncmp(DIR_HTML, $path, strlen(DIR_HTML)))	throw new MCWEB_TemplateNotFoundException();
	}
	$path = substr($path, strlen(DIR_HTML));

	//	拡張子を排除
	$path = str_replace('.xhtml', '', $path);
	$path = str_replace('.html', '', $path);
	$path = str_replace('.htm', '', $path);

	//	テンプレート書き換えフィルタ
	$filters = MCWEB_Framework::extract_filters(MCWEB_Framework::getInstance()->filter_factory->rewrite_template($path), $path);

	$names = array();
	foreach($filters as $filter)
	{
		$class_name = get_class($filter);
		if (in_array($class_name, $names))
		{
			//	同じフィルターの2度がけはエラー
			throw new MCWEB_LogicErrorException();
		}
		array_push($names, $class_name);
		$filter->rewrite($path, $dom);
	}
	return $dom->__toString();
}


?>
