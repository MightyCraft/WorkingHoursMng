<?php

class ProjectTeam
{
	var $instance_db;
	var $table="trn_project_team";

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();
	}

	/**
	 * 指定メンバーの所属データを取得
	 *
	 * @param integer $member_id メンバーID
	 */
	function getDataByMemberId($member_id)
	{
		$sql	= "SELECT * FROM {$this->table} WHERE member_id = ?";
		$res	= $this->instance_db->select($sql, array($member_id));
		return $res;
	}

	/**
	 * 指定プロジェクトの所属データを取得
	 *
	 * @param integer $project_id プロジェクトID
	 */
	function getDataByProjectId($project_id)
	{
		$sql	= "SELECT * FROM {$this->table} WHERE project_id = ?";
		$res	= $this->instance_db->select($sql, array($project_id));
		return $res;
	}

	/**
	 * 所属データ登録
	 *
	 * @param	array	$data
	 * @return	array	$res
	 */
	function writeProjectTeam($data)
	{
		$column	= array(
			'member_id',
			'project_id',
			'regist_date',
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
	 * メンバーIDによる削除
	 */
	function deleteProjectTeamByMemberId($member_id)
	{
		$sql="DELETE FROM {$this->table} WHERE member_id=?";
		$res = $this->instance_db->delete($sql,array($member_id));

		return $res;
	}

}

?>