<?php
/**
 * 定数定義ファイル
 *
 * システム固定の定数定義をする箇所です。
 * 利用環境に応じて設定変更される事は想定していません。
 *
 * user.define.ini.phpでは「USER_」で始まるようにしているので
 * 「USER_」から始まる定数は使わないで下さい。
 */

// プロジェクトタイプ設定
setProjectTypeDefine();
// 予算タイプ設定
define('BUDGET_TYPE_CONTENTS',		1);	// 新規開発などの品物に対する予算
define('BUDGET_TYPE_TIME',			2);	// 月額契約などの期間に対する予算（継続案件系）
define('BUDGET_TYPE_NOT_MANAGE',	3);	// 社内会議などの予算管理外プロジェクト
// プロジェクトコード自動採番の再トライ回数制限
define('PROJECT_CODE_AUTO_LIMIT', 100);
// 原価率を各計算式で使用する際は、この定数で割った値を使用します。
define('COST_RATE_BREAK', 100);



?>