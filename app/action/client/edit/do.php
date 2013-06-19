<?php
/**
 * クライアント管理　編集　更新処理
 */

require_once(DIR_APP . "/class/common/dbaccess/Client.php");
class _client_edit_do extends PostScene
{
	var $_submit;

	var $_id;
	var $_name;
	var $_memo;

	function check()
	{
		// クライアント名の前後の全角半角SPACE除去
		mb_regex_encoding('UTF-8');
		$conv_name = mb_ereg_replace('^\s*|\s*$', '', $this->_name);
		$this->_name = trim($conv_name);

		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id', ValidatorInt::createInstance()	// id
			, 'name', ValidatorString::createInstance()->min(1)->max(USER_CLIENT_NAME_MAX)	// クライアント名
			, 'memo', ValidatorString::createInstance()->nullable()->max(USER_CLIENT_MEMO_MAX)	// 備考
		);
		// エラーメッセージ
		$error_msg = array();
		if (!empty($errors))
		{
			if(isset($errors['id']))
			{
				if($errors['id'][0] == 'format')
				{
					$error_msg['id'] = 'クライアントID値が不正です。';
				}
				else
				{
					$error_msg['id'] = 'クライアントID値が不正です。';
				}
			}
			if(isset($errors['name']))
			{
				if($errors['name'][0] == 'max')
				{
					$error_msg['name'] = 'クライアント名は'.USER_CLIENT_NAME_MAX.'文字以下で入力して下さい。';
				}
				elseif($errors['name'][0] == 'null' || $errors['name'][0] == 'min')
				{
					$error_msg['name'] = 'クライアント名が未入力です。';
				}
				else
				{
					$error_msg['name'] = 'クライアント名の入力値が不正です。';
				}
			}
			if(isset($errors['memo']))
			{
				if($errors['memo'][0] == 'max')
				{
					$error_msg['memo'] = '備考は'.USER_CLIENT_MEMO_MAX.'文字以下（改行含む）で入力して下さい。';
									}
				else
				{
					$error_msg['memo'] = '備考の入力値が不正です。';
				}
			}
		}

		// 入力エラー
		if (!empty($error_msg))
		{
			$this->_submit	= 2;
			$this->_error	= $error_msg;
			$f = new MCWEB_SceneForward('/client/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_client = new Client;

		// 重複チェック
		$client_by_name = $obj_client->getClientByName($this->_name);	//クライアントリスト取得
		$error_msg = array();
		if (!empty($client_by_name))
		{
			foreach($client_by_name as $client_data)
			{
				if ($client_data['id'] != $this->_id)
				{
					//重複且つ自身のデータ以外が存在したらエラー
					$error_msg[]	= '入力したクライアント名は既に登録されています。';
				}
			}
		}

		if (!empty($error_msg))
		{
			$this->_submit	= 2;
			$this->_error	= $error_msg;
			$f = new MCWEB_SceneForward('/client/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}

		// 更新処理
		if (empty($this->_memo))
		{
			$this->_memo = NULL;
		}
		$data = array(
			$this->_name,
			$this->_memo,
			date('Y-m-d H:i:s'),
		);

		$res = $obj_client->updateClient($this->_id, $data);

		if($res == 1)
		{
				//更新OK
				MCWEB_Util::redirectAction("/client/edit/complete?id={$this->_id}");
				exit();
		}
		else
		{
				//更新NG
				MCWEB_InternalServerErrorException();
				exit();
		}
	}
}
?>