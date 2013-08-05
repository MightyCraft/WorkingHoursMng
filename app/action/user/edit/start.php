<?php
/**
 * 「アカウント管理」編集開始
 */
class _user_edit_start extends UserScene
{
	//編集対象のメンバーID
	var	$_id;

	//初期チェック
	function check()
	{
		//IDが指定されてなければ、アカウント一覧へ
		if(empty($this->_id))
		{
			MCWEB_Util::redirectAction("/user/index");
		}

		//権限チェック
		$this->checkAuthEditMyAccount($this->_id);

		$_SESSION['manhour']['tmp']['user_edit']				= array();
		$_SESSION['manhour']['tmp']['user_project_team_list']	= array();
	}

	//処理
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		//編集対象のメンバーのデータを取得
		$member_by_id	= $this->obj_member->getMemberById($this->_id);
		if(empty($member_by_id))
		{
			MCWEB_Util::redirectAction("/user/index");
		}

		$_SESSION['manhour']['tmp']['user_edit']['id']					= $member_by_id['id'];
		$_SESSION['manhour']['tmp']['user_edit']['member_code']			= $member_by_id['member_code'];
		$_SESSION['manhour']['tmp']['user_edit']['name']				= $member_by_id['name'];
		$_SESSION['manhour']['tmp']['user_edit']['auth_lv']				= $member_by_id['auth_lv'];
		$_SESSION['manhour']['tmp']['user_edit']['post']				= $member_by_id['post'];
		$_SESSION['manhour']['tmp']['user_edit']['position']			= $member_by_id['position'];
		$_SESSION['manhour']['tmp']['user_edit']['mst_member_type_id']	= $member_by_id['mst_member_type_id'];
		$_SESSION['manhour']['tmp']['user_edit']['mst_member_cost_id']	= $member_by_id['mst_member_cost_id'];
		$_SESSION['manhour']['tmp']['user_edit']['password']			= $member_by_id['password'];

		//所属プロジェクトのセッションデータをセット
		$this->setProjectTeamSessionByMemberId($this->_id);

		MCWEB_Util::redirectAction("/user/edit/index");
	}
}

?>