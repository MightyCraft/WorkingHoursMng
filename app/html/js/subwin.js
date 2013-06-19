
/*  ウィンドウを開く
 *--------------------------------------------------------------------------*/
function OpenWin(html_temp){
    win=window.open(html_temp,"new","width=500,height=700");
}


/*  サブウィンドウから親ウィンドウのURLを変更する
 *--------------------------------------------------------------------------*/
function ctrlWin() {
    window.opener.location.href = "site.html";
}


/*  ウィンドウを閉じる
 *--------------------------------------------------------------------------*/
function CloseWin(){
    //window.close();
	var nvua = navigator.userAgent;
		if(nvua.indexOf('MSIE') >= 0)
		{
			if(nvua.indexOf('MSIE 5.0') == -1)
			{
				top.opener = '';
			}
		}
		else if(nvua.indexOf('Gecko') >= 0)
		{
			top.name = 'CLOSE_WINDOW';
			wid = window.open('','CLOSE_WINDOW');
		}
	top.close();

}

/*  クリックしたら親ウィンドウのURLを変えて自分自身を閉じる
 *--------------------------------------------------------------------------*/
function subWinClose(html_temp) {
    window.opener.location.href = html_temp; //　親ウィンドウに表示するURL
    self.window.close();
} 