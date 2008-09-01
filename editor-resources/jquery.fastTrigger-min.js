/**
 * jQuery.FastTrigger - Faster event triggering for jQuery.
 * Copyright (c) 2008 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 4/5/2008
 * @author Ariel Flesler
 * @version 1.0.0
 */
;(function($){var h={pageX:0,pageY:0,which:0,button:0,metaKey:!1,ctrlKey:!1,charCode:' ',keyCode:0,preventDefault:function(){},stopPropagation:function(){}};$.fn.fastTrigger=function(c,d){var e=h,f,g=1;if(!d||!d.length)d=null;else if(d[0].preventDefault)e=d[0];else d.unshift(e);if(c.indexOf('!')!=-1){g=0;c=c.slice(0,-1)}f=c.split('.');e.type=c=f[0];g&=!(f=f[1]);return this.each(function(){var a=($.data(this,'events')||{})[c],b;if(a){e.target=e.relatedTarget=this;for(var i in a){b=a[i];if(g||b.type==f){e.data=b.data;if(d)b.apply(this,d);else b.call(this,e)}}}})};$.fastTrigger=function(a,b){$(document.getElementsByTagName('*')).add([window,document]).fastTrigger(a,b)}})(jQuery);