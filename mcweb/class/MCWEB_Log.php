<?php

class MCWEB_Log
{
	public static function write($message, $filename = 'debug.txt')
	{
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '-';
		$path = MCWEB_Framework::getInstance()->entry_path;

		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		if (!empty($query))
		{
			// eclipse用のデバッグパラメータは削除
			$match = array('start_debug', 'debug_host', 'send_sess_end', 'debug_session_id', 'original_url', 'debug_start_session', 'debug_no_cache', 'debug_port');
			$arr = explode('&', $query);
			$i = 0;
			while($i < count($arr))
			{
				foreach($match as $value)
				{
					if ($flg = (0 < preg_match('/^' . $value . '=/', $arr[$i])))
					{
						break;
					}
				}
				if ($flg)	array_splice($arr, $i, 1);
				else		++$i;
			}
			$query = implode('&', $arr);
		}
		if (!empty($query))
		{
			$path .= '?' . $query;
		}

		$stack = debug_backtrace();
		$file = str_replace('\\', '/', $stack[0]['file']);
		$line = $stack[0]['line'];
		if (0 == strncmp(DIR_LIB_ROOT, $file, strlen(DIR_LIB_ROOT))) $file = substr($file, strlen(DIR_LIB_ROOT));


		$fp = fopen(DIR_LOG . '/' . $filename, 'a');
		if (NULL !== $fp)
		{
			//	改行コードを削除
			$message = str_replace("\r\n", '', $message);
			$message = str_replace("\n", '', $message);
			fwrite($fp, sprintf("%s [%s] \"%s %s\" '%s:%s' %s\n", $addr, date("Y/m/d:H:i:s"), $method, $path, $file, $line, $message));
			fclose($fp);
		}
	}
}
?>