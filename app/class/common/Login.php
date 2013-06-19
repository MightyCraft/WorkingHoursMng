<?php
/**
 * ログイン画面用クラス群
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class Login
{
	/**
	 * 社員IDとパスワードが正しいか判定
	 *
	 * TODO: ブラッシュアップ
	 *
	 * @param integer $id 社員ID
	 * @param string $password パスワード
	 * @return bool
	 */
	public function checkMemberIdPassword($id,$password)
	{
		$obj_db_member	= new Member();
		$data	= $obj_db_member->getMemberByIdPassword($id,$password);

		// 非認証
		if( empty($data) )
		{
			return false;
		}

		return true;
	}
}