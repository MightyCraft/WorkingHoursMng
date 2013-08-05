<?php
/**
 *	部署マスタ管理　新規/修正　登録処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
class _post_edit_do extends PostScene
{
	// パラメータ
	var	$_id;				// 部署ID
	var	$_type;				// 部署タイプ
	var	$_name;				// 部署名
	var	$_delete_flg;		// 削除フラグ

	var $type=false;		// 新規or修正

	function check()
	{
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->type = 'new';
		}
		else
		{
			$this->type = 'edit';
		}

		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',				ValidatorInt::createInstance()->nullable()->min(0)
			, 'type',			ValidatorString::createInstance()
			, 'name',			ValidatorString::createInstance()->min(1)->max(USER_POST_NAME_MAX)
			, 'delete_flg',		ValidatorInt::createInstance()->nullable()->min(1)->max(1)
		);
		$post_type = returnArrayPostType();
		if (!empty($this->_type))
		{
			if (empty($post_type[$this->_type]))
			{
				$errors['type']['data'] = 'nodata';
			}
		}

		// エラー
		if (!empty($errors))
		{
			// この時点のエラーは不正なアクセス
			MCWEB_Util::redirectAction("/post/index?edit_type={$this->type}&id={$this->_id}&error_flg=1");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 削除フラグ
		if ($this->_delete_flg)
		{
			$delete_flg = 1;
		}
		else
		{
			$delete_flg = 0;
		}

		$obj_post			= new Post();
		if ($this->type == 'new')
		{
			// 新規登録の時
			$regist_data = array(
				'type'			=> $this->_type,
				'name'			=> $this->_name,
				'delete_flg'	=> $delete_flg,
			);
			list($return,$insert_id) = $obj_post->insertPost($regist_data);
		}
		else
		{
			// 修正の時
			$post_data	= $obj_post->getDataById($this->_id,true);	// 削除済み含む
			if (empty($post_data))
			{
				// 修正データが存在しない時は不正なアクセス
				MCWEB_Util::redirectAction("/post/index?edit_type={$this->type}&id={$this->_id}&error_flg=1");
			}

			$update_data = array(
				'type'			=> $this->_type,
				'name'			=> $this->_name,
				'delete_flg'	=> $delete_flg,
			);
			$return = $obj_post->updatePost($this->_id,$update_data);
		}

		if ($return > 0)
		{
			// 影響を与えた行数がある
			if ($this->type == 'new')
			{
				MCWEB_Util::redirectAction("/post/index?edit_type={$this->type}&id={$insert_id}");
			}
			else
			{
				MCWEB_Util::redirectAction("/post/index?edit_type={$this->type}&id={$this->_id}");
			}
		}
		else
		{
			MCWEB_Util::redirectAction("/post/index?edit_typet={$this->type}&id={$this->_id}&error_flg=1");
		}
	}
}

?>