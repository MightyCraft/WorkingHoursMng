<?php

/**
 * セッションを自動的にスタートさせます
 */
class mcweb_auto_session implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		@session_start();
		$uid = '';
		if (defined('CLIENT_UID')) $uid = CLIENT_UID;

		//	ここで、期限切れやら不明セッションIDやらの際の処理を行う
		if (empty($_SESSION))
		{
			session_regenerate_id(true);
			$this->created();
		}
		else
		{
			if ($_SESSION['MCWEB']['uid'] != $uid)
			{
				//	セッションハイジャック
				$old_session_id = session_id();
				session_write_close();

				//	不要だが、念のため
				$_SESSION = array();

				//	新しいセッションIDを自動生成させる
				session_id('');
				@session_start();
				$new_session_id = session_id();

				//	不要だが、念のため
				if ($old_session_id == $new_session_id)	throw new MCWEB_LogicErrorException();

				$this->created();
			}
			else
			{
				$this->accessed();
			}
		}
	}

	protected function created()
	{
		$_SESSION['MCWEB']['session']['created_time'] = time();
		$_SESSION['MCWEB']['session']['last_accessed_time'] = time();
		$_SESSION['MCWEB']['session']['time_from_last_accessed'] = 0;
		if (defined('CLIENT_UID'))
		{
			$_SESSION['MCWEB']['uid'] = CLIENT_UID;
		}
	}

	protected function accessed()
	{
		$_SESSION['MCWEB']['session']['time_from_last_accessed'] = time() - $_SESSION['MCWEB']['session']['last_accessed_time'];
		$_SESSION['MCWEB']['session']['last_accessed_time'] = time();
	}
}

?>