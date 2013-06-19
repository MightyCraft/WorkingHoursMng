<?php

class ValidatorTextWithEmoji extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct(NULL);
		$this->emoji = TRUE;
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorTextWithEmoji インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorTextWithEmoji;
	}
}

?>