<?php


/**
 * シーンクラスインターフェース。
 * 特定パスへのアクセスに対する処理を書く。
 */
interface MCWEB_InterfaceScene
{
	/**
	 * 入力の無害化と登録を行います。
	 * NULLバイト攻撃や、SQLインジェクションへの対策が目的です。
	 * $paramを介してURLパラメータを取得し、メンバー変数に保存してください。
	 *
	 * @param	$param	MCWEB_InterfaceUrlParam。URLパラメータから安全に変数を取得する。
	 * @return			処理の委譲が発生した場合、MCWEB_InterfaceSceneForwardを返す。通常はnullを返す。
	 */
	function input(MCWEB_InterfaceUrlParam $param);

	/**
	 * 入力の正当性をチェックします。
	 * 範囲外の値が渡されていないかのチェックなどを行い、問題があればエラーページに委譲しましょう。
	 *
	 * @return			処理の委譲が発生した場合、MCWEB_InterfaceSceneForwardを返す。通常はnullを返す。
	 */
	function check();

	/**
	 * データの処理を行います。
	 * DBへの書き込みや、セッションへの書き込みなどの更新処理が主となります。
	 *
	 * @param	$access		Smartyに安全にアクセスするためのインターフェースを提供する。
	 * @return				処理の委譲が発生した場合、MCWEB_InterfaceSceneForwardを返す。通常はnullを返す。
	 */
	function task(MCWEB_InterfaceSceneOutputVars $access);

	/**
	 * 画面出力内容について処理を行います。
	 * DBからの読み込みや、セッションからの読み込みなどの読み込み処理と、Smartyへの登録処理のみ行ってください。
	 *
	 * @param	$access		Smartyに安全にアクセスするためのインターフェースを提供する。
	 */
	function draw(MCWEB_InterfaceSceneOutputVars $access);


	function do_input(MCWEB_InterfaceUrlParam $param);
	function do_check();
	function do_task(MCWEB_InterfaceSceneOutputVars $access);
	function do_draw(MCWEB_InterfaceSceneOutputVars $access);
}

/**
 * 委譲時のみに利用できるシーンであることを表すインターフェース。
 * これをimplementsしているシーンは、ユーザーが直接呼び出した際に404例外となる。
 */
interface MCWEB_InterfaceScenePrivate
{
}

/**
 * 委譲処理を宣言するためのクラスインターフェース。
 */
interface MCWEB_InterfaceSceneForward
{
	function get_path();
	function get_param();
	function regist($type, $param);
}


/**
 * 出力テンプレートに、変数の内容を登録するためのインターフェースです。
 */
interface MCWEB_InterfaceSceneOutputVars
{

	/**
	 * 登録要素全てを、HTMLエスケープした後に登録します。
	 * 多次元配列だったとしても、全ての要素がHTMLエスケープされます。
	 */
	function text($tpl_var, $value = null);

	/**
	 * 要素をそのまま登録します。HTMLタグなどを除き、通常のテキストはtext関数を使って登録してください。
	 */
	function htmltag($tpl_var, $value = null);

	/**
	 * 既にセットした値を取得します。
	 */
	function get($tpl_var);

	/**
	 * いままで登録した要素を全て破棄します。
	 */
	function clear();

	/**
	 * 出力テンプレートのパスを設定します。設定を行わなかった場合は、このシーンと同一パスに存在するテンプレートが呼び出されます。
	 *
	 * @param	$template_path	テンプレートパス。
	 */
	function setTemplate($template_path);

	/**
	 * 設定せれている出力テンプレートのパスを取得します。
	 *
	 * @return	設定されている出力テンプレートパス。
	 */
	function getTemplate();

	/**
	 * 設定したテンプレートの内容を出力します。
	 * @return 成功した場合はTRUEを。テンプレートが見つからないなどでエラーとなった場合はFALSEを返します。
	 */
	function outputTemplate();
}

/**
 * MCWEB_InterfaceSceneOutputVarsで登録できるオブジェクトであることを表すインターフェースです。
 * これをimplementsしていないオブジェクトは、MCWEB_InterfaceSceneOutputVarsを使って内容を登録することができません。
 */
interface MCWEB_InterfaceSceneOutputableVarsObject
{
}

?>