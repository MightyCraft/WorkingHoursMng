<?php

/**
 * 処理にかかった時間を、HTMLの末尾に表示します。
 */
class mcweb_debug_output_execution_time implements MCWEB_filter_post_output
{
	function run($template_path, &$str)
	{
		if (DEVELOPMENT)
		{
			$time = microtime(true) - FRAMEWORK_START_MICROTIME;
			$n = strrpos($str, '</body>');
			if (FALSE !== $n)
			{
				$str = substr_replace($str, '<br />' . $time, $n, 0);
			}
		}
	}
}

?>