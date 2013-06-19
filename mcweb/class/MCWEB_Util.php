<?php

class MCWEB_Util
{
	/**
	 * テンポラリファイルとファイルのリネームを使って安全にファイルを上書きする。
	 * 通常の方法でファイルを上書きしようとすると、データ量が大きかった場合にロック時間が長くなってしまうのを回避することができる。
	 *
	 * @param	$filename	ファイル名
	 * @param	$data		書き込みデータ
	 */
	public static function safety_file_overwrite($filename, $data)
	{
		$tmp = tempnam(dirname($filename), 'TMP');
		file_put_contents($tmp, $data);

		//	既存ファイルを削除
		@unlink($filename);

		//	テンポラリファイルをリネーム
		rename($tmp, $filename);
	}


	/**
	 * リダイレクトを行う
	 * @param	$url	リダイレクト先URL
	 */
	public static function redirectURL($url)
	{
		header('Location: ' . $url);
		exit;
	}

	/**
	 * Action指定でのリダイレクトを行う
	 * @param	$action	リダイレクト先の、フルパス('/'から始まるパス)Action
	 */
	public static function redirectAction($action)
	{
		header('Location: ' . URL_FRAMEWORK_PHP . $action);
		exit;
	}

	/**
	 * NULLバイト攻撃に対処するための、文字列無害化
	 * 及び、エンコーディングの変更
	 */
	public static function sanitize_string($arr)
	{
		if (is_array($arr))
		{
			foreach($arr as $key => $value)
			{
				if (is_array($value))
				{
					$arr[$key] = sanitize($value);
				}
				else
				{
					//	NULLバイトの削除を行う
					$arr[$key] = str_replace("\0", "", $value);

					//	サーバーとクライアントで、文字列のエンコーディングが違う場合に変換
					if (SERVER_ENCODING != CLIENT_ENCODING)
					{
						if (!mb_check_encoding($arr[$key], CLIENT_ENCODING))
						{
							callback_invalid_var();
						}
						$arr[$key] = mb_convert_encoding($arr[$key], SERVER_ENCODING, CLIENT_ENCODING);
					}
					else
					{
						if (!mb_check_encoding($arr[$key], SERVER_ENCODING))
						{
							callback_invalid_var();
						}
					}
				}
			}
			return $arr;
		}
		return str_replace("\0", "", $arr);
	}

}

/*
function getRandom($length = 64)
{
	static $srand = false;

	if ($srand == false) {
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000) + getmypid());
		$srand = true;
	}

	$value = "";
	for ($i = 0; $i < 2; $i++) {
		// for Linux
		if (file_exists('/proc/net/dev')) {
			$rx = $tx = 0;
			$fp = fopen('/proc/net/dev', 'r');
			if ($fp != null) {
				$header = true;
				while (feof($fp) === false) {
					$s = fgets($fp, 4096);
					if ($header) {
						$header = false;
						continue;
					}
					$v = preg_split('/[:\s]+/', $s);
					if (is_array($v) && count($v) > 10) {
						$rx += $v[2];
						$tx += $v[10];
					}
				}
			}
			$platform_value = $rx . $tx . mt_rand() . getmypid();
		} else {
			$platform_value = mt_rand() . getmypid();
		}
		$now = strftime('%Y%m%d %T');
		$time = gettimeofday();
		$v = $now . $time['usec'] . $platform_value . mt_rand(0, time());
		$value .= md5($v);
	}

	if ($length < 64) {
		$value = substr($value, 0, $length);
	}
	return $value;
}
*/
?>