<?php
/**
 * コスト工数集計処理
 *
 */
require_once(DIR_APP . "/class/common/ManhourList.php");
class _commandline_cron_cost extends PostAndGetScene
{
	var $_date;
	
	function check()
	{
		date_default_timezone_set("Asia/Tokyo");
		
		// 集計日(指定が無ければ当日)
		if(isset($_SERVER['argv'][2]))
		{
			$this->date = $_SERVER['argv'][2];
		}
		else if ($this->_date)
		{
			$this->date = $this->_date;
		}
		else
		{
			$this->date = date('Y-m-d 00:00:00');
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		echo "start=" . date('Y-m-d H:i:s') . "\n";
		
		// 実行時間制限を無効
		ini_set('max_execution_time', 0);
		// メモリの上限設定を無効にする
		ini_set("memory_limit", "-1");
		
		// 現在有効なプロジェクトを取得
		$manhour_list_obj = new ManhourList();
		$manhour_list_obj->usedCostTotal($this->date);
		
		echo "end=" . date('Y-m-d H:i:s') . "\n";
		exit;
	}
}

?>