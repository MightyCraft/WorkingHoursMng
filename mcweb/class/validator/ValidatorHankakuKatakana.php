<?php

class ValidatorHankakuKatakana extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('\xEF(\xBD[\xA1-\xBF]|\xBE[\x80-\x9F])');
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorHankakuKatakana インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorHankakuKatakana;
	}
}

?>