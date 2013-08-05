/*

tooltip.js

 */

(function($) {

	// XXX ローカル
	// ツールチップ関連
	var showInterval = 500;
	var backgroundColor = "#FFF";
	var tooltipElement;
	var pxY = 0;
	var pxX = 0;
	var timerId;

	// 改行処理
	var nl2br = function(string)
	{
		string = String(string);
		string = string.replace(/\r\n/g, "<br />");
		string = string.replace(/(\n|\r)/g, "<br />");
		return string;
	};

	// ツールチップを表示
	var showTooltip = function(element)
	{
		var text = element.attr('message');

		tooltipElement
			.html(nl2br(text))
			.css({
				"position": "fixed",
				"top": pxY +5,
				"left": pxX +10,
				"z-index": 99,
				"background-color":backgroundColor
			})
			.show();
	};
	var cancelTimer = function()
	{
		if(0 < timerId)
		{
			clearTimeout(timerId);
			timerId = 0;
		}
	};
	var enterTooltipTrigger = function(element, event)
	{
		// 座標に変化があれば
		if(pxY != event.clientY || pxX != event.clientX)
		{
			pxY = event.clientY;
			pxX = event.clientX;
			cancelTimer();
			tooltipElement.hide();
			timerId = setTimeout(function(){
				console.log(element);
				showTooltip(element);
			}, showInterval);
		}
	};

	// グローバル格納領域大元
	$.vars = {};
	$.functions = {};

	/**
	 * 簡易ツールチップ
	 */
	$.vars.tooltip = {};
	$.functions.tooltip = function(trigger, display)
	{
		tooltipElement = $(display);
		
		$(trigger).bind({
			"mouseenter": function(event){
				enterTooltipTrigger($(this), event);
			},
			"mousemove": function(event){
				enterTooltipTrigger($(this), event);
			},
			"mouseleave": function(event){
				cancelTimer();
				tooltipElement.hide();
				pxY = 0;
				pxX = 0;
			}
		});
	};

	$(document).ready(function(){
	});

})(jQuery);
