<?php
require_once(DIR_APP . '/class/common/Login.php');
class _login_logout extends GetScene
{
	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		//COOKIE削除
		setcookie(
			'cookie_manhour_member_id', '', time() - 3600, '/',
			(isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['SERVER_NAME']));

		// セッション破棄
		$_SESSION = array();
		unset($_SESSION);
		// セッションクッキーを削除
		if (ini_get("session.use_cookies"))
		{
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"], $params["secure"], $params["httponly"]
			);
		}
		session_destroy();

		//ログイン画面へ遷移
		MCWEB_Util::redirectAction('/login/index');
	}
}

?>