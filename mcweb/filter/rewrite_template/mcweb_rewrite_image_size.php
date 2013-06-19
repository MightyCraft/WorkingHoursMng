<?php

/**
 * imgタグのwidthとheightを書き換えます
 * imgタグにrewrite_image_size='false'属性が付与されていなかった場合に動作します。
 *
 * デフォルト動作はtrueです。
 */
class mcweb_rewrite_image_size implements MCWEB_filter_rewrite_template
{
	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		$list = $dom->find('img');
		if (!empty($list))
		{
			foreach($list as $tag)
			{
				if (empty($tag->rewrite_image_size) || 'true' === $tag->rewrite_image_size)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype)
					{
						if (empty($tag->width) || empty($tag->height))
						{
							$path = DIR_HTML . '/' . dirname(substr($template_path, 1)) . '/' . $tag->src;
							$size = getimagesize($path);
							$width = $size[0];
							$height = $size[1];
							if (empty($tag->width))
							{
								$tag->width = $width;
							}
							if (empty($tag->height))
							{
								$tag->height = $height;
							}
						}

						if (FALSE === strpos($tag->width, '%'))
						{
							$tag->width = $LD . $tag->width . '|mcweb_image_resize:$IMAGE_SCALE' . $RD;
						}
						if (FALSE === strpos($tag->height, '%'))
						{
							$tag->height = $LD . $tag->height . '|mcweb_image_resize:$IMAGE_SCALE' . $RD;
						}
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_image_size))
				{
					$tag->rewrite_image_size = null;
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		//	VGA端末を見分けて倍率を変更する機能
		$mobile = new MCWEB_Mobile(MCWEB_CARRIER_DOCOMO | MCWEB_CARRIER_SOFTBANK | MCWEB_CARRIER_AU);
		if (MCWEB_DISPLAY_SCALE_VGA == $mobile->displayScale())		$output->text('IMAGE_SCALE', 2);
		else														$output->text('IMAGE_SCALE', 1);
	}
}

?>