<?php
require_once(DIR_APP . "/class/common/dbaccess/Post.php");

	// エクセル合計値マクロ用データ－列計（プロジェクト計）
	function returnArrayExcelColSum()
	{
		$array = array(
			'D7:AH7',
			'D8:AH8',
			'D9:AH9',
			'D10:AH10',
			'D11:AH11',
			'D12:AH12',
			'D13:AH13',
			'D14:AH14',
			'D15:AH15',
			'D16:AH16',
			'D17:AH17',
			'D18:AH18',
			'D19:AH19',
			'D20:AH20',
			'D21:AH21',
			'D22:AH22',
			'D23:AH23',
			'D24:AH24',
			'D25:AH25',
			'D26:AH26',
			'D27:AH27',
			'D28:AH28',
			'D29:AH29',
			'D30:AH30',
			'D31:AH31',
			'D32:AH32',
			'D33:AH33',
			'D34:AH34',
			'D35:AH35',
			'D36:AH36',
		);

		return $array;
	}

	//エクセル合計値マクロ用データ－行計（日計）
	function returnArrayExcelRowSum()
	{
		$array = array(
			'D7:D36',
			'E7:E36',
			'F7:F36',
			'G7:G36',
			'H7:H36',
			'I7:I36',
			'J7:J36',
			'K7:K36',
			'L7:L36',
			'M7:M36',
			'N7:N36',
			'O7:O36',
			'P7:P36',
			'Q7:Q36',
			'R7:R36',
			'S7:S36',
			'T7:T36',
			'U7:U36',
			'V7:V36',
			'W7:W36',
			'X7:X36',
			'Y7:Y36',
			'Z7:Z36',
			'AA7:AA36',
			'AB7:AB36',
			'AC7:AC36',
			'AD7:AD36',
			'AE7:AE36',
			'AF7:AF36',
			'AG7:AG36',
			'AH7:AH36',
			'AI7:AI36',
		);

		return $array;
	}

?>