/**
 * @version		$Id:  $Revision
 * @package		mootool
 * @subpackage	lofslidernews
 * @copyright	Copyright (C) JAN 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>. All rights reserved.
 * @website     http://landofcoder.com
 * @license		This plugin is dual-licensed under the GNU General Public License and the MIT License 
 */
if( typeof(LofK2SlideShow) == 'undefined' ){
        var LofK2SlideShowsCreator = new Class( {
                initialize:function(eMainIds, lofOptions, options){
                        var el, lof;
                        for (var i = 0, n = eMainIds.length; i < n; i++) {
                                el = $(eMainIds[i]);
                                lof = new LofK2SlideShow(
                                        el.getElement('.lof-main-wapper'), 
                                        el.getElement('.lof-navigator-outer .lof-navigator'),
                                        el.getElement('.lof-navigator-outer'),
                                        lofOptions
                                );
                                if (options.displayButton) {
                                        lof.registerButtonsControl( 
                                                'click', 
                                                {
                                                        next:el.getElement('.lof-next'),
                                                        previous:el.getElement('.lof-previous')
                                                } 
                                        );
                                }
                                lof.start(options.autoStart, el.getElement('.preload'));
                        }
                }
        });
        
	var LofK2SlideShow = new Class( {
		initialize:function( eMain, eNavigator,eNavOuter, options ){
			this.setting = $extend({
				autoStart			: true,
				descStyle	    	: 'sliding',
				mainItemSelector    : 'div.lof-main-item',
				navSelector  		: 'li' ,
				navigatorEvent		: 'click',
				interval	  	 	:  2000,
				auto			    :  false,
				navItemsDisplay:3,
				startItem:0,
				navItemHeight:100,
				navItemWidth:310,
				descOpacity:0.8
			}, options || {} );
			this.currentNo  = 0;
			this.nextNo     = null;
			this.previousNo = null;
			this.fxItems	= [];	
			this.minSize 	= 0;
			if( $defined(eMain) ){
				this.slides	   = eMain.getElements( this.setting.mainItemSelector );
				this.maxWidth  = eMain.getStyle('width').toInt();
				this.maxHeight = eMain.getStyle('height').toInt();
				this.styleMode = this.__getStyleMode(); 
				this.descriptions =[];
				var fx =  $extend( {waiting:false}, this.setting.fxObject );
				this.slides.each( function(item, index) {
					item.setStyles( eval('({"'+this.styleMode[0]+'": index * this.maxSize,"'+this.styleMode[1]+'":Math.abs(this.maxSize),"display" : "block"})') );		
					this.fxItems[index] = new Fx.Morph( item,  fx );
					if( item.getElement(".lof-description") ) {
						this.descriptions[index] = new Fx.Morph(item.getElement(".lof-description"));
						if(index!=0 && $defined(this.descriptions[index]) ){
							this.descriptions[index].start({"opacity":0});
						}
					}
				}.bind(this) );
				if( this.styleMode[0] == 'opacity' || this.styleMode[0] =='z-index' ){
					this.slides[0].setStyle(this.styleMode[0],'1');
				}
				eMain.addEvents( { 'mouseenter' : this.stop.bind(this),
							   	   'mouseleave' :function(e){ 
								   if( this.setting.auto  ) {
									this.play( this.setting.interval,'next', true ); } }.bind(this) } );
			}
			// if has the navigator
			if( $defined(eNavigator) ){
				this.navigatorItems = eNavigator.getElements( this.setting.navSelector );
				if( this.setting.navItemsDisplay > this.navigatorItems.length ){
					this.setting.navItemsDisplay = this.navigatorItems.length;	
				}
					eNavOuter.setStyles( {'height':this.setting.navItemsDisplay*this.setting.navItemHeight,
								   'width':this.setting.navItemWidth});
				this.navigatorFx = new Fx.Morph( eNavigator,
												{transition:Fx.Transitions.Quad.easeInOut,duration:800} );
				if( this.setting.auto  ) {
					this.registerMousewheelHandler( eNavigator ); // allow to use the srcoll
				}
				this.navigatorItems.each( function(item,index) {
					item.addEvent( this.setting.navigatorEvent, function(){													 
						this.jumping( index, true );
						this.setNavActive( index, item );	
					}.bind(this) );
						item.setStyles( { 'height':this.setting.navItemHeight,
									  	  'width'  : this.setting.navItemWidth} );
				}.bind(this) );
				this.setNavActive( 0 );
			}
		},
		navivationAnimate:function( currentIndex ) { 
			if (currentIndex <= this.setting.startItem 
				|| currentIndex - this.setting.startItem >= this.setting.navItemsDisplay-1) {
					this.setting.startItem = currentIndex - this.setting.navItemsDisplay+2;
					if (this.setting.startItem < 0) this.setting.startItem = 0;
					if (this.setting.startItem >this.slides.length-this.setting.navItemsDisplay) {
						this.setting.startItem = this.slides.length-this.setting.navItemsDisplay;
					}
			}		
			this.navigatorFx.cancel().start( { 'top':-this.setting.startItem*this.setting.navItemHeight} );	
		},
		setNavActive:function( index, item ){
			if( $defined(this.navigatorItems) && this.navigatorItems.length > 0){ 
				this.navigatorItems.removeClass('active');
				this.navigatorItems[index].addClass('active');	
				this.navivationAnimate( this.currentNo );	
			}
		},
		__getStyleMode:function(){
			switch( this.setting.direction ){
				case 'opacity': this.maxSize=0; this.minSize=1; return ['opacity','opacity'];
				case 'vrup':    this.maxSize=this.maxHeight;    return ['top','height'];
				case 'vrdown':  this.maxSize=-this.maxHeight;   return ['top','height'];
				case 'hrright': this.maxSize=-this.maxWidth;    return ['left','width'];
				case 'hrleft':
				default: this.maxSize=this.maxWidth; return ['left','width'];
			}
		},
		registerMousewheelHandler:function( element ){ 
			element.addEvent( 'mousewheel', function(e){
				e.stop();
				if( e.wheel > 0  ){
					this.previous(true);	
				} else {
					this.next(true);	
				}
			}.bind(this) );
		},
		registerButtonsControl:function( eventHandler, objects, isHover ){
			if( $defined(objects) && this.slides.length > 1 ){
				for( var action in objects ){ 
					if( $defined(this[action.toString()])  && $defined(objects[action]) ){
						objects[action].addEvent( eventHandler, this[action.toString()].bind(this, [true]) );
					}
				}
			}
			return this;	
		},
		start:function( isStart, obj ){
			this.setting.auto = isStart;
			// if use the preload image.
			if( obj ) {
				var images = [];
                                for (var i = 0, n = this.slides.length; i < n; i++) {
                                        this.slides[i].getElements('img').each(function(item, index){
                                                images[index] = item.get('src');
                                        }.bind(this));
                                }
                                if (images.length) {
                                        new Asset.images(images, { onComplete:function(){	
                                                (function(){ obj.fade('out')  ;}).delay(400);		

                                                if( isStart && this.slides.length > 0 ){this.play( this.setting.interval,'next', true );}	
                                        }.bind(this) } ); 
                                } else {
                                        (function(){ obj.fade('out')  ;}).delay(400);		

                                        if( isStart && this.slides.length > 0 ){this.play( this.setting.interval,'next', true );}	
                                }
			} else {
				if( isStart && this.slides.length > 0 ){this.play( this.setting.interval,'next', true );}	
			}
		},
		onProcessing:function( manual, start, end ){			
			this.previousNo = this.currentNo + (this.currentNo>0 ? -1 : this.slides.length-1);
			this.nextNo 	= this.currentNo + (this.currentNo < this.slides.length-1 ? 1 : 1- this.slides.length);				
			return this;
		},
		finishFx:function( manual, currentNo ){
			if( manual ) this.stop();
			if( manual && this.setting.auto ){ 
				this.setNavActive( currentNo );	
				this.play( this.setting.interval,'next', true );
			} else {
                                this.setNavActive( currentNo );	
                        }
			if( $defined(this.descriptions[currentNo]) && $defined(this.descriptions[currentNo].start) ){
				for( i = 0; i < this.descriptions.length;i++ ){
					this.descriptions[i].start({"opacity":0});
				}
				this.descriptions[currentNo].cancel().start({"opacity":this.setting.descOpacity});
			}
		},
		getObjectDirection:function( start, end ){
			return eval("({'"+this.styleMode[0]+"':["+start+", "+end+"]})");	
		},
		fxStart:function( index, obj ){
			this.fxItems[index].cancel().start( obj );
			return this;
		},
		jumping:function( no, manual ){
			this.stop();
			if( this.currentNo == no ) return;		
			this.onProcessing( null, manual, 0, this.maxSize )
				.fxStart( no, this.getObjectDirection(this.maxSize , this.minSize) )
				.fxStart( this.currentNo, this.getObjectDirection(this.minSize,  -this.maxSize) )
				.finishFx( manual, no );	
				this.currentNo  = no;
		},
		next:function( manual , item){
			this.currentNo += (this.currentNo < this.slides.length-1) ? 1 : (1 - this.slides.length);	
			this.onProcessing( item, manual, 0, this.maxSize )
				.fxStart( this.currentNo, this.getObjectDirection(this.maxSize ,this.minSize) )
				.fxStart( this.previousNo, this.getObjectDirection(this.minSize, -this.maxSize) )
				.finishFx( manual ,this.currentNo );
		},
		previous:function( manual, item ){
			this.currentNo += this.currentNo > 0 ? -1 : this.slides.length - 1;
			this.onProcessing( item, manual, -this.maxWidth, this.minSize )
				.fxStart( this.nextNo, this.getObjectDirection(this.minSize, this.maxSize) )
				.fxStart( this.currentNo,  this.getObjectDirection(-this.maxSize, this.minSize) )
				.finishFx( manual ,this.currentNo	);			
		},
		play:function( delay, direction, wait ){
			this.stop(); 
			if(!wait){ this[direction](false); }
			this.isRun = this[direction].periodical(delay,this,true);
		},stop:function(){; $clear(this.isRun ); }
	} );
}
