<?php

class ValidatorMailAddress extends ValidatorBaseString
{
	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorMailAddress インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorMailAddress;
	}

	/**
	 * 継承先で独自のフォーマットチェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return array フォーマットチェック結果
	 */
	protected function check_format_override($value)
	{
		if(!parent::check_format_override($value)) return FALSE;

		// メールアドレスチェック
		$address = $value;
		$n = strpos($address, '@');
		if (FALSE === $n)
		{
			return FALSE;
		}
		else
		{
			//	DoCoMoアドレス救済のためのトリック
			$localpart = substr($address, 0, $n);
			$domain = substr($address, $n);
			$localpart = trim($localpart, '.');
			do
			{
				$n = strlen($localpart);
				$localpart = str_replace('..', '.', $localpart);
			}
			while($n !== strlen($localpart));
			$address = $localpart . $domain;

			//	RFCに（ほぼ）準拠の正規表現
			if (1 !== preg_match('/^(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*$/', $address))
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}
?>