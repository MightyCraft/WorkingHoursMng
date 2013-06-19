<?php
/**
 * マスタメンテナンス関連用クラス群
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Post.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class MasterMaintenance
{
	/**
	 * 指定された所属タイプより社員データを取得
	 *
	 * @param	int		$post_type
	 * @param	string	$delete_flg	削除フラグ：trueの時削除も含む
	 * @return	array	取得データ
	 */
	function getMemberByPostType($post_type,$delete_flg=false)
	{
		$res = array();
		if (!empty($post_type))
		{
			$obj_post = new Post();
			$post_list = $obj_post->getDataByType($post_type,$delete_flg);

			if (!empty($post_list))
			{
				$post_ids = array();
				foreach ($post_list as $post_data)
				{
					$post_ids[] = $post_data['id'];
				}
				$obj_db_member	= new Member();
				$member_list = $obj_db_member->getMemberByPost($post_ids,$delete_flg);
			}

			if (!empty($member_list))
			{
				foreach ($member_list as $data)
				{
					$res[$data['id']]	= $data;
				}
			}
		}
		return $res;
	}

}