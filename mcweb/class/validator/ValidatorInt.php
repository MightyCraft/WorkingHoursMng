<?php

class ValidatorInt extends ValidatorBaseInt
{
	/**
	 * 新たなインスタンスを返す
	 * @return ValidatorInt インスタンス
	 */
	public static function createInstance()
	{
		return new ValidatorInt;
	}

	/**
	 * 継承先で独自のフォーマットチェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return boolean チェック結果
	 */
	protected function check_format_override($value)
	{
		if(!parent::check_format_override($value)) return FALSE;

		// 符号付き整数かどうかを確認
		if (1 !== preg_match('/^[\+\-]{0,1}[0-9]+$/', $value))	return FALSE;

		// +符号は判定上邪魔なのでここで削除
		$value = preg_replace('/^\+/', '', $value);

		// int型チェック
		if ((string)$value !== (string)intval($value))	return FALSE;

		return TRUE;
	}

}
?>