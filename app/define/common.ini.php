<?php

//	Smarty生成
require_once(DIR_LIB_ROOT . '/mcweb/smarty/libs/Smarty.class.php');
$smarty = new Smarty;
$smarty->template_dir		= DIR_HTML;
$smarty->compile_dir		= DIR_CACHE . '/smarty_files/templates_c';
$smarty->config_dir			= DIR_CACHE . '/smarty_files/configs';
$smarty->cache_dir			= DIR_CACHE . '/smarty_files/cache';
$smarty->left_delimiter		= '{';
$smarty->right_delimiter	= '}';
array_push($smarty->plugins_dir, DIR_APP . '/smarty_plugins');
array_push($smarty->plugins_dir, DIR_MCWEB . '/smarty/libs/plugins');
array_push($smarty->plugins_dir, DIR_APP . '/smarty/libs/plugins');
if (DEVELOPMENT)
{
	$smarty->force_compile = true;
}

if (!file_exists($smarty->compile_dir))		{mkdir($smarty->compile_dir, 0777, true);	chmod($smarty->compile_dir, 0777);}
if (!file_exists($smarty->config_dir))		{mkdir($smarty->config_dir, 0777, true);	chmod($smarty->config_dir, 0777);}
if (!file_exists($smarty->cache_dir))		{mkdir($smarty->cache_dir, 0777, true);		chmod($smarty->cache_dir, 0777);}


//	ライブラリ、インターフェースを読み込み
require(DIR_MCWEB . '/framework.php');

require_once(DIR_APP . '/function/array_func.php');
require_once(DIR_APP . '/function/common_func.php');
require_once(DIR_APP . '/function/date_time_func.php');
require_once(DIR_APP . '/function/define_func.php');

// 定数定義を読み込み
require(DIR_DEFINE . '/define.ini.php');
require(DIR_DEFINE . '/user_config/user.define.ini.php');

// メッセージ定義を設定
MessageManager::setPath(
	array(
		DIR_DEFINE . '/message.csv',
		DIR_DEFINE . '/user_config/user.message.csv',
		),
	DIR_CACHE . '/message/MessageDefine.php'
);

// メッセージ定義の入力ファイルを以下のように配列で複数渡すことも可能
//MessageManager::setPath(
//	array(
//		DIR_DEFINE . '/message1.csv',
//		DIR_DEFINE . '/message2.csv',
//	),
//	DIR_CACHE . '/message/MessageDefine.php'
//);

// 出力ファイルよりタイムスタンプが新しい入力ファイルが一つでもあれば、
// 出力ファイルは再生成されます。
// 構成の増減は再生成に影響しませんので、注意しましょう。

//	フレームワークに設定を定義
$c = MCWEB_Framework::getInstance();

//	変数出力インターフェースを設定
$c->setSceneOutputVars(new MCWEB_SceneOutputVarsBySmarty($smarty));

//	クラス自動読み込みフォルダを設定
$c->setClassAutoLoadDir(
	  DIR_APP . '/class'
	, DIR_APP . '/class/scene'
	, DIR_APP . '/class/database'
	, DIR_FILTER . '/startup'
	, DIR_FILTER . '/scene_create'
	, DIR_FILTER . '/scene_input'
	, DIR_FILTER . '/scene_check'
	, DIR_FILTER . '/scene_task'
	, DIR_FILTER . '/scene_draw'
	, DIR_FILTER . '/rewrite_template'
	, DIR_FILTER . '/post_output'
);

?>