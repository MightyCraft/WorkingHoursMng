<?php
/**
 * クライアント管理　新規　確認画面
 */
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
class _client_new_confirm extends PostScene
{
	var $_name;
	var $_memo;

	function check()
	{
		// クライアント名から前後の全角半角SPACE除去
		mb_regex_encoding('UTF-8');
		$conv_name = mb_ereg_replace('^\s*|\s*$', '', $this->_name);
		$this->_name = trim($conv_name);

		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'name', ValidatorString::createInstance()->min(1)->max(USER_CLIENT_NAME_MAX)		// クライアント名
			, 'memo', ValidatorString::createInstance()->nullable()->max(USER_CLIENT_MEMO_MAX)	// 備考
		);
		// エラーメッセージ
		$error_msg = array();
		if (!empty($errors))
		{
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
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/client/new/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// クライアント名重複チェック
		$obj_client = new Client;
		$client_by_name = $obj_client->getClientByName($this->_name);	//クライアントリスト取得
		$error_msg = array();
		if(!empty($client_by_name))
		{
			$error_msg[]	= '入力したクライアント名は既に登録されています。';
		}

		if (!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/client/new/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}
}
?>