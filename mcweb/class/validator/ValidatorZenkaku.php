<?php

class ValidatorZenkaku extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct(NULL);
		$this->zenkaku = TRUE;
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorZenkaku インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorZenkaku;
	}
}

?>