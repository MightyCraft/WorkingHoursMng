<?php

class ValidatorHiragana extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('\xE3(\x81[\x81-\xBF]|\x82[\x80-\x93]|\x83\xBC)');
		$this->zenkaku = TRUE;
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorHiragana インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorHiragana;
	}
}

?>