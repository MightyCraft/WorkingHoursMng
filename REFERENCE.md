# REFERENCE リファレンス

## アプリケーション構成

### ■ディレクトリ構成

    app         :アプリケーションソース格納ディレクトリ
    cache       :各種キャッシュファイル格納ディレクトリ
    log         :ログファイル格納ディレクトリ
    documentroot:公開用ディレクトリ
    mcweb       :MCWEBフレームワークソース格納ディレクトリ

## 各種設定

利用環境に応じて設定してもらう定数定義をする箇所についての説明です。

### ■システム設定 (app/define/user_config/user.define.ini.php)

* **USER_SYSTEM_TITLE**
  システムタイトル名を設定します。
  
#### 入力文字制限設定

* **USER_POST_NAME_MAX** 
  部署名上限文字数

* **USER_AUTHORITY_NAME_MAX** 
  権限名上限文字数

* **USER_MEMBER_TYPE_NAME_MAX** 
  社員タイプ名上限文字数

* **USER_MEMBER_COST_NAME_MAX** 
  社員コスト名上限文字数

* **USER_MEMBER_CODE_MAX**
  社員コード上限文字数
  
* **UUSER_MEMBER_CODE_MIN**
  社員コード上限文字数
  
* **UUSER_MEMBER_CODE_FORMAT**
  社員コード書式（正規表現/不要時は''をセット）
  
* **UUSER_MEMBER_NAME_MAX**
  社員名上限文字数
  
* **UUSER_MEMBER_PASSWORD_MAX**
  パスワード上限文字数
  
* **UUSER_CLIENT_NAME_MAX**
  クライアント名上限文字数

* **USER_CLIENT_MEMO_MAX**
  クライアント備考上限文字数

* **UUSER_PROJECT_CODE_MAX**
  プロジェクトコード上限文字数
  
* **USER_PROJECT_CODE_MIN**
  プロジェクトコード下限文字数

* **USER_PROJECT_CODE_FORMAT**
  プロジェクトコード書式（正規表現/不要時は''をセット）

* **USER_PROJECT_CODE_AUTO_CREATE**
  プロジェクトコード自動コード生成（仮コード時に使用）

* **USER_PROJECT_NAME_MAX**
  プロジェクト名上限文字数
  
* **USER_PROJECT_NOUKI_MAX**
  プロジェクト納期上限文字数
  
* **USER_PROJECT_MEMO_MAX**
  プロジェクト備考上限文字数
  
* **USER_MANHOUR_MEMO_MAX**
  工数データ備考上限文字数

#### エクセル工数表出力
* **USER_EXCEL_ROW_NUM**
  出力行数

#### キャッシュ有効時間

* **USER_CACHE_LOGIN_MEMBER_ID**
  ログイン時の初期表示ユーザID
  
* **USER_CACHE_LOGIN_STATE**
  ログイン状態の保持


### ■メッセージ設定 (app/define/user_config/user.message.csv)

* **USER_DUMMY_MESSAGE**
  定義例でシステムでは使用しません。
  新たに定義を追加する場合は、定義名には必ず「USER_」から始まる値を設定してください
  
* **USER_ERR_MESSAGE_PROJECT_CODE** プロジェクトコードの書式エラー時メッセージ
  
* **USER_GUIDE_MESSAGE_PROJECT_CODE_FORMAT**  プロジェクトコードの表示用書式定義
  
* **USER_ERR_MESSAGE_MEMBER_CODE**  社員コードの書式エラー時メッセージ
  
* **USER_GUIDE_MESSAGE_MEMBER_CODE_FORMAT**  社員コードの表示用書式定義

#### 部署タイプ1～3名称

* **USER_POST_TYPE_NAME_1**  部署タイプ1
  
* **USER_POST_TYPE_NAME_2**  部署タイプ2
  
* **USER_POST_TYPE_NAME_3**  部署タイプ3


### ■ip帯域のフィルタリング設定 (app/define/filter.txt)

アクセス可能なIP帯域を定義

##### 設定方法
  
$DEBUG以下に接続を許可するIP帯域を定義します。
また全てのアクセスを許可する場合は*を指定して下さい。

設定例1）
127.0.0.1と192.168.1.*からのアクセスを許可する場合

    $DEBUG
    127.0.0.1/32
    192.168.1.0/24

設定例2）
すべてのアクセスを許可する場合

    $DEBUG
    *

### ■システムロゴ設定

システムロゴ設定 （ヘッダ左上ロゴ画像）の変更が可能です。

##### 設定方法
  `app/html/img/logo_header.gif`の画像を差し替えることで変更可能