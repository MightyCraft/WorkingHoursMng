<?php
/**
 * ページャー共有
 *
 */
require_once('Pager.php');
class PagePager
{
	/**
	 * 管理側のページャーの作成
	 *
	 * @param	int		$page		ページ番号
	 * @param	int		$per_page	1ページあたりの項目数
	 * @param	int		$all		全項目数
	 * @param	int		$pass		リンクパス
	 * @return	array	全データ
	 */
	static function createAdminPagePager($page, $per_page, $all, $pass, $extraVars=array())
	{
		$params = array(
			'totalItems' => $all, // 総件数
			'perPage'    => $per_page,
			'mode'       => 'Sliding',
			'urlVar'     => 'page',
			'prevImg'    => '前の' . $per_page . '件へ',
			'nextImg'    => '次の' . $per_page . '件へ',
			'separator'  => '|',
			'spacesBeforeSeparator'	=> 1,
			'spacesAfterSeparator'	=> 1,
			'extraVars'  => $extraVars,
			'clearIfVoid' => true,
			'currentPage' => $page,
			'delta'       => 5,
			'path'        => URL_FRAMEWORK_PHP . $pass,
		);
		return	Pager::factory($params);
	}
}