<?php

define('MCWEB_CARRIER_OTHER',		0);
define('MCWEB_CARRIER_DOCOMO',		(1 << 1));
define('MCWEB_CARRIER_SOFTBANK',	(1 << 2));
define('MCWEB_CARRIER_AU',			(1 << 3));
define('MCWEB_CARRIER_EMOBILE',		(1 << 4));
define('MCWEB_CARRIER_IPHONE',		(1 << 5));
define('MCWEB_CARRIER_IPAD',		(1 << 6));

define('MCWEB_DISPLAY_SCALE_QVGA',	1);
define('MCWEB_DISPLAY_SCALE_VGA',	2);

class MCWEB_Mobile
{
	var $carrier_mask;
	var $carrier;
	var $device;
	var $uid;

	function __construct($carrier_mask)
	{
		$this->carrier_mask = $carrier_mask;
	}


	/**
	キャリア識別関数.
	*/
	public function carrier()
	{
		if (isset($this->carrier))
		{
			return $this->carrier;
		}

		$this->carrier = MCWEB_CARRIER_OTHER;

		// ユーザーエージェントで判別
		$user_agent = getenv('HTTP_USER_AGENT');
		if (preg_match('/^DoCoMo/', $user_agent))									$this->carrier = ($this->carrier_mask & MCWEB_CARRIER_DOCOMO) ? MCWEB_CARRIER_DOCOMO : MCWEB_CARRIER_OTHER;
		elseif (preg_match('/^(J\-PHONE|Vodafone|SoftBank|MOT\-)/', $user_agent))	$this->carrier = ($this->carrier_mask & MCWEB_CARRIER_SOFTBANK) ? MCWEB_CARRIER_SOFTBANK : MCWEB_CARRIER_OTHER;
		elseif (preg_match('/^(KDDI\-|UP\.Browser)/', $user_agent))					$this->carrier = ($this->carrier_mask & MCWEB_CARRIER_AU) ? MCWEB_CARRIER_AU : MCWEB_CARRIER_OTHER;
		elseif (preg_match('/^emobile/', $user_agent))								$this->carrier = ($this->carrier_mask & MCWEB_CARRIER_EMOBILE) ? MCWEB_CARRIER_EMOBILE : MCWEB_CARRIER_OTHER;
		elseif (preg_match('/\(iPhone\;/', $user_agent))								$this->carrier = ($this->carrier_mask & MCWEB_CARRIER_IPHONE) ? MCWEB_CARRIER_IPHONE : MCWEB_CARRIER_OTHER;
		elseif (preg_match('/\(iPad\;/', $user_agent))								$this->carrier = ($this->carrier_mask & MCWEB_CARRIER_IPAD) ? MCWEB_CARRIER_IPAD : MCWEB_CARRIER_OTHER;

		return $this->carrier;
	}

	/**
	デバイス識別関数.
	*/
	public function device()
	{
		if (isset($this->device))
		{
			return $this->device;
		}

		switch($this->carrier())
		{
		case MCWEB_CARRIER_DOCOMO:
			$agent = $_SERVER{'HTTP_USER_AGENT'};
			if(FALSE !== strpos($agent, 'DoCoMo/1.0') && FALSE !== strpos($agent, '/', 11))
			{
				$this->device = substr($agent, 11, (strpos($agent, '/', 11) - 11));
			}
			else if(FALSE !== strpos($agent, 'DoCoMo/2.0') && FALSE !== strpos($agent, '(', 11))
			{
				$this->device = substr($agent, 11, (strpos($agent, '(', 11) - 11));
			}
			else
			{
				$this->device = substr($agent, 11);
			}
			break;
		case MCWEB_CARRIER_SOFTBANK:
			$this->device = $_SERVER{'HTTP_X_JPHONE_MSNAME'};
			break;
		case MCWEB_CARRIER_AU:
			$agent = $_SERVER{'HTTP_USER_AGENT'};
			$this->device = substr($agent, (strpos($agent, '-') + 1), (strpos($agent, ' ') - strpos($agent, '-') - 1));
			break;
		case MCWEB_CARRIER_IPHONE:
			$this->device = 'iPhone';
			break;
		case MCWEB_CARRIER_IPAD:
			$this->device = 'iPad';
			break;
		case MCWEB_CARRIER_OTHER:
			$this->device = 'PC';
			break;
		}
		return $this->device;
	}

	/**
	ユニークID取得関数.
	*/
	public function uid()
	{
		//	SSL通信ではUIDは取得できない
		if (isset($_SERVER['HTTPS']))
		{
			return '';
		}

		if (isset($this->uid))
		{
			return $this->uid;
		}

		$this->uid = '';
		switch($this->carrier())
		{
			case MCWEB_CARRIER_DOCOMO:
				if ('' != getenv('HTTP_X_DCMGUID'))
				{
					$this->uid = getenv('HTTP_X_DCMGUID');
				}
				else if (!empty($_GET['uid']))
				{
					//	NULLGWDOCOMOは、12文字ではない長さの時、強制変換を行ってくれないので正しく12文字であることを確認する必要がある。
					$this->uid = $_GET['uid'];
					if (0 == preg_match('/^[a-zA-Z0-9]+$/', $_GET['uid']) || 12 != strlen($_GET['uid']))
					{
						throw new MCWEB_BadRequestException();
					}
				}
				else if (!empty($_POST['uid']))
				{
					//	NULLGWDOCOMOは、12文字ではない長さの時、強制変換を行ってくれないので正しく12文字であることを確認する必要がある。
					if (0 == preg_match('/^[a-zA-Z0-9]+$/', $_POST['uid']) || 12 != strlen($_POST['uid']))
					{
						throw new MCWEB_BadRequestException();
					}
					$this->uid = $_POST['uid'];
				}
				break;
			case MCWEB_CARRIER_SOFTBANK:
				$this->uid = getenv('HTTP_X_JPHONE_UID');
				break;
			case MCWEB_CARRIER_AU:
				$this->uid = getenv('HTTP_X_UP_SUBNO');
				break;
			case MCWEB_CARRIER_EMOBILE:
				$this->uid = getenv('HTTP_X_EM_UID');
				break;
			case MCWEB_CARRIER_IPHONE:
				$this->uid = getenv('REMOTE_ADDR');
				break;
			case MCWEB_CARRIER_IPAD:
				$this->uid = getenv('REMOTE_ADDR');
				break;
			case MCWEB_CARRIER_OTHER:
				$this->uid = getenv('REMOTE_ADDR');
				break;
		}
		return $this->uid;
	}

	/**
	 * ディスプレイの解像度が、VGAかQVGAかを調べる。
	 * 正しくVGAと判定されない限りは、QVGAを返す。
	 */
	public function displayScale()
	{
		switch($this->carrier())
		{
		case MCWEB_CARRIER_DOCOMO:
			{
				return MCWEB_DISPLAY_SCALE_QVGA;
			}
			break;
		case MCWEB_CARRIER_SOFTBANK:
			{
				$device = $this->device();

				if('911T' === $device || '920T' === $device )
				{
					// 例外端末処理
					return MCWEB_DISPLAY_SCALE_QVGA;
				}
				$size = explode('*', $_SERVER['HTTP_X_JPHONE_DISPLAY']);
				if (480 <= $size[0])
				{
					return MCWEB_DISPLAY_SCALE_VGA;
				}
			}
			break;
		case MCWEB_CARRIER_AU:
			{
				return MCWEB_DISPLAY_SCALE_QVGA;
			}
			break;
		}
		return MCWEB_DISPLAY_SCALE_QVGA;
	}

	/**
	 * キャリア識別子から、対応した文字列へ変換する静的メソッド
	 */
	public static function carrier2string($carrier)
	{
		static $arr = array(
			MCWEB_CARRIER_OTHER		=> 'OTHER',
			MCWEB_CARRIER_DOCOMO	=> 'DOCOMO',
			MCWEB_CARRIER_SOFTBANK	=> 'SOFTBANK',
			MCWEB_CARRIER_AU		=> 'AU',
			MCWEB_CARRIER_EMOBILE	=> 'EMOBILE',
			MCWEB_CARRIER_IPHONE	=> 'IPHONE',
			MCWEB_CARRIER_IPAD		=> 'IPAD',
			);
		return $arr[$carrier];
	}
}
?>