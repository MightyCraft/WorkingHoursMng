<?php
/**
 * 工数照会　社員毎画面
 *
 */

require_once(DIR_APP . '/class/common/ManhourList.php');
require_once(DIR_APP . '/class/common/dbaccess/Holiday.php');
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _list_member extends PostAndGetScene
{
	/**
	 * @access public
	 * @var integer 選択年
	 */
	var $_date_Year;
	/**
	 * @access public
	 * @var integer 選択月
	 */
	var $_date_Month;
	/**
	 * @access public
	 * @var integer 選択社員ID
	 */
	var $_member_id;
	var $_post_id;		// 選択所属ID

	/**
	 * @access public
	 * @var array 社員一覧
	 */
	var $member_list;

	/**
	 * @access public
	 * @var string プルダウン用選択月
	 */
	var $set_time;

	var $monhour_data;		// 社員別指定月工数データ
	var $daily_total_data;	// 日別工数集計データ
	var $weekends_holidays;	// カレンダー情報
	var $self_flg;			// 自分自身の情報かの判別
	var $post_list;			// 部署リストの取得

	function check()
	{
		// エラーチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'post_id',	ValidatorUnsignedInt::createInstance()->min(1)
			, 'member_id',	ValidatorUnsignedInt::createInstance()->min(1)
			, 'date_Year',	ValidatorString::createInstance()->min(1)->max(4)
			, 'date_Month',	ValidatorString::createInstance()->min(1)->max(2)
		);
		// 指定年月の正当性チェック
		if (!checkdate($this->_date_Month,1,$this->_date_Year))
		{
			$errors["date"] = 'error';
		}

		// 値が不正な場合は初期値を強制セット
		if(!empty($errors))
		{
			// 表示部署
			if (!empty($errors['post_id']))
			{
				$this->_post_id	= 0;
			}
			// 表示社員
			if (!empty($errors['member_id']))
			{
				if (!empty($this->_post_id))
				{
					$this->_member_id = 0;								// 部署指定がある時は「0：▼選択」にする
				}
				else
				{
					$this->_member_id	= (int)$_SESSION['member_id'];	// 部署指定がないor全ての時は自分自身をセット
				}
			}
			// 表示年月
			if (!empty($errors['date_Year']) || !empty($errors['date']))
			{
				$this->_date_Year	= date('Y');
			}
			if (!empty($errors['date_Month']) || !empty($errors['date']))
			{
				$this->_date_Month	= date('m');
			}
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_common_list	= new ManhourList();
		$obj_post			= new Post();
		$obj_member			= new Member();

		// 表示年月設定　TODO: ブラッシュアップ
		$this->set_time		= $this->_date_Year . '-' . $this->_date_Month . '-01';

		// 部署リストの設定
		$this->post_list	= $obj_post->getDataAll();

		// 社員リスト取得（削除フラグ=1も含む。有効社員＞削除社員順にソート。）
		$this->member_list	= $obj_member->getMemberAll(true,true);	


		// 表示社員が指定されている時のみ工数情報取得
		$this->monhour_data = array();
		if (!empty($this->_member_id))
		{
			// 工数情報取得（$this->daily_totalは参照変数）
			$this->monhour_data = $obj_common_list->getProjectListByUseridDate($this->_date_Year,$this->_date_Month,$this->_member_id,$this->daily_total_data);
		}

		// 表示年月のカレンダー情報取得（土日祝日埋め込み済）
		$this->weekends_holidays	= getWeekendsHolidays($this->_date_Year, $this->_date_Month);
		// 休日マスタに設定されている休日をカレンダーに反映
		$obj_holiday = new Holiday;
		$arr_mst_holiday = $obj_holiday->getData($this->_date_Year,$this->_date_Month);
		if (!empty($arr_mst_holiday))
		{
			foreach ($arr_mst_holiday as $value)
			{
				if ((int)($value['holiday_year'])  == $this->_date_Year &&
					(int)($value['holiday_month']) == $this->_date_Month)
				{
					// 基本休日に対して、マスタ設定休日を反映　※3:固定祝日として登録
					$this->weekends_holidays[$value['holiday_day']] = 3;
				}
			}
		}

		// 自分自身の表示かの判別
		$this->self_flg = false;
		if ($_SESSION["member_id"] == $this->_member_id)
		{
			$this->self_flg = true;
		}
		
		// メンバ名を表示
		$this->member = $obj_member->getMemberById($this->_member_id);
	}
}
?>