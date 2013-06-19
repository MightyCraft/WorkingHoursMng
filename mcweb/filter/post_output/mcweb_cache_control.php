<?php

/**
 * Cache-Controlヘッダを出力します。
 */
class mcweb_cache_control implements MCWEB_filter_post_output
{
	function run($template_path, &$str)
	{
		if ('SOFTBANK' === CLIENT_CARRIER)
		{
			header('Cache-Control: private');
		}
	}
}

?>