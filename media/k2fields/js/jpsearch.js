//$Copyright$

var JPSearch = new Class({
        options: {
                postVar: 'ft',
                minLength: 3,
                maxChoices: 10,
                autoSubmit: false,
                cache: true,
                filterSubset: false,
                moreResultsUrl: '',
                headerTxt:"Search Results",
                moreResultsTxt:"See all results for ",
                placeHolderClass:'placeholder',
                placeHolder:'Search...',
                advancedSearchContainer:'ascontainer',
                togglerElement:'cid',
                whenTogglerEmpty:'inactive',
                defaultMode:'active',
                dontShowIn:[]
        },

        currentChoice: 0,
        moreResultsAvailable: false,

        Implements : [ Options ],
        Extends: Autocompleter.Request.JSON,

        initialize: function(el, options, appendElements) {
                this.options.className = 'autocompleter-choices-search';
                this.options.indicatorClass = 'progress';

                options = Object.merge(this.options, options, {injectChoice:this._injector});

                this.parent(el, options.postUrl, options);

                this.addEvent('onRequest', function(el, req, data, val) {
                        if (appendElements) {
                                appendElements = Array.from(appendElements);
                                var url = this.request.options.url, params = url.fromQueryString(), name;
                                for (var i = 0, n = appendElements.length; i < n; i++) {
                                        name = appendElements[i];
                                        params[name] = document.getElement('[name='+name+']').get('value');
                                }
                                url = url.applyParams(params);
                                this.request.options.url = url;
                        }
                        this.currentChoice = 0;
                }.bind(this));

                if (appendElements) {
                        appendElements = Array.from(appendElements);
                        for (var i = 0, n = appendElements.length; i < n; i++) {
                                name = appendElements[i];
                                document.getElement('[name='+name+']').addEvent('change', function() {
                                        this.element.set('value', '');
                                        this.toggle(false);
                                }.bind(this));
                        }
                }

                this.addEvent('onShow', function(el, choices) {
                        var els = choices.getElements('li'), n = els.length - 1;
                        els.each(function(choice, i) {
                                if (i == 0) choice.addClass('first');
                                if (i == n - 1 && this.moreResultsAvailable) choice.addClass('last');
                        }.bind(this));
                });

                this.toggle(false);

                el.addEvents({
                        change:function(){ this.toggle(false); }.bind(this),
                        focus:function(){ this.toggle(true); }.bind(this),
                        blur:function(){ this.toggle(false); }.bind(this),
                        submit:function(){ this.element.get('value') == this.options.placeHolder && this.element.set("value", ""); }.bind(this)
                });

                var cid = document.id(this.options.togglerElement).get('value'), tog = this.element.getParent('li');

                document.id(this.options.togglerElement).addEvent('processingStart', function() {
                        cid = document.id(this.options.togglerElement).get('value');

                        if (this.options.dontShowIn.contains(cid)) {
                                tog.setStyle('display', 'none');
//                                                var fx = new Fx.Tween(tog, {'property':'visibility'});
//                                                fx.start(0);
                                this.element.set('value', '');
                        } else {
                                tog.setStyle('display', 'block');
                        }
                        this.toggleSearch('inactive');
                }.bind(this));

                document.id(this.options.togglerElement).addEvent('processingEnd', function() {
                        this.toggleSearch('active');
                }.bind(this));

                window.addEvent('load', function(){
                        if (this.options.togglerElement) {
                                if (this.options.dontShowIn.contains(cid)) {
                                        tog.setStyle('display', 'none');
                                        this.element.set('value', '');
                                } else {
                                        tog.setStyle('display', 'block');
                                }
                        }
                }.bind(this));

                document.id(this.element.form).addEvent('submit', function() {
                        this.element.get('value') == this.options.placeHolder &&
                                this.element.set('value', '');
                }.bind(this));
        },

        choiceSelect: function(el) {
                var a = el.getElement('a');
                if (a) document.location.href = a.get('href');
        },

        _injector: function(t) {
                this.currentChoice++;

                var choice = new Element("li");

                (new Element("span",{html:"<a href='"+t.url+"'>"+t.title+"</a>"})).inject(choice);
                (new Element("span",{html:t.text})).inject(choice);

                choice.inputValue = t.title;

                this.addChoiceEvents(choice).inject(this.choices);

                return choice;
        },

        toggle:function(reset){
                var val = this.element.get('value');
                if (val == "" || val == this.options.placeHolder) {
                        this.element[reset ? "removeClass" : "addClass"](this.options.placeHolderClass);
                        this.element.set("value", reset ? "" : this.options.placeHolder);
                }
        },

        toggleSearch: function(state) {
                var toggler = $$('#'+this.options.advancedSearchContainer+' .jptoggler')[0];

                if (!state) {
                        var cid = toggler.retrieve('cid');

                        if (cid) state = Cookie.read(cid);

                        if (!state) state = this.options.defaultMode;
                        else return;
                }

                var val = document.id(this.options.togglerElement).selectedIndex;

                if (val == undefined || val.toInt() == -1 || document.id(document.id(this.options.togglerElement).options[val.toInt()]).get('value') == '')
                        state = this.options.whenTogglerEmpty;

                if (!toggler.hasClass('jptoggler-'+state)) toggler.fireEvent('click', [toggler]);
        }
});