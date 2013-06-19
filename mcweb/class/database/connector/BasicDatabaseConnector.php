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

class BasicDatabaseConnector implements DatabaseConnector
{
	protected $db;
	protected $transaction = false;

	/**
	 * コンストラクタ
	 */
	function __construct($dsn)
	{
		$option = array(
			'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL,
		);

		$this->db = new MDB2;
		$this->db = $this->db->factory($dsn, $option);

		$pear = new PEAR;
		if ($pear->isError($this->db))
		{
			throw new Exception($this->db->userinfo);
		}
	}

	/**
	 * RDBに明示的に接続します。
	 *
	 * 基本的にこのメソッドを呼び出す必要はありません。
	 */
	public function connect()
	{
		$result = $this->db->connect();
		$pear = new PEAR;
		if ($pear->isError($result))
		{
			throw new Exception($result->userinfo);
		}
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
		//	ここで接続を行わないと、prepared_statementがエミュレートモードで処理されてしまう
		$this->connect();

		if (substr_count($query, '?') !== COUNT($args))
		{
			throw new Exception('unmatch number of arguments.');
		}

		if (0 === strncasecmp('SELECT', $query, strlen('SELECT')) || 0 === strncasecmp('SHOW', $query, strlen('SHOW')))
		{
			$stmt = $this->db->prepare($query);
			$pear = new PEAR;
			if ($pear->isError($stmt))
			{
				throw new Exception($stmt->userinfo);
			}
			$result = $stmt->execute($args);
			$stmt->free();
			return $result;
		}
		else
		{
			$is_transaction = $this->isTransaction();
			if (!$is_transaction)
			{
				$this->beginTransaction();
			}

			$pear = new PEAR;
			$dml = (0 === strncasecmp('DELETE', $query, strlen('DELETE')) || 0 === strncasecmp('UPDATE', $query, strlen('UPDATE')) || 0 === strncasecmp('INSERT', $query, strlen('INSERT')));
			if ($dml)
			{
				$stmt = $this->db->prepare($query, NULL, MDB2_PREPARE_MANIP);
			}
			else
			{
				$stmt = $this->db->prepare($query);
			}
			if ($pear->isError($stmt))
			{
				throw new Exception($stmt->userinfo);
			}
			$result = $stmt->execute($args);
			$stmt->free();

			if ($dml && !is_int($result))
			{
				$this->rollback();
				throw new Exception($result->userinfo);
			}

			if (!$is_transaction)
			{
				$this->commit();
			}

			return $result;
		}
	}

	/**
	 * 最後に挿入された行のIDを取得します。
	 *
	 * @return	int	最後に挿入された行のID
	 */
	public function lastInsertID()
	{
		$result = $this->db->lastInsertID();
		$pear = new PEAR;
		if ($pear->isError($result))
		{
			throw new Exception($result->userinfo);
		}
		return $result;
	}

	/**
	 * トランザクションを開始します。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function beginTransaction()
	{
		if ($this->isTransaction())
		{
			throw new Exception("transaction has already begun.");
		}
		$this->connect();

		$this->transaction = true;

		$result = $this->db->beginTransaction();
		$pear = new PEAR;
		if ($pear->isError($result))
		{
			throw new Exception($result->userinfo);
		}
	}

	/**
	 * トランザクションをコミットします。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function commit()
	{
		if (!$this->isTransaction())
		{
			throw new Exception("transaction was not started.");
		}
		$this->connect();

		$this->transaction = false;

		$result = $this->db->commit();
		$pear = new PEAR;
		if ($pear->isError($result))
		{
			throw new Exception($result->userinfo);
		}
	}

	/**
	 * トランザクションをロールバックします。
	 *
	 * 内部でconnectメソッドが呼び出されます。
	 */
	public function rollback()
	{
		if (!$this->isTransaction())
		{
			throw new Exception("transaction was not started.");
		}
		$this->connect();

		$this->transaction = false;

		$result = $this->db->rollback();
		$pear = new PEAR;
		if($pear->isError($result))
		{
			throw new Exception($result->userinfo);
		}
	}

	/**
	 * トランザクションが開始されているかを取得します。
	 *
	 * @return boolean true:開始されている、false:開始されていない
	 */
	public function isTransaction()
	{
		return $this->transaction;
	}
}
?>