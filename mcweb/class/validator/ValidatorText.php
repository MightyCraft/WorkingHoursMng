<?php

class ValidatorText extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct(NULL);
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorText インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorText;
	}
}

?>