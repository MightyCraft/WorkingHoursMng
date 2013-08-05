<?php
/**
 * プロジェクト管理　既存案件一覧・終了案件一覧画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
require_once(DIR_APP . "/class/common/PagePager.php");
class _project_index extends PostAndGetScene
{
	// プロジェクトタイプ
	const PROJECT_TYPE_ACTIVE      = '1'; // 有効なプロジェクト（通常or仮or後発）
	const PROJECT_TYPE_PROVISIONAL = '2'; // 仮のみ
	const PROJECT_TYPE_ABOLITION   = '3'; // 廃止のみ
	// 表示対象
	const DISPLAY_TYPE_NOT_DELETE = '1'; // 未削除のみ
	const DISPLAY_TYPE_DELETE     = '2'; // 削除のみ
	const DISPLAY_TYPE_ALL        = '3'; // 全て表示

	// ページ当たりの表示件数
	const PER_PAGE = 50;
	// ページャー
	private $obj_pager;

	// パラメータ
	var $_ret_type	= 4;				// SESSIONより表示条件復帰タイプ（1:検索ﾎﾞﾀﾝorﾒﾆｭｰ遷移、2:ﾍﾟｰｼﾞLINK遷移、3:ｿｰﾄLINK遷移、4:他画面から遷移）

	var $_type		= 'now';			// 表示画面（now：既存案件画面、end：終了案件画面）

	var $_page		= 1;				// 現在ページ
	var $_column	= 'project_type';	// ソート-カラム
	var $_order		= 'DESC';			// ソート-昇順・降順

	var $_radio			= 2;			// 検索タイプ（1:所属PJ､2:キーワード）
	var $_key_word		= '';			// キーワード
	var $_member_id;					// 担当営業					 パラム無しはNULL、指定無しは0
	var $_total_budget_chk;				// 総予算未設定
	var $_project_end_date_chk;			// 開発終了日未設定		// 画面遷移NULL、指定無しNULL、指定があれば1
	var $_project_type	= '1';			// プロジェクトタイプ
	var $_budget_type	= '0';			// 予算タイプ（0：全て）
	var $_display_type	= '1';			// 表示対象（削除/未削除）

	// 画面表示用
	var $list;					// 検索結果
	var $total;					// 検索件数
	var $last_page;				// ページ情報
	var $now_page;				//
	var $radio_list;			// 検索条件
	var $member_by_post_sales;	//
	var $project_type_list;		//
	var $budget_type_list;		//
	var $display_type_list;		//
	var $client_list;			// 一覧のクライアント名表示
	var $member_list;			// 一覧の営業担当名表示

	var $error = array();	// エラー

	function check()
	{
		// SESSIONより表示条件復帰が必要（検索ボタン遷移・メニュー遷移以外）
		if ($this->_ret_type != 1)
		{
			$this->_getSessionData();	// SESSIONから表示条件を各パラメータにセット
		}

		// 画面表示タイプの初期値設定
		if ($this->_type != 'end')
		{
			// 「now」or「end」以外の時は強制的に「now」をセット
			$this->_type = 'now';
		}
		// 担当営業の初期値設定
		if (!isset($this->_member_id))
		{
			// NULLor未定義の時は強制的に初期値セット
			if($_SESSION['manhour']['member']['post'] == 2)
			{
				// 自分が営業部の場合、自分自身をあらかじめ選択
				$this->_member_id = $_SESSION['manhour']['member']['id'];
			}
			else
			{
				$this->_member_id = 0;
			}
		}

		// パラメータのバリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'ret_type',				ValidatorInt::createInstance()->nullable()->min(1)->max(4)
			, 'type',					ValidatorString::createInstance()
			, 'page',					ValidatorInt::createInstance()->min(1)
			, 'column',					ValidatorString::createInstance()
			, 'order',					ValidatorString::createInstance()
			, 'radio',					ValidatorInt::createInstance()->min(1)->max(2)
			, 'key_word',				ValidatorString::createInstance()->nullable()->max(USER_PROJECT_NAME_MAX)	// プロジェクト名の文字数上限
			, 'member_id',				ValidatorInt::createInstance()->min(0)
			, 'total_budget_chk',		ValidatorInt::createInstance()->nullable()->min(0)->max(1)
			, 'project_end_date_chk',	ValidatorInt::createInstance()->nullable()->min(0)->max(1)
			, 'project_type',			ValidatorInt::createInstance()->min(self::PROJECT_TYPE_ACTIVE)->max(self::PROJECT_TYPE_ABOLITION)
			, 'budget_type',			ValidatorInt::createInstance()->min(0)->max(BUDGET_TYPE_NOT_MANAGE)
			, 'display_type',			ValidatorInt::createInstance()->min(self::DISPLAY_TYPE_NOT_DELETE)->max(self::DISPLAY_TYPE_ALL)
		);

		// エラーチェック
		if(!empty($errors))
		{
			// エラー
			$error_msg	= array();
			foreach($errors as $error_key => $error_value)
			{
				if ($error_key == 'radio')
				{
					$error_msg[] = '検索タイプ(所属プロジェクトorキーワード)の指定は必須です。';
				}
				elseif ($error_key == 'page')
				{
					$error_msg[] = '表示ページが指定されていません。';
				}
				elseif ($error_key == 'column')
				{
					$error_msg[] = 'ソート項目が指定されていません。';
				}
				elseif ($error_key == 'order')
				{
					$error_msg[] = 'ソートの昇順/降順が指定されていません。';
				}
				elseif ($error_key == 'key_word')
				{
					$error_msg[] = 'キーワードは'.USER_PROJECT_NAME_MAX.'文字以内で入力して下さい。';
				}
				elseif ($error_key == 'member_id')
				{
					$error_msg[] = '担当営業の項目を指定して下さい';
				}
				elseif ($error_key == 'project_type')
				{
					$error_msg[] = 'プロジェクトタイプの指定は必須です。';
				}
				elseif ($error_key == 'budget_type')
				{
					$error_msg[] = '予算タイプの指定は必須です。';
				}
				elseif ($error_key == 'display_type')
				{
					$error_msg[] = '表示対象の指定は必須です。';
				}
			}
			if (empty($error_msg))
			{
				// エラーメッセージが取得出来ない時は既定エラー文言セット
				$error_msg[] = '検索条件の値が不正です。';
			}

			$this->error = $error_msg;
		}
		else
		{
			// エラーがない時は、表示条件をSESSIONにセット
			$this->_setSessionData();
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 表示条件取得にエラーがあった時はデータ抽出は行わない
		if(!empty($this->error))
		{
			return;
		}


		// 検索条件生成
		$arr_search = array();
		// 検索タイプ
		if ($this->_radio == 1)
		{
			// 所属プロジェクト
			$obj_project_team = new ProjectTeam;
			$arr_project_team = $obj_project_team->getDataByMemberId($_SESSION['manhour']['member']['id']);
			if (!empty($arr_project_team))
			{
				foreach ($arr_project_team as $value)
				{
					$arr_search[] = $value['project_id'];
				}
			}
		}
		elseif ($this->_radio == 2)
		{
			// キーワード（PJコード/プロジェクト名/クライアント名）
			$arr_search = array(
					'MP.project_code' => $this->_key_word,
					'MP.name' => $this->_key_word,
					'MC.name' => $this->_key_word,
							);
		}

		$search_column = array();
		// 担当営業
		if($this->_member_id >= 1)
		{
			$search_column['member_id'] = $this->_member_id;
		}
		// 総予算未設定
		if($this->_total_budget_chk >= 1)
		{
			$search_column['total_budget_chk'] = $this->_total_budget_chk;
		}
		// 開発終了日未設定
		if($this->_project_end_date_chk >= 1)
		{
			$search_column['project_end_date_chk'] = $this->_project_end_date_chk;
		}
		// プロジェクトタイプ
		if(!empty($this->_project_type))
		{
			if($this->_project_type == self::PROJECT_TYPE_ACTIVE)
			{
				$search_column['project_type'] = array(PROJECT_TYPE_NORMAL,PROJECT_TYPE_INFORMAL,PROJECT_TYPE_BACK);
			}
			elseif ($this->_project_type == self::PROJECT_TYPE_PROVISIONAL)
			{
				$search_column['project_type'] = PROJECT_TYPE_INFORMAL;
			}
			else if ($this->_project_type == self::PROJECT_TYPE_ABOLITION)
			{
				$search_column['project_type'] = PROJECT_TYPE_REMOVAL;
			}
		}
		// 予算タイプ（0：全ての時は条件に指定しない）
		if(!empty($this->_budget_type))
		{
			$search_column['budget_type'] = $this->_budget_type;
		}
		// 表示対象（3：全ての時は条件に指定しない）
		if(!empty($this->_display_type))
		{
			if ($this->_display_type == self::DISPLAY_TYPE_NOT_DELETE)
			{
				$search_column['delete_flg'] = '0';
			}
			else if ($this->_display_type == self::DISPLAY_TYPE_DELETE)
			{
				$search_column['delete_flg'] = '1';
			}
		}


		// 表示するプロジェクト情報を取得
		$obj_project = new Project;
		$this->list = $obj_project->getDataByLimit($this->_page, self::PER_PAGE, $this->total, $this->_type, $this->_column, $this->_order, $this->_radio, $arr_search, $search_column);


		//該当件数がある場合、ページャー、ページ情報を設定
		if($this->total)
		{
			$extraVars = array(
				'type'		=> $this->_type,	// 表示画面
				'ret_type'	=> 2,				// ページLINK遷移
			);
			$pager	= PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $this->total, '/project', $extraVars);
			$this->last_page	= $pager->numPages();
			$this->now_page		= $this->_page;
			$access->htmltag('pager',	$pager->getLinks());
		}


		// 画面表示用文言など
		// 検索タイプ
		$this->radio_list = array(
			'1'	=> '所属プロジェクト',
			'2'	=> 'キーワード（PJコード/プロジェクト名/クライアント名）',
		);
		// 検索用営業部社員リスト（検索用プルダウンには削除済み含まない）
		$obj_maintenance = new MasterMaintenance;
		$this->member_by_post_sales = $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES);
		// プロジェクトタイプ
		$this->project_type_list = array(
				self::PROJECT_TYPE_ACTIVE		=>	'有効なプロジェクト(廃止以外)',
				self::PROJECT_TYPE_PROVISIONAL	=>	'仮のみ',
				self::PROJECT_TYPE_ABOLITION	=>	'廃止のみ',
		);
		// 予算タイプ
		$this->budget_type_list = returnArrayBudgetType();
		// 表示対象
		$this->display_type_list = array(
			self::DISPLAY_TYPE_NOT_DELETE=>'未削除のみ',
			self::DISPLAY_TYPE_DELETE=>'削除のみ',
			self::DISPLAY_TYPE_ALL=>'全て表示',
		);

		// クライアントリスト取得
		$obj_client			= new Client;
		$this->client_list	= $obj_client->getDataAll();
		// アカウントリスト取得（担当営業が退職や部署移動後にも対応
		$obj_member			= new Member;
		$this->member_list	= $obj_member->getMemberAll(true);	// 削除含む

	}

/** privete処理 **/
	/**
	 * SESSIONから初期表示条件を取得
	 *
	 * 他画面からの遷移の場合は
	 * SESSIONに表示条件が保持されていれば、その条件を使用して初期表示をする
	 *
	 * 但し「SESSIONに登録されているtype」と「パラメータのtype」が異なる場合は
	 * SESSIONの表示条件は使用しない
	 */
	private function _getSessionData()
	{
		if (isset($_SESSION['manhour']['projectlist']['type']) && ($this->_type == $_SESSION['manhour']['projectlist']['type']))
		{
			// 検索条件
			if (isset($_SESSION['manhour']['projectlist']['radio']))
			{
				$this->_radio = $_SESSION['manhour']['projectlist']['radio'];
			}
			if (isset($_SESSION['manhour']['projectlist']['key_word']))
			{
				$this->_key_word = $_SESSION['manhour']['projectlist']['key_word'];
			}
			if (isset($_SESSION['manhour']['projectlist']['member_id']))
			{
				$this->_member_id = $_SESSION['manhour']['projectlist']['member_id'];
			}
			if (isset($_SESSION['manhour']['projectlist']['project_type']))
			{
				$this->_project_type = $_SESSION['manhour']['projectlist']['project_type'];
			}
			if (isset($_SESSION['manhour']['projectlist']['budget_type']))
			{
				$this->_budget_type = $_SESSION['manhour']['projectlist']['budget_type'];
			}
			if (isset($_SESSION['manhour']['projectlist']['display_type']))
			{
				$this->_display_type = $_SESSION['manhour']['projectlist']['display_type'];
			}
			if (isset($_SESSION['manhour']['projectlist']['total_budget_chk']))
			{
				$this->_total_budget_chk = $_SESSION['manhour']['projectlist']['total_budget_chk'];
			}
			if (isset($_SESSION['manhour']['projectlist']['project_end_date_chk']))
			{
				$this->_project_end_date_chk = $_SESSION['manhour']['projectlist']['project_end_date_chk'];
			}

			// ページLINK遷移以外はページ設定も取得
			if  ($this->_ret_type != 2)
			{

				if (isset($_SESSION['manhour']['projectlist']['page']))
				{
					$this->_page = $_SESSION['manhour']['projectlist']['page'];
				}
			}
			// ソートLINK遷移以外はソート設定も取得
			if  ($this->_ret_type != 3)
			{

				if (isset($_SESSION['manhour']['projectlist']['column']))
				{
					$this->_column = $_SESSION['manhour']['projectlist']['column'];
				}
				if (isset($_SESSION['manhour']['projectlist']['order']))
				{
					$this->_order = $_SESSION['manhour']['projectlist']['order'];
				}
			}
		}

		// 条件復帰後初期化
		$_SESSION['manhour']['projectlist']	= array();
	}

	/**
	 * SESSIONに現在の表示条件を保管
	 *
	 */
	private function _setSessionData()
	{
		// セット前に初期化
		$_SESSION['manhour']['projectlist'] = array();

		// 表示画面
		$_SESSION['manhour']['projectlist']['type'] = $this->_type;

		// 検索条件
		$_SESSION['manhour']['projectlist']['radio']				= $this->_radio;
		$_SESSION['manhour']['projectlist']['key_word']				= $this->_key_word;
		$_SESSION['manhour']['projectlist']['member_id']			= $this->_member_id;
		$_SESSION['manhour']['projectlist']['project_type']			= $this->_project_type;
		$_SESSION['manhour']['projectlist']['budget_type']			= $this->_budget_type;
		$_SESSION['manhour']['projectlist']['display_type']			= $this->_display_type;
		$_SESSION['manhour']['projectlist']['total_budget_chk']		= $this->_total_budget_chk;
		$_SESSION['manhour']['projectlist']['project_end_date_chk']	= $this->_project_end_date_chk;

		// ページ設定
		$_SESSION['manhour']['projectlist']['page']	= $this->_page;
		// ソート設定
		$_SESSION['manhour']['projectlist']['column']	= $this->_column;
		$_SESSION['manhour']['projectlist']['order']	= $this->_order;
	}

}

?>