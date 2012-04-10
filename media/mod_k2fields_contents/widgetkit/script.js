//$Copyright$

var WKK2fields = new Class({
        initialize: function(tab) {
                window.addEvent('domready', function() {
                        this.init(tab);
                }.bind(this));
        },
        
        init: function(tab) {
                if (tab) {
                        tab = document.id(tab);
                        tab.getElements('dt').each(function(aTab, i) {
                                aTab.addEvent('click', function() { 
                                        this.adjustSlideshowSize(aTab.getParent().getNext().getChildren()[i].getChildren()[0]); 
                                }.bind(this));
                                this.adjustSettings(aTab.getParent().getNext().getChildren()[i].getChildren()[0]);
                        }.bind(this));
                } else {
                        $$('div.wk-slideshow').each(function(s) {
                                this.adjustSettings(s);
                        }.bind(this));
                        
                }
        },
        
        adjustSlideshowSize: function(s) {
                s = document.id(s);
                
                if (s.getSize().x == 0) {
                        var size = s.getParent('div.current').getSize(), ul = s.getElement('ul.slides'), lis = ul.getChildren(), d = 0;

                        s.setStyle('width', size.x);

                        size = ul.getSize();

                        lis.each(function(li) { 
                                li.setStyle('width', size.x);
                                d = Math.max(d, li.getChildren()[0].getSize().y);
                        }.bind(this));

                        ul.setStyle('height', d);
                        
                }
        },
        
        adjustSettings: function(s) {
                s = document.id(s);
                var settings = s.get('data-options');
                if (settings) {
                        settings = JSON.decode(settings);
                        if (settings.buttons) s.addClass('wk-btns');
                }
        },
        
        adjustStarting: function(s) {
                s = document.id(s);
                var settings = s.get('data-options');
                if (settings) {
                        settings = JSON.decode(settings);
                        if (settings.autoplay) 
                                s.addClass('wk-btns');
                }                
        }
});