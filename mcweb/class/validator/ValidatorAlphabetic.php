<?php

class ValidatorAlphabetic extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('[a-zA-Z]');
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorAlphabetic インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorAlphabetic;
	}
}

?>