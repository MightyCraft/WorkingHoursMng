<?php

/**
 * セッションIDに不正文字が挿入される攻撃に対処
 */
class mcweb_sanitize_session_id implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		if (is_array($_COOKIE) && array_key_exists(session_name(), $_COOKIE))
		{
			if (0 == preg_match("/^[a-zA-Z0-9]+$/", $_COOKIE[session_name()]))
			{
				throw new MCWEB_BadRequestException();
			}
		}
		else if (is_array($_POST) && array_key_exists(session_name(), $_POST))
		{
			if (0 == preg_match("/^[a-zA-Z0-9]+$/", $_POST[session_name()]))
			{
				throw new MCWEB_BadRequestException();
			}
		}
		else if (is_array($_GET) && array_key_exists(session_name(), $_GET))
		{
			if (0 == preg_match("/^[a-zA-Z0-9]+$/", $_GET[session_name()]))
			{
				throw new MCWEB_BadRequestException();
			}
		}
	}
}

?>