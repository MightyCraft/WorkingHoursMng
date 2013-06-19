<?php
/**
 * 「アカウント管理」
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_new_complete extends UserScene
{
	var $_insert_id;

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'insert_id', ValidatorInt::createInstance()
		);

		if(!empty($errors))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_member		= new Member;

		$member_by_id	= $obj_member->getMemberById($this->_insert_id);

		$array_auth_lv	= returnArrayAuthLv();
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();

		$password_tmp	= changePassWord($password_tmp = changePassWord(sprintf('%'.USER_MEMBER_PASSWORD_MAX.'s', 'a')));

		$obj_position = new Position();
		$array_position	= $obj_position->getDataAll();
		
		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('member_by_id',	$member_by_id);
		$access->text('password_tmp',	$password_tmp);
		$access->text('array_auth_lv',	$array_auth_lv);
		$access->text('array_post',		$array_post);
		$access->text('array_position',	$array_position);

		//所属リスト表示処理
		$this->setProjectTeamViewListByProjectTeamTable($this->_insert_id);
	}
}

?>