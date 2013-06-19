<?php

/**
 * 継承専用クラス
 */
abstract class ValidatorBaseDatetime
{
	protected $nullable = FALSE;
	protected $min;
	protected $max;

	/**
	 * バリデーションを行う
	 * @param any $value 対象
	 * @return array エラー配列
	 */
	function validate($value)
	{
		if (0 === strlen($value))
		{
			if ($this->nullable)	return array();
			else					return array('null');
		}

		// 日時型チェック
		if(FALSE === $this->check_value_format_override($value))	return array('format');

		$errors = array();
		if (isset($this->min))
		{
			if(0 < strcmp($this->min, $value))
			{
				array_push($errors, 'min');
			}
		}
		if (isset($this->max))
		{
			if(0 > strcmp($this->max, $value))
			{
				array_push($errors, 'max');
			}
		}

		return $errors;
	}

	/**
	 * 継承先で独自の妥当性チェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return boolean 不正ならばFALSE
	 */
	protected function check_value_format_override($value)
	{
		return FALSE;
	}

	/**
	 * 日時の妥当性チェックを行う
	 * @param string $datetime "YYYYMMDDHHMMSS"
	 * @return boolean 不正ならばFALSE
	 */
	protected function check_datetime_format($datetime)
	{
		if(!preg_match('/^[0-9]{14}$/', $datetime)) return FALSE;

		list($date, $time) = sscanf($datetime, '%08d%06d');
		if(!$this->check_date_format(sprintf('%08d', $date))) return FALSE;
		if(!$this->check_time_format(sprintf('%06d', $time))) return FALSE;

		return TRUE;
	}

	/**
	 * 日付の妥当性チェックを行う
	 * @param string $date "YYYYMMDD"
	 * @return boolean 不正ならばFALSE
	 */
	protected function check_date_format($date)
	{
		if(!preg_match('/^[0-9]{8}$/', $date)) return FALSE;

		list($year, $month, $day) = sscanf($date, '%04d%02d%02d');
		if(!checkdate($month, $day, $year)) return FALSE;

		return TRUE;
	}

	/**
	 * 時刻の妥当性チェックを行う
	 * @param string $time "HHMMSS"
	 * @return boolean 不正ならばFALSE
	 */
	protected function check_time_format($time)
	{
		if(!preg_match('/^[0-9]{6}$/', $time)) return FALSE;

		list($hour, $min, $sec) = sscanf($time, '%02d%02d%02d');
		if(0 > $hour || 23 < $hour) return FALSE;
		if(0 > $min || 59 < $min) return FALSE;
		if(0 > $sec || 59 < $sec) return FALSE;

		return TRUE;
	}

	/**
	 * 値がセットされていなかった場合、この他のエラーチェックは行わない。その際はエラーコード配列は空となる
	 * @return ValidatorBaseDatetime インスタンス
	 */
	public function nullable()
	{
		$c = clone $this;
		$c->nullable = TRUE;
		return $c;
	}

	/**
	 * @return ValidatorBaseDatetime インスタンス
	 */
	function min($d)
	{
		// 日時型チェック
		if(FALSE === $this->check_value_format_override($d))
		{
			throw new Exception('minの指定が不正です');
		}

		$c = clone $this;
		$c->min = $d;
		return $c;
	}

	/**
	 * @return ValidatorBaseDatetime インスタンス
	 */
	function max($d)
	{
		// 日時型チェック
		if(FALSE === $this->check_value_format_override($d))
		{
			throw new Exception('maxの指定が不正です');
		}

		$c = clone $this;
		$c->max = $d;
		return $c;
	}
}
?>