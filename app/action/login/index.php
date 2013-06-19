<?php
require_once(DIR_APP . '/class/common/Login.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _login_index extends GetScene
{
	/**
	 * @access public
	 * @var integer ユーザーID
	 */
	var $_member_id;

	/**
	 * @access public
	 * @var string エラーコード
	 */
	var $_error = '';

	/**
	 * @access public
	 * @var integer COOKIEのユーザーID
	 */
	var $_cookie_manhour_member_id_login;

	/**
	 * @access public
	 * @var array 社員一覧
	 */
	var $member_list;

	function check()
	{
		if(empty($this->_member_id) && !empty($this->_cookie_manhour_member_id_login))
		{
			$this->_member_id	= $this->_cookie_manhour_member_id_login;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_Member = new Member;

		// メンバーリスト取得（削除済社員は含まない）
		$this->member_list	=$obj_Member->getMemberAll();

		// セッション破棄
		if( isset($_SESSION['member_id']) )
		{
			unset($_SESSION['member_id']);
		}
	}
}

?>