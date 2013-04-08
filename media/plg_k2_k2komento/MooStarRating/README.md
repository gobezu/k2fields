MooImageTip
===========

A Mootools plugin that creates a non-obstrusive star rating control based on a set of radio input boxes. 
Based on Diego Alto's jQuery Star Rating Plugin.

![Screenshot](https://github.com/lorenzos/MooStarRating/raw/master/Graphics/logo.png)


How to use
----------

JS sample:

	// Basic usage, give only form name or ID
	var basicRating = new MooStarRating({ form: 'basic' });
	
	// Event callback
	basicRating.addEvent('click', function (value) {
		alert("Selected " + value);
	});
	
	// If you want more control, you can use some options
	var customRating = new MooStarRating({
		form: 'custom',
		radios: 'my_rating',                // Radios name
		half: true,                         // That means one star for two values!
		imageEmpty: 'my_star_empty.png',    // Different image
		imageFull:  'my_star_full.png',     // Different image
		imageHover: 'my_star_hover.png',    // Different image
		tip: 'Rate [VALUE] / 3.0',          // Mouse rollover tip
		tipTarget: $('simpleTip')           // Tip element
	}).addEvent('click', function (value) {
		alert("Selected " + value);
	});

HTML code:

	<!-- Basic form with "rating" radios -->
	<form name="basic">
	    <input type="radio" name="rating" value="1">
	    <input type="radio" name="rating" value="2">
	    <input type="radio" name="rating" value="3">
	    <input type="radio" name="rating" value="4">
	    <input type="radio" name="rating" value="5">
	</form>
	
	<!-- Here radios have a default value, 1.5 -->
	<form name="simple">
	    <label>Some options:</label>
	    <input type="radio" name="my_rating" value="0.5">
	    <input type="radio" name="my_rating" value="1.0">
	    <input type="radio" name="my_rating" value="1.5" checked>
	    <input type="radio" name="my_rating" value="2.0">
	    <input type="radio" name="my_rating" value="2.5">
	    <input type="radio" name="my_rating" value="3.0">
	    <span id="simpleTip"></span>
	</form>


Docs
----------

Implements:

	Options, Events

Syntax and options:

	var myRating = new MooStarRating(options);
	
	options (object): 
		Initial options for the class. Options are:
			form: Target form name, ID or element.
			radios: Target radio input boxes name (default "rating")
			half: TRUE if each star is used for two input values (dafult FALSE).
			disabled: TRUE if user cannot set rate value (default FALSE, or
				TRUE if input radio boxes has got the "disabled" attribute).
			linksClass: Links class name, for custom styling (default "star").
			imageFolder: Image folder path, relative or absolute (default "").
			imageEmpty: Empty star image name (default "star_empty.png").
			imageFull: Empty star image name (default "star_full.png").
			imageHover: Empty star image name (default as imageFull).
			width: Links width (default 16).
			height: Links height (default 16).
			tip: Tip text, you can use [VALUE] and [COUNT] as placeholders
				(default none).
			tipTarget: ID or element that will contain the tip (default none,
				the tip will be set as link title).
			tipTargetType: tip text type, "text" or "html" (default "text").

Events:

	click(value): 
		Fires when a star is clicked and value is set as radios input value.
	
	mouseenter(value):
		Stars rollover. Value under mouse position is given.
	
	mouseleave():
		Stars rollout.

Methods:

	setValue(value): 
		Manually set the rating value.
	
	getValue(): 
		Get the rating value.
	
	enable(): 
		Enables rating.
	
	disable(): 
		Disables rating.
	
	refresh():
		Force stars redrawing.
