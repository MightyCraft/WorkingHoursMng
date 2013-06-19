/**
 * 要素の無効化制御
 *
 */

/**
 * ラジオボタンで制御
 *
 * @param	changeRadioClass		制御を行うラジオボタンのクラス名
 * @param	disableElementClass		無効化をする要素のクラス名
 * @param	disableValue			無効化を発生させるラジオボタンの値
 */
function changeRadioButton(changeRadioClass, disableElementClass, disableValue)
{
		var radio_class		= '.'+changeRadioClass;
		var element_class	= '.'+disableElementClass;

		var value =$(radio_class+":checked").val();

		// 無効にする値が選択されたら無効化。それ以外なら有効化
		if(value == disableValue){
			$(element_class).attr("disabled", true);
		}
		else
		{
			$(element_class).attr("disabled", false);
		}
}

