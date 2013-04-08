// $Copyright$

var K2KomentoRate = new Class({
        Implements: [Options],
        
        rateElements:[],
        
        initialize: function(options) {
                this.setOptions(options);
                this.createRate();
                
                $$('.kmt-sorting a, .kmt-fame-tabs a').addEvent('click', function() {
                        var tab;
                        
                        var tmerSort = (function () {
                                tab = document.getElement('.'+this.get('tab'));
                                
                                if (tab) {
                                        tab = tab.getElement('.'+this.get('tab'));
                                        
                                        if (tab) {
                                                if (tab.get('loaded') == "1") {
                                                        clearInterval(tmerSort);
                                                        new JPProcessor().accordion(tab);
                                                }
                                        }
                                }
                        }.bind(this)).periodical(1000);
                });
        },
        
        createRate: function() {
                var 
                        frm = document.id('kmt-form').getElement('form'),
                        itemid,
                        key, lbl, opts, criteria, i, n, sel, selc, opt, ratec, id, ui, width, criterias = [], rEl;
                     
                if (!frm) {
                        var tmerFrm = (function () {
                                if (document.id('comments-form')) {
                                        clearInterval(tmerFrm);
                                        this.createRate();
                                }
                        }.bind(this)).periodical(1000);
                        
                        return;
                }
                
                itemid = Komento.cid;
                ratec = new Element('div', {'class':'ratescontainer'});
                ratec.inject(frm, 'top');
                
                for (key in this.options.rateDefinition) {
                        key = Number.from(key);
                        
                        if (isNaN(key) || key == null) continue;
                        
                        criteria = this.options.rateDefinition[key];
                        lbl = criteria[0];
                        opts = [['', lbl]].combine(criteria[2]);
                        ui = criteria[4];
                        id = 'k2frate_'+itemid+'_'+key;
                        
                        if (ui == 'select') {
                                sel = new Element('select', {'name':id});
                        } else if (ui == 'radio') {
                                sel = new Element('fieldset', {'class':'rateopts'});
                                new Element('legend', {html:opts[0][1]}).inject(sel);
                                width = Math.floor(1/(opts.length - 1) * 100 - 1);
                        } else if (ui == 'stars') {
                                sel = new Element('div', {'class':'rateopts'});
                                new Element('span', {html:opts[0][1], 'class':'ratelbl'}).inject(sel);
                                sel = new Element('span', {'class':'ratestars'}).inject(sel);
                        }
                        criterias = [];
                        for (i = 0, n = opts.length; i < n; i++) {
                                if (ui == 'select') {
                                        opt = new Option(opts[i][1], opts[i][0]);
                                        opt = document.id(opt);
                                        opt.innerHTML = opts[i][1];
                                } else if (ui == 'radio') {
                                        if (i == 0) continue;
                                        opt = new Element('span');
                                        rEl = new Element('input', {type:'radio','name':id,id:id+i,value:opts[i][0]}).inject(opt);
                                        new Element('label', {html:opts[i][1],'for':id+i}).inject(opt);
                                        opt.setStyle('width', width+'%');
                                } else if (ui == 'stars') {
                                        if (i == 0) continue;
                                        
                                        opt = new Element('input', {type:'radio','name':id,id:id+i,value:opts[i][0]});
                                        rEl = opt;
                                        criterias.push(opts[i][1]);
                                }
                                
                                opt.inject(sel);
                        }
                        
                        if (ui == 'select') {
                                selc = new Element('div', {'class':'rateopts'});
                                sel = sel.inject(selc);
                                selc.inject(ratec);
                                rEl = sel;
                        } else {
                                sel.getParent().inject(ratec);
                        }
                        
                        if (ui == 'stars') {
                                new Element('br')
                                new Element('span', {'id':id+'tip','class':'ratetip'}).inject(sel);
                                new Element('span', {'id':id+'tipstatic','class':'ratetipstatic'}).inject(sel);
                                
                                /* tipTarget: document.id(id+'tip'), tipTargetType: 'html', */
                                rEl = new MooStarRating({
                                        linksClass: 'ratestarbtn',
                                        imageFolder: this.options.base,
                                        imageEmpty: 'rating_stars.png',
                                        imageFull: 'rating_stars.png',
                                        imageHover: 'rating_stars.png',
                                        form: frm,
                                        radios: id,
                                        half: false,
                                        tip: '[CRITERIA]',
                                        criterias: criterias
                                });
                                
                                rEl.addEvent('click', function(v) {
                                        this.radios[0].getParent().getElement('.ratetipstatic').set('html', this.options.criterias[v.toInt()-1]);
                                        //document.id(this.options.tipTarget.get('id')+'static').set(this.options.tipTargetType, this.stars[v.toInt()-1].retrieve('ratingTip'));
                                });
                                
                                rEl.stars.each(function(s) {
                                        s.addEvents({
                                                'mouseenter': function () {
                                                        s.getNext('.ratetipstatic').setStyle('display', 'none');
                                                }.bind(this),
                                                'mouseleave': function () {
                                                        s.getNext('.ratetipstatic').setStyle('display', 'inline');
                                                }.bind(this) 
                                        });
                                });
                                //new Element('div', {'class':'clr'}).inject(sel, 'bottom');
                        }
                                                
                        this.rateElements.push(rEl);
                }
                
                Komento.rater = this;
        },
        
        resetRating: function() {
                var tips;
                for (var i = 0, n = this.rateElements.length; i < n; i++) {
                        if (typeOf(this.rateElements[i]) == 'object') {
                                this.rateElements[i].setCurrentIndex();
                                tips = this.rateElements[i].radios[0].getParent().getElement('.ratetipstatic');
                                tips.set('html', '');
                        }
                        else this.resetValue(this.rateElements[i]);
                }
        }
});
