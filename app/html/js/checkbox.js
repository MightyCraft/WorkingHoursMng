/**
 * チェックボックス
 */
/*
function allChange(id){
	var i;
	if(id){

		for(i = 0; i < document.form1.elements["id[]"].length; i++)
		{
			document.form1.elements["id[]"][i].checked = true;
		}
	}
	else
	{
		for(i = 0; i < document.form1.elements["id[]"].length; i++)
		{
			document.form1.elements["id[]"][i].checked = false;
		}
	}
}
*/
function allChange(cbid)
{
	var elem = document.form1.elements['cbid[]'];
	for(var i = 0; i < elem.length; i++)
	{
		elem[i].checked = cbid;
	}


}

/**
 * チェックボックスをすべてチェック/チェック解除する
 *
 */
var turnCheckbox = function(check)
{
	var target = "#checkbox-list input[type='checkbox']:visible";

	$(target).attr('checked', check);
	return false;
};

