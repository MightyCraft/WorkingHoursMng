<?php

class ValidatorZenkakuKatakana extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('\xE3(\x82[\xA1-\xBF]|\x83[\x80-\xB6])');
		$this->zenkaku = TRUE;
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorZenkakuKatakana インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorZenkakuKatakana;
	}
}

?>