<?php

/**
 * RDBとの接続用インターフェースです。
 *
 * RDBとの接続用インターフェースです。
 * 生成時には実際には接続されません。
 * 接続が必要になるか、connectメソッドを明示的に呼び出すことにより接続が行われます。
 *
 */
interface DatabaseConnector
{
	/**
	 * RDBに明示的に接続します。
	 *
	 * 基本的にこのメソッドを呼び出す必要はありません。
	 */
	public function connect();

	/**
	 * クエリを実行します。
	 *
	 * 内部でconnectメソッドが呼び出されます。
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
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function beginTransaction();

	/**
	 * トランザクションをコミットします。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function commit();

	/**
	 * トランザクションをロールバックします。
	 *
	 * 内部でconnectメソッドが呼び出されます。
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