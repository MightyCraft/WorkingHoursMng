<?php
error_reporting(E_ALL);
define('FRAMEWORK_START_MICROTIME', microtime(true));

//	対応した設定ファイルを読み込みます
if		(file_exists(dirname(__FILE__).'/.local'))	define('DEFINE_INI_GROUP', 'local');
else if (file_exists(dirname(__FILE__).'/.debug'))	define('DEFINE_INI_GROUP', 'debug');
else												define('DEFINE_INI_GROUP', 'release');
require(dirname(dirname(__FILE__)) . '/app/define/' . DEFINE_INI_GROUP . '.ini.php');

try
{
	$commandline = isset($_SERVER['windir']) || !isset($_SERVER['REQUEST_METHOD']);
	MCWEB_Framework::getInstance()->run($commandline);
}
catch(MCWEB_ActionNotFoundException $e)
{
	header('HTTP', true, 404);
	if (DEVELOPMENT)
	{
		echo 'ActionNotFound';
		echo ':<br>' . $e->getMessage();
	}
}
catch(MCWEB_TemplateNotFoundException $e)
{
	header('HTTP', true, 404);
	if (DEVELOPMENT)
	{
		echo 'TemplateNotFound';
		echo ':<br>' . $e->getMessage();
	}
}
catch(Exception $e)
{
	header('HTTP', true, 500);
	if (DEVELOPMENT)
	{
		$date = date('Y/m/d H:i:s');

		$str = get_class($e);
		$str .= "\nDate: $date\n\n";
		$str .= $e->getMessage() . "\n";
		$str .= $e->getFile() . ' : ' . $e->getLine() . "\n";
		$str .= $e->getTraceAsString() . "\n";

		$str = str_replace("\n", '<br>', $str);
		echo $str;
	}
	else
	{
		// エラー画面へリダイレクト
		MCWEB_Util::redirectAction('/errors/index/');
	}
}
?>