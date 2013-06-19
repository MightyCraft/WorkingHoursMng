<?php
/**
 * 「アカウント管理」
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_edit_complete extends UserScene
{
	var $_id;
	var $_password_change;
	var $_length;
	
	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
		);
		if(!empty($errors))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}

		//権限チェック
		$this->checkAuthEditOtherAccount($this->_id);
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_member		= new Member;

		$member_by_id	= $obj_member->getMemberById($this->_id);
		
		$array_auth_lv	= returnArrayAuthLv();
		
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		
		$obj_position = new Position();
		$array_position	= $obj_position->getDataAll();

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('password_change',	$this->_password_change);
		$access->text('member_by_id',	$member_by_id);
		$access->text('array_auth_lv',	$array_auth_lv);
		$access->text('array_post',		$array_post);
		$access->text('array_position',	$array_position);

		//所属リスト表示処理
		$this->setProjectTeamViewListByProjectTeamTable($this->_id);
	}
}

?>