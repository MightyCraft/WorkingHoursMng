<?php

/**
 * 継承専用クラス
 */
abstract class ValidatorBaseString
{
	protected $nullable = FALSE;
	protected $width = FALSE;
	protected $min = NULL;
	protected $max = NULL;
	protected $preg = NULL;
	protected $not_preg = NULL;
	protected $ngword = NULL;

	protected $error_info = array();

	protected function __construct()
	{
	}

	/**
	 * 拡張エラーメッセージを取得する
	 */
	public function getErrorInfo()
	{
		return $this->error_info;
	}

	/**
	 * バリデーションを行う
	 * @param any $value 対象
	 * @return array エラー配列
	 */
	public function validate($value)
	{
		//	拡張エラーメッセージをクリア
		$this->error_info = array();

		if (0 === strlen($value))
		{
			if ($this->nullable)	return array();
			else					return array('null');
		}

		$errors = array();

		// フォーマットチェックの実施
		if (!$this->check_format_override($value))	array_push($errors, 'format');

		$min = (isset($this->min)) ? $this->min : 0;
		$max = (isset($this->max)) ? $this->max : 2147483647;

		if ($this->width)
		{
			$value_sjis = mb_convert_encoding($value,'sjis-win','UTF-8');
			// 文字列幅による判定
			if (strlen($value_sjis) < $min)	array_push($errors, 'min');
			if (strlen($value_sjis) > $max)	array_push($errors, 'max');
		}
		else
		{
			// 文字数による判定
			if (mb_strlen($value, 'UTF-8') < $min)	array_push($errors, 'min');
			if (mb_strlen($value, 'UTF-8') > $max)	array_push($errors, 'max');
		}

		if (!is_null($this->ngword))
		{
			$c = new NgWord();
			$result = $c->check($value, $this->ngword);

			if (false !== $result)
			{
				$this->error_info['ngword']['index'] = $result['start'];
				$this->error_info['ngword']['match'] = $result['word'];
				array_push($errors, 'ngword');
			}
		}

		return $errors;
	}

	/**
	 * 継承先で独自のフォーマットチェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return boolean フォーマットチェック結果
	 */
	protected function check_format_override($value)
	{
		if (isset($this->preg) && 1 !== preg_match($this->preg, $value))	return FALSE;

		if (isset($this->not_preg) && 0 !== preg_match($this->not_preg, $value))	return FALSE;

		return TRUE;
	}

	/**
	 * 値がセットされていなかった場合、この他のエラーチェックは行わない。その際はエラーコード配列は空となる
	 * @return ValidatorBaseString インスタンス
	 */
	public function nullable()
	{
		$c = clone $this;
		$c->nullable = TRUE;
		return $c;
	}

	/**
	 * 正規表現にマッチするかチェックする。マッチしなかった場合エラーとなる。
	 * @return	ValidatorBaseString インスタンス
	 */
	public function preg($match)
	{
		$c = clone $this;
		$c->preg = $match;
		return $c;
	}

	/**
	 * 正規表現にマッチしないかチェックする。マッチした場合エラーとなる。
	 * @return	ValidatorBaseString インスタンス
	 */
	public function not_preg($match)
	{
		$c = clone $this;
		$c->not_preg = $match;
		return $c;
	}

	/**
	 * 文字数の下限を設定する。半角全角を問わず1文字とカウントする。
	 * @return ValidatorBaseString インスタンス
	 */
	public function min($n)
	{
		$c = clone $this;
		$c->min = $n;
		return $c;
	}

	/**
	 * 文字数の上限を設定する。半角全角を問わず1文字とカウントする。
	 * @return ValidatorBaseString インスタンス
	 */
	public function max($n)
	{
		$c = clone $this;
		$c->max = $n;
		return $c;
	}

	/**
	 * 上下限の判定を文字数ではなく幅（半角を1、全角を2）で行うよう切り替える。
	 * @return ValidatorBaseString インスタンス
	 */
	public function width()
	{
		$c = clone $this;
		$c->width = TRUE;
		return $c;
	}

	/**
	 * NGワードが存在するかをチェックする。
	 * @param	$words NGワードを指定するための二次元配列。
	 * 			$words[0] = array('シネ', 'スペシネフ');
	 * 			$words[1] = array('バカ', 'バカンス', 'シバカリ');
	 * 			このようにした場合、文字列に'シネ'か'バカ'が含まれていた場合にエラーとする。
	 * 			ただしその'シネ'が'スペシネフ'という文字列の一部だった場合は許容する（エラーとしない）
	 * 			'バカ'が'バカンス'もしくは'シバカリ'という文字列の一部だった場合は許容する。
	 * @return ValidatorBaseString インスタンス
	 */
	public function ngword($words)
	{
		$c = clone $this;
		$c->ngword = $words;
		return $c;
	}

}
?>