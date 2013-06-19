<?php

class Position
{
	var $instance_db;
	var $table="mst_position";

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();
	}


	/**
	 * 役職マスタのデータを全て取得
	 *
	 * @param	boolean	$delete_flg	trueで削除済みも含む
	 * @return	array	IDをKEYにセットした状態の全データ
	 */
	function getDataAll($delete_flg=false)
	{
		$where_columns	= array();
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}
		
		$params = array();
		$where = _makeWhereQuery($where_columns, $params);

		$sql	= 'SELECT * FROM '. $this->table. ' '. $where;
		$result	= $this->instance_db->select($sql, $params);

		$return	= array();
		foreach($result as $key => $value)
		{
			$return[$value['id']] = $value;
		}
		return $return;
	}

	/**
	 * 役職マスタのデータをID指定で取得
	 *
	 * @param	integer	$post_id
	 * @param	boolean	$delete_flg
	 * @return array 全データ
	 */
	function getDataById($post_id,$delete_flg=false)
	{
		$return = array();
		if (!empty($post_id))
		{
			$where_columns['id'] = (int)$post_id;

			$params = array();
			$where = _makeWhereQuery($where_columns, $params);


			$sql	= 'SELECT * FROM '. $this->table. ' '. $where;
			$result	= $this->instance_db->select($sql, $params);
			if (!empty($result))
			{
				$return = $result[0];
			}
		}

		return $return;
	}

	/**
	 * 役職データ登録
	 *
	 * @param	array $regist_data	登録内容
	 * @return	array 登録結果
	 */
	function insertPosition($regist_data)
	{
		$response=null;
		$insert_id=null;

		if (!empty($regist_data) && is_array($regist_data))
		{
			$regist_data['regist_date'] = date('Y-m-d H:i:s');
			$regist_data['update_date'] = date('Y-m-d H:i:s');

			$params = array();
			foreach ($regist_data as $key => $value)
			{
				$tmp_columns[]	= '`'.$key.'`';			// 登録カラム
				$tmp_values[]	= "?";					//
				$params[]		= $value;				// パラメータ
			}
			$columns	= '('.implode(',', $tmp_columns).')';
			$values		= '('.implode(',', $tmp_values).')';

			// insert処理
			$sql="INSERT INTO {$this->table} {$columns} VALUE {$values}";
			$response	= $this->instance_db->insert($sql,$params);
			$insert_id	= $this->instance_db->lastInsertID();
		}

		return array($response,$insert_id);
	}

	/**
	 * 役職データ更新
	 *
	 * @param	integer	更新する役職ID
	 * @param	array	更新内容
	 * @return	array	更新結果
	 */
	function updatePosition($id,$update_columns)
	{
		$update_columns['update_date'] = date('Y-m-d H:i:s');
		$where_columns = array(
			'id'		=> (int)$id,
		);

		// set句生成
		$update_params = array();
		$set = _makeUpdateSetQuery($update_columns,$update_params);
		// where句生成
		$where_params = array();
		$where = _makeWhereQuery($where_columns, $where_params);

		$params = array();
		$params	= array_merge($update_params,$where_params);

		// update処理
		$sql	= "UPDATE {$this->table} SET {$set} {$where}";
		$response	= $this->instance_db->update($sql,$params);

		return $response;
	}

}
?>