<?php
/**
 * 「アカウント管理」 編集完了画面
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
class _user_edit_complete extends UserScene
{
	var $_id;
	var $_password_change;

	// 画面
	var $member_by_id;
	var $array_auth_lv;
	var $array_post;
	var $array_position;
	var $array_member_type;
	var $array_member_cost;
	var $password_change;

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
		$this->checkAuthEditMyAccount($this->_id);
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

		// 登録情報
		$obj_member		= new Member;
		$this->member_by_id	= $obj_member->getMemberById($this->_id);

		// 権限
		$this->array_auth_lv	= returnArrayAuthLv();
		// 所属マスタ
		$obj_post			= new Post();
		$this->array_post	= $obj_post->getDataAll();
		// 役職マスタ
		$obj_position 			= new Position();
		$this->array_position	= $obj_position->getDataAll();
		// 社員タイプマスタ
		$obj_member_type			= new MemberType();
		$this->array_member_type	= $obj_member_type->getDataAll();
		// 社員コストマスタ
		$obj_member_cost			= new MemberCost();
		$this->array_member_cost	= $obj_member_cost->getDataAll();

		$this->password_change = $this->_password_change;

		//所属リスト表示処理
		$this->setProjectTeamViewListByProjectTeamTable($this->_id);
	}
}

?>