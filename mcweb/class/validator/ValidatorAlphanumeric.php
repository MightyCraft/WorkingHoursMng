<?php

class ValidatorAlphanumeric extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('[a-zA-Z0-9]');
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorAlphanumeric インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorAlphanumeric;
	}
}

?>