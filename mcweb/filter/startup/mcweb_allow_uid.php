<?php

/**
 * 'CLIENT_UID'から判断し、指定したUID以外からのアクセスをブロックします。
 * 指定したUID以外からのアクセスの場合、MCWEB_DenyUidExceptionをthrowします。
 *
 * 指定に'*'を使用した場合、どのUIDからのアクセスもブロックしません。
 */
class mcweb_allow_uid implements MCWEB_filter_startup
{
	protected $filter_text_path;

	/**
	 * コンストラクタ
	 * @apram $filter_text_path		UIDフィルタを記述したテキストファイルのパス
	 */
	function __construct($filter_text_path)
	{
		$this->filter_text_path = $filter_text_path;
	}

	function run($entry_path)
	{
		if (!defined('CLIENT_UID'))		throw new MCWEB_LogicErrorException('CLIENT_UIDが定義されていません');

		$handle = fopen($this->filter_text_path, "r");
		if (FALSE == $handle)
		{
			throw new MCWEB_LogicErrorException();
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
				if (CLIENT_CARRIER === $name)
				{
					//	クライアントキャリア帯が定義されているのに、UIDが見当たらなかった
					fclose($handle);
					throw new MCWEB_DenyUidException();
				}
				$name = trim(substr($line, 1));
			}
			else
			{
				$line = trim($line);

				if (('*' === $line || CLIENT_UID === $line) && CLIENT_CARRIER === $name)
				{
					//	UIDを発見した
					fclose($handle);
					return;
				}
			}
		}
		fclose($handle);
		if (CLIENT_CARRIER === $name)
		{
			//	クライアントキャリア帯が定義されているのに、UIDが見当たらなかった
			throw new MCWEB_DenyUidException();
		}

	}
}

?>