<?php

/**
 * 使用するフィルタのインスタンスを返すための、コールバックメソッドを定義するためのインターフェース
 */
interface MCWEB_filter_factory
{
	function startup($entry_path);
	function scene_create($action_path);
	function scene_input($action_path);
	function scene_check($action_path);
	function scene_task($action_path);
	function scene_draw($action_path);
	function rewrite_template($template_path);
	function post_output($template_path);
}

/**
 * アクセス直後に動作するフィルタ
 */
interface MCWEB_filter_startup
{
	function run($entry_path);
}

/**
 * シーンクラス生成直前に動作するフィルタ
 * runでMCWEB_InterfaceSceneForwardを返すことにより、シーンの委譲を発生させることもできる
 */
interface MCWEB_filter_scene_create
{
	function run($action_path, MCWEB_InterfaceUrlParam $param);
}

/**
 * MCWEB_InterfaceScene#input が呼び出される直前に動作するフィルタ
 */
interface MCWEB_filter_scene_input
{
	function run($action_path, $scene, MCWEB_InterfaceUrlParam $param);
}

/**
 * MCWEB_InterfaceScene#check が呼び出される直前に動作するフィルタ
 */
interface MCWEB_filter_scene_check
{
	function run($action_path, $scene);
}

/**
 * MCWEB_InterfaceScene#task が呼び出される直前に動作するフィルタ
 */
interface MCWEB_filter_scene_task
{
	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output);
}

/**
 * MCWEB_InterfaceScene#draw が呼び出される直前に動作するフィルタ
 */
interface MCWEB_filter_scene_draw
{
	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output);
}

/**
 * HTMLをSmartyテンプレートに変換する際に動作するフィルタ。（rewriteが呼び出される）
 * また、常にMCWEB_InterfaceScene#drawの直後にも動作する。（runが呼び出される）
 */
interface MCWEB_filter_rewrite_template
{
	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output);
	function rewrite($template_path, &$dom);
}

/**
 * 最終出力結果を変換する際に動作するフィルタ
 */
interface MCWEB_filter_post_output
{
	function run($template_path, &$str);
}

?>
