<?php

	//権限レベル定数クラス
	class AuthLvDefine
	{
		const	NORMAL			= 0;		//一般
		const	HEAD			= 1;		//主任
		const	MANAGER			= 2;		//課長
		const	GENERAL_MANAGER	= 3;		//部長
		const	DIRECTOR		= 4;		//室長
		const	PRESIDENT		= 5;		//社長
	}

	// 所属タイプ
	class PostTypeDefine
	{
		const	ADMINISTRATION		= 1;		//経営企画部
		const	SALES				= 2;		//営業部
		const	GENE_PRODUCT		= 3;		//制作部
	}
	
	// 管理者フラグ
	class AdministratorFlgDefine
	{
		const	ADMINISTRATOR_FLG_OFF	= 0;		//管理者権限 有 
		const	ADMINISTRATOR_FLG_ON	= 1;		//管理者権限 無
	}

	// PJコードタイプ定数クラス
	function setProjectTypeDefine()
	{
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			define('PROJECT_TYPE_NORMAL',	0);	// 通常
			define('PROJECT_TYPE_INFORMAL',	1);	// 仮登録PJコード
			define('PROJECT_TYPE_REMOVAL',	3);	// 廃止PJコード
			define('PROJECT_TYPE_BACK',		2);	// 後発作業用コード
		}
		else
		{
			// 通常環境
			define('PROJECT_TYPE_NORMAL',	1);
			define('PROJECT_TYPE_INFORMAL',	2);
			define('PROJECT_TYPE_REMOVAL',	3);
			define('PROJECT_TYPE_BACK',		4);	// 後発作業用コード(通常環境ではこのタイプのマスタデータは存在しないが定義は必要)
		}
	}

	//権限
	function returnArrayAuthLv()
	{
		$obj_authority = new Authority();
		$data_all = $obj_authority->getDataAll();
		$array = array();
		foreach($data_all as $key => $data)
		{
			$array[$key] = $data['name'];
		}
		return $array;
	}

	//所属タイプ
	function returnArrayPostType()
	{
		$mm = MessageManager::getInstance();
		$array = array(
			PostTypeDefine::ADMINISTRATION		=> $mm->sprintfMessage(MessageDefine::USER_POST_TYPE_NAME_1),
			PostTypeDefine::SALES				=> $mm->sprintfMessage(MessageDefine::USER_POST_TYPE_NAME_2),
			PostTypeDefine::GENE_PRODUCT		=> $mm->sprintfMessage(MessageDefine::USER_POST_TYPE_NAME_3),
		);

		return $array;
	}

	// PJコードタイプ
	function returnArrayPJtype()
	{
		$mm = MessageManager::getInstance();
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			$array = array(
				PROJECT_TYPE_NORMAL		=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_NORMAL),
				PROJECT_TYPE_INFORMAL	=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_INFORMAL),
				PROJECT_TYPE_BACK		=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_BACK),
				PROJECT_TYPE_REMOVAL	=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_REMOVAL),
			);
		}
		else
		{
			// 通常環境
			$array = array(
				PROJECT_TYPE_NORMAL		=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_NORMAL),
				PROJECT_TYPE_INFORMAL	=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_INFORMAL),
				PROJECT_TYPE_REMOVAL	=> $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_REMOVAL),
			);

		}
		return $array;
	}

	// 予算タイプ
	function returnArrayBudgetType()
	{
		$mm = MessageManager::getInstance();
		$array = array(
			BUDGET_TYPE_CONTENTS	=> $mm->sprintfMessage(MessageDefine::USER_BUDGET_TYPE_NAME_CONTENTS),
			BUDGET_TYPE_TIME		=> $mm->sprintfMessage(MessageDefine::USER_BUDGET_TYPE_NAME_TIME),
			BUDGET_TYPE_NOT_MANAGE	=> $mm->sprintfMessage(MessageDefine::USER_BUDGET_TYPE_NAME_NOT_MANAGE),
		);

		return $array;
	}

?>