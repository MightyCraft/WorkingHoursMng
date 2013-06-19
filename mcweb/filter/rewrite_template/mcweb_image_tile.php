<?php



/**
 * カスタムSmartyタグである、block.iamge_tile.php と連携し、画像を隙間なく並べます。
 *
 * デフォルト動作はfalseです。
 */
class mcweb_image_tile implements MCWEB_filter_rewrite_template
{
	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		$div = $dom->find('div');
		if (!empty($div))
		{
			foreach($div as $tag)
			{
				if (!empty($tag->image_tile) && 'true' == $tag->image_tile)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype)
					{
						$insert = new simple_html_dom_node($dom);
						$insert->outertext = $LD . '$IMAGE_TILE_PARENT_TAG_EX_START' . $RD;
						array_unshift($tag->nodes, $insert);

						$insert = new simple_html_dom_node($dom);
						$insert->outertext = $LD . '$IMAGE_TILE_PARENT_TAG_EX_END' . $RD;
						array_push($tag->nodes, $insert);

						foreach($tag->children as $child)
						{
							$insert = new simple_html_dom_node($dom);
							$insert->outertext = $LD . '$IMAGE_TILE_CHILD_TAG_EX_START' . $RD;
							array_unshift($child->nodes, $insert);

							$insert = new simple_html_dom_node($dom);
							$insert->outertext = $LD . '$IMAGE_TILE_CHILD_TAG_EX_END' . $RD;
							array_push($child->nodes, $insert);

							$child->tag = $LD . '$IMAGE_TILE_CHILD_TAG' . $RD;
						}
					}
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		if (defined('CLIENT_CARRIER') && 'AU' === CLIENT_CARRIER)
		{
			$output->htmltag('IMAGE_TILE_PARENT_TAG_EX_START', '<table cellspacing="0" cellpadding="0">');
			$output->htmltag('IMAGE_TILE_PARENT_TAG_EX_END', '</table>');
			$output->htmltag('IMAGE_TILE_CHILD_TAG', 'tr');
			$output->htmltag('IMAGE_TILE_CHILD_TAG_EX_START', '<td>');
			$output->htmltag('IMAGE_TILE_CHILD_TAG_EX_END', '</td>');
		}
		else if (defined('CLIENT_CARRIER') && ('DOCOMO' === CLIENT_CARRIER || 'MOVA' === CLIENT_CARRIER || 'SOFTBANK' === CLIENT_CARRIER))
		{
			$output->htmltag('IMAGE_TILE_PARENT_TAG_EX_START', '<div>');
			$output->htmltag('IMAGE_TILE_PARENT_TAG_EX_END', '</div>');
			$output->htmltag('IMAGE_TILE_CHILD_TAG', 'div');
			$output->htmltag('IMAGE_TILE_CHILD_TAG_EX_START', '');
			$output->htmltag('IMAGE_TILE_CHILD_TAG_EX_END', '');
		}
		else
		{
			$output->htmltag('IMAGE_TILE_PARENT_TAG_EX_START', '<div style="margin: 0px; border: 0px; padding: 0px; font-size: 0px;">');
			$output->htmltag('IMAGE_TILE_PARENT_TAG_EX_END', '</div>');
			$output->htmltag('IMAGE_TILE_CHILD_TAG', 'div');
			$output->htmltag('IMAGE_TILE_CHILD_TAG_EX_START', '');
			$output->htmltag('IMAGE_TILE_CHILD_TAG_EX_END', '');
		}
	}
}


?>