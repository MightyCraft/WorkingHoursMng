<?php

class ValidatorZenkakuWithEmoji extends ValidatorFormatString
{
	protected function __construct()
	{
		parent::__construct(NULL);
		$this->zenkaku = TRUE;
		$this->emoji = TRUE;
	}

	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorZenkakuWithEmoji インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorZenkakuWithEmoji;
	}
}

?>