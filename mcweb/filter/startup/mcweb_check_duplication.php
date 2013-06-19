<?php

/**
 * 二重投稿をチェックするための機能を提供します
 */
class mcweb_check_duplication implements MCWEB_filter_startup
{
	protected static $key_name;
	protected static $regkey;

	function __construct($key_name)
	{
		self::$key_name = $key_name;
	}

	function run($entry_path)
	{
		$value = FALSE;
		if (isset($_GET[self::$key_name]))		$value = $_GET[self::$key_name];
		if (isset($_POST[self::$key_name]))		$value = $_POST[self::$key_name];
		if (isset($_COOKIE[self::$key_name]))	$value = $_COOKIE[self::$key_name];

		self::$regkey = $value;
	}

	/**
	 * 二重投稿をチェックするとともに、セッションにIDが保存されます。
	 * 二重投稿の時はTRUEが、そうでない時はFALSEが返ります。
	 *
	 * セッションへの保存をstartupフィルタの動作段階でやらないのは、HTTPリダイレクトを発生させる可能性があるからです。
	 * 例えば、startupフィルタの段階で二重投稿かをチェックし、セッションにIDを記録します。
	 * その後HTTPリダイレクトが発生した場合、再度同じIDでアクセスが発生します。（リダイレクトですから当然です）
	 * これではリダイレクト後のアクセスでは、必ず二重投稿と判定されてしまうでしょう。
	 */
	static function check()
	{
		if (FALSE === self::$regkey)
		{
			return FALSE;
		}
		if (isset($_SESSION['MCWEB']['mcweb_check_duplication'][self::$regkey]))
		{
			return TRUE;
		}
		$_SESSION['MCWEB']['mcweb_check_duplication'][self::$regkey] = 1;
		return FALSE;
	}
}

?>