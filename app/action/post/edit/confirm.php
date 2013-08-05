<?php
/**
 *	部署マスタ管理　新規/修正確認画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
class _post_edit_confirm extends PostScene
{
	// パラメータ
	var	$_id;				// 部署ID
	var	$_type;				// 部署タイプ
	var	$_name;				// 部署名
	var	$_delete_flg;		// 削除フラグ

	var $post_type;			// 部署タイプ
	var $new_flg=false;		// 新規登録フラグ
	var $member_flg=false;	// 削除時有効社員フラグ

	function check()
	{
		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',				ValidatorInt::createInstance()->nullable()->min(0)
			, 'type',			ValidatorString::createInstance()
			, 'name',			ValidatorString::createInstance()->min(1)->max(USER_POST_NAME_MAX)
			, 'delete_flg',		ValidatorInt::createInstance()->nullable()->min(1)->max(1)
		);

		$this->post_type = returnArrayPostType();
		if (!empty($this->_type))
		{
			if (empty($this->post_type[$this->_type]))
			{
				$errors['type']['data'] = 'nodata';
			}
		}

		// エラー
		if (!empty($errors))
		{
			$error_msg = array();
			foreach ($errors as $param => $error)
			{
				switch ($param)
				{
					case 'id':
						$error_msg[] = '管理IDの指定が不正です。';
						break;
					case 'type':
						$error_msg[] = '部署タイプを指定して下さい。';
						break;
					case 'name':
						$error_msg[] = '部署名は必須です。'.USER_POST_NAME_MAX.'文字以内で入力して下さい。';
						break;
					case 'delete_flg':
						$error_msg[] = '削除状態の指定が不正です。';
						break;
					default:
						$error_msg[] = '入力項目にエラーがあります。';
						break;
				}
			}
			$this->_error		= $error_msg;
			$this->_back_flg	= true;
			$f = new MCWEB_SceneForward('/post/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->new_flg = true;
		}

		if (!$this->new_flg)
		{
			// 修正の時はデータ取得
			$obj_post			= new Post();
			$post_data	= $obj_post->getDataById($this->_id,true);	// 削除済み含む
			if (empty($post_data))
			{
				// 修正データが無かった時はエラー
				$this->_error[] = '指定されたIDの部署データがありません。';
				$this->_back_flg	= true;
				$f = new MCWEB_SceneForward('/post/edit/index');
				$f->regist('FORWARD', $this);
				return $f;
			}

			// 削除状態にする場合紐付く社員が存在するかチェック（存在しても使用不可にするのは可能とする）
			if ($this->_delete_flg)
			{
				$obj_member			= new Member();
				$post_member_data	= $obj_member->getMemberByPost($this->_id);	// 削除済み社員含まない
				if (!empty($post_member_data))
				{
					$this->member_flg	= true;
				}
			}
		}
	}
}

?>