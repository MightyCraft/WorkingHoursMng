<?php
/**
 * DB比較用のクラス定義ファイル
 *
 * ※まずないとは思いますが、
 * 　SHOW TABLES FROM ～
 * 　SHOW CREATE TABLE ～
 * 　上記構文の出力内容がMySQL仕様的に変更された場合、
 * 　正常に動かない可能性があります。
 */

require_once('MDB2.php');

/**
 * DB比較用のクラス
 *
 * @package	db
 * @author	takaoka
 * @since	2010/09/03
 */
class DatabaseCompare
{
	protected $db_infos;	//DB情報格納
	protected $dbs;			//DBオブジェクト格納
	
	protected $ignore_strings;	//無視テーブル名称判定
	protected $devide_strings;	//分割テーブル名称判定
	
	protected $mismatch_tables;			//不一致テーブル
	protected $mismatch_devide_tables;	//不一致テーブル(分割テーブル)

	/**
	 * コンストラクタ
	 *
	 * @param array			$db_infos		個々の$db_infoは'username','password','server','default'で構成
	 */
	protected function __construct($db_infos)
	{
		$this->dbs = array();
		$this->db_infos = $db_infos;
		foreach($this->db_infos as $db_info)
		{
			$db = new MDB2;
			$db = $db->factory('mysql://'.$db_info['username'].':'.$db_info['password'].'@'.$db_info['server'].'/'.$db_info['default'].'?charset=utf8');

			$pear = new PEAR;
			if ($pear->isError($db))
			{
				throw new Exception("create DB error");
			}
			$this->dbs[] = $db;
		}
		
		$this->ignore_strings = array();
		$this->devide_strings = array();
		$this->mismatch_tables = array();
		$this->mismatch_devide_tables = array();
	}
	
	/**
	 * インスタンスを生成します
	 *
	 * @param array			$db_infos		個々の$db_infoは'username','password','server','default'で構成
	 * @return object		インスタンス
	 */
	static public function createInstance($db_infos)
	{
		try
		{
			return new self($db_infos);
		}
		catch(Exception $e)
		{
			return NULL;
		}
	}

	/**
	 * 無視テーブル条件の配列をセットします
	 *
	 * @param array			$ignore_strings		無視テーブル条件配列（正規表現で条件を記述）
	 */
	public function setIgnoreStrings($ignore_strings)
	{
		if(!isset($ignore_strings) || !is_array($ignore_strings))
		{
			$this->setError("setIgnoreStrings params error!!");
		}
		$this->ignore_strings = $ignore_strings;
	}

	/**
	 * 分割テーブル条件の配列をセットします
	 *
	 * @param array			$devide_strings		分割テーブル条件配列（正規表現で条件を記述）
	 */
	public function setDevideStrings($devide_strings)
	{
		if(!isset($devide_strings) || !is_array($devide_strings))
		{
			$this->setError("setDevideStrings params error!!");
		}
		$this->devide_strings = $devide_strings;
	}

	/**
	 * 比較用のテーブルクリエイト取得
	 *
	 * @param integer			$index		
	 * @return テーブルクリエイト文配列
	 */
	protected function getTableCreate($index)
	{
		$ret = array();
		
		$tables = $this->select($index, 'SHOW TABLES FROM '.$this->db_infos[$index]['default'], array());
		foreach($tables as $table)
		{
			$table_name = $table['tables_in_'.$this->db_infos[$index]['default']];
			
			//無視テーブル名称判定
			$ignore_flg = FALSE;
			foreach($this->ignore_strings as $ignore_string)
			{
				if(preg_match($ignore_string, $table_name))
				{
					$ignore_flg = TRUE;
					break;
				}
			}
			if($ignore_flg) continue;
			
			//CREATE文の取得
			$table_create = $this->select($index, 'SHOW CREATE TABLE '.$table_name, array());
			$create = $table_create[0]['create table'];
			
			//AUTO_INCREMENT=xは決め打ちでCREATE文から削除
			$create = preg_replace("/[\s]*AUTO\_INCREMENT[\s]*=[\s]*[0-9]+[\s]*/", " ", $create);
			
			//分割テーブル名称判定
			$devide_name = NULL;
			foreach($this->devide_strings as $devide_string)
			{
				if(preg_match($devide_string, $table_name))
				{
					//分割テーブル名称の判定箇所を空文字列に置き換え
					$devide_name = preg_replace($devide_string, "", $table_name);
					$create = preg_replace("/^CREATE TABLE `{$table_name}`/", "CREATE TABLE `{$devide_name}`", $create);
					$break;
				}
			}
			
			$name = $table_name;
			if(isset($devide_name))
			{
				$name = $devide_name;
			}
			
			if(!isset($ret[$name]))
			{
				//初出のテーブル
				$ret[$name] = $create;
			}
			else
			{
				//初出ではない＝分割テーブルチェック実施
				if(0 != strcmp($ret[$name], $create))
				{
					if(!isset($this->mismatch_devide_tables[$index][$name]['(base)']))
					{
						$this->mismatch_devide_tables[$index][$name]['(base)'] = $ret[$name];
					}
					$this->mismatch_devide_tables[$index][$name][$table_name] = $create;
				}
			}
		}
		return $ret;
	}

	/**
	 * SELECTクエリの実行
	 *
	 * @param integer		$index		
	 * @param string		$query		SQLクエリ
	 * @param array			$arg		'?'に置き換わる値リスト
	 * @return 取得した値
	 */
	protected function select($index,$query,$arg)
	{
		if(preg_match("/^SELECT/",$query) == 0)
		{
			if(preg_match("/^SHOW/",$query) == 0)
			{
				throw new Exception("unmatch method. call 'SELECT'");
			}
		}
		$rs = $this->execute($index,$query,$arg);
		$pear = new PEAR;
		if($pear->isError($rs))
		{
			$errmes	= '[execQuery]'.$query.'('.print_r($arg,true).')' ;
			throw new Exception("bad query.".$errmes);
		}

		$data = array();
		while($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC))
		{
		    array_push($data, $row);
		}
		return $data;
	}

	/**
	 * クエリの実行
	 *
	 * @param integer		$index		
	 * @param string		$query		SQLクエリ
	 * @param array			$arg		'?'に置き換わる値リスト
	 * @return 実行結果のオブジェクト
	 */
	protected function execute($index,$query,$arg)
	{
		$stmt = $this->dbs[$index]->prepare($query);
		$pear = new PEAR;
		if($pear->isError($stmt))
		{
			$errmes	= '[execQuery]'.$query.'('.print_r($arg,true).')' ;
			throw new Exception("bad query.".$errmes);
		}
		$result = $stmt->execute($arg);
		$stmt->free();
		return $result;
	}

	/**
	 * エラーメッセージ設定
	 *
	 * @param string		$msg		メッセージ
	 */
	protected function setError($msg)
	{
		throw new Exception("set error '".$msg."'");
	}
	
	/**
	 * テーブルチェック実施関数
	 *
	 * @param integer		$index1
	 * @param integer		$index2
	 * @return int			0:一致 1:不一致
	 */
	public function compareTableCreate($index1, $index2)
	{
		// チェック結果格納先の初期化
		$this->mismatch_tables = array();
		$this->mismatch_devide_tables = array();
		
		$table_create1 = $this->getTableCreate($index1);
		$table_create2 = $this->getTableCreate($index2);
		
		if(0 < count($this->mismatch_devide_tables))
		{
			// そもそも分割テーブル不正
			return 1;
		}
		
		$keys = array_keys($table_create1);
		for($i = 0; $i < count($keys); $i++)
		{
			$ki = $keys[$i];
			if(!isset($table_create2[$ki]))
			{
				// 比較先に同名テーブルが存在しない
				continue;
			}
			$tc1 = $table_create1[$ki];
			$tc2 = $table_create2[$ki];
			
			if(0 != strcmp($tc1, $tc2))
			{
				// 不一致テーブル
				$this->mismatch_tables[$ki] = array();
				$this->mismatch_tables[$ki][$index1] = $tc1;
				$this->mismatch_tables[$ki][$index2] = $tc2;
			}
			unset($table_create1[$ki]);
			unset($table_create2[$ki]);
		}
		
		// 比較元にしかないテーブル
		foreach($table_create1 as $key => $value)
		{
			$this->mismatch_tables[$key] = array();
			$this->mismatch_tables[$key][$index1] = $value;
			$this->mismatch_tables[$key][$index2] = NULL;
		}
		
		// 比較先にしかないテーブル
		foreach($table_create2 as $key => $value)
		{
			$this->mismatch_tables[$key] = array();
			$this->mismatch_tables[$key][$index1] = NULL;
			$this->mismatch_tables[$key][$index2] = $value;
		}
		
		// 不一致テーブル数で比較結果判定
		if(0 < count($this->mismatch_tables))
		{
			return 1;
		}
		return 0;
	}
	
	/**
	 * 直前のチェック結果文字列を取得します。
	 *
	 * @param boolean		$detail	詳細を表示するかどうか
	 * @return string 直前のチェック結果
	 */
	public function getCompareResults($detail)
	{
		$ret = "";
		
		if(0 < count($this->mismatch_devide_tables))
		{
			$ret .= "DEVIDE TABLE MISMATCHED!!\n";
			foreach($this->mismatch_devide_tables as $index => $mismatch_devide_table)
			{
				$db_info = $this->db_infos[$index];
				$ret .= "({$db_info['server']}/{$db_info['default']})\n";
				foreach($mismatch_devide_table as $name => $tables)
				{
					$ret .= "devide_base_name : {$name}\n";
					foreach($tables as $table_name => $create)
					{
						if($detail)
						{
							$ret .= "[{$table_name}]\n";
							$ret .= "================================\n";
							$ret .= "{$create}\n";
							$ret .= "================================\n";
						}
						else
						{
							if(0 != strcmp('(base)', $table_name))
							{
								$ret .= "[{$table_name}]\n";
							}
						}
					}
				}
			}
		}
		
		if(0 < count($this->mismatch_tables))
		{
			$ret .= "TABLE MISMATCHED!!\n";
			foreach($this->mismatch_tables as $table_name => $mismatch_table)
			{
				$db_strs = array();
				$count = 0;
				$exist_index = -1;
				$not_exist_index = -1;
				
				foreach($mismatch_table as $index => $create)
				{
					$db_info = $this->db_infos[$index];
					$db_strs[$index] .= "({$db_info['server']}/{$db_info['default']})";
					if(isset($create))
					{
						$exist_index = $index;
						$count++;
					}
					else
					{
						$not_exist_index = $index;
					}
				}
				
				if(2 == $count)
				{
					// 両方存在して不一致
					$ret .= "[{$table_name}] mismatched!!\n";
					if($detail)
					{
						foreach($mismatch_table as $index => $create)
						{
							$ret .= "================================\n";
							$ret .= "{$db_strs[$index]}\n";
							$ret .= "{$create}\n";
						}
						$ret .= "================================\n";
					}
				}
				else if(1 == $count)
				{
					// 片方のみ存在
					$ret .= "[{$table_name}] not exist!! {$db_strs[$not_exist_index]}\n";
					if($detail)
					{
						$ret .= "================================\n";
						$ret .= "{$db_strs[$exist_index]}\n";
						$ret .= "{$mismatch_table[$exist_index]}\n";
						$ret .= "================================\n";
					}
				}
				
			}
		}
		
		return $ret;
	}
	
}
?>