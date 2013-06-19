<?php

/**
 * 継承専用クラス
 */
abstract class ValidatorBaseInt
{
	protected $nullable = FALSE;
	protected $min;
	protected $max;

	/**
	 * バリデーションを行う
	 * @return array エラー配列
	 */
	function validate($value)
	{
		if (0 === strlen($value))
		{
			if ($this->nullable)	return array();
			else					return array('null');
		}

		// フォーマットチェック
		if (!$this->check_format_override($value))	return array('format');

		$value = intval($value);

		$min = (isset($this->min)) ? $this->min : -2147483647 - 1; // -2147483648 と書くと浮動小数点になってしまう。原因は'-' と 2147483648 が別トークンとして扱われるため
		$max = (isset($this->max)) ? $this->max : 2147483647;

		$errors = array();
		if ($value < $min)	array_push($errors, 'min');
		if ($value > $max)	array_push($errors, 'max');

		return $errors;
	}

	/**
	 * 継承先で独自のフォーマットチェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return boolean チェック結果
	 */
	protected function check_format_override($value)
	{
		return TRUE;
	}

	/**
	 * 値がセットされていなかった場合、この他のエラーチェックは行わない。その際はエラーコード配列は空となる
	 * @return ValidatorInt インスタンス
	 */
	public function nullable()
	{
		$c = clone $this;
		$c->nullable = TRUE;
		return $c;
	}

	/**
	 * @return ValidatorInt インスタンス
	 */
	function min($n)
	{
		$c = clone $this;
		$c->min = $n;
		return $c;
	}

	/**
	 * @return ValidatorInt インスタンス
	 */
	function max($n)
	{
		$c = clone $this;
		$c->max = $n;
		return $c;
	}
}
?>