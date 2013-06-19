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

// プロジェクトコード自動採番の再トライ回数制限
define('PROJECT_CODE_AUTO_LIMIT', 100);

?>