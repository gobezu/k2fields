//$Copyright$

var JPProcessor = new Class({
        Implements: [Options],

        options: {
                idPrefix:'jpproc',
                modal: {selector:'.jpmodal', handler:'iframe', size:{x:990, y:650}},
                pageClass:'jppage',
                accordion: {
                        selector:{togglers:'.jpcollapse', elements:'', sets:'.itemList', done:'.jpcollapsed'}, 
                        alwaysHide:true,
                        show:-1, 
                        display:-1,
                        onActive: function(toggler, element) {
                                toggler.removeClass('jptoggler-inactive').addClass('jptoggler-active');
                                
                                if (this.isInitial) return;
                                
                                if (this.persist) {
                                        var index = this.findIndex(element);
                                        this.setState(index);
                                }
                        },
                        onBackground: function(toggler, element) { 
                                toggler.removeClass('jptoggler-active').addClass('jptoggler-inactive');
                                if (this.isInitial) return;
                                if (this.persist) this.setState(-1);
                        },
                        preventLinkClicks:true
                },
                ajax: {selector:{togglers:'.jpajax', elements:''}},
                paginate: {selector:'k2fPageBtn', busyLabel:'Loading...'},
                returnvalue: {},
                tip: {selector:'jptips'},
                jmodal: {}
        },

        cnt:0,
        isInitial:true,
        currentPaginator:false,
        accordionSets:[],

        initialize: function(options) {
                options = Object.merge(this.options, options);
                this.setOptions(options);
        },

        process: function(c, o) {
                if (!c) {
                        var c = $$('.'+this.options.pageClass);
                        if (c) c = c[c.length - 1];
                        if (!c) c = document.id(document.body);
                }

                if (o) o = Array.from(o);

                if (!o || o.contains('modal')) this.modal(c);
                if (!o || o.contains('accordion')) this.accordion(c);
                if (!o || o.contains('ajax')) this.ajax(c);
                if (!o || o.contains('jmodal')) this.jmodal(c);
                if (!o || o.contains('returnvalue')) this.returnvalue(c);

                if (this.isInitial) {
                        if (!o || o.contains('paginate')) this.paginate(c);
                        if (!o || o.contains('tip')) this.tip(c);
                        this.isInitial = false;
                }

                window.removeEvent('domready', this.ev);
        },

        isProcessed:function(c) {return c.getElement('.jpprocessed');},
        setProcessed:function(c) {new Element('div', {style:{display:'none'}, 'class':'jpprocessed'}).inject(c);},

        modal: function(c, selector, options) {
                if (!selector) selector = this.options.modal.selector;
                if (!options) options = {};
                c = document.id(c);
                options = Object.merge(options, this.options.modal);
                var els = c.getElements(selector);
                els.each(function(el) {SqueezeBox.assign(el, options);}.bind(this));
        },

        accordion: function(c, selector, options) {
                var set = this.setupCollapsibleElements('accordion', c, selector, options);

                if (!set) return;
                
                if (!JPProcessor.accordionSets) JPProcessor.accordionSets = [];

                var sets = Array.from(this.options.accordion.selector.sets), i, n = set[0].length, j, m = sets.length, found;
                
                for (i = 0; i < n; i++) {
                        found = false;

                        for (j = 0; j < m; j++) {
                                if (found = document.id(set[0][i][0]).getParent(sets[j])) {
                                        break;
                                } 
                        }

                        if (found) {
                                if (JPProcessor.accordionSets[j]) {
                                        JPProcessor.accordionSets[j][1].push(set[0][i][0]);
                                        JPProcessor.accordionSets[j][2].push(set[1][i][0]);
                                } else {
                                        JPProcessor.accordionSets[j] = [];
                                        JPProcessor.accordionSets[j][0] = set[3][i];
                                        JPProcessor.accordionSets[j][1] = [set[0][i][0]];
                                        JPProcessor.accordionSets[j][2] = [set[1][i][0]];
                                }
                        } else {
                                if (!document.id(set[0][i][0]).hasClass(this.options.accordion.done)) {
                                        new JPAccordion(set[3][i], set[0][i], set[1][i], Object.clone(set[2]), this.options.accordion.preventLinkClicks);
                                        document.id(set[0][i][0]).addClass(this.options.accordion.done);
                                }
                        }
                }
                
                n = JPProcessor.accordionSets.length;
                
                for (i = 0; i < n; i++) {
                        if (!document.id(JPProcessor.accordionSets[i][1][0]).hasClass(this.options.accordion.done)) {
                                new JPAccordion(
                                        JPProcessor.accordionSets[i][0], 
                                        JPProcessor.accordionSets[i][1], 
                                        JPProcessor.accordionSets[i][2],
                                        Object.clone(set[2]),
                                        this.options.accordion.preventLinkClicks
                                );
                                document.id(JPProcessor.accordionSets[i][1][0]).addClass(this.options.accordion.done);
                        }
                }
        },

        ajax: function(c, selector, options) {
                if (!options) options = {};

                options = Object.merge(options, this.options.accordion);

                var set = this.setupCollapsibleElements('ajax', c, selector, options);

                if (!set) return;

                for (var i = 0, n = set[0].length; i < n; i++) {
                        new JPAccordion(set[3][i], set[0][i], set[1][i], set[2]);
                }
        },

        setupCollapsibleElements: function(type, c, selector, options) {
                if (!c) c = document.body;

                var typeOptions = Object.clone(this.options[type]);

                if (!selector) selector = typeOptions.selector;

                delete typeOptions['selector'];

                if (!options) options = {};

                c = document.id(c);

                options = Object.merge(options, typeOptions);

                var togglers = c.getElements(selector['togglers']), elements, a;

                if (!togglers || togglers.length == 0) return;

                if (!selector['elements']) {
                        var togglersSet = [], elementsSet = [], setStart = 0, j, k, i, n = togglers.length, jel, el, jels;

                        elements = [];

                        for (i = 0; i < n; i++) {
                                jels = [];
                                togglers[i].addClass('jptoggler jptoggler-inactive');
                                jel = togglers[i];
                                a = jel.getElement('a');
                                while (jel) {
                                        el = jel;
                                        jel = jel.getNext('.jpjump');
                                        if (jel)
                                                jels.push(jel);
                                }
                                el = el.getNext();
                                el.addClass('jpelement');
                                elements.push(el);
                                if (type == 'accordion' && a) {
                                        new Element('a', {'href':a.get('href'), 'html':'Read more', 'title':a.get('title') , 'class':'k2fbtn k2flinkbtn jpreadmore'}).inject(el);
                                }
                                for (j = 0, k = jels.length; j < k; j++) {
                                        jels[j].inject(el, 'after');
                                }
                                if (!togglers.contains(el.getNext())) {
                                        elementsSet.push(elements);
                                        togglersSet.push(togglers.slice(setStart, i+1));
                                        setStart = i+1;
                                        elements = [];
                                }
                        }

                        togglers = togglersSet;
                        elements = elementsSet;
                } else {
                        togglers = [togglers];
                        elements = [c.getElements(selector['elements'])];
                }

                var ids = [], id;

                for (i = 0, n = togglers.length; i < n; i++) {
                        this.cnt++;
                        id = this.options.idPrefix+this.cnt;
                        ids.push(id);
                        togglers[i].each(function(tog) {tog.store('cid', id);}.bind(this));
                }

                return [togglers, elements, options, ids];
        },

        deactivateLinks: function(els) {
                var links = [], a;
                els.each(function(el){
                        if (el.get('tag') == 'a' && !el.hasClass('readmore')) {
                                el.addEvent('click', function(e) { 
                                        e.stop(); 
                                });
                        }
                        a = el.getElement('a');
                        links.push(a ? a.get('href') : '');
                }.bind(this));
                return links;
        },

        paginate: function() {
                var btn = document.id(this.options.paginate.selector);

                if (!btn || btn.get('disabled')) return;

                var url = btn.get('link') || btn['link'];

                if (this.isInitial) {
                        if (url.indexOf('tmpl=component') < 0)
                                url += (url.indexOf('?') >= 0 ? '&' : '?') + 'tmpl=component';

                        btn.set('link', url);

                        btn.addEvent('click', function() {this.paginate();}.bind(this));

                        var limit = (btn.get('limit') || btn['limit']).toInt(), total = (btn.get('total') || btn['total']).toInt();

                        this.currentPaginator = limit;
                        this.options.paginate.limit = limit;
                        this.options.paginate.total = total;
                        this.options.paginate.label = btn.get('text');

                        return;
                }

                btn.set('html', this.options.paginate.busyLabel);
                btn.set('disabled', true);
                url = url.replace(/start=\d+/, 'start='+this.currentPaginator);

                var el = new Element('span', {'class':this.options.pageClass}).inject(document.id('k2Container'));

                // TODO: evalScripts:false, 
                new Request.HTML({
                        url:url,
                        update:el,
                        noCache:true,
                        onSuccess:function() {
                                this.currentPaginator += this.options.paginate.limit;
                                btn.set('disabled', false);
                                btn.set('html', this.options.paginate.label);
                                // this.process(el);
                                new Fx.Scroll(window).toElement(el);

                                if (this.currentPaginator >= this.options.paginate.total) {
                                        var fx = new Fx.Tween(btn, {'property':'opacity','duration':'short'});
                                        fx.start(0).chain(function(){this.element.setStyle('display', 'none');});
                                        return;
                                }
                        }.bind(this)
                }).get();
        },

        tip: function(c) {
                if (!c) c = document.body;

                c = document.id(c);

                var els = c.getElements('.'+this.options.tip.selector);

                if (els.length == 0) return;

                var tipEls = [];

                els.each(function(el) {
                        var tip = el.get(this.options.tip.selector), tipEl;
                        if (tip) {
                                tip = tip.split('::', 2);
                                tip[0] = tip[0];
                                tip[1] = this._tip(tip[1]);
                                tipEl = new Element('span', {'class':'jptip','text':el.get('text')});
                                el.empty();
                                tipEl.inject(el);
                                tipEl.store('tip:title', tip[0]).store('tip:text', tip[1]);
                                tipEls.push(tipEl);
                        }                        
                }.bind(this));

                new Tips(tipEls, {maxTitleChars: 50, fixed: false});
        },

        _tip:function(s) {
                if (s.indexOf('nlbrli') > -1) {
                        s = s.split('nlbrli');
                        s = '<ul><li>'+s.join('</li><li>')+'</li></ul>';
                }
                s = s.replace(/slsl/g, '/').replace(/ltlt/g, '<').replace(/gtgt/g, '>');
                s = s.replace('nlbr', '<br />');
                s = new Element('span', {'class':'nlbr','html':s});
                return s;
        },

        msg:'',
        returnURL:'',
        returnCurrent:false,

        jmodal: function(c) {
                if (!c) c = document.body;

                c = document.id(c);

                var i, n, selector, els, selectors = this.options.jmodal;

                if (!selectors) return;

                for (i = 0, n = selectors.length; i < n; i++) {
                        selector = selectors[i];

                        els = c.getElements('a[href^='+selector[0]+']');

                        if (els.length == 0) els = c.getElements('a[href^='+selector[1]+']');

                        if (els.length == 0) continue;

                        els.each(function(el) {
                                var options = {
                                        'iframeOptions' : {
                                                'events' : {
                                                        'load' : function(e){this.processFormSubmission(e);}.bind(this)
                                                }
                                        },
                                        size : {
                                                'x' : selector.length > 2 ? selector[2] : this.options.modal.size.x,
                                                'y' : selector.length > 3 ? selector[3] : this.options.modal.size.y
                                        },
                                        'handler' : this.options.modal.handler
                                };

                                this.addParam(el, 'tmpl', 'component');
                                this.addParam(el, 'from', 'jpmodal');

                                if (selector.length > 4) {
                                        this.msg = selector[4];

                                        if (selector.length > 5) {
                                                this.returnCurrent = selector[5] == 'true' ? 'current' : 'no';

                                                if (this.returnCurrent) {
                                                        this.returnURL = document.location.href;
                                                } else {
                                                        this.returnURL = selector[5];
                                                }
                                        }
                                }

                                if (selector.length > 7 && selector[6] == 'true')
                                        SqueezeBox.toggleListeners = function(state) {
                                                var fn = (state) ? 'addEvent' : 'removeEvent';
                                                this.closeBtn[fn]('click', this.bound.close);
                                                this.doc[fn]('keydown', this.bound.key)[fn]('mousewheel', this.bound.scroll);
                                                this.doc.getWindow()[fn]('resize', this.bound.window)[fn]('scroll', this.bound.window);
                                        };

                                SqueezeBox.assign(el, options);
                        }.bind(this));
                }
        },

        returnvalue: function(c) {
                if (!c) c = document.body;

                c = document.id(c);

                var i, n, selector, els, selectors = this.options.returnvalue;

                if (!selectors) return;

                for (i = 0, n = selectors.length; i < n; i++) {
                        selector = selectors[i];
                        els = c.getElements('a[href^='+selector[0]+']');

                        if (els.length == 0) els = c.getElements('a[href^='+selector[1]+']');

                        els.each(function(a) {
                                this.addParam(a, 'return', selector[2]);
                        }.bind(this));
                }
        },

        addParam: function(el, name, value) {
                if (el.get('href').indexOf(name+'='+value) < 0)
                        el.set('href', el.get('href')+(el.get('href').indexOf('?') >= 0 ? '&' : '?')+name+'='+value);
                return el;
        },

        processFormSubmission: function(e) {
                e = document.id(e.target);

                var frm = document.id(e.contentDocument.getElement('form'));

                if (!frm) return;

                new Element('input', {'type':'hidden', 'name':'from', 'value':'jpmodal'}).inject(frm);
                new Element('input', {'type':'hidden', 'name':'tmpl', 'value':'component'}).inject(frm);

                if (this.returnURL) {
                        if (frm.getElement('[name=return]')) 
                                frm.getElement('[name=return]').set('value', '');

                        new Element('input', {'type':'hidden', 'name':'jpreturn', 'value':this.returnCurrent}).inject(frm);
                        new Element('input', {'type':'hidden', 'name':'jpreturnurl', 'value':this.returnURL}).inject(frm);
                }

                if (this.msg)
                        new Element('input', {'type':'hidden', 'name':'jpmodalmsg', 'value':this.msg}).inject(frm);

                frm.set('send', {
                        noCache:true,
                        onComplete:function(d) {
                                d = JSON.decode(d);

                                if (d.msg) alert(d.msg);

                                if (d.url) document.location.href = d.url;
                                else if (!d.failure) window.parent.document.getElementById('sbox-window').close();
                        }
                });

                frm.addEvent('submit', function(e) {
                        e.stop();
                        document.id(e.target).send();
                });
        }        
});

var JPAccordion = new Class({
        Extends: Fx.Accordion,
        vis:false,
        fetched:false,
        id:'',
        persist:false,
        ajax:false,
        preventLinkClicks:false,
        useStorage:true,
        isInitial:true,
        initialState:-1,
        initialize: function(id, togglers, elements, options, preventLinkClicks) {
                // Keeps all closed
                options.show = false;
                options.display = -1;
                
                this.id = id;
                this.persist = togglers[0].hasClass('jppersist');
                this.ajax = togglers[0].hasClass('jpajax');

                options['onActive'].bind(this);
                options['onBackground'].bind(this);

                if (options['alwaysHide'] == undefined) options['alwaysHide'] = true;

                if (this.persist) {
                        // Allows across pages sustained state
                        if (togglers[0].id) this.id = togglers[0].id;
                        
                        options.trigger = 'cclick';
                        
                        var index = this.getState();

                        // For our current purpose it suffices to assume that only 
                        // initial element is one to be activated
                        if (!index && togglers[0].hasClass('jpdefaultactive')) index = '0';
                        
                        if (index) this.initialState = index.toInt();
                        
                        togglers[0].addEvent('click', function(el) { 
                                if (this.isInitial) {
                                        if (this.initialState != -1) this.display(this.initialState);
                                        this.isInitial = false;
                                } else {
                                        togglers[0].fireEvent(options.trigger, [togglers[0]]);
                                }
                        }.bind(this));
                }
                
                this.preventLinkClicks = preventLinkClicks;
                this.preventClicks(togglers);
                this.preventClicks(elements);
                this.parent(togglers, elements, options);
        },
        resetState: function() {this.setState();},
        getState: function() {
                try {
                        return localStorage.getItem(this.id);
                } catch (e) {
                        return Cookie.read(this.id);
                }
//                if (Browser.Features.localStorage) {
//                        return localStorage.getItem(this.id);
//                } else {
//                        return Cookie.read(this.id);
//                }
        },
        setState: function(val) {
                try {
                        if (val == undefined) {
                                localStorage.removeItem(this.id);
                        } else {
                                localStorage.setItem(this.id, val)
                        } 
                } catch (e) {
                        if (val == undefined) {
                                Cookie.dispose(this.id);
                        } else {
                                Cookie.write(this.id, val);
                        }
                }
//                if (Browser.Features.localStorage) {
//                        if (val == undefined) {
//                                localStorage.removeItem(this.id);
//                        } else {
//                                localStorage.setItem(this.id, val)
//                        }
//                } else {
//                        if (val == undefined) {
//                                Cookie.dispose(this.id);
//                        } else {
//                                Cookie.write(this.id, val);
//                        }
//                }
        },
        preventClicks: function(els) {
                if (!this.preventLinkClicks) return;
                els = Array.from(els);
                els.each(function(el) {
                        document.id(el).getElements('a').each(function(a) {
                                if (!a.hasClass('jpreadmore'))
                                        a.addEvent('click', function(event) {event.preventDefault();});
                        });
                        
                        if (document.id(el).getParent('.jpcollapse') != null) {
                                el.addEvent('click', function(event) {
                                        event.stopPropagation();
                                });
                        }
                });                
        },
        addSection: function(toggler, element) {
                this.preventClicks(toggler);
                this.preventClicks(element);
                return this.parent(toggler, element);
        },
        display: function(index, useFx) {
                var update = this.elements[index];

                if (this.ajax && update) {
                        var url = update.get('href');

                        if (!this.vis && !this.fetched)
                                new Request.HTML({
                                        url:url, 
                                        update:update, 
                                        evalScripts:false, 
                                        async:false,
                                        onComplete:function() {new JPProcessor(update);}
                                }).get();

                        this.vis = !this.vis;
                        this.fetched = true;
                }

                if (!this.ajax) return this.parent(index, useFx);

                return this.parent(index, useFx).chain(function() {
                        if (update) {
                                if (update.getStyle('visibility') == 'hidden')
                                        update.setStyle('display', 'none');
                                else
                                        update.setStyle('display', 'block');
                        }
                }.bind(this));
        },
        findIndex: function(element) {
                var i, n;
                for (i = 0, n = this.elements.length; i < n; i++)
                        if (this.elements[i] == element)
                                break;
                return i < n && this.elements[i] == element ? i : -1;
        }
});
