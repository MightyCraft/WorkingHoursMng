<?php

class MCWEB_UrlParam implements MCWEB_InterfaceUrlParam
{
	var $url_params;

	function __construct($url_params)
	{
		$this->url_params = $url_params;
	}

	function get($type, $key)
	{
		if (isset($this->url_params['GET'][$key]))
		{
			return $this->input($type, $this->url_params['GET'][$key]);
		}
	}

	function post($type, $key)
	{
		if (isset($this->url_params['POST'][$key]))
		{
			return $this->input($type, $this->url_params['POST'][$key]);
		}
	}

	function cookie($type, $key)
	{
		if (isset($this->url_params['COOKIE'][$key]))
		{
			return $this->input($type, $this->url_params['COOKIE'][$key]);
		}
	}

	function forward($type, $key)
	{
		if (isset($this->url_params['FORWARD'][$key]))
		{
			return $this->input($type, $this->url_params['FORWARD'][$key]);
		}
	}

	function validate_binary($input)
	{
		return $input;
	}
	function validate_string($input)
	{
		//	NULLバイト削除
		return str_replace("\0", "", $input);
	}
	function validate_int($input)
	{
		return intval($input);
	}
	function validate_float($input)
	{
		return floatval($input);
	}


	private function input($type, $input)
	{
		if (is_array($input))
		{
			foreach($input as $key => $value)
			{
				if (is_array($value))
				{
					$input[$key] = $this->input($type, $value);
				}
				else
				{
					switch($type)
					{
						case VAR_TYPE_BINARY:	$input[$key] = $this->validate_binary($value);	break;
						case VAR_TYPE_STRING:	$input[$key] = $this->validate_string($value);	break;
						case VAR_TYPE_INT:		$input[$key] = $this->validate_int($value);		break;
						case VAR_TYPE_FLOAT:	$input[$key] = $this->validate_float($value);	break;
					}
				}
			}
			return $input;
		}
		else
		{
			switch($type)
			{
				case VAR_TYPE_BINARY:	return $this->validate_binary($input);
				case VAR_TYPE_STRING:	return $this->validate_string($input);
				case VAR_TYPE_INT:		return $this->validate_int($input);
				case VAR_TYPE_FLOAT:	return $this->validate_float($input);
			}
			return null;
		}
	}

}