<?php

class ValidatorUpperAlphabetic extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('[A-Z]');
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorUpperAlphabetic インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorUpperAlphabetic;
	}
}

?>