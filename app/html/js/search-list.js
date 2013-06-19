/**
 * リスト要素のキーワード絞り込みテキスト追加
 *
 * <pre>
 *  以下の例に従いhtmlの&lt;head"&gt;タグ内に以下の記述を追加
 *  &lt;script type="text/javascript" src="../js/jquery.min.js"&gt;&lt;/script&gt;
 *  &lt;script type="text/javascript" src="../js/search-select.js"&gt;&lt;/script&gt;
 *  &lt;script type="text/javascript"&gt;
 *  	// リスト要素
 *  	creatSearchListText('now_project', '1' , '70%', 'now_project_keyword');
 *  &lt;/script&gt;
 * </pre>
 *
 * @param targetListName 絞り込み対象のselect部品の名前(name属性)
 * @param searchedListSize 絞り込み対象のリスト要素の行数(size属性)
 * @param searchedListWidth 絞り込み対象のリスト要素の横幅(width属性)
 * @param keywordTextName 絞り込みキーワードとして使用するテキスト部品のid(id属性)
 *
 */
// 絞り込み対象が存在しなかった場合のテキスト
var unSearchedText = "（該当無し）";
// 絞り込み後のリスト用領域要素IDの接尾語
var searchedListAreaIdPostfix = "_return";
// 絞り込み後のリスト要素IDの接尾語
var searchedListIdPostfix   = "_searched";

function creatSearchListText(targetListName, searchedListSize, searchedListWidth, keywordTextName) {

	var searchedListAreaId = targetListName + searchedListAreaIdPostfix;
	var searchedListId = targetListName + searchedListIdPostfix;
	// 要素が見つからない場合は処理抜け
	var listElement = 'select#' + targetListName ;
	if ($(jQEscape(listElement)) === 'undefined') {
		return;
	}
	$(jQEscape(listElement)).ready(
			function() {
				var targetList = $(jQEscape(listElement));
				var keywordText = $(jQEscape('#' + keywordTextName));
				var searchedList = $(jQEscape("select#" + searchedListId));
				// 要素が見つからない場合は処理抜け
				if (keywordText === 'undefined') {
					return;
				}
				// セレクトボックスの横幅のデフォルト値
				if (typeof searchedListWidth === 'undefined') {
					return;
				}
				// キーワード入力項目とリスト用の領域を定義
				targetList.parent().append('<span id="' + searchedListAreaId + '"></span>');
				targetList.attr('id', targetListName);
				targetList.css({
					'display' : 'none',
					'visibility' : 'hidden'
				});
				// 初期表示時用のリスト要素を作成
				var  keywordTextvalue = keywordText.val();
				if (keywordTextvalue === 'undefined') {
					keywordTextvalue = '';
				}
				// 絞り込み後リストを生成
				makeSearchedList(keywordTextvalue);

				// ------------------------------------------
				// イベント定義
				// ------------------------------------------
				// キーワード入力イベント キーアップ、変更時
				keywordText.keyup(function() {
					makeSearchedList($(this).val());
					syncTargetList();
				});
				keywordText.change(function() {
					makeSearchedList($(this).val());
					syncTargetList();
				});
				// キーワード入力イベント フォーカスアウト
				keywordText.blur(function() {
					// 絞り込み対象リストにonChangeイベンントが有る場合,起動
					if (!(targetList.attr('onchange') === 'undefined')) {
						targetList.change();
					}
					syncTargetList();
				});
				// 絞り込み後リストイベント 変更時
				$(jQEscape("select#" + searchedListId)).change(function() {
					// 絞り込み対象リストにonChangeイベンントが有る場合,起動
					if (!(targetList.attr('onchange') === 'undefined')) {
						targetList.change();
					}
				});

				// 指定のキーワードでリストを絞込まれた要素でリストを生成
				function makeSearchedList(text) {
					var res = '';
					// 初期設定
					targetList.children("option[text*=" + text + "]").each(function() {
						// 初期値が未設定の場合のみ
						if ((typeof $(jQEscape("select#" + searchedListId)).val()==='undefined') && $(this).val() ==targetList.val()) {
							res += '<option value="' + $(this).val() + '" selected>' + $(this).text() + '</option>';
						} else {
							res += '<option value="' + $(this).val() + '">' + $(this).text() + '</option>';
						}
					});

					// 該当データが存在しない場合
					if (res == '') {
						res += '<option value="0">' + unSearchedText + '</option>';
					}
					var html = '';
					html += '<select value="" size=' + searchedListSize + ' id="' + jQEscape(searchedListId)  + '" onchange="$(\'select#'
							+ jQEscape(jQEscape(targetListName)) + '\').val($(this).val())" style="width:' + searchedListWidth + ';" >';
					html += res;
					html += '</select>';
					$(jQEscape("span#" + searchedListAreaId)).html(html);
				}

				// 絞り込み対象リスト（非表示）と絞り込み後リスト（表示）の選択要素の同期を行う
				function syncTargetList() {
					if ((targetList.val($(jQEscape("select#" + searchedListId)).val())) == null) {
						targetList.val(0);
					}
				}
			});
	
	// 絞り込み条件に初期値設定時対応 初期値で絞り込み処理を起動
	$(jQEscape("select#" + searchedListId)).change(function() {
		// 絞り込み対象リストにonChangeイベンントが有る場合,起動
		if (!(targetList.attr('onchange') === 'undefined')) {
			targetList.change();
		}
	});
	
	
	
	
	// エスケープ
	function jQEscape(str)
	{
		return str.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
	}

}


/**
 * 検索対象リストの全件保持
 *
 * プルダウン連動で使用します。
 *
 * @param searchKeyId			絞り込みKEYとして使用する親プルダウンのid(id属性)を指定
 */
var keepAllList = {};	// 検索するリストを全件保持
function saveSearchList(searchListId)
{
	var i=0;

	$('#'+searchListId).children().each(function(){
		keepAllList[i]=$(this);
		i++;
	});
}
/**
 * プルダウン連動
 *
 * １．事前に検索するリストを[function saveSearchList]で保持しておいて下さい。
 * ２・親プルダウンのchangeイベントに指定します。
 * ３・子プルダウンのoptionタグには判別に使用する値をセットする任意のプロパティ[searchValueProperty]を追加して下さい。
 * ４．[<script type="text/javascript" src="../js/jquery.min.js"></script>]も使用します。
 *
 * @param searchKeyId			絞り込みKEYとして使用する親プルダウンのid(id属性)を指定
 * @param searchListId			絞り込みをする子プルダウンのid(id属性)を指定
 * @param searchValueProperty	子プルダウンで絞り込み判別に使用する値がセットされているプロパティ名
 * @param unsearchKeyValue		親プルダウンで絞り込み処理の対象外とするvalue値をセットして下さい。（「全て」など）
 * @param unsearchListValue		子プルダウンでどの絞り込みでも固定表示させたい行のvalue値をセットして下さい。（「全て」など）
 */
function createSearchListId(searchKeyId, searchListId, searchValueProperty, unsearchKeyValue, unsearchListValue)
{
	var searchKey	= '#'+searchKeyId;
	var searchList	= '#'+searchListId;

	// 検索KEYにする値の取得
	var key_value = $(searchKey).find('option:selected').val();
	// 現在選択されているリストの値の取得
	var list_value = $(searchList).find('option:selected').val();
	// 絞り込み後の初期選択行の判別フラグ
	var list_select_flg = false;

	// プルダウン要素全削除
	$(searchList).children().remove();

	// プルダウン要素再生成
	jQuery.each(keepAllList, function(i, element) {

		var show_flg = true;	// 表示判別フラグ

		// 表示判別
		if(key_value!=unsearchKeyValue){
			// KEYが絞り込み対象の値の時だけ絞込みを行う（=「全て」など絞り込み対象外を選択した場合は全件表示対象）
			if(element.val()!=unsearchListValue){
				// 行の値が絞り込み対象の時だけ絞り込みを行う（=「全て」など絞り込み対象外の行は常時表示対象）
				show_flg = matchValue(key_value, element.attr(searchValueProperty));
			}
		}

		// 表示対象行の時
		if(show_flg){
			// 要素を追加
			$(searchList).append(element);

			// 表示対象内に現在表示している行がある場合はフラグON
			if(list_value == element.val()){
				list_select_flg = true;
			}
		}
	});

	// プルダウンの初期選択行をセット
	if(list_select_flg){
		// 絞り込み前に選択していた行が絞り込み後も存在すればその行を表示
		$(searchList).val(list_value);
	}
	else{
		// 行が存在しない場合は選択行リセット
		$(searchList).attr('selectedIndex', 0);
	}
}
/**
 * 値がKEYと一致するか判別
 *
 * @param key
 * @param value
 * @return boolean
 */
var matchValue = function(key, value)
{
	var matched = false;

	if(key == value){
		matched = true;
	}
	return matched;
};


