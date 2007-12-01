/* Copyright (c) 2006 Mathias Bank (http://www.mathias-bank.de)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 */
 
 
/**
 * Display a customized tooltip instead of the default one
 * for every selected element. The tooltip behaviour mimics
 * the default one, but lets you style the tooltip and
 * specify the delay before displaying it.
 *
 * In addition, it displays the href value, if it is available.
 * 
 * @example $('li').colorize('#ff1313', '#000000', [1,1,1],"color");
 * @desc sets the css tag "color" for all li elements, changing the 
 	color from #ff1313 to #000000 in alinear way
 *
 * @name ColorGradient
 * @type jQuery
 * @cat Plugins/ColorGradient
 * @author Mathias Bank (http://www.mathias-bank.de)
 */
 
jQuery.extend({

	hexDigits: ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"],
	
	/**
	* generates a rgb value, using a hex value
	*/
	hex2rgb: function(hex) {
		
		var rgb = new Array();
		try {
			hex = this.checkHex(hex);
			rgb[0]=parseInt(hex.substr(1, 2), 16);
			rgb[1]=parseInt(hex.substr(3, 2), 16);
			rgb[2]=parseInt(hex.substr(5, 2), 16);
			return rgb;
		} catch (e) {
			throw e;
		}
	},

	//generates the hex-digits for a color.	
	hex: function(x) {
		return isNaN(x) ? "00" : this.hexDigits[(x - x % 16) / 16] + this.hexDigits[x % 16];
	},


	/**
	* generates a hex value, using a rgb value
	* @param array(red, greed, blue);
	*/
	rgb2hex: function(rgb) {
		try {
			this.checkRGB(rgb);
			
			return "#" + hex(rgb[0]) + hex(rgb[1]) + hex(rgb[2]);
		} catch (e) {
			throw e;
		}
	},
	
	
	/**
	* checks, if an array of three values is a valid rgb-array
	*/
	checkRGB: function(rgb) {
		if (rgb.length!=3) throw "this is not a valid rgb-array";
		
		if (isNaN(rgb[0]) || isNaN(rgb[1]) || isNaN(rgb[2])) throw "this is not a valid rgb-array";
		
		if (rgb[0]<0 || rgb[0]>255 || rgb[1]<0 || rgb[1]>255 || rgb[2]<0 || rgb[3]>255) throw "this is not a valid rgb-array";
		
		return rgb;
	},
	
	/**
	* checks, if a given number is a hexadezimal number
	*/
	checkHex: function(hex) {
		if (!hex || hex==""  || hex=="#") throw "No valid hexadecimal given.";
		
		hex = hex.toUpperCase();
				
		switch(hex.length) {
			case 6:
				hex	= "#" + hex;
				break;
			case 7:
				break;
			case 3:
				hex	= "#" + hex;
				break;
			case 4:
				hex	= "#" + hex.substr(1, 1) + hex.substr(1, 1) + hex.substr(2, 1) + hex.substr(2, 1) + hex.substr(3, 1) + hex.substr(3, 1);
				break;
		}
		if(hex.substr(0, 1) != "#" || !this.checkHexDigits(hex.substr(1))) {
			throw "No valid hexadecimal given.";
		}
		
		return hex;	
	},
	
	/**
	* checks, if there is any unvalid digit for a hex number
	*/
	checkHexDigits: function(s) {
		var	j, found;
		for(var i = 0; i < s.length; i++) {
			found	= false;
			for(j = 0; j < this.hexDigits.length; j++)
				if(s.substr(i, 1) == this.hexDigits[j])
					found	= true;
			if(!found)
				return false;
		}
		return true;
	},
	
	/**
	* calculates an array with hex values. 
	* @param startColor: starting color (hex-format or rgb)
	* @param endColor: ending color (hex-format or rgb)
	* @param options: defines, how the color should be generated. The options are defined
				by an object with:
				count: specifies, how many colors should be generated
				type: array for each color. Speciefies, how the missing color should be calculated:
										1: linear
										2: trigonometrical 
										3: accidentally
										4: ordered accident
	*/
	calculateColor: function(startColor, endColor, options)	{
		if (!options || !options.type || !options.type[0] || !options.type[1] || !options.type[2] || !options.count)
			options = this.colorGradientOptions;
		
		var	color	= new Array();
		try {
			try {
				var	start	= this.hex2rgb(startColor);
				var end 	= this.hex2rgb(endColor);
			} catch (e) {
				//no hex-value => check if rgb
				this.checkRGB(startColor);
				var start = startColor;
				this.checkRGB(endColor);
				var end = endColor;
			}
			
			var rgb = new Array();
			rgb[0]	= this.calculateGradient(start[0], end[0], options.count, options.type[0]);
			rgb[1]	= this.calculateGradient(start[1], end[1], options.count, options.type[1]);
			rgb[2]	= this.calculateGradient(start[2], end[2], options.count, options.type[2]);
		
			for(var i = 0; i < options.count; i++) {
				color[i] = "#" + this.hex(rgb[0][i]) + this.hex(rgb[1][i]) + this.hex(rgb[2][i]);
			}
		} catch (e) {
			throw e;
		}
		return color;
	},
	
	/**
	* calculateGradient for a color
	* @param startVal
	* @param endVal
	* @param count
	* @param type: array for each color. Speciefies, how the missing color should be calculated:
										1: linear
										2: trigonometrical 
										3: accidentally
										4: ordered accident
	*/
	calculateGradient: function(startVal, endVal,count, type) {
		var a = new Array();
		if(!type || !count) {
			return null;
		} else if (1<count && count < 3) {
			a[0] = startVal;
			a[1] = endVal;
			return a;
		} else if (count==1) {
			a[0] = endVal;
			return a;
		}
		
		switch(type) {
			case 1: //"linear"
				var i;
				for(i = 0; i < count; i++)
					a[i] = Math.round(startVal + (endVal - startVal) * i / (count - 1));
				break;
	
			case 2: //trigonometrical 
				var i;
				for(i = 0; i < count; i++)
					a[i] = Math.round(startVal + (endVal - startVal) * ((Math.sin((-Math.PI / 2) + Math.PI * i / (count - 1)) + 1) / 2));
				break;
	
			case 3: //accident
				var i;
				for(i = 1; i < count - 1; i++)
					a[i] = Math.round(startVal + (endVal - startVal) * Math.random());
				a[0]	= startVal;
				a[count - 1]	= endVal;
				break;
	
			case 4: //ordered accident
				var i;
				for(i = 1; i < count - 1; i++)
					a[i] = Math.round(startVal + (endVal - startVal) * Math.random());
				a[0]	= startVal;
				a[count - 1]	= endVal;
				if((typeof(a.sort) == "function") && (typeof(a.reverse) == "function"))
				{
					a.sort(this.cmp);
					if(startVal > endVal)
						a.reverse();
				}
				break;
		}
		return a;
	}, 
	
	//compares two values to sort
	cmp: function(a, b) {
		return a - b;
	},
	
	/**
	*
	*/
	colorGradientOptions: {
		count: 5,
		type: [1,1,1]
		
	}
});

jQuery.fn.extend( {

	/**
	* colorizes all matching elements, using type-specification for each color:
			1: linear
			2: trigonometrical 
			3: accidentally
			4: ordered accident
	* @param startColor: starting Color in hex or as a rgb-array
	* @param endColor: ending Color in hex or as a rgb-array	
	* @param type: array for each color (=> length: 3) to specify the color-algorithm
	* @param cssTag: specifies, which css tag should be changed
	*/
	colorize: function(startColor,endColor,type,cssTag) {
		var color = jQuery.calculateColor(startColor, endColor, {"count": this.length, "type":type});
		var counter = 0;
		for (var i = 0; i<this.length; i++) {
			$(this[i]).css(cssTag, color[i]);
		}
		
	}
});
