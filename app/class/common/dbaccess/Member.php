<?php

class Member
{
	var $instance_db;
	var $table="mst_member";

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();

	}

	/**
	 * 全てのデータを取得
	 *
	 * @param	string	$delete_flg		削除フラグ：trueの時削除も含む
	 * @param	string	$delete_sort	削除フラグでソートを行うか
	 * @return array 取得データ
	 */
	function getMemberAll($delete_flg=false, $delete_sort=false)
	{
		$sql = 'SELECT * FROM ' . $this->table;
		if (!$delete_flg)
		{
			// 削除済み(退職者)は含まない
			$sql .=  ' WHERE delete_flg = 0';
		}

		if ($delete_sort)
		{
			$sql .= ' ORDER BY delete_flg, id';
		}
		else
		{
			$sql .= ' ORDER BY id';
		}

		$result	= $this->instance_db->select($sql,array());

		$return = array();
		if( is_array($result) )
		{
			foreach ($result as $value)
			{
				$return[$value['id']]	= $value;
			}
		}

		return $return;
	}

	/**
	 * IDとパスワードでデータを取得
	 *
	 * TODO: ブラッシュアップ
	 *
	 * @param integer $id 社員ID
	 * @param string $password パスワード
	 * @return array 取得データ
	 */
	function getMemberByIdPassword($id,$password)
	{
		$res	= $this->instance_db->select('SELECT * FROM ' . $this->table . ' WHERE delete_flg = 0 AND id = ? AND password = ?',array($id,$password));
		return $res;
	}

	/**
	 * メンバーIDよりデータを取得
	 *
	 * @param	integer	$id			社員ID
	 * @param	string	$delete_flg	削除フラグ：trueの時削除も含む
	 * @return	array	取得データ
	 */
	function getMemberById($id,$delete_flg=false)
	{
		$where_columns['id'] = $id;
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}
		$params = array();
		$where = _makeWhereQuery($where_columns, $params);

		$sql = 'SELECT * FROM '. $this->table. ' '. $where. ' LIMIT 1';
		$res	= $this->instance_db->select($sql,$params);

		return (isset($res[0]) ? $res[0] : false);
	}

	/**
	 * メンバーコードよりデータを取得
	 *
	 * @param integer $member_code 社員コード
	 * @return array 取得データ
	 */
	function getMemberByCode($member_code)
	{
		$res = $this->instance_db->select('SELECT * FROM ' . $this->table . ' WHERE delete_flg = 0 AND member_code = ? LIMIT 1',array($member_code));

		return (isset($res[0]) ? $res[0] : false);
	}

	/**
	 * 指定された所属ID（複数指定可）よりデータを取得
	 *
	 * @param	integer/array	$post	所属ID（複数指定時はarrayで指定）
	 * @param	string	$delete_flg	削除フラグ：trueの時削除も含む
	 * @param	string	$delete_sort	削除フラグでソートを行うか
	 * @return	array	取得データ
	 */
	function getMemberByPost($post,$delete_flg=false,$delete_sort=false)
	{
		// 抽出する所属ID
		if (is_array($post))
		{
			// IDが複数指定の時
			foreach ($post as $post_id)
			{
				$tmp_values[] = "?";
				$params[] = $post_id;
			}
			$values = '('.implode(',', $tmp_values).')';
			$sql = "SELECT * FROM {$this->table} WHERE post IN {$values}";
		}
		else
		{
			$sql = "SELECT * FROM {$this->table} WHERE post = ?";
			$params = array($post);
		}

		// 削除済み(退職者)を含むか
		if (!$delete_flg)
		{

			$sql .=  ' AND delete_flg = 0';
		}
		// 削除済み(退職者)フラグでソートするか
		if ($delete_sort)
		{
			$sql .= ' ORDER BY delete_flg, id';
		}
		else
		{
			$sql .= ' ORDER BY id';
		}

		$res = $this->instance_db->select($sql,$params);

		return $res;
	}

	/**
	 * 指定された役職ID（複数指定可）よりデータを取得
	 *
	 * @param	integer/array	$position	役職ID（複数指定時はarrayで指定）
	 * @param	string	$delete_flg	削除フラグ：trueの時削除も含む
	 * @param	string	$delete_sort	削除フラグでソートを行うか
	 * @return	array	取得データ
	 */
	function getMemberByPosition($position, $delete_flg=false, $delete_sort=false)
	{
		// 抽出する役職ID
		if (is_array($position))
		{
			// IDが複数指定の時
			foreach ($position as $position_id)
			{
				$tmp_values[] = "?";
				$params[] = $position_id;
			}
			$values = '('.implode(',', $tmp_values).')';
			$sql = "SELECT * FROM {$this->table} WHERE position IN {$values}";
		}
		else
		{
			$sql = "SELECT * FROM {$this->table} WHERE position = ?";
			$params = array($position);
		}

		// 削除済み(退職者)を含むか
		if (!$delete_flg)
		{

			$sql .=  ' AND delete_flg = 0';
		}
		// 削除済み(退職者)フラグでソートするか
		if ($delete_sort)
		{
			$sql .= ' ORDER BY delete_flg, id';
		}
		else
		{
			$sql .= ' ORDER BY id';
		}

		$res = $this->instance_db->select($sql,$params);

		return $res;
	}

	/**
	 * 指定された社員タイプID（複数指定可）よりデータを取得
	 *
	 * @param	integer/array	：$member_type	社員タイプID（複数指定時はarrayで指定）
	 * @param	string			：$delete_flg	削除フラグ：trueの時削除も含む
	 * @param	string			：$delete_sort	削除フラグでソートを行うか
	 * @return	array			：取得データ
	 */
	function getMemberByMemberType($member_type, $delete_flg=false, $delete_sort=false)
	{
		// 抽出する社員タイプID
		if (is_array($member_type))
		{
			// IDが複数指定の時
			foreach ($member_type as $member_type_id)
			{
				$tmp_values[] = "?";
				$params[] = $member_type_id;
			}
			$values = '('.implode(',', $tmp_values).')';
			$sql = "SELECT * FROM {$this->table} WHERE mst_member_type_id IN {$values}";
		}
		else
		{
			$sql = "SELECT * FROM {$this->table} WHERE mst_member_type_id = ?";
			$params = array($member_type);
		}

		// 削除済み(退職者)を含むか
		if (!$delete_flg)
		{

			$sql .=  ' AND delete_flg = 0';
		}
		// 削除済み(退職者)フラグでソートするか
		if ($delete_sort)
		{
			$sql .= ' ORDER BY delete_flg, id';
		}
		else
		{
			$sql .= ' ORDER BY id';
		}

		$res = $this->instance_db->select($sql,$params);

		return $res;
	}

	/**
	 * 指定された社員コストID（複数指定可）よりデータを取得
	 *
	 * @param	integer/array	：$member_cost	社員コストID（複数指定時はarrayで指定）
	 * @param	string			：$delete_flg	削除フラグ：trueの時削除も含む
	 * @param	string			：$delete_sort	削除フラグでソートを行うか
	 * @return	array			：取得データ
	 */
	function getMemberByMemberCost($member_cost, $delete_flg=false, $delete_sort=false)
	{
		// 抽出する社員コストID
		if (is_array($member_cost))
		{
			// IDが複数指定の時
			foreach ($member_cost as $member_cost_id)
			{
				$tmp_values[] = "?";
				$params[] = $member_cost_id;
			}
			$values = '('.implode(',', $tmp_values).')';
			$sql = "SELECT * FROM {$this->table} WHERE mst_member_cost_id IN {$values}";
		}
		else
		{
			$sql = "SELECT * FROM {$this->table} WHERE mst_member_cost_id = ?";
			$params = array($member_cost);
		}

		// 削除済み(退職者)を含むか
		if (!$delete_flg)
		{

			$sql .=  ' AND delete_flg = 0';
		}
		// 削除済み(退職者)フラグでソートするか
		if ($delete_sort)
		{
			$sql .= ' ORDER BY delete_flg, id';
		}
		else
		{
			$sql .= ' ORDER BY id';
		}

		$res = $this->instance_db->select($sql,$params);

		return $res;
	}
	
	/**
	 * 指定された権限（複数指定可）よりデータを取得
	 *
	 * @param	integer/array	：$auth_lv	権限（複数指定時はarrayで指定）
	 * @param	string			：$delete_flg	削除フラグ：trueの時削除も含む
	 * @param	string			：$delete_sort	削除フラグでソートを行うか
	 * @return	array			：取得データ
	 */
	function getMemberByAuthLv($auth_lv, $delete_flg = false, $delete_sort = false)
	{
		// 抽出する社員タイプID
		if (is_array($auth_lv))
		{
			// IDが複数指定の時
			foreach ($auth_lv as $auth_lv_id)
			{
				$tmp_values[] = "?";
				$params[] = $auth_lv_id;
			}
			$values = '('.implode(',', $tmp_values).')';
			$sql = "SELECT * FROM {$this->table} WHERE auth_lv IN {$values}";
		}
		else
		{
			$sql = "SELECT * FROM {$this->table} WHERE auth_lv = ?";
			$params = array(
					$auth_lv
			);
		}

		// 削除済み(退職者)を含むか
		if (!$delete_flg)
		{

			$sql .= ' AND delete_flg = 0';
		}
		// 削除済み(退職者)フラグでソートするか
		if ($delete_sort)
		{
			$sql .= ' ORDER BY delete_flg, id';
		}
		else
		{
			$sql .= ' ORDER BY id';
		}

		$res = $this->instance_db->select($sql, $params);

		return $res;
	}
	
	/**
	 * ユーザー登録
	 *
	 * @param	array $regist_data	登録内容
	 * @return	array 登録結果
	 */
	function insertMember($regist_data)
	{
		$response=null;
		$insert_id=null;

		if (!empty($regist_data) && is_array($regist_data))
		{
			$regist_data['regist_date'] = date('Y-m-d H:i:s');
			$regist_data['update_date'] = $regist_data['regist_date'];

			$tmp_columns	= array();
			$tmp_values		= array();
			$params			= array();
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
	 * ユーザー更新(パラメータ指定)
	 *
	 * @param 	array	$id
	 * @param 	array 	$data
	 * @param	boolean	$delete_flg	trueの時は有効社員で無いと修正しない
	 * @return	array $res
	 */
	function updateMemberToParam($id, $data, $delete_flg = true)
	{
		// 更新日時
		$data['update_date']	= date('Y-m-d H:i:s');

		// SET
		$set	= array();
		$param	= array();
		if (is_array($data))
		{
			foreach($data as $key => $value)
			{
				$set[]	= "{$key} = ?";
				$param[]= $value;
			}
		}

		// WHERE
		$where = ' WHERE  id = ? ';
		$param[]	= $id;
		if ($delete_flg)
		{
			$where .= " AND delete_flg = 0 ";
		}

		$sql	="UPDATE {$this->table} SET " . implode(',', $set) . $where;
		$res	= $this->instance_db->update($sql,$param);

		return $res;
	}

	/**
	 * ユーザー削除
	 *
	 * @param array $data
	 *
	 * @return array $res
	 */
	function deleteMember($data, $update_date=null)
	{
		if (empty($update_date))
		{
			$update_date = date('Y-m-d H:i:s');
		}
		$params = array($update_date, $data[0]);
		$sql="UPDATE {$this->table} SET delete_flg = 1 , update_date = ? WHERE id = ? ";
		$res = $this->instance_db->update($sql, $params);
		return $res;
	}

	/**
	 * ユーザー情報取得ページング対応
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $column
	 * @param string $order
	 *
	 * @return array $res
	 */
	function getMemberAllPager($offset,$limit,$column='id',$order='ASC')
	{
		$sql	= "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table}"
		. " WHERE delete_flg = 0 ORDER BY {$column} {$order}"
		. " LIMIT {$offset},{$limit}";
		$data	= $this->instance_db->select($sql,array());

		$query_rows = "SELECT FOUND_ROWS()";
		$result_rows = $this->instance_db->select($query_rows,array());
			$all_num = $result_rows[0]['found_rows()'];

					return array($data,$all_num);
	}

	/**
	 * ユーザー情報取得ページング対応
	 * (Where句指定)
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $column
	 * @param string $order
	 * @param string $where_columns
	 * @param string $where_columns_keyword
	 *
	 * @return array $res
	 */
	function getMemberAllPagerByWhere($offset,$limit,$column='id',$order='ASC', $where_columns=array(), $where_columns_keyword=array())
	{
		$params = array();
		$where_keyword_array = array();

		// Where句生成
		$where = _makeWhereQuery($where_columns, $params);

		// キーワード検索 Where句生成
		foreach($where_columns_keyword as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}
			$where_keyword_array[] = $key. ' LIKE ?';
			$params[] = '%'.$value.'%';

		}
		if (!empty($where_keyword_array))
		{
			$where .= ' AND ';
			$where .= implode(' AND ', $where_keyword_array);
		}
		$sql	= "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table}"
				. " {$where} ORDER BY {$column} {$order}"
				. " LIMIT {$offset},{$limit}";
		$data	= $this->instance_db->select($sql,$params);

		$query_rows = "SELECT FOUND_ROWS()";
		$result_rows = $this->instance_db->select($query_rows,array());
		$all_num = $result_rows[0]['found_rows()'];

		return array($data,$all_num);
	}

	/**
	 * 登録されているかの確認
	 *
	 * @param string $member_code
	 *
	 * @return bool
	 */
	function isMember($member_code)
	{
		$member_all = $this->getMemberAll();

		$chk_flg = 0;
		foreach($member_all as $key=>$value)
		{
			if($member_all[$key]['member_code'] == $member_code)
			{
				$chk_flg = 1;
				break;
			}
		}
		return $chk_flg;
	}
}

?>