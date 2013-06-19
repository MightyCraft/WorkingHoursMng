<?php

class Client
{
	var $instance_db;
	var $table="mst_client";

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();
	}

	/**
	 * クライアントデータを全て取得
	 *
	 * TODO: ブラッシュアップ
	 *
	 * @return array 全データ
	 */
	function getDataAll()
	{
		$res	= $this->instance_db->select('SELECT * FROM ' . $this->table . ' WHERE delete_flg = 0 ORDER BY name ASC',array());

		$ret=array();
		if (!empty($res))
		{
			foreach($res as $key => $value)
			{
				$ret[$value['id']]	= $value;
			}
		}

		return $ret;
	}

	/**
	 * クライアントIDよりデータを取得
	 *
	 * @param integer $id			クライアントID
	 * @param boolean $delete_flg	削除フラグ：trueなら削除済みも含む
	 * @return array 取得データ
	 */
	function getClientById($client_id,$delete_flg=false)
	{
		$where_columns = array();
		$where_columns['id'] = $client_id;
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}

		$params = array();
		$where = _makeWhereQuery($where_columns, $params);

		$sql = 'SELECT * FROM '. $this->table. ' '. $where;
		$res	= $this->instance_db->select($sql,$params);

		if (!empty($res))
		{
			return $res[0];
		}
		else
		{
			return $res;
		}
	}

	/**
	 * プロジェクトIDから顧客情報を取得
	 */
	function getClientByProject($project_id)
	{
		$sql="SELECT CM.* FROM mst_project as PM LEFT JOIN {$this->table} as CM ON (PM.client_id = CM.id AND CM.delete_flg = 0) WHERE PM.delete_flg = 0 AND PM.id = ?";
		$res = $this->instance_db->select($sql,array($project_id));

		if($res) return $res[0];
	}

	/**
	 * クライアント名よりデータを取得
	 *
	 * @param integer $name クライアント名
	 *
	 * @return array 取得データ
	 */
	function getClientByName($name)
	{
		$res = $this->instance_db->select('SELECT * FROM ' . $this->table . ' WHERE delete_flg = 0 AND name = ? ',array($name));
		return $res;
	}

	/**
	 * クライアント登録
	 *
	 * @param array $data
	 *
	 * @return array $res
	 */
	function insertClient($data)
	{
		$sql="INSERT INTO {$this->table} (name,memo,regist_date,update_date) VALUE (?,?,?,?)";

		$res = $this->instance_db->insert($sql,$data);

		$insert_id = $this->instance_db->lastInsertID();

		return array($res,$insert_id);
	}

	/**
	 * クライアント更新
	 *
	 * @param	integer		$id
	 * @param	array		$data
	 * @return	array		$res
	 */
	function updateClient($id,$data)
	{
		$param = array_merge($data,array($id));

		$sql="UPDATE {$this->table} SET name = ?, memo = ?, update_date = ? WHERE delete_flg = 0 AND id = ?";

		$res = $this->instance_db->update($sql,$param);

		return $res;
	}

	/**
	 * クライアント削除
	 *
	 * @param array $data
	 *
	 * @return array $res
	 */
	function deleteClient($data)
	{
		$sql="UPDATE {$this->table} SET delete_flg = 1 WHERE id = ?";
		$res = $this->instance_db->update($sql,$data);
		return $res;
	}

	/**
	 * クライアント情報取得ページング対応
	 *
	 * @param array $data
	 *
	 * @return array $res
	 */
	function getClientAllPager($offset,$limit,$search=NULL)
	{
		// キーワード検索
		$where = '';
		if (!empty($search))
		{
			$where = ' AND name LIKE ? ';
			$param = array('%'.$search.'%',$offset,$limit);
		}
		else
		{
			$param = array($offset,$limit);
		}

		$sql  = 'SELECT SQL_CALC_FOUND_ROWS * ';
		$sql .=	'  FROM '.$this->table;
		$sql .= ' WHERE delete_flg = 0 '.$where;
		$sql .= ' ORDER BY name ASC';
		$sql .= ' LIMIT ?, ? ';

		$data = $this->instance_db->select($sql,$param);

		$query_rows = "SELECT FOUND_ROWS()";
		$result_rows = $this->instance_db->select($query_rows,array());
		$all_num = $result_rows[0]['found_rows()'];

		return array($data,$all_num);
	}

}

?>