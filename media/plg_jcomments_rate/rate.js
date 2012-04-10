// $Copyright$

var JcommentsRate = new Class({
        Implements: [Options],
        
        initialize: function(options) {
                this.setOptions(options);
                this.createRate();
        },
        
        createRate: function() {
                var 
                        frm = document.id('comments-form'),
                        itemid, contentidName = 'object_id',
                        key, lbl, opts, criteria, i, n, sel, selc, opt, ratec, id, ui, width, criterias = [], rEls = [], rEl;
                     
                if (!frm) {
                        var tmerFrm = (function () {
                                if (document.id('comments-form')) {
                                        clearInterval(tmerFrm);
                                        this.createRate();
                                }
                        }.bind(this)).periodical(1000);
                        
                        return;
                }
                
                itemid = frm.getElements('input[name='+contentidName+']')[0].get('value');
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
                                sel.inject(ratec);
                        }
                        
                        if (ui == 'stars') {
                                new Element('span', {'id':id+'tip','class':'ratetip'}).inject(sel);
                                new Element('span', {'id':id+'tipstatic','class':'ratetipstatic'}).inject(sel);
                                
                                rEl = new MooStarRating({
                                        imageFolder: this.options.base,
                                        imageEmpty: 'rating_stars.png',
                                        imageFull: 'rating_stars.png',
                                        imageHover: 'rating_stars.png',
                                        form: frm,
                                        radios: id,
                                        half: false,
                                        tip: 'Rate <i>[CRITERIA]</i>',
                                        tipTarget: document.id(id+'tip'),
                                        tipTargetType: 'html',
                                        criterias: criterias
                                });
                                
                                rEl.addEvent('click', function(v) {
                                        document.id(this.options.tipTarget.get('id')+'static').set(this.options.tipTargetType, this.stars[v.toInt()-1].retrieve('ratingTip'));
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
                        }
                                                
                        rEls.push(rEl);
                }
                
                document.id('comments-form-buttons').getElement('a').addEvent('click', function() {
                        var tmer = (function () {
                                if (document.id('jc').getElement('div.busy').getStyle('display') == 'none') {
                                        clearInterval(tmer);

                                        this.resetRating(rEls);
                                        var tips, i, n = rEls.length;
                                        for (i = 0; i < n; i++) {
                                                tips = document.id(rEls[i].options.tipTarget.get('id')+'static');
                                                tips.set(rEls[i].options.tipTargetType, '');
                                        }
                                }
                        }.bind(this)).periodical(500);
                }.bind(this));
                
                // frm.addEvent('reset', resetter.bind(this));
        },
        
        resetRating: function(els) {
                for (var i = 0, n = els.length; i < n; i++) {
                        if (typeOf(els[i]) == 'object') els[i].setCurrentIndex();
                        else this.resetValue(els[i]);
                }
        }
});
