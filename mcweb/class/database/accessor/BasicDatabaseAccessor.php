<?php

require_once('DatabaseAccessor.php');

class BasicDatabaseAccessor implements DatabaseAccessor
{
	/**
	 * コンストラクタで渡されたDatabaseConnectorインスタンス
	 */
	protected $connector;

	/**
	 * コンストラクタ
	 * @param $connector	DatabaseConnectorのインスタンス
	 */
	function __construct(DatabaseConnector $connector)
	{
		$this->connector = $connector;
	}

	/**
	 * RDBに明示的に接続します。
	 *
	 * 基本的にこのメソッドを呼び出す必要はありません。
	 */
	public function connect()
	{
		$this->connector->connect();
	}

	/**
	 * SELECTクエリを実行し、取得内容を指定されたモードでfetchして返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @param	$mode	FETCHMODE定数によるモード指定
	 * @return	array	取得内容の配列
	 */
	public function select($query, $args, $mode = self::FETCHMODE_ASSOC)
	{
		$method = 'select';
		$query = trim($query);
		if (0 !== strncasecmp($method, $query, strlen($method)))
		{
			throw new Exception('unmatch method \'' . $method . '\'');
		}
		return $this->read($query, $args, $mode);
	}

	/**
	 * SHOWクエリを実行し、取得内容を指定されたモードでfetchして返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @param	$mode	FETCHMODE定数によるモード指定
	 * @return	array	取得内容の配列
	 */
	public function show($query, $args, $mode = self::FETCHMODE_ASSOC)
	{
		$method = 'show';
		$query = trim($query);
		if (0 !== strncasecmp($method, $query, strlen($method)))
		{
			throw new Exception('unmatch method \'' . $method . '\'');
		}
		return $this->read($query, $args, $mode);
	}

	/**
	 * SELECT COUNT()クエリを実行し、COUNTで取得した件数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		COUNTで取得した件数
	 */
	public function count($query, $args)
	{
		$query = trim($query);
		if (0 === preg_match('/^select\s+count\s*\(/i', $query))
		{
			throw new Exception('unmatch method \'count\'');
		}
		$result = $this->read($query, $args, self::FETCHMODE_ORDERED);
		return (int)$result[0][0];
	}

	/**
	 * INSERTクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	public function insert($query, $args)
	{
		$method = 'insert';
		$query = trim($query);
		if (0 !== strncasecmp($method, $query, strlen($method)))
		{
			throw new Exception('unmatch method \'' . $method . '\'');
		}
		return $this->write($query, $args);
	}

	/**
	 * UPDATEクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	public function update($query, $args)
	{
		$method = 'update';
		$query = trim($query);
		if (0 !== strncasecmp($method, $query, strlen($method)))
		{
			throw new Exception('unmatch method \'' . $method . '\'');
		}
		return $this->write($query, $args);
	}

	/**
	 * DELETEクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	public function delete($query, $args)
	{
		$method = 'delete';
		$query = trim($query);
		if (0 !== strncasecmp($method, $query, strlen($method)))
		{
			throw new Exception('unmatch method \'' . $method . '\'');
		}
		return $this->write($query, $args);
	}

	/**
	 * クエリを実行し、成否を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	boolean	SQLの実行成否
	 */
	public function bool($query, $args)
	{
		$result = $this->connector->execute($query, $args);
		$pear = new PEAR;
		$error = $pear->isError($result);
		if ($result instanceof MDB2_Result_Common)
		{
			$result->free();
		}
		return !$error;
	}

	/**
	 * クエリを実行します。
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	class	結果
	 */
	public function execute($query, $args)
	{
		return $this->connector->execute($query, $args);
	}

	/**
	 * 最後に挿入された行のIDを取得します。
	 *
	 * @return	int	最後に挿入された行のID
	 */
	public function lastInsertID()
	{
		return $this->connector->lastInsertID();
	}

	/**
	 * トランザクションを開始します。
	 */
	public function beginTransaction()
	{
		$this->connector->beginTransaction();
	}

	/**
	 * トランザクションをコミットします。
	 */
	public function commit()
	{
		$this->connector->commit();
	}

	/**
	 * トランザクションをロールバックします。
	 */
	public function rollback()
	{
		$this->connector->rollback();
	}

	/**
	 * トランザクションが開始されているかを取得します。
	 *
	 * @return boolean true:開始されている、false:開始されていない
	 */
	public function isTransaction()
	{
		return $this->connector->isTransaction();
	}

	/**
	 * SELECT, SHOWいずれかのクエリを実行し、取得内容を指定されたモードでfetchして返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @param	$mode	FETCHMODE定数によるモード指定
	 * @return	array	取得内容の配列
	 */
	private function read($query, $args, $mode = self::FETCHMODE_ASSOC)
	{
		$query = trim($query);
		if (0 !== strncasecmp('SELECT', $query, strlen('SELECT')) && 0 !== strncasecmp('SHOW', $query, strlen('SHOW')))
		{
			throw new Exception('unmatch method \'read\'');
		}
		$result = $this->connector->execute($query, $args);
		$pear = new PEAR;
		if ($pear->isError($result))
		{
			throw new Exception($result->userinfo);
		}

		switch($mode)
		{
			case self::FETCHMODE_ASSOC:
				$mode = MDB2_FETCHMODE_ASSOC;
				break;
			case self::FETCHMODE_ORDERED:
				$mode = MDB2_FETCHMODE_ORDERED;
				break;
			default:
				throw new Exception('no such mode');
		}

		$data = $result->fetchAll($mode);
		$result->free();
		return $data;
	}

	/**
	 * INSERT, UPDATE, DELETEいずれかのクエリを実行し、更新行数を返す
	 *
	 * @param 	$query	SQLクエリ
	 * @param	$args	引数配列
	 * @return	int		影響を与えた行数を返す
	 */
	private function write($query, $args)
	{
		$query = trim($query);
		if (0 !== strncasecmp('DELETE', $query, strlen('DELETE')) && 0 !== strncasecmp('UPDATE', $query, strlen('UPDATE')) && 0 !== strncasecmp('INSERT', $query, strlen('INSERT')))
		{
			throw new Exception('unmatch method \'write\'');
		}
		return $this->connector->execute($query, $args);
	}

}
?>