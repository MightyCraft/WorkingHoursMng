<?php
/**
 * 定数定義ファイル
 *
 * 利用環境に応じて設定してもらう定数定義をする箇所です。
 * 必ず定数名は「USER_」から開始するようにして下さい。
 */

/**
 * システムタイトル
 * ・htmlの<title>タグ、ヘッダ左上のLINKに使用しています。
 */
define('USER_SYSTEM_TITLE', 'WEB工数管理システム');


/**
 * 入力文字制限設定
 * ・上限文字数がカラムのlengthよりも増やす場合はテーブルの修正もして下さい
 */
// 部署マスタ関連
define('USER_POST_NAME_MAX',		64);		// 部署名上限文字数

// 社員マスタ関連
define('USER_MEMBER_CODE_MAX',		16);				// 社員コード上限文字数
define('USER_MEMBER_CODE_MIN',		1);					// 　　　　　下限文字数
define('USER_MEMBER_CODE_FORMAT',	'/^[0-9]+$/');		// 　　　　　書式（正規表現/不要時は''をセット）
define('USER_MEMBER_NAME_MAX',		32);		// 社員名上限文字数
define('USER_MEMBER_PASSWORD_MAX',	16);		// パスワード上限文字数

// クライアント名
define('USER_CLIENT_NAME_MAX',		64);		// クライアント名上限文字数
define('USER_CLIENT_MEMO_MAX',		5000);		// 備考上限文字数

// プロジェクトマスタ関連
define('USER_PROJECT_CODE_MAX',			10);																// プロジェクトコード上限文字数
define('USER_PROJECT_CODE_MIN',			10);																// 　　　　　　　　　下限文字数
define('USER_PROJECT_CODE_FORMAT',		'/^[a-zA-Z0-9\-]+$/');												// 　　　　　　　　　書式（正規表現/不要時は''をセット）
define('USER_PROJECT_CODE_AUTO_CREATE',	'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-');	// 　　　　　　　　　自動コード生成（仮コード時に使用）
define('USER_PROJECT_NAME_MAX',		256);		// プロジェクト名上限文字数
define('USER_PROJECT_NOUKI_MAX',	20);		// 納期上限文字数
define('USER_PROJECT_MEMO_MAX',		5000);		// 備考上限文字数

// 工数データ関連
define('USER_MANHOUR_MEMO_MAX',		128);		// 備考上限文字数


/**
 * エクセル工数表出力
 *
 */
define('USER_EXCEL_ROW_NUM', 30);	// 出力行数（固定）


/**
 * キャッシュ有効時間
 *
 */
// ログイン時の初期表示ユーザID
define('USER_CACHE_LOGIN_MEMBER_ID',	60*60*24*30);
// ログイン状態の保持
define('USER_CACHE_LOGIN_STATE',		60*60*24*30);


/**
 * 総予算から総割当工数の算出する計算式
 * ・eval関数を使用して計算します。
 * ・「%1$d」の位置にプロジェクトマスタの総予算がセットされます
 * ・小数点以下は四捨五入されます
 */
define('USER_TOTAL_BUDGET_MANHOUR_EQUATION', '%1$d / 10');


?>