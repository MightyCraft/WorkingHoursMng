<?php

require_once('MDB2.php');

//	MDB2のMYSLQ用Driverの中から、既知のバグを修正したものを読み込む
//	これは、デフォルトのPEARフォルダの中の古いものよりも優先して読み込まれる
require_once(DIR_MCWEB . '/PEAR/MDB2/Driver/mysql.php');
require_once(DIR_MCWEB . '/PEAR/MDB2/Driver/Datatype/mysql.php');
require_once(DIR_MCWEB . '/PEAR/MDB2/Driver/Function/mysql.php');
require_once(DIR_MCWEB . '/PEAR/MDB2/Driver/Manager/mysql.php');
require_once(DIR_MCWEB . '/PEAR/MDB2/Driver/Native/mysql.php');
require_once(DIR_MCWEB . '/PEAR/MDB2/Driver/Reverse/mysql.php');

require_once('DatabaseConnector.php');

/**
 * マスター/スレイブ構成のDB設計に対するコネクタです。
 *
 * SELECT、もしくはSHOWの場合のみ、スレイブにアクセスします。
 * もしSELECT、もしくはSHOWであってもマスターにアクセスしたい場合は、トランザクション中に行ってください。
 * トランザクション中は、全てのアクセス対象がマスターになります。
 */
class ReplicationDatabaseConnector implements DatabaseConnector
{
	protected $master;
	protected $slave;

	/**
	 * コンストラクタ
	 */
	function __construct(DatabaseConnector $master, DatabaseConnector $slave)
	{
		$this->master = $master;
		$this->slave = $slave;
	}

	/**
	 * RDBに明示的に接続します。
	 *
	 * 基本的にこのメソッドを呼び出す必要はありません。
	 */
	public function connect()
	{
		$this->master->connect();
		$this->slave->connect();
	}

	/**
	 * クエリを実行します。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	class	結果
	 */
	public function execute($query, $args)
	{
		$query = trim($query);
		if ($this->isTransaction() || (0 !== strncasecmp('SELECT', $query, strlen('SELECT')) && 0 !== strncasecmp('SHOW', $query, strlen('SHOW'))))
		{
			$rs = $this->master->execute($query, $args);
		}
		else
		{
			$rs = $this->slave->execute($query, $args);
		}
		return $rs;
	}

	/**
	 * 最後に挿入された行のIDを取得します。
	 *
	 * @return	int	最後に挿入された行のID
	 */
	public function lastInsertID()
	{
		return $this->master->lastInsertID();
	}

	/**
	 * トランザクションを開始します。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function beginTransaction()
	{
		$this->master->beginTransaction();
	}

	/**
	 * トランザクションをコミットします。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function commit()
	{
		$this->master->commit();
	}

	/**
	 * トランザクションをロールバックします。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function rollback()
	{
		$this->master->rollback();
	}

	/**
	 * トランザクションが開始されているかを取得します。
	 *
	 * @return boolean true:開始されている、false:開始されていない
	 */
	public function isTransaction()
	{
		return $this->master->isTransaction();
	}
}
?>