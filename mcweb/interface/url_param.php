<?php

define('VAR_TYPE_BINARY', 1);
define('VAR_TYPE_STRING', 2);
define('VAR_TYPE_INT', 3);
define('VAR_TYPE_FLOAT', 4);


/**
 * URLパラメータから、安全に値を取得するためのインターフェース。
 */
interface MCWEB_InterfaceUrlParam
{

	/**
	 * $_GETから、値を取得します。
	 * @param	type	型を指定します。
	 * @param	key		連想配列キーです。
	 */
	function get($type, $key);

	/**
	 * $_POSTから、値を取得します。
	 * @param	type	型を指定します。
	 * @param	key		連想配列キーです。
	 */
	function post($type, $key);

	/**
	 * $_COOKIEから、値を取得します。
	 * @param	type	型を指定します。
	 * @param	key		連想配列キーです。
	 */
	function cookie($type, $key);

	/**
	 * シーン委譲用パラメータ $backup['FORWARD']から、値を取得します。
	 * @param	type	型を指定します。
	 * @param	key		連想配列キーです。
	 */
	function forward($type, $key);

	/**
	 * バイナリデータは、そのまま登録します。
	 */
	function validate_binary($input);

	/**
	 * NULLバイト攻撃に対するチェックを行った後、エンコード変換を行ってから登録します。
	 */
	function validate_string($input);

	/**
	 * INT型として返します。
	 */
	function validate_int($input);

	/**
	 * FLOAT型として返します。
	 */
	function validate_float($input);
}

/**
 * クラスの特定メンバー変数に、URLパラメータのデータを自動登録する機能を提供するためのインターフェース。
 *
 * バイナリデータとして登録するか、テキストデータとして登録するかは、prefixで判断される。
 * 両方のprefixを満たした場合は、prefixが長い側として登録する。
 * 例：バイナリが'_bin_'、テキストが'_'だった場合、'_bin_data'は双方の要件を満たすが、より接頭語の長いバイナリ側として登録される。
 */
interface MCWEB_InterfaceUrlParamAutoRegist
{
	/**
	 * 自動で変数登録される場合の、URLパラメータ優先度を配列として返すようにオーバーライドする。
	 * 例えば array('GET', 'COOKIE') を返した場合、$_GETからパラメータを取得した後、$_COOKIEからパラメータを取得（同名があれば上書き）する。
	 */
	function regist_types();

	/**
	 * バイナリデータとして自動登録をする変数の先頭につける、接頭語を返す。
	 */
	function prefix_binary();

	/**
	 * テキストデータとして自動登録をする変数の先頭につける、接頭語を返す。
	 */
	function prefix_text();
}

?>