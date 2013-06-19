<?php

/**
 * Validatorを便利に使うためのstaticクラス。
 * validateでバリデートエラーがでた場合、そのエラーを内部で保存する。
 * （エラーが出なかった場合は何も起こらない。以前のエラーをクリアすることもない）
 */

class MCWEB_ValidationManager
{
	static $manager;

	private function __construct()
	{}

	/**
	 * エラーチェック関数
	 *
	 * @param	$scene			シーンクラス
	 * @param	$param_name1	URLパラメータ名
	 * @param	$validator1		Validatorインスタンス
	 * @param	[$param_name2]...
	 *
	 * @return mixed エラー配列
	 *
	 */
	public static function validate(MCWEB_InterfaceUrlParamAutoRegist $scene)
	{
		$arr = func_get_args();
		if (count($arr) < 1 || 0 != ((count($arr) - 1) % 2))
		{
			throw new MCWEB_LogicErrorException();
		}
		array_shift($arr);

		$values = array();
		for($i = 0; $i < count($arr); $i += 2)
		{
			$key = $arr[$i];
			$value = $arr[$i + 1];
			$values[$key] = array($scene->{$scene->prefix_text() . $key}, $value);
		}

		$manager = new ValidationManager;
		$errors = $manager->validate($values);

		if (0 < count($errors))
		{
			//	static変数にエラーを登録
			self::$manager = $manager;
		}
		return $errors;
	}

	/**
	 * 最後に記録されたエラー配列から、キーを指定してエラータイプ名配列を取得する
	 *
	 * @param	$key	キー名
	 * @return array	エラータイプ名配列
	 */
	public static function get($key)
	{
		return self::$manager->get($key);
	}

}


?>