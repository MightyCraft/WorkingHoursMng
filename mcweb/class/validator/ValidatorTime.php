<?php

class ValidatorTime extends ValidatorBaseDatetime
{
	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorDatetime インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorTime;
	}

	/**
	 * 継承先で独自の妥当性チェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return boolean 不正ならばFALSE
	 */
	protected function check_value_format_override($value)
	{
		return $this->check_time_format($value);
	}
}
?>