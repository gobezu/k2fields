/*
---

name: Slider.Range

license: MIT-style license.

copyright: Copyright (c) 2011 [Ryan Florence](http://ryanflorence.com/).

author: Ryan Florence

requires:
  - More/Slider

provides: [Slider.Range]

...
*/
!function (){

var MonkeyPatch = new Class({
	Extends: Slider,
	initialize: function (){
		this.parent.apply(this, arguments);
		this.knob.setStyle('position', '');
		this.knob.setStyle('position', this.knob.getStyle('position') == 'static' ? 'relative' : '');
	},
	clickedElement: function (){ return; }
});

this.Slider.Range = new Class({

	Implements: [Options, Events],

	options: $extend({
		knob: '.knob',
		input: 'input',
		initialSteps: [0, 100],
		collision: true
	}, Slider.prototype.options),

	initialize: function (element, options){
		this.setOptions(options);
		this.element = document.id(element);
		this.knobs = this.element.getElements(this.options.knob);
		this.inputs = this.element.getElements(this.options.input);
		this.sliders = [];
		this.createSliders();
		this.firstKnobWidth = this.knobs[0].getSize().x;
	},

	createSliders: function (){
		var chain = [];

		(2).times(function (i){
			var initialStep = this.options.initialSteps[i],
				options = $merge({}, this.options, {initialStep: initialStep}),
				slider = new MonkeyPatch(this.element, this.knobs[i], options);

			slider.addEvent('change', function (step){
				this.change(i, step);
			}.bind(this));

			if (this.options.collision){
				slider.drag.addEvent('drag', function (){
					this.setLimits(i)
				}.bind(this));
			}

			this.sliders.push(slider);

			chain.push(function (){
				this.change(i, initialStep);
			}.bind(this))
		}.bind(this));

		chain.each(function (fn){ fn(); });

		this.sliders.each(function (slider, i){
			var style = slider.drag.element.getStyle('left').toInt();
			slider.drag.value.now.x = style;
		}, this);
//                .each(function (slider, i){
//			this.setLimits(i);
//		}, this);
//		
	},

	setLimits: function (i){
		var limit = {},
			slider = this.sliders[i],
			other =  this.sliders[!i + 0]

		limit.x = (i === 1)
			? [-slider.options.offset, slider.drag.value.now.x]
			: [-slider.options.offset + slider.drag.value.now.x, slider.full - slider.options.offset];

		other.drag.options.limit = limit;
	},

	change: function (i, step){
		this.inputs[i].value = step;
		this.fireEvent('change-' + i, step);
	}
});
	
}.call(this);
