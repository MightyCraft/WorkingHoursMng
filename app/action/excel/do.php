<?php
/**
 * エクセル工数表ダウンロード処理
 */
require_once(DIR_APP . '/class/common/ManhourList.php');
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . '/class/common/FileArchive.php');		// ファイルのダウンロード用

class _excel_do extends PostAndGetScene
{
	// パラメータ
	var $_member_id;	// 社員ID
	var $_post_id;		// 所属ID
	var $_date_Year;	// 出力年月
	var $_date_Month;	//

	// クラス
	var $obj_common_list;
	var $obj_member;
	var $obj_project;

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// 年
			, 'date_Year', ValidatorString::createInstance()->preg('/^[0-9]+$/')
			// 月
			, 'date_Month', ValidatorString::createInstance()->preg('/^[0-9]+$/')
		);

		if(!empty($errors))
		{
			MCWEB_Util::redirectAction('/excel/index/');
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		mb_language("Japanese");

		$this->obj_common_list	= new ManhourList;
		$this->obj_member		= new Member;
		$this->obj_project		= new Project;

		//エクセル出力権限チェック＆出力対象社員情報取得
		$member_by_ids	= array();
		$post_name		= '';
		$auth_excel	= checkAuthExcel($_SESSION['manhour']['member']['auth_lv'], $_SESSION['manhour']['member']['post']);
		if($auth_excel)
		{
			// 出力権限あり
			if (!empty($this->_member_id))
			{
				// 出力社員が指定されていた場合は指定社員の情報を取得
				$member_datas[] = $this->obj_member->getMemberById($this->_member_id,true);
			}
			else
			{
				// 指定されていない場合は指定された部署所属者全員を出力対象にする
				if ($this->_post_id == '0')
				{
					$member_datas =$this->obj_member->getMemberAll(true);
					$post_name = '全て';
				}
				else
				{
					$member_datas = $this->obj_member->getMemberByPost($this->_post_id,true);
					$obj_post	= new Post();
					$post_list	= $obj_post->getDataAll();
					$post_name = $post_list[$this->_post_id]['name'];
				}
			}
		}
		else
		{
			// 自分以外の出力権限が無い人は強制的に自分をセット
			$member_datas[] = $this->obj_member->getMemberById($_SESSION['member_id'],true);
		}


		// エクセル表出力処理
		if (empty($member_datas))
		{
			// 出力対象者の社員マスタ情報無し
			$error_msg = '出力対象者が存在しません。';
		}
		else
		{
			$get_date = $this->_date_Year . '-' . $this->_date_Month . '-01';
			$monhour_list = array();

			// 出力形式でデータ取得
			foreach ($member_datas as $member_data)
			{
				//工数データ取得
				$manhour_data = $this->obj_common_list->getManhourListByUseridYm($this->_date_Year,$this->_date_Month,$member_data['id'],$daily_total);
				if (!empty($manhour_data))
				{
					// 工数データが存在する時にエクセルデータに成形
					$monhour_list[]		= $this->_createExcelLayout($member_data,$manhour_data,$daily_total);			// 文字コード変換済
					$filename_list[]	= $this->_date_Year.'年'.$this->_date_Month.'月'.$member_data['name'].'.xls';	// excelファイル名生成
				}
			}

			if (empty($monhour_list))
			{
				// エクセル工数表出力データが1件も無い
				$error_msg = 'エクセル工数表出力用データがありません。';
			}
			else
			{
				// TODO: ブラッシュアップ
				// 　　　FileArchiveクラスには生成済みファイルのDL処理もありますのでDL方法を切り替える際は使って下さい

				// ダウンロード処理
				if (count($monhour_list) == 1)
				{
					// 1ファイルの時はexcelファイルでDL
					$dl_flg = FileArchive::downloadDataToFile($monhour_list[0], $filename_list[0]);		// ダウンロード実行
				}
				else
				{
					// 複数ファイルの時はzipにしてDL
					$dl_filename = $this->_date_Year.'年'.$this->_date_Month.'月'.$post_name.'.zip';			// zipファイル名生成
					$dl_flg = FileArchive::downloadDatasToArchive($monhour_list, $dl_filename, $filename_list);	// ダウンロード実行
				}

				if ($dl_flg)
				{
					// ダウンロード処理が行えた場合はexit
					exit;
				}
				else
				{
					// ダウンロード処理時エラー
					$error_msg = 'ダウンロードに失敗しました。';
				}
			}
		}

		// エラー処理
		if (!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/excel/index');
			$f->regist('FORWARD', $this);
			return $f;
		}

		exit();
	}

	/**
	 * 工数データよりエクセル出力用レイアウトを生成
	 *
	 * ※元のエクセル表が計算式になっている部分は計算式が入るようにする
	 * ※日付は月に関わらず31日まで固定で列がある必要があります
	 * ※明細部分の行も固定で30行ある必要があります
	 * ※TODO: ブラッシュアップ
	 *
	 * @param	array	$member_data	１社員分の社員データ
	 * @param	array	$manhour_data	１社員分の工数データ
	 * @param	array	$daily_total	日計データ
	 * @return	array	sjisにエンコード済のエクセル書式データ
	 */
	function _createExcelLayout($member_data,$manhour_data,$daily_total)
	{
		$excel_format = '';

		// ヘッダ行
		$member_code	= $member_data['member_code'];	// 社員番号
		$name			= $member_data['name'];			// 社員名
		$year			= $this->_date_Year;			// 対象年
		$month			= $this->_date_Month;			// 対象月
		$excel_format = $this->_createExcelHeader($member_code,$name,$year,$month);
		// 一覧の項目名行
		$excel_format .= $this->_createExcelTitle();
		// 一覧の詳細行
		$excel_format .= $this->_createExcelBody($manhour_data);
		// 一覧の合計行
		$excel_format .= $this->_createExcelTotal($daily_total);
		// フッタ行
		$excel_format .= $this->_createExcelFooter();

		return mb_convert_encoding($excel_format, 'SJIS-win', 'UTF-8');		// 文字コード変換
	}

	/**
	 * Excel表の表タイトル行の書き出し
	 */
	function _createExcelTitle()
	{
		// 日付セル（日付は31日固定）
		$title_date = '';
		for ($day=1;$day<=31;$day++)
		{
			$title_date .= "<td class=xl32 style='border-left:none' x:num>".$day."</td>\n";
		}

		$title_line = '';
$title_line = <<<ECHO_TEXT
 <tr height=30 style='mso-height-source:userset;height:22.5pt'>
  <td height=30 style='height:22.5pt'></td>
  <td class=xl32>プロジェクトコード</td>
  <td class=xl32 style='border-left:none'>プロジェクト<ruby>名<span style='display:none'><rt>メイ</rt></span></ruby></td>
  $title_date
  <td class=xl33 style='border-left:none'><ruby>計<span style='display:none'><rt>ケイ</rt></span></ruby></td>
 </tr>
ECHO_TEXT;

		return $title_line;
	}
	/**
	 * Excel表の工数詳細行の書き出し
	 *
	 * ※詳細行は書き出し行数に関わらず30行固定
	 * ※TODO: ブラッシュアップ
	 *
	 * @param $manhour_data
	 */
	function _createExcelBody($manhour_data)
	{
		$array_excel_sum = returnArrayExcelColSum();										// プロジェクト計計算対象セル

		$body_line = '';
		for ($row=1;$row<=USER_EXCEL_ROW_NUM;$row++)
		{
			$body_day_data = '';	// 日付セル用
			$row_data = array_shift($manhour_data);	// 書き出しデータの切り出し

			if (!is_null($row_data))
			{
				// 書き出しデータあり
				$tmp_project_code = '';
				if (($row_data['project_type']!=PROJECT_TYPE_INFORMAL) && ($row_data['project_type']!=PROJECT_TYPE_REMOVAL))
				{
					// プロジェクトマスタや仮コードや廃止コードの際はプロジェクトコードをセットしない
					$tmp_project_code = $row_data['project_code'];
				}
				$tmp_project_name = $row_data['project_name'];	// 後発の場合は後発用に整形された名称がセットされています

				// 日付部分
				for ($day=1;$day<=31;$day++)
				{
					$tmp_manhour = '';
					if (!empty($row_data[$day]['man_hour']))
					{
						$tmp_manhour = sprintf("%01.2f",$row_data[$day]['man_hour']);
					}
					//プロジェクト毎の日毎データ
					$body_day_data .= "<td class=xl39 style='border-top:none;border-left:none' x:num=\"{$tmp_manhour}\">".$tmp_manhour."</td>\n";
				}

				// プロジェクト毎工数合計
				$tmp_row_total = sprintf("%01.2f",0);
				if($row_data['total_manhour'] > 0)
				{
					$tmp_row_total = sprintf("%01.2f",$row_data['total_manhour']);
				}
			}
			else
			{
				// 書き出しデータがない時は空欄行を生成
				$tmp_project_code = '';	//プロジェクトコード
				$tmp_project_name = '';	// プロジェクト名
				// 日付部分
				for ($day=1;$day<=31;$day++)
				{
					$body_day_data .= "<td class=xl39 style='border-top:none;border-left:none'></td>\n";		// 日付
				}
				// プロジェクト毎工数合計
				$tmp_row_total = sprintf("%01.2f",0);
			}

			$body_line .= "<tr height=33 style='mso-height-source:userset;height:24.95pt'>\n";							// 行開始
			$body_line .= "<td height=33 style='height:24.95pt'></td>\n";												// A列
			$body_line .= "<td class=xl43 width=282 style='border-top:none;width:212pt'>".$tmp_project_code."</td>\n";	// プロジェクトコード
			$body_line .= "<td class=xl45 style='border-top:none;border-left:none'>".$tmp_project_name."</td>\n";		// プロジェクト名
			$body_line .= $body_day_data;																				// 日付データ
			// プロジェクト毎工数合計
			$array_num = $row-1;
			$body_line .= "<td class=xl34 style='border-top:none;border-left:none' x:num=\"{$tmp_row_total}\" x:fmla=\"=SUM({$array_excel_sum[$array_num]})\">0.00</td>\n";

			$body_line .= "</tr>\n";	// 行終了
		}

		return $body_line;
	}
	/**
	 * Excel表の合計行の書き出し
	 */
	function _createExcelTotal($daily_total)
	{
		// TODO: ブラッシュアップ
		$total_line_data	= '';						// Excel書式
		$total_man_hour		= 0;						// 月の総合計
		$array_excel_sum = returnArrayExcelRowSum();	// 日計計算対象セル

		// 日計を生成
		for ($day=1;$day<=31;$day++)
		{
			// 該当日付にデータがあれば取得
			$tmp_daily = sprintf("%01.2f",0);
			if (!empty($daily_total[$day]))
			{
				$tmp_daily = sprintf("%01.2f",$daily_total[$day]);
				$total_man_hour += $daily_total[$day];
			}
			// セル「D7～D36」の計を「AI」列まで
			$array_num = $day-1;
			$total_line_data .= "<td class=xl34 style='border-left:none' x:num=\"{$tmp_daily}\" x:fmla=\"=SUM({$array_excel_sum[$array_num]})\">0.00</td>\n";
		}
		// 総合計を生成
		$tmp_total_man_hour = sprintf("%01.2f",$total_man_hour);
		$array_num = 31;
		$total_line_data .= "<td class=xl34 style='border-top:none;border-left:none' x:num=\"{$tmp_total_man_hour}\" x:fmla=\"=SUM({$array_excel_sum[$array_num]})\">0.00</td>\n";

		// 合計行全体の生成
		$total_line = '';
$total_line = <<<ECHO_TEXT
 <tr height=39 style='mso-height-source:userset;height:29.25pt'>
  <td height=39 style='height:29.25pt'></td>
  <td class=xl35 style='border-top:none'><ruby>合<span style='display:none'><rt>ゴウ</rt></span></ruby>
  <ruby>計<span style='display:none'><rt>ケイ</rt></span></ruby></td>
  <td class=xl30 style='border-top:none;border-left:none'>　</td>
  $total_line_data
 </tr>
ECHO_TEXT;

		return $total_line;
	}
	/**
	 * Excel表のヘッダ行部分の書き出し
	 *
	 * @param $member_code
	 * @param $name
	 * @param $year
	 * @param $month
	 */
	function _createExcelHeader($member_code,$name,$year,$month)
	{
		$header = '';
$header = <<<ECHO_TEXT
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=shift_jis">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 9">
<style>
<!--table
	{mso-displayed-decimal-separator:"\.";
	mso-displayed-thousand-separator:"\,";}
@page
	{margin:.98in .79in .98in .79in;
	mso-header-margin:.51in;
	mso-footer-margin:.51in;
	mso-page-orientation:landscape;}
tr
	{mso-height-source:auto;
	mso-ruby-visibility:none;}
col
	{mso-width-source:auto;
	mso-ruby-visibility:none;}
br
	{mso-data-placement:same-cell;}
.style0
	{mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	white-space:nowrap;
	mso-rotate:0;
	mso-background-source:auto;
	mso-pattern:auto;
	color:windowtext;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:"ＭＳ Ｐゴシック";
	mso-generic-font-family:auto;
	mso-font-charset:128;
	border:none;
	mso-protection:locked visible;
	mso-style-name:標準;
	mso-style-id:0;}
.style22
	{mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	white-space:nowrap;
	mso-rotate:0;
	mso-background-source:auto;
	mso-pattern:auto;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	border:none;
	mso-protection:locked visible;
	mso-style-name:標準_ＰＪリスト;}
td
	{mso-style-parent:style0;
	padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:windowtext;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:"ＭＳ Ｐゴシック";
	mso-generic-font-family:auto;
	mso-font-charset:128;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:none;
	mso-background-source:auto;
	mso-pattern:auto;
	mso-protection:locked visible;
	white-space:nowrap;
	mso-rotate:0;}
.xl25
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;}
.xl26
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;}
.xl27
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:right;}
.xl28
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;}
.xl29
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;}
.xl30
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;
	border:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;}
.xl31
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;
	border-top:.5pt solid windowtext;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;}
.xl32
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;
	vertical-align:middle;
	border:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;}
.xl33
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;
	vertical-align:middle;
	border:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;}
.xl34
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
	text-align:right;
	border:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;}
.xl34b
{mso-style-parent:style0;
font-size:10.0pt;
font-family:"ＭＳ Ｐゴシック", monospace;
mso-font-charset:128;
mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
text-align:center;
border:.5pt solid windowtext;
background:#CCFFCC;
mso-pattern:auto none;}
.xl35
	{mso-style-parent:style0;
	border:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;}
.xl36
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl37
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐ明朝", serif;
	mso-font-charset:128;
	text-align:center;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl38
	{mso-style-parent:style0;
	mso-number-format:"\@";
	text-align:center;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl39
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
	text-align:center;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl40
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl41
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:.5pt hairline windowtext;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl42
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
	text-align:center;
	border-top:.5pt solid windowtext;
	border-right:.5pt solid windowtext;
	border-bottom:.5pt hairline windowtext;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl43
	{mso-style-parent:style0;
	text-align:left;
	vertical-align:middle;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;
	white-space:normal;}
.xl44
	{mso-style-parent:style0;
	text-align:left;
	vertical-align:middle;
	border-top:.5pt solid windowtext;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;
	white-space:normal;}
.xl45
	{mso-style-parent:style0;
	text-align:left;
	vertical-align:middle;
	border:.5pt solid windowtext;}
.xl46
	{mso-style-parent:style0;
	text-align:left;
	vertical-align:middle;
	border:.5pt solid windowtext;
	white-space:normal;}
.xl47
	{mso-style-parent:style22;
	text-align:left;
	vertical-align:middle;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;
	white-space:normal;}
.xl48
	{mso-style-parent:style0;
	text-align:left;
	border-top:.5pt solid windowtext;
	border-right:.5pt hairline windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl49
	{mso-style-parent:style0;
	text-align:left;
	border:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl50
	{mso-style-parent:style0;
	text-align:left;
	border-top:.5pt solid windowtext;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl51
	{mso-style-parent:style0;
	text-align:left;
	vertical-align:middle;
	border-top:.5pt solid windowtext;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;}
.xl52
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-number-format:"0\.00_\)\;\[Red\]\\\(0\.00\\\)";
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:.5pt solid windowtext;
	border-left:.5pt solid windowtext;
	mso-protection:unlocked visible;}
.xl53
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	text-align:center;
	border:.5pt solid windowtext;
	background:#CCFFCC;
	mso-pattern:auto none;
	mso-protection:locked hidden;}
ruby
	{ruby-align:left;}
rt
	{color:windowtext;
	font-size:6.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:"ＭＳ Ｐゴシック", monospace;
	mso-font-charset:128;
	mso-char-type:katakana;
	display:none;}
-->
</style>
<!--[if gte mso 9]><xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>フォーム</x:Name>
    <x:WorksheetOptions>
     <x:DefaultRowHeight>270</x:DefaultRowHeight>
     <x:FitToPage/>
     <x:Print>
      <x:ValidPrinterInfo/>
      <x:PaperSizeIndex>9</x:PaperSizeIndex>
      <x:Scale>54</x:Scale>
      <x:HorizontalResolution>300</x:HorizontalResolution>
      <x:VerticalResolution>300</x:VerticalResolution>
     </x:Print>
     <x:CodeName>Sheet2</x:CodeName>
     <x:Zoom>75</x:Zoom>
     <x:Selected/>
     <x:FreezePanes/>
     <x:FrozenNoSplit/>
     <x:SplitHorizontal>6</x:SplitHorizontal>
     <x:TopRowBottomPane>6</x:TopRowBottomPane>
     <x:SplitVertical>3</x:SplitVertical>
     <x:LeftColumnRightPane>3</x:LeftColumnRightPane>
     <x:ActivePane>0</x:ActivePane>
     <x:Panes>
      <x:Pane>
       <x:Number>3</x:Number>
      </x:Pane>
      <x:Pane>
       <x:Number>1</x:Number>
      </x:Pane>
      <x:Pane>
       <x:Number>2</x:Number>
      </x:Pane>
      <x:Pane>
       <x:Number>0</x:Number>
       <x:ActiveRow>3</x:ActiveRow>
       <x:RangeSelection>D4:H4</x:RangeSelection>
      </x:Pane>
     </x:Panes>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
  <x:WindowHeight>15330</x:WindowHeight>
  <x:WindowWidth>16365</x:WindowWidth>
  <x:WindowTopX>-120</x:WindowTopX>
  <x:WindowTopY>1695</x:WindowTopY>
  <x:ProtectStructure>False</x:ProtectStructure>
  <x:ProtectWindows>False</x:ProtectWindows>
 </x:ExcelWorkbook>
</xml><![endif]-->
</head>

<body link=blue vlink=purple>

<table x:str border=0 cellpadding=0 cellspacing=0 width=2001 style='border-collapse:
 collapse;table-layout:fixed;width:1509pt'>
 <col width=25 style='mso-width-source:userset;mso-width-alt:800;width:19pt'>
 <col width=282 style='mso-width-source:userset;mso-width-alt:9024;width:212pt'>
 <col width=227 style='mso-width-source:userset;mso-width-alt:7264;width:170pt'>
 <col width=45 span=31 style='mso-width-source:userset;mso-width-alt:1440;
 width:34pt'>
 <col width=72 style='width:54pt'>
 <tr height=18 style='height:13.5pt'>
  <td height=18 width=25 style='height:13.5pt;width:19pt'></td>
  <td class=xl25 width=282 style='width:212pt'></td>

  <td width=227 style='width:170pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>

  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>

  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>

  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td class=xl25 width=45 style='width:34pt'></td>
  <td width=72 style='width:54pt'></td>
 </tr>
 <tr height=19 style='height:14.25pt'>
  <td height=19 style='height:14.25pt'></td>

  <td class=xl28>タイムレポート（執務時間報告書）</td>
  <td class=xl26></td>
  <td colspan=32 class=xl27 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 style='height:13.5pt'></td>
  <td class=xl30><ruby>個人<span style='display:none'><rt>コジン</rt></span></ruby>コード</td>

  <td class=xl30 style='border-left:none'><ruby>氏名<span style='display:none'><rt>シメイ</rt></span></ruby></td>
  <td class=xl26></td>
  <td></td>
  <td colspan=3 class=xl27 style='mso-ignore:colspan'></td>
  <td colspan=3 class=xl53>年</td>
  <td class=xl31 style='border-left:none'>月</td>
  <td colspan=23 style='mso-ignore:colspan'></td>

 </tr>
 <tr height=36 style='mso-height-source:userset;height:27.0pt'>
  <td height=36 style='height:27.0pt'></td>
  <td class=xl38 style='border-top:none'>$member_code</td>
  <td class=xl37 style='border-top:none;border-left:none'>$name</td>
  <td class=xl29></td>
  <td></td>
  <td colspan=3 class=xl27 style='mso-ignore:colspan'></td>

  <td colspan=3 class=xl36 x:num>$year</td>
  <td class=xl36 style='border-left:none' x:num>$month</td>
  <td colspan=23 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 style='height:13.5pt'></td>
  <td class=xl25></td>
  <td class=xl26></td>

  <td colspan=32 class=xl27 style='mso-ignore:colspan'></td>
 </tr>
ECHO_TEXT;

		return $header;
	}
	/**
	 * Excel表のフッタ行部分の書き出し
	 *
	 */
	function _createExcelFooter()
	{
		$footer = '';
$footer = <<<ECHO_TEXT
 <![if supportMisalignedColumns]>
 <tr height=0 style='display:none'>
  <td width=25 style='width:19pt'></td>

  <td width=282 style='width:212pt'></td>
  <td width=227 style='width:170pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>

  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>

  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>

  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=45 style='width:34pt'></td>
  <td width=72 style='width:54pt'></td>
 </tr>
 <![endif]>

</table>

</body>

</html>

ECHO_TEXT;

		return $footer;
	}
}

?>