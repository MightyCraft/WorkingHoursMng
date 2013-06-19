<?php

class Project
{
	var $instance_db;
	var $table="mst_project";

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();
	}

	/**
	 * プロジェクトマスタの全データを取得
	 * （KEYにIDを使用/ID昇順)
	 *
	 * @param	boolean	$delete_flg				tureの時は削除済みも含む
	 * @param	boolean	$add_endunixtime_flg	案件終了日をUNIXTIMEに変換した値の付加指定
	 * @return	array	全プロジェクトデータ
	 */
	function getDataAll($delete_flg=false,$add_endunixtime_flg=false)
	{
		// where句
		$where_columns = array();
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}
		$params = array();
		$where = _makeWhereQuery($where_columns, $params);
		// orderby句
		$order =  ' ORDER BY id ASC';
		// SQL発行
		$sql	= 'SELECT * FROM '. $this->table. ' '. $where. $order;
		$res	= $this->instance_db->select($sql, $params);

		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['id']]	= $value;

			// 終了案件比較用情報追加
			if ($add_endunixtime_flg)
			{
				$end_unixtime	= null;
				if (!empty($value['end_date']))
				{
					$end_unixtime	= strtotime($value['end_date']);
				}
				$ret[$value['id']]['end_unixtime'] = $end_unixtime;
			}
		}
		return $ret;
	}

	/**
	 * プロジェクトマスタの全データをプロジェクト名順にソートして取得
	 *
	 * @param	boolean	$delete_flg				tureの時は削除済みも含む
	 * @return	array 全データ
	 */
	function getDataAllSortName($delete_flg=false)
	{
		// where句
		$where_columns = array();
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}
		$params = array();
		$where = _makeWhereQuery($where_columns, $params);
		// orderby句
		$order =  ' ORDER BY name ASC';
		// SQL発行
		$sql	= 'SELECT * FROM '. $this->table. ' '. $where. $order;
		$res	= $this->instance_db->select($sql, $params);

		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['id']]	= $value;
		}
		return $ret;
	}

	/**
	 * プロジェクトマスタの全データをプロジェクト名順にソートして取得（クライアント名も付加）
	 *
	 * @param	boolean	$delete_flg				tureの時は削除済みも含む
	 * @return	array 全データ
	 */
	function getDataAllAddClient($delete_flg=false)
	{
		// where句
		$where_columns = array();
		$where = array();
		$params = array();
		if (!$delete_flg)
		{
			$where = ' WHERE MP.delete_flg = ? ';
			$params[] = 0;
		}
		// orderby句
		$order =  ' ORDER BY MC.name ASC';
		// SQL発行
		$sql	= 'SELECT MP.* , MC.name AS cname';
		$sql	.= ' FROM '. $this->table. ' AS MP INNER JOIN mst_client AS MC ON MP.client_id = MC.id ';
		$sql	.= $where. $order;
		$res	= $this->instance_db->select($sql, $params);
	
		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['id']]	= $value;
		}
		return $ret;
	}
	
	
	/**
	 * 指定クライアントIDを持つデータを全て取得
	 *
	 * @param	int		$client_id
	 * 			array	$project_type：必要なタイプを配列で指定
	 * @return	array	該当データ
	 */
	function getDataByClientId($client_id,$arr_project_type=array())
	{
		$where	= array();
		$param	= array();

		$param[]	= $client_id;
		if (!empty($arr_project_type))
		{
			$tmp_where = array();
			foreach($arr_project_type as $project_type)
			{
				$tmp_where[]	= ' ? ';
				$param[]		= $project_type;
			}
			$where[]	= ' project_type IN ('. implode(' , ', $tmp_where) .')';
		}

		$sql  = 'SELECT * FROM ' . $this->table;
		$sql .= ' WHERE delete_flg = 0 AND client_id = ? ';
		$sql .= (!empty($where)  ?  ' AND '.implode(' AND ', $where)  :  '');
		$sql .= ' ORDER BY name ASC';
		$res	= $this->instance_db->select($sql,$param);
		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['id']]	= $value;
		}
		return $ret;
	}
	/**
	 * プロジェクトマスタのデータをページ指定で取得
	 *
	 * @param	int		$page	ページ番号
	 * @param	int		$limit	1ページあたりの項目数
	 * @param	int		&$total	全項目数
	 * @param	string	$range	取得範囲
	 * @param	string	$column	ソートKEY
	 * @param	string	$order	昇順/降順
	 * @param	array	$search_type	検索タイプ（１：所属プロジェクト、２：キーワード）
	 * @param	array	$search			検索条件（タイプ１：プロジェクトID、タイプ２：指定カラムの部分一致）
	 * @param	array	$search_column	検索条件（担当営業id、総予算フラグ、仮PJコードフラグ）
	 * @return	array	全データ
	 */
	function getDataByLimit($page, $limit, &$total, $range='all', $column='MP.id', $order='DESC', $search_type=0, $search=array(), $search_column=array())
	{
		$where	= array();
		$param	= array();

		//取得範囲確認
		$now_date = date("Y-m-d");
		switch((string)$range)
		{
			//継続案件（削除、廃止は対象外）
			case 'now':
				// 終了案件切り替え日になっていない
				$where[]	= '(MP.end_date IS NULL OR MP.end_date >= ?)';
				$param[]	= $now_date;
				// 対象プロジェクトタイプ
				break;
			//終了案件（削除、廃止は対象外）
			case 'end':
				$where[]	= 'MP.end_date < ?';
				$param[]	= $now_date;
				// 対象プロジェクトタイプ
				break;
			//全案件（削除、廃止は対象外）
			case 'all':
				// 対象プロジェクトタイプ
				break;
			//条件無し（削除は対象外）
			default:
				break;
		}

		// 検索条件
		if (($search_type == 1) && (!empty($search)))
		{
			// 所属プロジェクト検索
			foreach ($search as $value)
			{
				$arr_search[]	= '?';
				$param[]		= $value;
			}

			$where[] = 'MP.id IN ('. implode(',', $arr_search) .')';
		}
		elseif (($search_type == 2) && (!empty($search)))
		{
			// キーワード検索
			foreach($search as $key=>$value)
			{
				if (!empty($value))
				{
					$arr_search[] = $key. ' LIKE ?';
					$param[] = '%'.$value.'%';
				}
			}
			if (!empty($arr_search))
			{
				$where[] = '('.implode(' OR ', $arr_search).')';
			}
		}

		$sql_member_id = '';
		$sql_total_budget_chk = '';
		$sql_project_type_chk = '';
		$sql_delete_flg_chk = '';
		$search_column['member_id'] = isset($search_column['member_id']) ? $search_column['member_id'] : 0;
		$search_column['total_budget_chk'] = isset($search_column['total_budget_chk']) ? $search_column['total_budget_chk'] : 0;
		$search_column['project_type_chk'] = isset($search_column['project_type_chk']) ? $search_column['project_type_chk'] : 0;
		//担当営業が指定されていた場合
		if($search_column['member_id'] >= 1)
		{
			$sql_member_id = ' AND MP.member_id = ?';
			$param[] = $search_column['member_id'];
		}

		//総予算未設定が指定されていた場合
		if($search_column['total_budget_chk'] == 1)
		{
			$sql_total_budget_chk = " AND MP.total_budget < '1'";
		}
 		// プロジェクトタイプの指定がある場合
 		if(isset($search_column['project_type']))
 		{
 			$sql_project_type_chk = " AND MP.project_type ='".$search_column['project_type']."'";
 		}
 		// 削除フラグの指定がある場合
 		if(isset($search_column['delete_flg']))
 		{
 			$sql_delete_flg_chk = " AND MP.delete_flg = '".$search_column['delete_flg']."'";
 		}
 		
		$sql	= "SELECT SQL_CALC_FOUND_ROWS MP.*, MC.name as cname"
				. " FROM {$this->table} as MP"
				. " LEFT JOIN mst_client as MC ON (MC.delete_flg = 0 AND MP.client_id = MC.id)"
				. " WHERE 1=1 ".(!empty($where) ? ' AND '.implode(' AND ', $where) : '')
				. "{$sql_member_id}{$sql_total_budget_chk}{$sql_project_type_chk}{$sql_delete_flg_chk}"
				. " ORDER BY {$column} {$order}"
				. " LIMIT ?, ?";
		$limit_offset	= ($page - 1) * $limit;
		$param[]	= $limit_offset;
		$param[]	= $limit;
		$res		= $this->instance_db->select($sql, $param);

		//全項目数取得の為のエイリアスが渡されている場合
		$total_res	= $this->instance_db->select('SELECT FOUND_ROWS()', array());
		$total		= $total_res[0]['found_rows()'];
		return $res;
	}

	/**
	 * プロジェクトマスタの情報をID指定で取得する
	 *
	 * @param	integer	$project_id,：プロジェクトID
	 * @param	boolean	$delete_flg：true：削除フラグONを含む
	 * @return	array	マスタデータ1件
	 */
	function getDataById($project_id,$delete_flg=false)
	{
		$where_columns['id'] = $project_id;
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
	 * プロジェクトマスタの情報をID指定で取得する（複数対応）
	 *
	 * @param	array	$project_ids：プロジェクトIDorIDリスト
	 * @param	boolean	$delete_flg：true：削除フラグONを含む
	 * @return	array	マスタデータ複数件
	 */
	function getDataByIds($project_ids,$delete_flg=false)
	{
		$where_columns['id'] = $project_ids;
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}

		$params = array();
		$where = _makeWhereQuery($where_columns, $params);

		$sql = 'SELECT * FROM '. $this->table. ' '. $where;
		$res	= $this->instance_db->select($sql,$params);

		return $res;
	}

	/**
	 * プロジェクトタイプ指定で取得
	 *
	 * @param 	array	$project_type	抽出対象のタイプをarrayで指定
	 * @param	boolean	$delete_flg		tureの時は削除済みも含む
	 * @return	array	プロジェクトマスタデータ
	 */
	function getDataByType($project_type=array(),$delete_flg=false)
	{
		$where_columns['project_type'] = $project_type;
		if (!$delete_flg)
		{
			$where_columns['delete_flg'] = 0;
		}
		$params = array();
		$where = _makeWhereQuery($where_columns, $params);

		$sql = 'SELECT * FROM '. $this->table. ' '. $where;
		$res	= $this->instance_db->select($sql,$params);

		return $res;
	}

	/**
	 * 備考必須フラグを指定してプロジェクトを取得
	 *
	 * @param	boolean	$memo_flg		tureの時は必須のプロジェクトのみ、falseは必須じゃないプロジェクトのみ
	 * @return	array	プロジェクトマスタデータ
	 */
	function getDataByMemoflg($memo_flg=false)
	{
		$where_columns['memo_flg'] = 0;
		if (!$memo_flg)
		{
			$where_columns['memo_flg'] = 1;
		}
		$params = array();
		$where = _makeWhereQuery($where_columns, $params);

		$sql = 'SELECT * FROM '. $this->table. ' '. $where;
		$res	= $this->instance_db->select($sql,$params);

		return $res;
	}

	/**
	 * 削除されていないプロジェクト（delete_flg=0）のみ抽出
	 *
	 */
	function getAllProject()
	{
		$sql="SELECT PM.id as PID,PM.client_id as PCID,CM.id as CID ,PM.name as PNAME,CM.name as CNAME,PM.end_date, PM.project_code,PM.project_type"
			." FROM {$this->table} as PM LEFT JOIN mst_client as CM ON (PM.client_id=CM.id AND CM.delete_flg=0)"
			." WHERE PM.delete_flg = 0";
		$res=$this->instance_db->select($sql,array());
		//ソート
		foreach ($res as $key => $value){
			$cnameValues[$key] = $value['cname'];
		}

		array_multisort($cnameValues,SORT_ASC,$res,SORT_ASC);
		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['pid']]	= $value;
		}
		return $ret;
	}

	/**
	 * 案件終了日が設定/未設定のプロジェクトIDを抽出
	 * ※プロジェクトタイプ=2(後発作業用コード)は特殊コードなので除く
	 *
	 * @param	boolean $end_flg		true:設定されているのを抽出/false：未設定を抽出
	 * @param	boolean $delete_flg		true:削除ONも含む/false：削除ONは含まない
	 * @return	array	対象プロジェクトIDのリスト
	 */
	function getProjectIdBySettingEndDate($end_flg=false,$delete_flg=false)
	{
		$where		= ' WHERE `project_type` != ?';
		$params[]	= PROJECT_TYPE_BACK;
		if ($end_flg)
		{
			$where .= " AND `end_date` IS NOT NULL ";
		}
		else
		{
			$where .= " AND `end_date` IS NULL ";
		}
		if (!$delete_flg)
		{
			$where .= " AND `delete_flg` = ? ";
			$params[] = 0;
		}

		$sql	= 'SELECT * FROM '. $this->table. $where;
		$result	= $this->instance_db->select($sql, $params);

		$return = array();
		// IDリストに変換
		foreach($result as $value)
		{
			$return[]	= $value['id'];
		}

		return $return;
	}



	/**
	 * 基準日指定で終了していないプロジェクトを抽出
	 * ※削除プロジェクトは（delete_flg=1）は対象外
	 *
	 * @param	array	$arr_project_type：任意でプロジェクトタイプ指定可能
	 * @param	int		$view_year,$view_month,$view_day：終了案件判定に使用する日付
	 */
	function getNowProject($arr_project_type=array(),$view_year=null,$view_month=null,$view_day=null)
	{
		$where	= array();
		$param	= array();

		// 検索条件
		$param[]	= getCheckDate($view_year,$view_month,$view_day);	// 未指定or無効日は当日日付を取得

		if (!empty($arr_project_type))
		{
			$tmp_where = array();
			foreach($arr_project_type as $project_type)
			{
				$tmp_where[]	= ' ? ';
				$param[]		= $project_type;
			}
			$where[]	= ' project_type IN ('. implode(' , ', $tmp_where) .')';
		}

		// 指定日時点で有効なプロジェクトを取得
		$sql="SELECT PM.id as PID,PM.client_id as PCID,CM.id as CID ,PM.name as PNAME,CM.name as CNAME,PM.end_date, PM.project_code,PM.project_type"
			." FROM {$this->table} as PM LEFT JOIN mst_client as CM ON (PM.client_id=CM.id AND CM.delete_flg=0)"
			." WHERE PM.delete_flg = 0 AND (PM.end_date IS NULL OR PM.end_date >= ?)"
			.(!empty($where)  ?  ' AND '.implode(' AND ', $where)  :  '');
		$res=$this->instance_db->select($sql,$param);

		//ソート
		$cnameValues = array();
		foreach ($res as $key => $value){
			$cnameValues[$key] = $value['cname'];
		}
		array_multisort($cnameValues,SORT_ASC,$res,SORT_ASC);
		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['pid']]	= $value;
		}
		return $ret;
	}

	/**
	 * 基準日指定で終了しているプロジェクトを抽出
	 * ※削除プロジェクトは（delete_flg=1）は対象外
	 *
	 * @param	array	$arr_project_type：任意でプロジェクトタイプ指定可能
	 * @param	int		$view_year,$view_month,$view_day：終了案件判定に使用する日付
	 */
	function getEndProject($arr_project_type=array(),$view_year=null,$view_month=null,$view_day=null)
	{
		// 検索条件
		$now_date	= getCheckDate($view_year,$view_month,$view_day);	// 未指定or無効日は当日日付を取得
		$param[]	= $now_date;
		if (!empty($arr_project_type))
		{
			$tmp_where = array();
			foreach($arr_project_type as $project_type)
			{
				$tmp_where[]	= ' ? ';
				$param[]		= $project_type;
			}
			$where[]	= ' project_type IN ('. implode(' , ', $tmp_where) .')';
		}

		$sql="SELECT PM.id as PID,PM.client_id as PCID,CM.id as CID ,PM.name as PNAME,CM.name as CNAME,PM.end_date, PM.project_code,PM.project_type"
			." FROM {$this->table} as PM LEFT JOIN mst_client as CM ON (PM.client_id=CM.id AND CM.delete_flg=0)"
			." WHERE PM.delete_flg = 0 AND end_date < ?"
			.(!empty($where)  ?  ' AND '.implode(' AND ', $where)  :  '');

		$res=$this->instance_db->select($sql,$param);

		//ソート
		$cnameValues = array();
		foreach ($res as $key => $value){
			$cnameValues[$key] = $value['cname'];
		}
		array_multisort($cnameValues,SORT_ASC,$res,SORT_ASC);
		$ret=array();
		foreach($res as $key => $value)
		{
			$ret[$value['pid']]	= $value;
		}
		return $ret;
	}

	/**
	 * プロジェクトマスタのデータをプロジェクトコード指定で取得
	 *
	 * @param integer	$project_code	プロジェクトコード
	 * @param boolean	$b_non_delete	削除済みデータ参照確認
	 * @return array データ
	 */
	function getProjectByCode($project_code, $b_non_delete = true)
	{
		//削除済みデータにも参照するか確認
		$where_delete_flg	= '';
		if($b_non_delete) {
			$where_delete_flg	= 'delete_flg = 0 AND';
		}
		$res = $this->instance_db->select('SELECT * FROM '.$this->table.' WHERE '.$where_delete_flg.' project_code = ?',array($project_code));

		return $res;
	}

	/**
	 * プロジェクトマスタのデータを名前指定で取得
	 *
	 * @param integer	$name			プロジェクト名
	 * @param integer	$client_id		クライアントID(falseの場合、指定無し)
	 * @param boolean	$b_non_delete	削除済みデータ参照確認
	 * @return array データ
	 */
	function getProjectByName($name, $client_id = false, $b_non_delete = true)
	{
		$where	= array();
		$param	= array();

		//削除済みデータにも参照するか確認
		if($b_non_delete) {
			$where[]	= 'delete_flg = 0';
		}

		//クライアントID
		if(($client_id !== false) && is_numeric($client_id)) {
			$where[]	= 'client_id = ?';
			$param[]	= $client_id;
		}

		//名前
		$where[]	= 'name = ?';
		$param[]	= $name;

		$res = $this->instance_db->select('SELECT * FROM '.$this->table.' WHERE '.implode(' AND ', $where), $param);

		return $res;
	}

	/**
	 * プロジェクト登録
	 *
	 * @param array $data
	 *
	 * @return array $res
	 */
	function insertProject($data)
	{
		$column	= array(
			'project_code',
			'name',
			'client_id',
			'project_type',
			'total_budget_manhour',
			'total_budget',
			'project_start_date',
			'project_end_date',
			'end_date',
			'member_id',
			'nouki',
			'memo_flg',
			'memo',
			'regist_date',
			'update_date',
		);
		$values = array();
		foreach($column as $value)
		{
			$values[]	= "?";
		}
		$sql="INSERT INTO {$this->table} (".implode(',', $column).") VALUE (".implode(',', $values).")";

		$res		= $this->instance_db->insert($sql,$data);
		$insert_id	= $this->instance_db->lastInsertID();
		return array($res,$insert_id);
	}

	/**
	 * プロジェクト更新
	 *
	 * @param	integer	$id
	 * @param	array	$data
	 * @return array $res
	 */
	function updateProject($id, $data)
	{
		$column	= array(
			'project_code',
			'name',
			'client_id',
			'project_type',
			'total_budget_manhour',
			'total_budget',
			'project_start_date',
			'project_end_date',
			'end_date',
			'member_id',
			'nouki',
			'memo_flg',
			'memo',
			'update_date'
		);
		foreach($column as $key => $value)
		{
			$column[$key]	= "{$value} = ?";
		}
		$sql="UPDATE {$this->table} SET ".implode(',', $column)." WHERE delete_flg = 0 AND id = ?";

		$param = array_merge($data,array($id));

		$res = $this->instance_db->update($sql,$param);

		return $res;
	}

	/**
	 * プロジェクト削除
	 *
	 * @param array $data
	 *
	 * @return array $res
	 */
	function deleteProject($data)
	{
		$sql="UPDATE {$this->table} SET delete_flg = 1 WHERE id = ?";
		$res = $this->instance_db->update($sql,$data);
		return $res;
	}

	/**
	 * ランダムなPJコードを生成
	 *
	 *	@return	string	ランダムなPJコード
	 */
	function createRandomPJCode()
	{
		// PJコードの長さ
		$nLength = USER_PROJECT_CODE_MAX;

		//ランダム準備
		mt_srand();
		$sCharList			= USER_PROJECT_CODE_AUTO_CREATE;
		$nCharListLength	= strlen($sCharList);


		$res			= '';	// 戻り値用
		$count			= 1;	// 生成回数
		$loop_limit_flg	= false;	// 一定回数以上の生成しなおしを制限
		do {
			$res	= '';
			for($i = 0; $i < $nLength; $i++) {
				$res .= $sCharList[mt_rand(0, $nCharListLength - 1)];
			}

			// 他に存在したPJコードでないか確認
			$other	= $this->instance_db->select("SELECT * FROM {$this->table} WHERE project_code = ?", array($res));

			// 無限ループ禁止
			if (!empty($other) && $count > PROJECT_CODE_AUTO_LIMIT)
			{
				// 重複有でループ回数が限界を超えていた場合は生成失敗にする
				$loop_limit_flg = true;
			}
			$count++;

		} while(!empty($other) && !$loop_limit_flg);	// 重複有ANDループ回数の限界を超えてないの時のみ再度生成しなおし

		return array($loop_limit_flg,$res);

	}

}

?>