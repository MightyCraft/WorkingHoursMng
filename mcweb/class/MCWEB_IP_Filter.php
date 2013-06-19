<?php

class MCWEB_IP_Filter
{
	var $filters;

	function load($filename)
	{
		$filters = array();

		$handle = fopen($filename, "r");
		if (FALSE == $handle)
		{
			return FALSE;
		}

		$name = "";
		while(FALSE != ($line = fgets($handle)))
		{
			$line = trim($line);
			if ('' == $line)
			{
				continue;
			}
			if ('$' == $line[0])
			{
				$name = substr($line, 1);
			}
			else
			{
				$parts = explode("/", $line);
				if (2 != count($parts))
				{
					fclose($handle);
					return FALSE;
				}

				$ip = ip2long($parts[0]);
				$mask = intval($parts[1]);
				if ($mask < 0 || 32 < $mask)
				{
					fclose($handle);
					return FALSE;
				}
				if (0 == $mask)		$mask = 0;
				else				$mask = 0xffffffff << (32 - $mask);
				$ip &= $mask;

				array_push($filters, array('ip' => $ip, 'mask' => $mask, 'name' => $name));
			}
		}
		fclose($handle);

		$this->filters = $filters;
		return TRUE;
	}

	function check($ip)
	{
		$ip = ip2long($ip);
		foreach($this->filters as $value)
		{
			if (($ip & $value['mask']) == $value['ip'])
			{
				return $value['name'];
			}
		}
		return FALSE;
	}
}
?>