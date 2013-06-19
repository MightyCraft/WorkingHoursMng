<?php

/**
 * RDBとのアクセス用インターフェースです。
 *
 * RDBとのアクセス用インターフェースです。
 * DatabaseConnectorを介してRDBに接続し、SQL文でやりとりをサポートします。
 *
 */
interface DatabaseAccessor
{
	/**
	 * select, showメソッドの引数として利用される定数です。条件に該当したデータを連想配列として取得できます。
	 */
	const FETCHMODE_ASSOC = 0;

	/**
	 * select, showメソッドの引数として利用される定数です。条件に該当したデータを配列として取得できます。
	 */
	const FETCHMODE_ORDERED = 1;

	/**
	 * RDBに明示的に接続します。
	 *
	 * 基本的にこのメソッドを呼び出す必要はありません。
	 */
	public function connect();

	/**
	 * SELECTクエリを実行し、取得内容を指定されたモードでfetchして返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @param	$mode	FETCHMODE定数によるモード指定
	 * @return	array	取得内容の配列
	 */
	public function select($query, $args, $mode = self::FETCHMODE_ASSOC);

	/**
	 * SHOWクエリを実行し、取得内容を指定されたモードでfetchして返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @param	$mode	FETCHMODE定数によるモード指定
	 * @return	array	取得内容の配列
	 */
	public function show($query, $args, $mode = self::FETCHMODE_ASSOC);

	/**
	 * SELECT COUNT()クエリを実行し、COUNTで取得した件数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		COUNTで取得した件数
	 */
	public function count($query, $args);

	/**
	 * INSERTクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	public function insert($query, $args);

	/**
	 * UPDATEクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	public function update($query, $args);

	/**
	 * DELETEクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	public function delete($query, $args);

	/**
	 * クエリを実行し、成否を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	boolean	SQLの実行成否
	 */
	public function bool($query, $args);

	/**
	 * クエリを実行します。
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	class	結果
	 */
	public function execute($query, $args);

	/**
	 * 最後に挿入された行のIDを取得します。
	 *
	 * @return	int	最後に挿入された行のID
	 */
	public function lastInsertID();

	/**
	 * トランザクションを開始します。
	 */
	public function beginTransaction();

	/**
	 * トランザクションをコミットします。
	 */
	public function commit();

	/**
	 * トランザクションをロールバックします。
	 */
	public function rollback();

	/**
	 * トランザクションが開始されているかを取得します。
	 *
	 * @return boolean true:開始されている、false:開始されていない
	 */
	public function isTransaction();
}
?>