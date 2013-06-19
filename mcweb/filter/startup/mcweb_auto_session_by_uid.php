<?php

/**
 * UIDをセッションIDとしたセッションを自動的にスタートさせます
 */
class mcweb_auto_session_by_uid implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		if (!defined('CLIENT_CARRIER'))	throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');
		if (!defined('CLIENT_UID'))		throw new MCWEB_LogicErrorException('CLIENT_UIDが定義されていません');

		if ('DOCOMO' === CLIENT_CARRIER || 'MOVA' === CLIENT_CARRIER || 'SOFTBANK' === CLIENT_CARRIER || 'AU' === CLIENT_CARRIER)
		{
			session_id(sha1(CLIENT_CARRIER . CLIENT_UID));
			session_start();
		}
		else
		{
			$session_id = isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : '';
			session_id($session_id);
			@session_start();
			$session_id = session_id();

			$path = substr(URL_SITE_ROOT, strlen(URL_DOMAIN_ROOT) - 1);
			$domain = trim(substr(URL_DOMAIN_ROOT, strpos(URL_DOMAIN_ROOT, '//') + 2), '/');
			if ('localhost' === $domain) $domain = false;	//	ドメイン名を指定する場合、'localhost'ではうまく動かない。代わりにfalseを指定してやると良い
			setcookie(session_name(), $session_id, $this->liftime(), $path, $domain);
		}

		//	ここで、期限切れやら不明セッションIDやらの際の処理を行う
		if (empty($_SESSION))
		{
			$this->created();
		}
		else
		{
			$this->accessed();
		}
	}

	protected function liftime()
	{
		if (DEVELOPMENT)
		{
			return 0;
		}
		else
		{
			return time() + 60 * 30;
		}
	}

	protected function created()
	{
		$_SESSION['MCWEB']['session']['created_time'] = time();
		$_SESSION['MCWEB']['session']['last_accessed_time'] = time();
		$_SESSION['MCWEB']['session']['time_from_last_accessed'] = 0;
		$_SESSION['MCWEB']['uid'] = CLIENT_UID;
	}

	protected function accessed()
	{
		$_SESSION['MCWEB']['session']['time_from_last_accessed'] = time() - $_SESSION['MCWEB']['session']['last_accessed_time'];
		$_SESSION['MCWEB']['session']['last_accessed_time'] = time();
	}
}

?>