<?php
/**
 * クライアント管理
 *
 */

require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/PagePager.php");

class _client_index extends PostAndGetScene
{
	// ページャー
	private $obj_pager;
	// 現在ページ
	var $_page = NULL;
	// ページ当たりの表示件数
	const PER_PAGE = 30;
	// 検索キーワード
	var $_key_word;

	function check()
	{
		// 未セット時は初期化
		if(!isset($_SESSION['manhour']['clientlist']))
		{
			$_SESSION['manhour']['clientlist']	= array();
		}
		elseif(empty($this->_page))//クライアント管理をクリックした場合
		{
			$_SESSION['manhour']['clientlist']	= array();
		}

		// 初期値セット
		// 表示ページ
		if (empty($this->_page) || !is_numeric($this->_page))
		{
			$this->_page = 1;
		}
		// 検索キーワード：画面から未入力の時（メニューから遷移 or ページLINK遷移）
		if (empty($this->_key_word))
		{
			if (empty($_SESSION['manhour']['clientlist']['key_word']))
			{
				// SESSION未セット時
				$this->_key_word = '';
			}
			else
			{
				$this->_key_word	= $_SESSION['manhour']['clientlist']['key_word'];
			}
		}

		// 検索条件のSESSION保存
		$_SESSION['manhour']['clientlist']['key_word']	= $this->_key_word;

		// 入力文字チェック
		$errors =	MCWEB_ValidationManager::validate(
			$this
			, 'key_word', ValidatorString::createInstance()->width()->max(256)
						);
		//エラー文言配列
		$error_format_array =	array(
			'key_word'					=> 'キーワードは256文字以内で入力して下さい。',
								);
		//エラーチェック
		$error_msg	= array();
		foreach($errors as $error_key => $error_value)
		{
			if ( ($error_value[0] != 'null') && (isset($error_format_array[$error_key])) )
			{
				$error_msg[]	= $error_format_array[$error_key];
			}
		}
		//エラー
		if(!empty($error_msg))
		{
			$this->_error = $error_msg;
			return;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

		// クライアントデータ取得・設定（エラー無しの時のみ）
		if(empty($this->_error))
		{
			$obj_client = new Client;

			$offset = ($this->_page - 1) * self::PER_PAGE;
			$limit = self::PER_PAGE;

			// クライアントデータ取得
			list($member_all,$all_num) = $obj_client->getClientAllPager($offset,$limit,$this->_key_word);

			// ページャーセット
			$this->obj_pager = PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $all_num, '/client', array('key_word' => $this->_key_word));
			
			$first_index = 0;
			$last_index = 0;
			//ページ毎の最初の番号
			$first_index = ($this->_page - 1) * self::PER_PAGE + 1;
			//ページ毎の最後の番号
			$last_index = $first_index + (self::PER_PAGE - 1);

			//最後のページの特殊ケースへの対応
			$is_last = $this->obj_pager->isLastPage();
			if($is_last)
			{
				$last_index = $all_num;
			}

			$last_page = $this->obj_pager->numPages();

			//該当件数0件
			if($all_num == 0)
			{
				$first_index = 0;
				$last_index = 0;
			}

			//テンプレートへセット
			$access->text('member_all', $member_all);
			$access->text('all_num', $all_num);
			$access->text('first_index', $first_index);
			$access->text('last_index', $last_index);
			$access->text('last_page', $last_page);
			$access->htmltag('pager', $this->obj_pager->getLinks());
		}

		//テンプレートへセット	//GET値POST値等PUBLICなメンバー変数は自動的にセット
		// 権限設定
		$array_auth_lv = returnArrayAuthLv();
		$access->text('array_auth_lv', $array_auth_lv);
		// 検索条件設定
		$access->text('key_word', $this->_key_word);
	}
}

?>