<?php

/**
 * style属性の中身を整えるためのプラグイン。
 * 同じスタイル指定が重なっていた場合に、後から宣言されているもの優先する。
 * mcweb_include_cssフィルターと連携して働くために用意。
 *
 */
function smarty_block_mcweb_normalize_style($params, $content, &$smarty, &$repeat)
{
	if (is_null($content))
	{
		//	ブロック開始タグで行うことはありません
		return;
	}

	$arr = explode(';', $content);
	$styles = array();
	foreach($arr as $value)
	{
		if (2 <= strlen($value) && '%' === $value[0] && '%' === $value[strlen($value) - 1])
		{
			$value = trim($value, '%');
			$classes = explode(' ', $value);

			//	タグ名指定無しでのスタイル設定のほうが優先度が低いので、先に解析
			$data = $smarty->get_template_vars('INCLUDE_CSS');

			if (isset($data['ALL']))
			{
				$sets = $data['ALL'];
				$names = array_keys($sets);

				//	CSSファイルの中で、後に定義されたもののほうが優先度が高い
				//	なかなかやりづらい実装だが、keysをforeachすることで再現
				foreach($names as $name)
				{
					if (in_array($name, $classes))
					{
						$styles = array_merge($styles, $sets[$name]);
					}
				}
			}
			if (isset($data[$params['tag']]))
			{
				$sets = $data[$params['tag']];
				$names = array_keys($sets);
				foreach($names as $name)
				{
					if (in_array($name, $classes))
					{
						$styles = array_merge($styles, $sets[$name]);
					}
				}
			}
		}
		else
		{
			$d = explode(':', $value);
			if (2 === COUNT($d))
			{
				$styles = array_merge($styles, array(trim($d[0]) => trim($d[1])));
			}
		}
	}
	$content = '';
	foreach($styles as $key => $value)
	{
		$content .= $key . ':' . $value . ';';
	}

    return $content;
}