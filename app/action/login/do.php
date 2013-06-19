<?php
require_once(DIR_APP . '/class/common/Login.php');
class _login_do extends PostScene
{
	var $_member_id;
	var $_pass;
	var $_save	= '';

	function check()
	{
		$errors	= MCWEB_ValidationManager::validate(
			$this
			, 'member_id', ValidatorUnsignedInt::createInstance()->min(1)
			, 'pass', ValidatorAlphanumeric::createInstance()->nullable()->min(0)->max(USER_MEMBER_PASSWORD_MAX)
		);
		// ログイン画面へ
		if( !empty($errors) )
		{
			MCWEB_Util::redirectAction("/login/index?member_id={$this->_member_id}&error=failure");
		}
		$obj_common_login	= new Login();
		$flg	= $obj_common_login->checkMemberIdPassword($this->_member_id, hashPassWord($this->_pass));
		// ログイン画面へ
		if( $flg === false )
		{
			MCWEB_Util::redirectAction("/login/index?member_id={$this->_member_id}&error=failure");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		if (!mcweb_check_duplication::check())
		{
			// セッション情報をリフレッシュ
			unset($_SESSION['manhour']);

			// 社員IDをセッションにセット
			$_SESSION['member_id']	= $this->_member_id;

			$domain	=	(isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['SERVER_NAME']);

			//COOKIEにログイン情報を保存するか確認
			if(!empty($this->_save))
			{
				//30日以内後に設定

				//常にログイン状態を確認するCOOKIE
				setcookie('cookie_manhour_member_id', $_SESSION['member_id'], time() + USER_CACHE_LOGIN_MEMBER_ID, '/', $domain);

				//再ログインCOOKIE
				setcookie('cookie_manhour_member_id_login', $_SESSION['member_id'], time()+ USER_CACHE_LOGIN_STATE, '/', $domain);
			}
			else
			{
				//削除
				setcookie('cookie_manhour_member_id', '', time() - 3600, '/', $domain);
				setcookie('cookie_manhour_member_id_login', '', time() - 3600, '/', $domain);
			}
		}
		MCWEB_Util::redirectAction('/input/index');
	}
}
?>