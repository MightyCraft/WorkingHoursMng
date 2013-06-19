<?php

/**
 * 継承専用クラス
 */
abstract class ValidatorFormatString extends ValidatorBaseString
{
	protected $regex = NULL;
	protected $multiline = FALSE;
	protected $whitespace = FALSE;
	protected $zenkaku = FALSE;
	protected $emoji = FALSE;

	protected function __construct($regex)
	{
		parent::__construct();
		$this->regex = $regex;
	}

	/**
	 * 継承先で独自のフォーマットチェックを行うオーバライド用メソッド
	 * @param any $value 対象
	 * @return boolean フォーマットチェック結果
	 */
	protected function check_format_override($value)
	{
		// 文字コード正当性チェック
		if (!mb_check_encoding($value, 'UTF-8')) return FALSE;

		//	親でのチェックから先に行います
		if (!parent::check_format_override($value)) return FALSE;

		$pattern = array();
		$anti_pattern = array();

		// 空白文字
		if ($this->whitespace)	$pattern[] = '[ \t]';
		else					$anti_pattern[] = '[ \t]';
		// 改行文字
		if ($this->multiline)	$pattern[] = '[\r\n\f]';
		else					$anti_pattern[] = '[\r\n\f]';
		// 絵文字
		if ($this->emoji)		$pattern[] = '\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]';
		else					$anti_pattern[] = '\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]';

		if (0 < count($anti_pattern))
		{
			// 不許可文字のチェック
			if (0 !== preg_match('/'.implode('|', $anti_pattern).'/', $value))	return FALSE;
		}
		if (0 < count($pattern))
		{
			// 許容文字の削除
			$value = preg_replace('/'.implode('|', $pattern).'/', '', $value);
		}

		// 制御コードチェック
		if (0 !== preg_match('/[\x00-\x1F]|\x7F|\xC2[\x80-\xA0]|\xC2\xAD/', $value)) return FALSE;

		if (isset($this->regex))
		{
			// 条件のみ存在確認
			if (1 !== preg_match('/^(' . $this->regex . ')*$/', $value)) return FALSE;
		}

		if (!$this->zenkaku)
		{
			// 全角のみでなければ半角ASCIIと半角カナを削除する
			$value = preg_replace('/\xEF(\xBD[\xA1-\xBF]|\xBE[\x80-\x9F])|[\x21-\x7E]/', '', $value);
		}
		// 全角正当性チェック
		if (0 < strlen($value))
		{
			// 文字数と文字列幅を比較チェック
			$len = mb_strlen($value, 'UTF-8');
			$value_sjis = mb_convert_encoding($value, 'sjis-win', 'UTF-8');
			$width = strlen($value_sjis);
			if ($len * 2 !== $width) return FALSE;
		}

		return TRUE;
	}

	/**
	 * 改行[\r\n\f]を許容するようになります。
	 */
	public function multiline()
	{
		$c = clone $this;
		$c->multiline = TRUE;
		return $c;
	}

	/**
	 * スペース[ \t]を許容するようになります。
	 */
	public function whitespace()
	{
		$c = clone $this;
		$c->whitespace = TRUE;
		return $c;
	}

}