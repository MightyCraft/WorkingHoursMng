<?php
/**
 * DoCoMo、SoftBank、AUでのサイト登録月額課金システムをサポートするクラスのインターフェースを定義します。
 *
 * mcweb_mobile_site_register(new MyMobileSiteRegister, new MyMobileSiteUnregister)
 * フィルターに上記のように登録します。
 */
interface MCWEB_InterfaceMobileSiteRegister
{
	const SUCCEEDED = 1;		//	登録が正しく完了しました
	const FAILED = 2;			//	登録に失敗しました
	const ALREADY_DONE = 3;		//	既に登録されていました

	/**
	 * エントリーとなるActionパスを返してください。
	 * このActionパスにアクセスがあった場合に、このクラスのコールバックが呼び出されます。
	 */
	function actionEntry();

	/**
	 * 不正なアクセスが来た場合にコールバックされます。
	 * 通常は 404エラーなどを返すと良いでしょう。
	 *
	 * 不正にならないための条件（正当であるという条件）は以下の通り。
	 * これらに反した場合、このメソッドがコールバックされる。
	 *
	 * DoCoMo
	 * ・DoCoMoのIP帯からのアクセスである
	 * ・UserAgentが付与されていない
	 *
	 * SoftBank
	 * ・SoftBankのIP帯からのアクセスである
	 * ・HTTPリクエストヘッダに、"HTTP_X_JPHONE_REGISTERED"が存在する
	 *
	 * AU
	 * ・AUのIP帯からのアクセスである
	 * ・SOUP通信で確かめた結果、正しいリクエストだと保証された
	 */
	function illegalAccess();

	/**
	 * ユーザーの登録を行います。
	 * @return int クラス定数のうち、SUCCEEDED, FAILED, ALREADY_DONE いずれかを返してください
	 */
	function task();

	/**
	 * SoftBank、AUのみで使用。
	 * 登録が正規終了した際に表示するシーンのActionパスを返してください。
	 * @return String Actionパス
	 */
	function actionSucceeded();

	/**
	 * SoftBank、AUのみで使用。
	 * 登録が異常終了した際に表示するシーンのActionパスを返してください。
	 * @return String Actionパス
	 */
	function actionFailed();

	/**
	 * SoftBankのみで使用。
	 * 登録がユーザー操作でキャンセルされた際に表示するシーンのActionパスを返してください。
	 * @return String Actionパス
	 */
	function actionCanceled();
}


interface MCWEB_InterfaceMobileSiteUnregister
{

	const SUCCEEDED = MCWEB_InterfaceMobileSiteRegister::SUCCEEDED;			//	削除が正しく完了しました
	const FAILED = MCWEB_InterfaceMobileSiteRegister::FAILED;				//	削除に失敗しました
	const ALREADY_DONE = MCWEB_InterfaceMobileSiteRegister::ALREADY_DONE;		//	既に削除されていました

	/**
	 * エントリーとなるActionパスを返してください。
	 * このActionパスにアクセスがあった場合に、このクラスのコールバックが呼び出されます。
	 */
	function actionEntry();

	/**
	 * 不正なアクセスが来た場合にコールバックされます。
	 * 通常は 404エラーなどを返すと良いでしょう。
	 *
	 * 不正にならないための条件（正当であるという条件）は以下の通り。
	 * これらに反した場合、このメソッドがコールバックされる。
	 *
	 * DoCoMo
	 * ・DoCoMoのIP帯からのアクセスである
	 * ・UserAgentが付与されていない
	 *
	 * SoftBank
	 * ・SoftBankのIP帯からのアクセスである
	 * ・HTTPリクエストヘッダに、"HTTP_X_JPHONE_REGISTERED"が存在する
	 *
	 * AU
	 * ・AUのIP帯からのアクセスである
	 * ・SOUP通信で確かめた結果、正しいリクエストだと保証された
	 */
	function illegalAccess();

	/**
	 * ユーザーの削除を行います。
	 * @return int クラス定数のうち、SUCCEEDED, FAILED, ALREADY_DONE いずれかを返してください
	 */
	function task();

	/**
	 * SoftBank、AUのみで使用。
	 * 削除が正規終了した際に表示するシーンのActionパスを返してください。
	 * @return String Actionパス
	 */
	function actionSucceeded();

	/**
	 * SoftBank、AUのみで使用。
	 * 削除が異常終了した際に表示するシーンのActionパスを返してください。
	 * @return String Actionパス
	 */
	function actionFailed();

	/**
	 * SoftBankのみで使用。
	 * 削除がユーザー操作でキャンセルされた際に表示するシーンのActionパスを返してください。
	 * @return String Actionパス
	 */
	function actionCanceled();

}



?>