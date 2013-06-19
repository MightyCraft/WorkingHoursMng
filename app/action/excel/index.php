<?php
/**
 *	エクセルダウンロード画面
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _excel_index extends GetScene
{
	// エラーメッセージ
	var $_error;

	var $_member_id;
	var $_post_id;
	var $_date_Year;
	var $_date_Month;

	var $member_list;	// 社員リスト
	var $post_list;		// 所属リスト

	function check()
	{
		// 表示メンバの初期値セット
		if (empty($this->_member_id))
		{
			$this->_member_id = $_SESSION['member_id'];
			$this->_post_id   = 0;
		}
		// 表示年月の初期値セット
		if ( (empty($this->_date_Year)) || (empty($this->_date_Month)) )
		{
			$this->_date_Year	= date('Y');
			$this->_date_Month	= date('m');
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

		// エクセル権限チェック
		$auth_excel	= checkAuthExcel($_SESSION['manhour']['member']['auth_lv'], $_SESSION['manhour']['member']['post']);
		if($auth_excel)
		{
			// 権限OKの場合、社員プルダウン表示（自分以外も出力可能）
			$obj_member			= new Member();
			$this->member_list	= $obj_member->getMemberAll(true,true);	// 削除社員含む、削除フラグでソートする

			$obj_post			= new Post();
			$this->post_list	= $obj_post->getDataAll();
		}

		// 年月プルダウンの表示設定
		$access->text('set_time', $this->_date_Year.'-'.$this->_date_Month.'-01');
	}
}

?>