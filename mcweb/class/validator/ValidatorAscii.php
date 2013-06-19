<?php

class ValidatorAscii extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct('[\x21-\x7E]');
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorAscii インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorAscii;
	}
}

?>