/*
	A simple Plugin for JQuery to enhance its excellent CSS/XPath-like selectors.

	There are numerous debates on the JQuery forumm about the merits of these selectors.
	It is right to keep them out of the JQuery Base. They can be useful none the less, so
	perhaps there is a place for some of them in the Forms Plugin (until it gets moved into Base!)


	This plugin adds several new selectors to jQuery, including:

	Selector		- Usage example		- Description
	:hover			- $("DIV:hover")	- Find the element under the mouse.
	:focus			- $("INPUT:focus")	- Find the element that has the focus. This will typically be a <INPUT>, <TEXTAREA>, <SELECT>, <BUTTON> or <A> element.
	:blur			- $("INPUT:blur")	- Find the element that has just lost the focus. This will typically be a <INPUT>, <TEXTAREA>, <SELECT>, <BUTTON> or <A> element.
	:modified		- $("*:modified")	- Find <INPUT>, <TEXTAREA> or <SELECT> elements who's value or checked attribute has changed since the page loaded. (For <INPUT> elements, this only applies where type = text, file, hidden or password)
	:Contains		- $("DIV:Contains('some text')") - Same as :contains() but is case insensitive.
	:option			- $("*:option")		- Find multiple/choice form items: RADIO, CHECKBOX and OPTION elements.
	:option-def		- $("*:option-def") - Find <OPTION>, RADIO and CHECKBOX elements that were selected/checked originally, before changes were made.
	:option-sel		- $("*:option-sel") - Find <OPTION>, RADIO and CHECKBOX elements that are currently selected/checked.
	:option-mod		- $("*:option-mod") - Find <OPTION>, RADIO and CHECKBOX elements un/selected or un/checked since page loaded. Same as :modified but is for <OPTION>, radio and checkbox elements only.
	:text			- $("*:text")		- Find TEXT and TEXTAREA elements.		Equivalent to $("INPUT[@type='text'], TEXTAREA")
	:textarea		- $("*:textarea")	- Find <TEXTAREA> elements.				Equivalent to $("TEXTAREA"). Only included for continuity with the INPUT selectors. Not particularly useful!
	:select			- $("*:select")		- Find <SELECT>   elements.				Equivalent to $("SELECT").   Only included for continuity with the INPUT selectors. Not particularly useful!
	:multiple		- $("*:multiple")	- Find <SELECT multiple> elements.		Equivalent to $("SELECT[@multiple]"). Only included for continuity with the INPUT selectors.
	:selected		- $("*:selected")	- Find <SELECT> elements with 1 or more selections, and all <OPTION> elements that are selected.

	Written by George Adamson, Software Unity, September 2006-2007 (george.jquery@softwareunity.com).
	v1.0		- 27-Sep-2006 Released. The ":modified" selector streamlined to use the .defaultValue .defaultSelected etc.
	v1.0a		- 28-Sep-2006 Added ":selected" and "nth-last-child", improved ":modified", reduced code size.
	v1.0b		- 02-Oct-2006 Added ":Contains()" and various "xxx-of-type()" selectors.
	v1.0c		- 03-Oct-2006 Added ":nth-child-of-type()", enhanced "xxx-of-type()" selectors to distinguish INPUT tags by type and to filter on the set rather than siblings.
	v1.0d		- 11-May-2007 Changed blur/focus selector to use getElementsByTagName because it is much faster on large complex pages.
	v1.1.3.1	- 14-Jul-2007 Fully updated for jQuery 1.1.3.1. Added more selectors and some handy new methods too.

*/

var $jqColors;	// Sorry folks, I've used a global variable. I'll fix it I promise!


// Make Jquery aware of the new selectors:
jQuery.extend(jQuery.expr[':'], {

	/*
	 *	KEY/LEGEND: Params made available by jQuery for use in the selector definitions:
	 *	 r		= jQuery array of elements being scrutinised. (eg: r.length = Number of elements)
	 *	 i		= index of element currently under scrutiny, within array r.
	 *	 a		= element currently under scrutiny. Selector statement must return true to include it in its matched results.
	 *	 m[2]	= nodeName or * that we a looking for (left of colon).
	 *	 m[3]	= param passed into the :selector(param). Typically an index number, as in :nth-of-type(5), or a string, as in :color(blue).
	 */

	// Text check:
	Contains	: "(a.innerText||a.innerHTML).toUpperCase().indexOf(m[3].toUpperCase())>=0",	// Same as :contains() but ignores case.
	// An enhanced :text selector that matches TEXTAREAs as well, -or- any element who's inner-text matches that specified in :text(mytext)
	text		: "!m[3] ? a.type=='text' || a.nodeName=='TEXTAREA' : ( jQuery(a).text().toLowerCase()==m[3].toLowerCase() || (a.value && a.value.toLowerCase()==m[3].toLowerCase()) )",


	// Form attributes:
	hover		: "a==document.hoverElement",		// hoverElement is custom property maintained by dedicated document.mouseover event (see below).
	focus		: "a==document.activeElement",		// activeElement is natively available in IE. Is custom property in other browsers, maintained by dedicated blur/focus events.
	blur		: "a==document.lastActiveElement",	// lastActiveElement is custom property maintained by dedicated blur/focus events.
	modified	: "(a.nodeName=='INPUT' && (a.type=='checkbox' || a.type=='radio') && a.defaultChecked != a.checked) || (a.nodeName=='INPUT' && a.defaultValue != a.value && {'text':true,'hidden':true,'file':true,'password':true}[a.type]) || (a.nodeName=='TEXTAREA' && a.defaultValue != a.value) || (a.nodeName=='SELECT' &&  jQuery(a).is('['OPTION:option-mod']') )",
	unchecked	: "a.nodeName=='INPUT' && !a.checked",
	selected	: "(a.nodeName=='SELECT' && jQuery('OPTION[@selected]',a).is()) || (a.nodeName=='OPTION' && a.selected)",
	"option-sel": "(a.nodeName=='OPTION' && a.selected) || (a.nodeName=='INPUT' && a.checked && (a.type=='checkbox' || a.type=='radio'))",
	"option-def": "(a.nodeName=='OPTION' && a.defaultSelected) || (a.nodeName=='INPUT' && (a.type=='checkbox' || a.type=='radio') && a.defaultChecked)",
	"option-mod": "(a.nodeName=='OPTION' && a.defaultSelected != a.selected) || (a.nodeName=='INPUT' && (a.type=='checkbox' || a.type=='radio') && a.defaultChecked != a.checked)",


	// Form elements: (The commented ones have now been moved into jQuery itself, hurrah!)
	// Things like :textarea are pretty pointless IMO but hey, they're there now!
	//input		: "a.nodeName=='INPUT' || a.nodeName=='SELECT' || a.nodeName=='TEXTAREA' || a.nodeName=='BUTTON'",
	//radio		: "a.nodeName=='INPUT' && a.type=='radio'",
	//button	: "(a.nodeName=='INPUT' && 'button,submit,reset,image'.indexOf(a.type) >= 0) || a.nodeName=='BUTTON'",
	//checkbox	: "a.nodeName=='INPUT' && a.type=='checkbox'",
	//file		: "a.nodeName=='INPUT' && a.type=='file'",
	//password	: "a.nodeName=='INPUT' && a.type=='password'",
	//image		: "a.nodeName=='IMG' || (a.nodeName=='INPUT' && a.type=='image')",
	//submit	: "a.nodeName=='INPUT' && a.type=='submit'",
	//reset		: "a.nodeName=='INPUT' && a.type=='reset'",
	textarea	: "a.nodeName=='TEXTAREA'",
	select		: "a.nodeName=='SELECT'",
	multiple	: "a.nodeName=='SELECT' && a.multiple",
	option		: "(a.nodeName=='OPTION') || ( a.nodeName=='INPUT' && (a.type=='checkbox' || a.type=='radio') )",
	// Match element name with a regular expression: (eg: $(xml).find( "//customer/*:name('addressline[0-5]')" )
	nodename	: "(new RegExp(m[3])).test(a.nodeName)",


	// Parent and Sibling checks:
	// These :siblings(x) and :parents(x) selectors match elements that have siblings or parents that match simple selector x.
	// Sample usage: $(myElem).is(":parents('FORM')")	- identifies whether element is within a <form>.
	siblings	: "jQuery(a).siblings(m[3]).length>0",
	parents		: "jQuery(a).parents(m[3]).length>0",


	// Type and Child checks:
	"only-of-type"	: "1==jQuery(r).filter( a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']').length",
	"first-of-type"	: "a==jQuery(r).filter( a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']')[0]",
	"nth-of-type"	: "a==jQuery(r).filter( a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']')[m[3]]",
	"last-of-type"	: "a==jQuery(r).filter((a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']') +':last')[0]",
	// Note: These use the enhanced siblings() method which accepts extra param to keep current element (self) in the results:
	"only-child-of-type"	: "1==jQuery(a).siblings(  a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']', true).length",	
	"first-child-of-type"	: "a==jQuery(a).siblings(  a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']', true)[0]",	
	"nth-child-of-type"		: "a==jQuery(a).siblings(  a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']', true)[m[3]]",
	"last-child-of-type"	: "a==jQuery(a).siblings( (a.nodeName!='INPUT' ? a.nodeName : a.nodeName+'[@type='+a.type+']', true) +':last')[0]",	
	"nth-last-child"		: "jQuery.sibling(a,jQuery.sibling(a).length-1-m[3]).cur",


	// WARNING! The following selectors are WORK IN PROGRESS...! (They may be experimental or not fully tested)

	// CSS checks:
	// Match element class, allowing for optional * wildcard too: (Note: '\u005C\u005Cs' resolves to '\s' which matches whitespace characters). Known issue: Wildcard canot be used at start of class name.
	"class"		: "( new RegExp('(^|\u005C\u005Cs)' + m[3].replace('*','[^\u005C\u005Cs]*') + '(\u005C\u005Cs|$)') ).test(a.className)",
	// Match element style eg: "INPUT:css({border-width:'1px'})"
	css			: "function(a,styles){ var result=true; eval('styles='+styles); for(var name in styles){ result&=(jQuery(a).css(name)==styles[name]) } return result }(a,m[3])",

	// Visibility and Appearance:
	// Find elements that are within visible area of a scolled page or parent element. Much room for improvement here I reckon!
	"in-view"		: "a.scrollTop==0 && jQuery(a).parent().is(':in-view')",
	// Use our custom $jqColors object (defined below) to compare colours even when they're in different formats:
	color			: "$jqColors.match( m[3], jQuery(a).css('color') )",
	"bg-color"		: "$jqColors.match( m[3], jQuery(a).css('backgroundColor') )",
	//"border-color"	: "a.currentStyle.borderColor && ( $jqColors.match(m[3],a.currentStyle.borderColor) || ( $jqColors.toHex(m[3]) == (a.currentStyle.borderWidthTop > 0 && $jqColors.toHex(a.currentStyle.borderColorTop)) == $jqColors.toHex(a.currentStyle.borderColorLeft) == $jqColors.toHex(a.currentStyle.borderColorBottom) == $jqColors.toHex(a.currentStyle.borderColorRight) ))",

	"bg-url"		: "a.currentStyle.backgroundImage.toLowerCase().indexOf(m[3].toLowerCase()) != -1",	// Work in progress!


	// Find elements that are targets of an <a href="#bookmark"> link on the same page: (It is not realistic to expect to find elements that are a "target of the referring URI" from other pages!)
	target			: "a.id && jQuery(document.getElementsByTagName('A')).is('[@href*=#' + a.id + ']')",

	// Elements who's named attribute value exists in a csv string: Usage: $("INPUT:csv(value:John,George,Fred)")
	//,csv			: "function(a,p){ p=p.split(":"); return p.length==2 && a && (","+p[1]+",").indexOf(","+jQuery(a).attr(p[0])+",") >= 0 }(a,m[3])"
	csv				: "function(a,prm){ prm=prm.split(':'); return prm.length==2 && a && jQuery.inArray( jQuery(a).attr(prm[0]), prm[1].split(',') ) >= 0 }(a,m[3])",

	// Tables:
	// TH and TD elements in the specified column: (Allows for colSpan>1 in preceeding siblings. Relies on new sum() method defined below)
	colIndex		: "(a.nodeName=='TD' || a.nodeName=='TH') && ( m[3] == jQuery(a).siblings('TH,TD').lt( (parseInt(jQuery(a).attr('cellIndex')) || 0) ).sum('colSpan',1) )"

	// Elements whose numeric css style or attribute is the largest/smallest in the set: (Attempts to work with both css styles and attributes)
	// Eg: $("DIV:max(zIndex)") will return DIVs with the largest z-index.
	,max			: "jQuery(a).css(m[3]) != undefined ? jQuery(a).css(m[3]) == Math.max.apply({}, jQuery.map( r, function(el){return jQuery(el).css(m[3])} )) : jQuery(a).attr(m[3]) == Math.max.apply({}, jQuery.map( r, function(el){return jQuery(el).attr(m[3])} ))"
	,min			: "jQuery(a).css(m[3]) != undefined ? jQuery(a).css(m[3]) == Math.min.apply({}, jQuery.map( r, function(el){return jQuery(el).css(m[3])} )) : jQuery(a).attr(m[3]) == Math.min.apply({}, jQuery.map( r, function(el){return jQuery(el).attr(m[3])} ))"

});

	// RETIRED because jQuery 1.1.3 does not allow extension of the @ selectors.
	// Create a ',=' selector to match attribute value when it exists in csv string: Eg: $("INPUT[@name,='txt1,txt2,txt3']")
	// I agree this selector looks ugly, but at least it reminds you what it is for!
	// Not very happy with this solution because it overwrites jQuery's own regex. TODO: Would be better to add our own :csv(attr,csv) selector instead.
	//jQuery.parse[0] = /^\[ *(@)([a-z0-9_-]*) *([!*$^,=]*) *('?"?)(.*?)\4 *\]/i;		// Same as default regex but with a comma added in the middle!
	//jQuery.extend(jQuery.expr['@'], {
	//	",=": "z && jQuery.inArray(z,m[4].split(',')) >= 0"	// or: "z&&(','+m[4]+',').indexOf(','+z+',')>=0"
	//});

	// RETIRED because jQuery 1.1.3 does not allow extension of the @ selectors.
	// Extend JQuery's '*=' attribute selector to accept '*' as a wildcard: (Eg: $("INPUT[@class*='clsCol*Row1']") will match classnames 'clsCol1Row1' and 'clsCol100Row1' etc.)
	// Note: This restricts wildcards to match non-whitespace characters only (ie within whole words only, eg for matching classnames).
	//jQuery.extend(jQuery.expr['@'], {
	//	"*=": "z&&( m[4].indexOf('*')==-1 ? z.indexOf(m[4])>=0 : (new RegExp('(^|\u005Cs)' + m[4].replace('*','[^\u005Cs]*') + '(\u005Cs|$)')).test(z) )"
	//	// Note: \u005C resolves to a backslash (alternatively I'd need to type 8 backslashes in a row to resolve to the required 1 backslash at runtime!)
	//	// If text does not contain a '*' then revert to original jQuery match which was: "*=": "z&&z.indexOf(m[4])>=0",
	//});


	// Plugin to wrap html around non-empty text node(s) within an element: (ignores text in child elements)
	// Eg: $("LI").wrapText("<LABEL/>") Before: "<li><input/>some text</li>" After: "<li><input/><label>some text</label></li>"
	jQuery.fn.wrapText = function(html){	// Returns jQuery object so chaining is not broken.
		return this.each(function(){
			jQuery(this.childNodes).filter("[@nodeType=3]").each(function(){
				if(jQuery.trim(this.nodeValue).length > 0) jQuery(this).wrap(html);
			})
		});
	};


	// Plugin to return array of attribute values from the set of elements for a named attribute.
	// Much like .attr() but this returns an array of values insead of just one.
	// Eg: $("SELECT").attrs("id") will return an array of IDs of all the SELECT elements.
	jQuery.fn.attrs = function(attrName,dedupe){
		if(dedupe)
			return jQuery.map( this, function(el){return jQuery(el).attr(attrName)} );
		else
			{ var arr = []; this.each(function(){ arr.push(jQuery(this).attr(attrName)) }); return arr; }
	}


	// Plugin to return array of text from each matched element.
	// - deep : Specify true (default) to include text nodes found in child elements.
	// - includeBlanks : Specify true (default) to include empty text nodes in the result.
	// Eg: $("#myList OPTION").texts() will return an array of innerTexts from all the list items in #myList.
	jQuery.fn.texts = function(deep,includeBlanks){
		var arr = [];
		this.each(function(){
			if(this.firstChild){
				jQuery(this.childNodes).filter("[@nodeType=3]").each(function(){
					if(this.nodeValue.length>0 || includeBlanks!=false) arr.push(this.nodeValue);
				});
				if(deep!=false) arr=arr.concat( $(this).children().texts(deep,includeBlanks) );
			};
		});
		return arr;
	}

	// Return outerHTML of the current item(s): (Use optional 'deep' param (default true) to include all html of children too)
	jQuery.fn.outerHtml = function(deep){
		return jQuery("<div></div>").append( this.clone(deep!=false) ).html();
	}

	// Invert the immediately preceeding .filter() action: (Not properly tested with any other "descructive" methods!)
	jQuery.fn.invert = function(){
		var $curr = this;
		return $curr.pushStack( $curr.end().filter(function(){ return jQuery.inArray(this,$curr)==-1 }).get() );
	}

	// Return the sum of an attribute from all matched elements:
	// Non-numeric attr values default to alt or zero. Specify deep as true to sum the attr in child elements too.
	// TODO: Make a more generic calc() emthod to do other calulations too.
	jQuery.fn.sum = function(attr,alt,deep){
		var sum=0; alt=parseFloat(alt)||0;
		this.each(function(){
			sum += (parseFloat(jQuery(this).attr(attr)) || alt) + (deep ? jQuery(this).children().sum(attr,alt,deep) : 0)
		});
		return sum;
	}

	// Return 0-based column index of a TH or TD cell, taking colSpans into account in preceeding siblings:
	// Uses the custom sum() method defined in this plugin.
	jQuery.fn.colIndex = function(){
		var $cell	= this.filter("TH,TD").eq(0),						// Get first cell in current selection.
			cellIdx	= parseInt($cell.attr("cellIndex")) || 0;
		return $cell.siblings("TH,TD").lt(cellIdx).sum("colSpan",1);	//Same as: return this.eq(0).siblings("TH,TD",true,"prev").sum("colSpan");
	}


	// Return all cells in same column(s) as the selected TH or TD elements, -or- those in specified colIdx:
	// colIdx param works with TABLE, THEAD, TBODY, TFOOT, TH, TD elements. (TODO: Also accept COLGROUP, COL, CAPTION elements)
	jQuery.fn.colCells = function(colIdx){

		var cells = []; colIdx = parseInt(colIdx) || null;

		// Get all rows in the selected table, head, body, foot and rows:
		var $rows = this.filter("TABLE").children("THEAD,TBODY,TFOOT")
			.add( this.filter("THEAD,TBODY,TFOOT") )
			.children("TR").add( this.filter("TR") )

		// Find all the cells that match colIdx:
		cells = cells.concat( $rows.children("TH,TD").filter(":colIndex(" + (colIdx||0) + ")").get() );

		// If colIdx IS specified and selection contains cells, get all their rows too:
		if(colIdx)
			cells = cells.concat( this.filter("TH,TD").parent("TR").parent("THEAD,TBODY,TFOOT").children("TR").get() );
		// If colIdx is NOT specified then we need to read it from each selected cell so that
		// we can return only cells in the same column(s): (Rather slow when selection contains many cells!)
		else
			this.filter("TH,TD").each(function(){
				var i = $(this).colIndex() || 0;
				cells = cells.concat( $(this).parent("TR").parent("THEAD,TBODY,TFOOT").children("TR").children("TH,TD").filter(":colIndex(" + i + ")").get() );
			})

		return this.pushStack(cells);
	}

	// Plugin to extend toggleClass to accept extra conditional params to 
	// act rather like VB's IIf function, or Javascript's "x ? y : z" syntax.
	// Usage:
	//	- $(x).toggleClass("myClass")							- Default jQuery usage (still works as expected).
	//	- $(x).toggleClass(myBoolean, "myClass")				- Toggle myClass depending on myBoolean true/false.
	//	- $(x).toggleClass(myBoolean, "myClass", "myClass2")	- Toggle between myClass and myClass2 depending on myBoolean true/false.
	//	- $(x).toggleClass("mySelector", "myClass")				- Toggle myClass depending on whether mySelector applies to any of the current elements.
	//	- $(x).toggleClass("mySelector", "myClass", "myClass2")	- Toggle between myClass and myClass2 depending on whether mySelector applies to any of the current elements.
	var _toggleClass = jQuery.fn.toggleClass;
	jQuery.fn.toggleClass = function(c, trueC, falseC){

		if(trueC == undefined)
			// Ensure the original toggleClass() method functionality works as normal:
			return _toggleClass.apply(this,[c]);
		else{
			if( c.constructor == String ? this.is(c) : c ){
				this.addClass(trueC);
				if(falseC) this.removeClass(falseC);
			}else{
				this.removeClass(trueC);
				if(falseC) this.addClass(falseC);
			}
			return this;
		}

	}


$(function(){

	(function($){

		// Get references to the original jQuery methods before we add steroids!
		var _filter = $.filter;
		var _parents = $.fn.parents;
		var _siblings = $.fn.siblings;

		// Plugin to extend siblings() method to include the current items (self) in the results if desired (default=false).
		// The standard siblings() method excludes self, so this does too by default, that way existing code will not break.
		// You can also specify 'prev' or 'next' (or true or false)) to only return siblings before or after the current one in the html.
		$.fn.siblings = function(s,orSelf,prevNext){
			if(prevNext=="undefined")
				// Default to the standard jQuery functionality: (Trying to minimise impact on performance too!)
				return !orSelf ? _siblings.apply(this,[s]) : _siblings.apply(this,[s]).add(this);
			else{
				// Restrict results to only include siblings before or after the current element(s):
				var r = [];
				prevNext = (prevNext==true || prevNext=='next' || prevNext=='after');	// Else false/prev/before.
				this.each(function(){
					var n = this;
					if(orSelf) prevNext ? r.push(n) : r.unshift(n);				// Include self in the results?
					if(prevNext){
						while( n = n.nextSibling ){ if( n.nodeType==1 ) r.push(n) };	// We explicity choose to use push or unshift in order to retain original source-order of elements in the results.
					}else{
						while( n = n.previousSibling ){ if( n.nodeType==1 ) r.unshift(n) };
					}
				});
				return this.pushStack( jQuery.multiFilter(s,r) );						// Filter results by the selector before adding them to jQuery chain.
			}
		}


		// Plugin to extend parents() method to optionally include the current item(s) in the results if desired (default=false).
		// The standard parents() method excludes self, so this does too by default, that way existing code will not break.
		$.fn.parents = function(s,orSelf){
			return !orSelf ? _parents.apply(this,[s]) : _parents.apply(this,[s]).add(this);
		}


		// Experimental:
		// Plugin to return siblings that are within siblings of parents! Rudimentary but worked for the context I needed it.
		// (Returns a jQuery object so chaining is not broken.)
		// TODO: Improve performance. Try using concat() instead of $.merge() then rely on pushStack to do the dedupuing.
		$.fn.cousins = function(a,orSelf){
			var arrResult = []; a = ( a.indexOf('>')!=0 ? '>' : '' ) + (a || '*');
			this.each(function(){
				$.merge( arrResult, $(this).parents("[" + a + "]:first").find(a).not( orSelf ? [] : this ) );
			})
			return this.pushStack(arrResult);
			//return this.pushStack( this.parents("[" + a + "]:first").find(a).not( orSelf ? [] : this ).get() );
		};


		// Plugin to extend filter() method to optionally filter by attributes provided in {name:value} object notation:
		// The technique could be much better, especially to improve performance, but hey, it works!
		$.filter = function(t) {
			if(typeof t != "object") {
				// Ensure the original filter() method functionality works as normal:
				return _filter.apply(this,arguments);
			}else{
				// Here, t is neither a selector string nor a filter function, but an object of {name:value} pairs.
				// Build a selector string from the {name:value} pairs then use it to call this filter() method again.
				// (Also relies on our custom :csv() selector if we detect commas in a filter attribute value.)
				var sel = '';
				for(var n in t){		// Get filter name n.
					var v = t[n];		// Get filter value v.
					if(v.length > 0 && v != '*'){
						if(n=="txt"){	// Allow for custom "txt" attribute for matching innerText.
							sel += ":Contains('" + v + "')";
						}else{
							// Look for commas in the value to decide whether to match attr in csv string or not:
							sel += (v.indexOf(",") == -1) ? "[@" + n + "='" + v + "']" : ":csv('" + n + ":" + v + "')";
						}
					}
				};
				arguments[0] = sel;
				return _filter.apply(this,arguments);
			}
		};


		// Plugin to un/check all option, radio and checkbox elements:
		// - on is an optional true/false value (true by default to select/check items on)
		// - val is an optional value to only un/select/check items who's value matches val.
		// - toggleSiblings defaults to true to unselect/uncheck all siblings when 'on' is true.
		// (Returns the same jQuery object that called this method, so chaining is not broken.)
		// Does not allow for the daft "selected" error in IE6 that can occur immediately after dynamically building a list .
		// TODO: Allow for OPTGROUP (because OPTION items will not strictly be siblings!)
		$.fn.sel = function(on,val,toggleSiblings){

			if(on!=false)
				return this
					.filter("INPUT:radio,INPUT:checkbox")
						.filter({ value: val||'*' })
							.attr({checked:"checked"})
							.filter(function(){ return toggleSiblings!=false })
								.siblings("INPUT:radio:checked,INPUT:checkbox:checked")
									.removeAttr("checked")
								.end()
							.end()
						.end()
					.end()
					.filter("OPTION")
						.filter({ value: val||'*' })
							.attr({selected:"selected"})
							.filter(function(){ return toggleSiblings!=false })
								.siblings("OPTION:selected")
									.removeAttr("selected")
								.end()
							.end()
						.end()
					.end();
			else
				return this
					.filter("INPUT:radio,INPUT:checkbox,OPTION")
						.filter({ value: val||'*' })
							.removeAttr("checked")
							.removeAttr("selected")
						.end()
					.end();
		};

	})(jQuery);




// Some of the new :selectors rely on custom attributes or events.
// Set these up as soon as the document has loaded:

	document.lastActiveElement = document.body;	// Custom property to record previous activeElement setting.

	// Add support for ":focus" and ":blur"
	// Add events to support document.activeElement in browsers that do not do it natively.
	// Works by tracking onBlur and onFocus events of all input and list elements.
	// The focus & blur events do not bubble so we cannot just watch them at the document level.
	// (But in IE there is an ondeactivate event that should serve us just as well.)
	// KNOWN ISSUE: This will not work on any elements created since the page loaded.
	// KNOWN ISSUE: Page load will be slower if there are alot of form elements. (except in IE)
	if(document.activeElement && (document.ondeactivate || document.ondeactivate==null)) {

		// For browsers that natively support document.activeElement (such as IE):
		// Add event handler to maintain lastActiveElement property for the :blur selector.
		// (No need to add event handlers to maintain activeElement for the :focus selector.)

		// IE provides the ondeactivate event that bubbles to the document level so lets use that:
		// At least IE users can benefit from not having to add event handlers to every form element!
		$(document).bind("deactivate", function(e){

			// Make a note of the current activeElement before it loses focus:
			if(e.target != document.body) document.lastActiveElement = e.target;

		});

		/*
			// For browsers other than IE that also support the activeElement property:
			// Add onblur handlers to maintain lastActiveElement property for the :blur selector.
			// (At the time of writing there were no other browsers so this may never be needed!)
			$("INPUT, SELECT, TEXTAREA, BUTTON")

				.blur(function(e){
					// Make a note of the current activeElement before it loses focus:
					document.lastActiveElement = e.srcElement || e.target;
				});
		*/

	} else {	// Applies to browsers where document.ondeactivate == 'undefined'

		// For browsers that do not natively support document.activeElement:
		// Add onblur handlers to maintain lastActiveElement property for the :blur selector.
		// Add onfocus handlers to maintain activeElement property for the :focus selector.

		document.activeElement = document.body;	// Initialise custom property to mimic the native IE property.

		// Note: On large DOMs, getElementsByTagName() is much faster than $("INPUT,SELECT,BUTTON,TEXTAREA,A").
		//jQuery(document.getElementsByTagName("INPUT"))
		//.add(document.body.getElementsByTagName("SELECT"))
		//.add(document.body.getElementsByTagName("BUTTON"))
		//.add(document.body.getElementsByTagName("TEXTAREA"))
		//.add(document.body.getElementsByTagName("A"))
		jQuery("INPUT,SELECT,BUTTON,TEXTAREA,A")

			.bind("blur",function(e){
				// Make a note of the current activeElement before it loses focus:
				document.lastActiveElement = e.target;
				document.activeElement = document.body;
			})

			.bind("focus",function(){
				// Update activeElement to indentify element that gained focus:
				document.activeElement = this;
			})

	}


	// Add support for the ":hover" selector.
	// Add event to maintain a custom document.hoverElement property:
	// This enables use of the custom :hover selector.
	// KNOWN ISSUE: e.srcElement (target) is not always up to date when we use mouseover.
	// (Note: mouseover is lighter on cpu than mousemove but srcElement is not always up to date in certain circumstances!)
	jQuery(document.body).mouseover(function(e){ document.hoverElement = e.target; });


	// Functions and lookups for comparing COLOUR-strings:
	// All colours are converted to #rrggbb formats when being compared.
	// Thanks to http://www.w3schools.com/css/css_colornames.asp for the list of colour names and codes.
	// TODO: Handle rgb() percentage values, eg: rgb(10%,50%,100%).
	// TODO: Handle abbreviated hex codes, eg: #f00.
	// TODO: Handle hsl() codes.
	// TODO: Handle alpha transparency such as #aarrggbb, rgba() and hsla()
	$jqColors = {

		// Build maps to convert "hex", "rgb(r,g,b)" and named-colour strings to #hex to provide fastest possible lookups:
		//lookupHex	: {},	// hex map will be populated by the .each() loop below, to look like this: {'f0f8ff':'#f0f8ff', ...etc}
		//lookupRgb	: {},	// rgb map will be populated by the .each() loop below, to look like this: {'rgb(240,248,255)':'#f0f8ff', ...etc}
		names	: {transparent:'ffffffff',aliceblue:'f0f8ff',antiquewhite:'faebd7',aqua:'00ffff',aquamarine:'7fffd4',azure:'f0ffff',beige:'f5f5dc',bisque:'ffe4c4',black:'000000',blanchedalmond:'ffebcd',blue:'0000ff',blueviolet:'8a2be2',brown:'a52a2a',burlywood:'deb887',cadetblue:'5f9ea0',chartreuse:'7fff00',chocolate:'d2691e',coral:'ff7f50',cornflowerblue:'6495ed',cornsilk:'fff8dc',crimson:'dc143c',cyan:'00ffff',darkblue:'00008b',darkcyan:'008b8b',darkgoldenrod:'b8860b',darkgray:'a9a9a9',darkgrey:'a9a9a9',darkgreen:'006400',darkkhaki:'bdb76b',darkmagenta:'8b008b',darkolivegreen:'556b2f',darkorange:'ff8c00',darkorchid:'9932cc',darkred:'8b0000',darksalmon:'e9967a',darkseagreen:'8fbc8f',darkslateblue:'483d8b',darkslategray:'2f4f4f',darkslategrey:'2f4f4f',darkturquoise:'00ced1',darkviolet:'9400d3',deeppink:'ff1493',deepskyblue:'00bfff',dimgray:'696969',dimgrey:'696969',dodgerblue:'1e90ff',firebrick:'b22222',floralwhite:'fffaf0',forestgreen:'228b22',fuchsia:'ff00ff',gainsboro:'dcdcdc',ghostwhite:'f8f8ff',gold:'ffd700',goldenrod:'daa520',gray:'808080',grey:'808080',green:'008000',greenyellow:'adff2f',honeydew:'f0fff0',hotpink:'ff69b4',indianred :'cd5c5c',indigo :'4b0082',ivory:'fffff0',khaki:'f0e68c',lavender:'e6e6fa',lavenderblush:'fff0f5',lawngreen:'7cfc00',lemonchiffon:'fffacd',lightblue:'add8e6',lightcoral:'f08080',lightcyan:'e0ffff',lightgoldenrodyellow:'fafad2',lightgray:'d3d3d3',lightgrey:'d3d3d3',lightgreen:'90ee90',lightpink:'ffb6c1',lightsalmon:'ffa07a',lightseagreen:'20b2aa',lightskyblue:'87cefa',lightslategray:'778899',lightslategrey:'778899',lightsteelblue:'b0c4de',lightyellow:'ffffe0',lime:'00ff00',limegreen:'32cd32',linen:'faf0e6',magenta:'ff00ff',maroon:'800000',mediumaquamarine:'66cdaa',mediumblue:'0000cd',mediumorchid:'ba55d3',mediumpurple:'9370d8',mediumseagreen:'3cb371',mediumslateblue:'7b68ee',mediumspringgreen:'00fa9a',mediumturquoise:'48d1cc',mediumvioletred:'c71585',midnightblue:'191970',mintcream:'f5fffa',mistyrose:'ffe4e1',moccasin:'ffe4b5',navajowhite:'ffdead',navy:'000080',oldlace:'fdf5e6',olive:'808000',olivedrab:'6b8e23',orange:'ffa500',orangered:'ff4500',orchid:'da70d6',palegoldenrod:'eee8aa',palegreen:'98fb98',paleturquoise:'afeeee',palevioletred:'d87093',papayawhip:'ffefd5',peachpuff:'ffdab9',peru:'cd853f',pink:'ffc0cb',plum:'dda0dd',powderblue:'b0e0e6',purple:'800080',red:'ff0000',rosybrown:'bc8f8f',royalblue:'4169e1',saddlebrown:'8b4513',salmon:'fa8072',sandybrown:'f4a460',seagreen:'2e8b57',seashell:'fff5ee',sienna:'a0522d',silver:'c0c0c0',skyblue:'87ceeb',slateblue:'6a5acd',slategray:'708090',slategrey:'708090',snow:'fffafa',springgreen:'00ff7f',steelblue:'4682b4',tan:'d2b48c',teal:'008080',thistle:'d8bfd8',tomato:'ff6347',turquoise:'40e0d0',violet:'ee82ee',wheat:'f5deb3',white:'ffffff',whitesmoke:'f5f5f5',yellow:'ffff00',yellowgreen:'9acd32'},
		map		: {},	// $jqColors.map will be populated with all common colour names, hex codes and rgb combinations for speedy matching.

		// Return true when two colours are same: (Automagically matches different formats: #rrggbb, rgb(r,g,b) and named colours)
		match	: function(colA,colB){ return colA == colB || $jqColors.toHex(colA) == $jqColors.toHex(colB) },

		// Helper function to convert a colour-string from any format to "#rrggbb" hex format ready for comparison:
		// Will return defaultCol or null if no convertion can be achieved. Used by $jqColors.match()
		toHex	: function(col,defaultCol){
			col = col.replace(" ","").toLowerCase();
			return $jqColors.map[col]			// Lookup common names, hex codes and rgb() combinations.
				//|| $jqColors.names[col]		// Lookup #hex for common "ColourName".	Retired: Replaced by single .map object.
				//|| $jqColors.lookupHex[col]	// Lookup #hex for common "rrggbb".		Retired: Replaced by single .map object.
				//|| $jqColors.lookupRgb[col]	// Lookup #hex for common "rgb(r,g,b)".	Retired: Replaced by single .map object.
				|| $jqColors.rgbToHex(col)		// Convert uncommon "rgb(r,g,b)" string to #hex.
				|| ( $jqColors.regexHex.test(col) && (col.indexOf('#') == 0 ? col : '#' + col) )	// Return uncommon #hex string "as is".
				|| defaultCol
				|| null
		},

		// Helper function to convert "rgb(r,g,b)" string to hex "#rrggbb": (Used by $jqColors.toHex)
		rgbToHex : function(rgb){
			var m = $jqColors.regexRgb.exec(rgb);
			return ( m && m.length == 4 )
				? '#' + (m[1]*1).toString(16) + (m[2]*1).toString(16) + (m[3]*1).toString(16)	// *1 is just a tight way to convert a string of digits to a number.
				: null
		},

		// Helper function to return css valid hex from awkward hex strings such as the abbreviation "#FFF":
		// INCOMPLETE!
		fixHex : function(hex){

			var m; hex = hex.replace(" ","").toLowerCase();

			if( hex && (hex = hex.indexOf('#')==0 ? hex : '#' + hex) && (m = $jqColors.regexHex.exec(hex)) ){
				return m
				// m is an array of up to 6 byte values in m[1] to m[6]: (m[0] just equals hex)
				//if(m[6]) return hex;
				//if(!m[6] && !m[5] && !m[4] && m[3] && m[2] && m[1])
			}
			return null;		
		},

		// Helper function to convert hex "#rrggbb" to "rgb(r,g,b)" string: (Used by by script init each() loop below)
		// Intended for internal use only: Does not handle shorthand colours such as "#ff" or "#ffff".
		// Uses parseInt to convert hex to decimal, then bitwise '&' to extract the R, G and B parts.
		hexToRgb : function(hex){
			hex = "0x" + hex.replace("#","")	// Or use: var dec = parseInt(hex.replace("#",""),16);
			return 'rgb('+ (hex>>16) +','+ (hex>>8 & 0xff) +','+ (hex & 0xff) +')';
		},

		// Prepare regular expressions to optimise performance:
		//regexHex: /^#?[0-9a-f]{2,6}$/ ,						// ^=Start of string, #?=Optional hash char, [0-9a-f]{2,6}=Match 2 to 6 hex chars , $=End of string.
		regexHex: /^#?([0-9a-f]{1,2})([0-9a-f]{1,2})([0-9a-f]{1,2})$/ ,		// ^=Start of string, #?=Optional hash char, [0-9a-f]{1,2}=Match one or two hex chars, $=End of string.
		regexRgb: /^rgb\((\d{1,3}),(\d{1,3}),(\d{1,3})\)$/	// ^=Start of string, \d{1,3}=Match 1 to 3 digits (\d is equivalent to [0-9]), 3-Bracketed expressions result in 3 matched values in array m, $=End of string.

	};

	// Initialise a hash of common colour names, #hex codes and rgb() combinations to speed up and simplify color lookups in selectors etc:
	jQuery.each($jqColors.names,function(n,hex){
		// Also prepend '#' to the hex strings, eg: 'f0f8ff' --> '#f0f8ff' (They're defined without '#' to reduce script file size)
		// $jqColors.lookupRgb[$jqColors.hexToRgb(hex)] = $jqColors.names[n] = $jqColors.lookupHex[hex] = $jqColors.lookupHex['#'+hex] = '#'+hex;
		$jqColors.map[n] = $jqColors.map['#'+hex] = $jqColors.map[hex] = $jqColors.map[$jqColors.hexToRgb(hex)] = '#'+hex;
	});

});
