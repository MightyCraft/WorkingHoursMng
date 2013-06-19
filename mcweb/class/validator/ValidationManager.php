<?php

/**
 * Validatorを便利に使うためのクラス。
 * validateでバリデートエラーがでた場合、そのエラーを内部で保存する。
 * （エラーが出なかった場合は何も起こらない。以前のエラーをクリアすることもない）
 */

class ValidationManager
{
	protected $errors;

	public function __construct()
	{}

	/**
	 * エラーチェック関数
	 *
	 * @param	$data	array(パラメータ内容, Validatorインスタンス)を要素とした連想配列
	 *
	 * @return mixed エラー配列
	 *
	 */
	public function validate($data)
	{
		$errors = array();
		foreach($data as $key => $value)
		{
			$e = $value[1]->validate($value[0]);
			if (0 < count($e))
			{
				$errors[$key] = $e;
			}
		}
		$this->errors = $errors;
		return $errors;
	}

	/**
	 * 最後に記録されたエラー配列から、キーを指定してエラータイプ名配列を取得する
	 *
	 * @param	$key	キー名
	 * @return array	エラータイプ名配列
	 */
	public function get($key)
	{
		return $this->errors[$key];
	}

}


?>