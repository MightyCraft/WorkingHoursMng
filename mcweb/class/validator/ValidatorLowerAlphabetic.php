<?php

class ValidatorLowerAlphabetic extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('[a-z]');
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorLowerAlphabetic インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorLowerAlphabetic;
	}
}

?>