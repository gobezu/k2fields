//$Copyright$

var SlideShowCreator = Class({
        slideshows:[],
        navs:[],
        initialize: function(shows, options) {
                document.addEvent('domready', function(){ this.init(shows, options); }.bind(this));
        },
        init: function(shows, options) {
                // show navigator
                // show tip
                // pause on mouseenter
                shows = shows || [];
                
                var opts;
                
                shows.each(function(aShow, showIndex) {
                        this.navs[showIndex] = false;
                        
                        if (document.id(aShow).getNext('ul'))
                                this.navs[showIndex] = document.id(aShow).getNext('ul').getElements('a');
                         
                        opts = Object.merge({
                                onShow: function(data){
                                        if (this.navs[showIndex]) {
                                                this.navs[showIndex][data.previous.index].removeClass('current');
                                                if (data.next.index == this.navs[showIndex].length) data.next.index = 0;
                                                this.navs[showIndex][data.next.index].addClass('current');
                                        }
                                }.bind(this)
                        }, options);
                                                
                        this.slideshows[showIndex] = new SlideShow(aShow, opts);
                        
                        if (this.navs[showIndex] && this.navs[showIndex].length > 0) {
                                this.navs[showIndex].each(function(item, index){
                                        item.addEvent('click', function(event){
                                                event.stop();
                                                // pushLeft or pushRight, depending upon where
                                                // the slideshow already is, and where it's going
                                                var transition = (this.slideshows[showIndex].index < index) ? 'pushLeft' : 'pushRight';
                                                // call show method, index of the navigation element matches the slide index
                                                // on-the-fly transition option
                                                this.slideshows[showIndex].show(index, {transition: transition});
                                        }.bind(this));
                                }.bind(this));
                        
                                new Tips(this.navs[showIndex], {
                                        fixed: true,
                                        text: '',
                                        offset: {
                                                x: -100,
                                                y: -60
                                        },
                                        className:'rpflorence-SlideShow-tip-wrap'
                                });
                        }

                        document.id(aShow).addEvents({
                                'mouseenter':function(){
                                        this.slideshows[showIndex].pause();
                                }.bind(this),
                                'mouseleave':function(){
                                        this.slideshows[showIndex].play();
                                }.bind(this)
                        });                         
                }.bind(this));
        }
});