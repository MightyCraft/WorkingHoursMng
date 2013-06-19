chMenu=0;
function subopen(tName){
	tMenu=(document.all)?document.all(tName).style:document.getElementById(tName).style;
	if(chMenu)chMenu.display="none";
	if(chMenu==tMenu){
		chMenu=0;
	}
	else{
		chMenu=tMenu;
		tMenu.display="block";
	}
}
