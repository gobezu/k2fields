//$Copyright$

var k2fields = new Class({
        Implements: [Options, JPForm, k2fieldsoptions],
        
        options: {
                dateMin: new Date('1970-01-01'),
                dateMax: new Date('2099-12-31'),
                selectTreshold: 7,
                base: '',
                pre: 'K2ExtraField_',
                fieldsOptions: {},
                layout: 'table',
                async:true
        },
        fields: [],
        conditions: [],
        validator: null,
        utility: null,
        lookup: [],
        
        basicTypes: [
                'select', 
                'textarea', 
                'radio', 
                'checkbox', 
                'input', 
                'numeric', 
                'real', 
                'alpha', 
                'alphanum',
                'alphastrict',
                'alphanumstrict',
                'phone',
                'int',
                'integer',
                'text',
                'url',
                'email',
                'title'
        ],
        
        autoFields: ['title', 'rate'],
        
        initialize: function(options) {
                this.setOptions(options);
                
                this.options['datetimeFormat'] = this.convertPHPToJSDatetimeFormat(this.options['datetimeFormat']);
                this.options['dateFormat'] = this.convertPHPToJSDatetimeFormat(this.options['dateFormat']);
                this.options['timeFormat'] = this.convertPHPToJSDatetimeFormat(this.options['timeFormat']);
                
                if (this.isMode('editfields')) return;
                
                this.options.internalMode = this.isMode('menu') ? 'menu' : '';
                this.options.mode = this.isMode('menu') ? 'search' : this.options.mode;
                
                if (this.isMode('search')) this.assignName = true;
                
                window.addEvent('load', function(){
                        this.options['isNew'] = !this.isMode('search') && $$('input[name=id]')[0].value == '';
                        this.utility = new JPUtility({base:this.options.base,k2fbase:this.options.k2fbase});
                        
                        var c = this.categoryEl();
                        
                        if (!c) return;

                        $K2('#catid').unbind('change');
                        
                        c.addEvent('change', function(e) {
                                this.processingStart();
                                this.getFieldsDefinition();

                                if (this.isMode('search')) {
                                        this.createFields();
                                } else {
                                        var tmer = (function () {
                                                if (!document.id('extrafields-plch')) {
                                                        clearInterval(tmer);
                                                        this.createFields();
                                                }
                                        }.bind(this)).periodical(50);
                                }
                        }.bind(this));
                        
                        if (this.isIMode('menu')) {
                                this.menuItemHandler = new JPMenuItemHandler(this);
                                this.menuItemHandler.init();
                        }
                        
                        this.containerEl().addEvent('processingStart', function(el) {
                                var fx = new Fx.Tween(el, {'property':'visibility'});
                                fx.start(0);
                        });
                        
                        this.containerEl().addEvent('processingEnd', function(el) {
                                var fx = new Fx.Tween(el, {'property':'visibility'});
                                fx.start(1);
                        });
                        
                        // this.containerEl().setStyle('visibility', 'visible');
                        
                        this.processingStart();
                        this.wireForm();
                        this.extendK2fields('basic', true);
                        this.extendK2fields('datetime', true);
                        if (this.isMode('edit')) this.getFieldsDefinition();
                        this.createFields();
                }.bind(this));
        },
        
        isMode: function(assertedMode) {
                //if (assertedMode == 'editfields') assertedMode = 'k2fields-editor';
                return this.options.mode == assertedMode;
        },
        
        isIMode: function(assertedMode) {
                return this.options.internalMode == assertedMode;
        },
        
        getDefaultValue: function(proxyField) {
                var fix = this.isMode('edit') || this.isMode('editfields') ? '' : this.options.mode;
                return this.getOpt(proxyField, 'default'+fix);
        },
        
        modeFilter: function() { 
                if (this.isMode('search')) return 'search';
                
                return false;
        },
        
        processingStart: function() {
                if (this.isMode('search')) {
                        this.categoryEl().fireEvent('processingStart', [this.categoryEl()]);
                } else {
                        this.containerEl().fireEvent('processingStart', [this.containerEl()]);
                }
        },
        
        processingEnd: function() {
                if (this.isMode('search')) {
                        this.categoryEl().fireEvent('processingEnd', [this.categoryEl()]);
                } else {
                        this.containerEl().fireEvent('processingEnd', [this.containerEl()]);
                }
        },
        
        getFieldsDefinition: function() {
                var task = this.isMode('search') ? 'search' : '', cid = this.categoryId();
                
                if (!cid && task != 'search') return;
                
                this.processingStart();
                
                var url = 'index.php?option=com_k2fields&view=fields&task=retrieve&type='+task+'fields&cid='+cid, initState = this.categoryInitState();
                
                if (initState) url += '&'+initState;
                
                if (task != 'search') {
                        if (this.form()) {
                                var id = this.form().getElement('input[name=id]');

                                if (id) {
                                        id = id.get('value');
                                        url += '&id='+id;
                                }
                        }
                } else {
                        url += '&module='+this.form().getElement('[name=module]').get('value');
                        
                        if (!initState && this.options['initState']) {
                                var init = ('?'+this.options['initState']).fromQueryString();
                                if (cid == init['cid']) url += '&'+this.options['initState'];
                        }
                }
                
                new Request.HTML({
                        url: url,
                        method: 'get',
                        async: false,
                        update: this.containerEl(),
                        onSuccess: function(response){
                                if (task != 'search' && typeof initExtraFieldsEditor == 'function') {
                                        initExtraFieldsEditor();
                                }
                        }.bind(this)
                }).send();
        },
        
        categoryEl: function() {
                return document.id('cid') || document.id('catid');
        },
        
        categoryInitState: function() {
                var c = this.categoryEl();
                if (c.selectedIndex == -1) return false;
                c = document.id(c.options[c.selectedIndex]).get('init-state');
                return c;
        },
        
        categorySetItemid: function() {
                var c = this.categoryEl();
                if (c.selectedIndex == -1) return;
                c = c.options[c.selectedIndex].get('init-itemid');
                this.form().getElement('[name=Itemid]').set('value', c);
        },        
        
        categoryId: function() {
                var c = this.categoryEl();
                if (c.selectedIndex == -1) return false;
                c = c.options[c.selectedIndex].value;
                c = parseInt(c);
                return c;
        },
        
        containerEl: function() {
                return document.id('extraFieldsContainer');
        },
        
//        prepareSubmission: function() {
//                if (!this.validator.validateFields()) {
//                        alert('Please correct the errors and try again');
//                        return false;
//                }
//                
//                this.containerEl().getElements('input[type=radio]').set('name', '');
//                
//                return true;
//        },
//
        form: function() {
                return document.id(document.body).getElement('form[name='+(this.isMode('edit') ? 'admin' : 'search')+'Form]');
        },
        
        formSubmitButton: function() {
                return document.id('k2fSearchBtn');
        },
        
        formResetButton: function() {
                return document.id('k2fResetBtn');
        },
        
        actOnForm: function(a) {
                if (!this.validator.validateFields()) {
                        alert('Please correct the errors and try again');
                        return false;
                }
                
                if (this.isMode('edit')) {
                        if (this.categoryEl()) this.categoryEl().set('disabled', false);

                        this.containerEl().getElements('input[type=radio]').set('name', '');
                } else if (this.isMode('editfields')) {
                        k2fseditor.updateFieldDefinition();
                }
                
                var js = a.retrieve('js');
                
                if (js) {
                        ('<script>'+js+'</script>').replace(/([\n\s\;])return[^\;]+[\;]{0,1}/, '$1').stripScripts(true);
                }
                
                return true;
        },
        
        wireForm: function(frm) {
                if (this.isMode('edit') || this.isMode('editfields')) {
                        this.validator = new JPValidator(frm || this.form(), {});
                        
                        if (this.isMode('edit')) {
                                var el = document.id('title')

                                el.addClass('required');
                                this.validator.watchField(el);

                                el = this.categoryEl();

                                el.addClass('required').addClass("excludeValue:0").store('errorMsg', 'Select category.');
                                this.validator.watchField(el);
                        }
                        
                        var js;
                        
                        $$('a.toolbar').each(function(a) {
                                var js = a.get('onclick');
                                
                                if (!js || js.indexOf("submitbutton('cancel')") >= 0) return;
                                
                                a.store('js', js);
                                a.set('onclick', '');
                                a.removeEvent('click');
                                
                                a.addEvent('click', function(e) {
                                        e.stop();
                                        e = document.id(e.target);
                                        if (e.get('tag') != 'a') e = e.getParent('a');
                                        this.actOnForm(e);
                                }.bind(this));
                        }.bind(this));
                } else if (this.isMode('search')) {
                        var btn = this.formSubmitButton();
                        
                        if (btn)
                                btn.addEvent('click', function(e) {
                                        e = this._tgt(e);
                                        var frm = this.form(e);
                                        frm.submit();
                                }.bind(this));
                        
                        btn = this.formResetButton();
                        
                        if (btn)
                                btn.addEvent('click', function(e) {
                                        this.resetElements(this._tgt(e).form, this.categoryEl());
                                }.bind(this));
                }
        },
        
        createFields: function() {
                if (this.isMode('edit')) {
                        (new Element('span', {
                                'id':'extrafields-plch',
                                'style':{
                                        'display':'none'
                                }
                        })).inject(this.containerEl());
                }
                
                var els;
                
                if (this.isMode('search')) {
                        els = document.id('extraFields');
                } else if (this.isMode('editfields')) {
                        els = $$('ul.extraFields');
                } else {
                        els = $$('table.extraFields');
                }
        
                if (!els || els.length == 0) {
                        this.processingEnd();
                        return;
                }
                
                if (!this.isMode('search')) {
                        var _els = els, t, tabs, cont, nav, btn, cId, sec, cnt = 0;
                        els = [];
                        
                        if (!this.isMode('editfields')) {
                                tabs = document.id('tabExtraFields').getSiblings();

                                if (tabs.length > 0) {
                                        document.id('tabExtraFields').setStyle('display', tabs[0].getStyle('display'));
                                }
                        }
                        
                        tabs = new Element('div', {'class':'simpleTabs','id':'k2fieldsTabs'});
                        tabs.inject(_els[0], 'before');
                        nav = new Element('ul', {'class':'simpleTabsNavigation'});
                        nav.inject(tabs);
                        
                        for (var i = 0, n = _els.length; i < n; i++) {
                                sec = _els[i].get('section');
                                
                                if (!this.autoFields.contains(_els[i].get('valid'))) {
                                        id = _els[i].get('id');
                                        cId = 'k2fieldsTabs'+(i+1);

                                        btn = new Element('li', {'id':'tab'+id.capitalize()});
                                        new Element('a', {
                                                'href':'#'+cId, 
                                                'html':sec
                                        }).inject(btn);
                                        btn.inject(nav);

                                        cont = new Element('div', {'class':'simpleTabsContent','id':cId});
                                        cont.inject(tabs);
                                        _els[i].inject(cont);
                                        cnt++;
                                }
                                
                                t = _els[i].getElements('[name^='+this.options.pre+']');

                                if (t) els.push(t);
                        }
                        $K2('#k2fieldsTabs').tabs();
                        // 'option', 'select', 1
                } else {
                        els = els.getElements('[name^='+this.options.pre+']');
                }
                
                els = els.flatten();
                
                if (this.isMode('search') && !this.isIMode('menu')) {
                        var data, initState = this.categoryInitState() || this.options.initState;
                        
                        this.categorySetItemid();
                        
                        if (initState)
                                data = 'index.php?'+initState;
                        else 
                                data = document.location.href;
                        
                        data = data.fromQueryString(unescape);
                        
                        var 
                                valPat = new RegExp('^('+this.options.pre+'\\d+)_(\\d+)(\\[\\])?$'),
                                m, vals = {}, val, id, pos, mv;
                                
                        for (var key in data) {
                                val = data[key];
                                m = key.match(valPat);
                                
                                if (m) {
                                       id = m[1];
                                       pos = m[2];
                                       
                                       if (!vals[id]) vals[id] = {};
                                       
                                       vals[id][pos] = val;
                                }
                        }
                        
                        /**
                         * TODO: couple with getK2CustomFieldValue?
                         */
                        var fValue, v, el;
                        
                        for (id in vals) {
                                el = document.id(id) || $$('[name='+id+']')[0];
                                
                                if (!el || el.get('value')) continue;
                                
                                val = vals[id];
                                fValue = '';
                                m = 0;
                                for (pos in val) if (pos>m) m = pos;
                                
                                for (pos = 0; pos <= m; pos++) {
                                        v = val[pos] ? val[pos] : '';
                                        if (typeOf(v) == 'array') v = v.join(this.options.multiValueSeparator)
                                        fValue += (pos > 0 ? this.options.valueSeparator : '') + v;
                                }
                                
                                el.set('value', fValue);
                        }
                        
                        this.autoFill(this.form(), undefined, new RegExp('^'+this.options.pre+'\\d+'));
                }
                
                els.each(function(fld) {
                        if (this.parseFieldOptions(fld)) {
                                if (!this.isMode('search') && this.isAutoField(fld)) {
                                        if (this.isTitle(fld)) {
                                                var title = this.fieldsOptions[fld.get('name')]['label'];

                                                if (title) {
                                                        $$('label[for=title]')[0].set('html', title);
                                                        if ($$('label[for=alias]').length > 0)
                                                                $$('label[for=alias]')[0].set('html', title+' alias');
                                                }
                                        }
                                        
                                        this.removeProxyFieldContainer(fld);
                                        
                                        return;
                                }
                                
                                if (this.isEditable(fld)) {
                                        this.createEditable(fld);
                                } else {
                                        this.createField(fld);
                                }
                        } else if (!this.isMode('edit')) {
                                /**
                                 * @@todo: remove definitions without any visible members (such as non-complex or complex with no search)
                                 * 
                                 * Note: only complete definitions can be reomved as we need to preserve expected value positions in relation to available/visible fields
                                 */
                                this.removeProxyFieldContainer(fld);
                        }
                }.bind(this));
                
                if (this.isIMode('menu'))
                        this.menuItemHandler.loadValues();
                
                this.processingEnd();
        },
        
        removeProxyFieldContainer: function(fld) {
                var cont = this.getProxyFieldContainer(fld);
                
                if (this.isMode('edit')) {
                        var tab = cont.getParent('.simpleTabsContent');
                        
                        cont.dispose();
                        
                        if (tab && tab.getElements('tr').length <= 0) {
                                var ind = tab.getParent().getElements('.simpleTabsContent').indexOf(tab);
                                $K2('#k2fieldsTabs').tabs('remove', ind);
                        }
                        
                        return;
                }
                
                cont.dispose();
        },
        
        isEditable: function(field) {
                var nn = field.get('tag').toLowerCase();
                if (nn != 'select' && (nn != 'input' || field.get('type') != 'radio')) return false;
                if (this.fields.contains(field.get('name'))) return false;
                return this.getOpt(field, 'editable');
        },

        createEditable: function (field) {
                var btn = new Element('input', {
                        'type':'button',
                        'events':{
                                'click':function(e) {
                                        this.addOptionEditable(this._tgt(e));
                                }.bind(this)
                        },
                        'value':'Add option',
                        'id':this.generateId(field)
                });
                btn.inject(field.getParent());

                var type = field.get('tag').toLowerCase(), fields;
                
                if (type == 'select') {
                        fields = [field];
                } else if (type == 'input') {
                        fields = field.form.getElements('input[name='+field.get('name')+']');
                }

                fields.each(function(fld) {
                        fld.addEvent('change', function (e) {
                                var tgt = this._tgt(e);
                                var value = tgt.getParent().getElement('input[type=text]');
                                
                                if (value) {
                                        this.removeValue(tgt, this.options.userAddedValuePrefix + value.get('value'));
                                        this.resetValue(value);
                                }
                        }.bind(this));
                }.bind(this));
        },

        addOptionEditable: function(btn) {
                var div = new Element('div');
                var id = this.generateId(this.getProxyFieldId(btn));
                var m = /(\d+)$/.exec(id);

                if (parseInt(m[1]) > 1) return;

                var fld = new Element('input', {
                        'id':id,
                        'type':'text',
                        'events':
                        {
                                'change':function(e) {
                                        this.doAddOptionEditable(this._tgt(e));
                                }.bind(this)
                        }
                });

                fld.inject(div);
                div.inject(btn.getParent());
        },

        doAddOptionEditable: function(addFieldId) {
                var field = document.id(this.getProxyFieldId(addFieldId));
                var value = document.id(addFieldId).get('value');

                if (this.existsValue(field, this.options.userAddedValuePrefix, true, false, 'pre')) {
                        this.removeValue(field, this.options.userAddedValuePrefix, false, 'pre');
                }
                
                if (!this.existsValue(field, value, true, false, 'full', 'text')) {
                        this.addValue(field, {'value':this.options.userAddedValuePrefix + value, 'text':value}, true, false);
                }

                return false;
        },

        createField: function(proxyField) {
                if (this.chkOpt(proxyField, 'list', 'conditional')) {
                        var condition;

                        if (this.getOpt(proxyField, 'conditions')) {
                                var conditions = this.getOpt(proxyField, 'conditions').split('%%');

                                this.setOpt(proxyField, 'conditions', conditions);
                                this.setOpt(proxyField, 'listmax', conditions.length);
                                
                                condition = this.createListSelect(conditions);
                        } else {
                                var plch = this.getOpt(proxyField, 'conditionlabel', null, 'Description');
                                condition = new Element('textarea', {
                                        'cols':'60',
                                        'rows':'2',
                                        'placeholder':plch,
                                        'class':'autogrow condition'
                                });
                        }

                        this.conditions[proxyField.get('name')] = condition;
                }

                proxyField.set('id', proxyField.get('name'));

                var list, item;

                if (this.options['isNew'] && this.chkOpt(proxyField, 'list') && this.chkOpt(proxyField, 'listdefault')) {
                        list = [];
                        for (var i = 0, n = this.getOpt(proxyField, 'listdefault'); i < n; i++) list.push('');
                } else {
                        list = proxyField.get('value').split(this.options.listItemSeparator);
                }
                
                for (var i = 0; i < list.length; i++) {
                        item = list[i].split(this.options.listConditionSeparator);
                        this.createFieldSub(proxyField, item[0], item[1]);
                }
        },

        createFieldSub: function(proxyField, value, condition, holder) {
                if (this.isExceeded(proxyField)) return [];
                
                if (!value) value = '';
                
                var type = this.isType(proxyField, 'basic') ? 
                        'basic' : 
                        this.getOpt(proxyField, 'valid', null, 'basic').toLowerCase();
                
                return this.enqueueType(type, proxyField, value, condition, holder);
        },
        
        getCondition: function(field) {
                var el = this.getValueContainer(field).getPrevious();

                if (el.hasClass('k2fcondition')) {
                        var cond = el.getElement('select');
                        return cond ? cond : el.getElement('input') || el.getElement('textarea')
                } else {
                        return;
                }
        },

        isConditional: function(field) {
                var proxyField = document.id(this.getProxyFieldId(field));
                return this.getOpt(proxyField, 'list') == 'conditional';
        },
        
        getValueHolders: function(proxyField) {
                if (this.options.layout == 'table') {
                        return proxyField.getParent().getParent().getNext().getElements('td')[1].getElements('[valueholder=true]');
                }
        },

        getValueHolder: function(field) {
                return document.id(field).getParent('[valueholder=true]');
        },

        setProxyFieldValue: function(k2field, overrideValue) {
                var proxyField = document.id(this.getProxyFieldId(k2field));
                var subfieldOf = this.getOpt(proxyField, 'subfieldof');
                
                if (subfieldOf) {
                        this.setProxyFieldValue(subfieldOf);
                        return;
                }
                
                var value = '';
                
                if (overrideValue) {
                        if (overrideValue.indexOf(this.options.listConditionSeparator) < 0)
                                overrideValue += this.options.listConditionSeparator;
                        
                        value = overrideValue;
                } else {
                        var flds = this.getCell(proxyField, '[valueholder=true]'), curr, condition;

                        if (flds) {
                                flds.each(function(fld) {
                                        curr = this.getK2FieldValue(fld);

                                        if (typeof curr == 'number' || typeof curr == 'boolean' || curr) {
                                                if (value) {
                                                        value += this.options.listItemSeparator;
                                                }

                                                value += curr + this.options.listConditionSeparator;
                                                
                                                if (this.isConditional(fld)) {
                                                        condition = this.getCondition(fld);
                                                        condition = this.getValue(condition);
                                                        value += condition;
                                                }
                                        }
                                }.bind(this));
                        }
                }

                proxyField.set('value', value);
                
                if (this.isIMode('menu')) 
                        this.menuItemHandler.build();
        },
        
        /** type handling **/
        typeDependencies: {
                'availability':'basic',
                'complex':'basic', 
                'yesno':'basic', 
                'creditcard':'basic', 
                'range':'basic', 
                'datetimerange':'datetime',
                'date':'datetime', 
                'time':'datetime',
                'duration':'datetime',
                'map':'complex'
        },
        
        typeCreateQueue: {},
        
        typeDependentQueue: {},
        
        extendK2fields: function(type, execute) {
                if (!this.options.extendables.contains(type)) return false;
                
                if (this.doExtend(type, execute)) return;
                
                return this.utility.load(
                        'tag', 
                        this.options.base + this.options.k2fbase + 'js/k2fields' + type + '.js', 
                        false, 
                        false, 
                        '', 
                        function() {
                                this.doExtend(type, execute);
                        }.bind(this)
                );
        },
        
        doExtend: function(type, execute) {
                var extender = this.extenderName(type);
                
                try {
                        eval('extender = ' + extender);
                } catch (exception) { 
                        return false;
                }
                
                if (!extender) return false;
                
                k2fields.implement(extender);

                var initFn = '_init'+type.capitalize();

                if (typeof this[initFn] == 'function') this[initFn]();

                if (execute) this.executeTypeQueue(type);
                
                return true;
        },
        
        extenderName: function(type) {return 'k2fields_type_' + type;},
        
        enqueueDependentType: function(type, dependent) {
                if (this.typeDependentQueue[type] == undefined) {
                        this.typeDependentQueue[type] = [];
                }
                
                this.typeDependentQueue[type].push(dependent);
        },
        
        enqueueType: function(type, proxyField, value, condition, holder, fn) {
                if (this.typeCreateQueue[type] == undefined) this.typeCreateQueue[type] = [];
                
                var el = [proxyField, value, condition], creator = this.existsTypeCreator(type);
                
                if (holder) el.push(holder);
                
                if (fn) {
                        this.typeCreateQueue[type].push(fn);
                } else {
                        if (arguments.length == 1) el = [];

                        this.typeCreateQueue[type].push(el);
                }
                
                var existsUnloadedDeps = this.loadDependencies(type);
                
                if (!existsUnloadedDeps) 
                        creator = this.existsTypeCreator(type);
                
                if (!creator) {
                        this.extendK2fields(type, true);
                        return [];
                }
                
                if (existsUnloadedDeps) return [];
                
                return this.executeTypeQueue(type);
        },
        
        loadDependencies: function(type) {
                if (this.typeDependencies[type]) {
                        var deps = Array.from(this.typeDependencies[type]), stopExec = false, dep;
                        
                        for (var i = 0, n = deps.length; i < n; i++) {
                                dep = deps[i];
                                
                                if (!this.existsTypeCreator(dep)) {
                                        this.extendK2fields(dep);
                                        this.enqueueDependentType(dep, type);
                                        stopExec = true;
                                }
                        }
                        
                        if (stopExec) return true;
                }
                
                return false;
        },
        
        executeDependents: function(type) {
                if (!this.typeDependentQueue[type]) return [];
                
                var queue = this.typeDependentQueue[type], t, depType, result = [];

                for (var i = 0, n = queue.length; i < n; i++) {
                        depType = queue[i];
                        t = this.executeTypeQueue(depType);

                        if (!t) result.combine(t);
                }

                delete this.typeDependentQueue[type];
                
                return result
        },
        
        isTypeQueueEmpty: function(type) {
                if (type) {
                        return !this.typeCreateQueue[type] || this.typeCreateQueue[type].length == 0;
                } else {
                        return Object.keys(this.typeCreateQueue).length == 0;
                }
        },
        
        executeTypeQueue: function(type) {
                var fn = this.existsTypeCreator(type), i, n;
                
                if (!fn) return false;
                
                var result = this.executeDependents(type);
                
                if (!this.typeCreateQueue[type]) return [];
                
                var queue = this.typeCreateQueue[type], mFn = this.existsTypeCreator(type, true);
                
                if (mFn) fn = mFn;
                
                var q, id, holder, placement, status, proxyField, value, condition, isSimple;
                
                for (i = 0, n = queue.length; i < n; i++) {
                        q = queue[i];
                        
//                        isSimple = typeOf(q) != 'array' || q.length == 0 || typeOf(q[0]) != 'element';
//                        
//                        if (isSimple && this.simpleTypes.contains(type)) {
//                                this[fn](q);
//                        } else {
                                if (typeof q == 'function') {
                                        // TODO: overlaps with simpletypes
                                        q();
                                        continue;
                                }
                                
                                proxyField = q[0];
                                
                                holder = value = condition = null;
                                
                                if (q.length > 0 && proxyField) {
                                        value = q[1];
                                        condition = q[2];
                                        
                                        if (q.length > 3 && q[3]) {
                                                holder = q[3];
                                                id = this.getProxyFieldId(proxyField);
                                        } else {
                                                id = this.generateId(proxyField);
                                                holder = new Element('span', {id:id, valueholder:'true'});
                                                placement = this.place(holder, proxyField, condition, false, true);
                                        }
                                }
                                
                                status = this[fn](holder, proxyField, value, condition);
                                
                                if (status === false) {
                                        placement.dispose();
                                } else {
                                        if (status) result.combine(status);
                                        
                                        this.onFormElementComplete(id, status);
                                }
//                        }
                }
                
                delete this.typeCreateQueue[type];
                
                if (this.isTypeQueueEmpty()) {
                        // Fire event requiring all elements are created
                        // TODO: remaining is those dependent on asynchronous request to get element values
                }
                
                return result;
        },

        typeName: function(fn) {
                if (!fn) return '';
                
                return fn.replace(/^create/).toLowerCase();
        },
        
        existsTypeCreator: function(type, modeSensitive) {
                if (!type) return false;
                
                var fn = 'create' + (modeSensitive ? this.options.mode.capitalize() : '') + type.capitalize();
                
                if (typeof this[fn] == 'function') return fn;
                
                return false;
        },

        formCompleteElements: {},
        createdFields: {},
        
        onFormElementComplete: function(callForElement, createdFields) {
                var action, el;
                
                callForElement = this.getProxyFieldId(callForElement);
                
                for (action in this.formCompleteElements) {
                        if (this.formCompleteElements[action] && this.formCompleteElements[action][callForElement]) {
                                el = this.formCompleteElements[action][callForElement];
                                
                                if (typeof el[0] == 'function') el[0] = el[0](el[2]);
                                
                                if (el[1] && document.id(el[1]) || !el[1]) {
                                        this[action].apply(this, Array.from(el[0]));
                                        delete this.formCompleteElements[action][callForElement];
                                }
                        }
                }
                
                if (this.isPartOf(callForElement)) this.createdFields[callForElement] = createdFields;
        },
        
        addFormElementComplete: function(action, id, el, exists, args) {
                id = this.getProxyFieldId(id) || id;
                
                if (!this.formCompleteElements[action]) this.formCompleteElements[action] = [];
                
                this.formCompleteElements[action][id] = [el, exists, args];
        },
        
        isPartOf: function(field) {
                return this.getOpt(field, 'subfieldof') != '';
        },
        
        isType: function(field, assertedType) {
                assertedType = Array.from(assertedType);
                
                var type = this.getOpt(field, 'valid', null, 'basic');
                
                if (assertedType.contains(type)) return true;
                
                if (this.basicTypes.contains(type)) type = 'basic';
                
                return assertedType.contains(type);
        },
        
        isBasic: function(field) { return this.isType(field, 'basic') || this.isDateTime(field); },
        
        isAutoField: function(field) { return this.options.autoFields.contains(this.getOpt(field, 'valid')); },
        
        isTitle: function(field) {return this.isType(field, 'title');},

        isMedia: function(field) {return this.isType(field, 'media');},
        
        isNumeric: function(field) {return this.isType(field, ['integer', 'numeric']);},
        
        isDateTime: function(field) {
                var type = this.getOpt(field, 'valid'), types = ['datetime'];
                for (var t in this.typeDependencies) {
                        if (this.typeDependencies[t] == 'datetime') types.push(t);
                }
                return types.contains(type);
        },
        
        // TODO: argument as object, ref: http://blog.rebeccamurphey.com/objects-as-arguments-in-javascript-where-do-y
        ccf: function(
                proxyField, 
                value, 
                position, 
                validType,
                lbl, 
                into,
                type, 
                opts, 
                clearAfter, 
                internalValueSeparator, 
                hidden, 
                isAttachEvent, 
                preventAutoComplete
        ) {
                if (!type) type = 'input';

                if (!opts) opts = {};
                
                if (typeof opts == 'string' && type == 'input') opts = {type:opts};
                
                if (type == 'input' && !opts['type']) opts.type = 'text';

                if (!value) {
                        if (this.options['isNew'] || this.isMode('search')) value = this.getDefaultValue(proxyField);
                        if (!value) value = '';
                }
                
                if (clearAfter == undefined) clearAfter = true;
                
                var 
                        id = this.generateId(proxyField), 
                        values = opts['values'] ? opts['values'] : '', 
                        field,
                        rcb = type == 'input' && opts['type'] && ['radio', 'checkbox'].contains(opts['type']);
                
                if (values) delete opts['values'];

                if (clearAfter) {
                        var _into = into;
                        into = new Element('div');
                        into.inject(_into);
                }
                
                if (lbl && (opts['showlabel'] == undefined || opts['showlabel'])) 
                        new Element('label', {'for': id, 'class':'lbl'}).set('text', lbl).inject(into);
                
                if (this.isMode('search') && !opts['ignore']) {
                        var pEl = proxyField, pId = pEl.get('id');
                        
                        if (opts['subfieldof'] || (opts['subfieldof'] = this.getOpt(proxyField, 'subfieldof'))) {
                                pId = opts['subfieldof'];
                                pEl = document.id(pId);
                        }
                        
                        opts['name'] = pId + '_' + position;
                        pEl.set('name', null);
                }
                
                if (type == 'select') {
                        field = this.createListSelect(
                                values, 
                                opts['valueName'] ? opts['valueName'] : '', 
                                opts['textName'] ? opts['textName'] : '', 
                                id, opts['name'],
                                opts['first'], 
                                opts['multiple'], 
                                opts['size'],
                                into
                        );
                } else {
                        opts['id'] = id;
                        
                        if (rcb) {
                                field = this.createListInput(
                                        opts['type'], 
                                        values, 
                                        opts, 
                                        into, 
                                        opts['valueName']  ? opts['valueName'] : '', 
                                        opts['textName'] ? opts['textName'] : '', 
                                        opts['imageName'] ? opts['imageName'] : ''
                                );
                                        
                                field = this.getSyblings(field, true);
                        } else {
                                if (opts['type'] == 'file') {
                                        var _id = this.getProxyFieldId(id);
                                        _id = _id.replace(this.options.pre, '').match(/^\d+/);
                                        opts['name'] = 'k2fieldsmedia_' + _id[0] + (opts['thumb'] ? '_thumb' : '') + '[]';
                                } else if (type != 'textarea') {
                                        if (!this.getOpt(proxyField, 'size')) {
                                                var s = this.getOpt(proxyField, 'maxlen') || this.getOpt(proxyField, 'maxlength');
                                                if (s) opts['size'] = s;
                                        }
                                }
                                
                                field = new Element(type, opts);
                                field.inject(into);
                        }
                }
                
                if (!rcb) field = [field];
                
                if (opts['ignore']) isAttachEvent = false;
                else field[0].set('customvalueholder', 'true');
                
                this.setOpt(field[0], 'position', position);
                
                // Calculate index:
                var i = this.cInd(field[0]), n;
                
                this.setOpt(field[0], 'index', i);
                
                if (!this.lookup[proxyField.get('id')]) {
                        this.lookup[proxyField.get('id')] = [];
                }
                
                if (!this.lookup[proxyField.get('id')][i]) {
                        this.lookup[proxyField.get('id')][i] = [];
                }
                
                if (!this.lookup[proxyField.get('id')][i][position]) {
                        this.lookup[proxyField.get('id')][i][position] = [];
                }
                
                this.lookup[proxyField.get('id')][i][position].push(id);
                
                if (internalValueSeparator) this.setOpt(field[0], 'internalValueSeparator', internalValueSeparator);

                var pre = this.getOpt(proxyField, 'pre'), post = this.getOpt(proxyField, 'post');
                
                for (i = 0, n = field.length; i < n; i++) {
                        if (pre)
                                new Element('span', {'class':'pre'}).set('html', pre).inject(field[i], 'before');
                        
                        this.wire(field[i], isAttachEvent, preventAutoComplete, validType);
                        
                        if (post)
                                new Element('span', {'class':'post'}).set('html', post).inject(field[i], 'after');
                        
                        if (!this.isMode('search')) {
                                this.autoGrow(field[i]);
                                this.placeHold(field[i]);
                        }
                }
                
                if (hidden) this.toggleCustomField(field);
                
                if (value) this.setValue(field[0], value, undefined, undefined, undefined, true);
                
                return field;
        },
        
        autoGrow: function(field, ag, minRows) {
                var t = field.get('tag');
                
                if (t != 'textarea') return;
                
                if (ag == undefined)
                        ag = this.getOpt(field, 'ag');
                
                if (minRows == undefined)
                        minRows = this.getOpt(field, 'agr', null, 2);
                        
                new Form.AutoGrow(field, {minHeightFactor:minRows});
                
                return field;
        },
        
        placeHold: function(field, ph) {
                var t = field.get('tag');
                
                if (ph == undefined)
                        ph = this.getOpt(field, 'ph');
                
                if (typeof ph == 'string') 
                        field.set('placeholder', ph);
                
                if ((t == 'input' && field.get('type') == 'text' || t == 'textarea') && ph) 
                        new Form.Placeholder(field);
                
                return field;
        },
        
        ind: function(proxyField, index, position) {
                if (typeof index != 'number') index = this.getOpt(index, 'index');
                return this.lookup[document.id(proxyField).get('id')][index][position];
        },
        
        cInd: function(el) {
                var c = el.getParent('.k2fcontainer'), cs = c.getParent().getChildren(), i, n;
                
                for (i = 0, n = cs.length; i < n; i++) 
                        if (c == cs[i]) break;
                
                return i;
        },
        
        toggleCustomField: function(field, mode) {
                var container, m;
                
                if (typeof field == 'string' && (m = field.match(/^id\:(\d+)$/))) {
                        container = this.getValueRow(this.options.pre+m[1]);
                        
                        if (!container) {
                                this.addFormElementComplete(
                                        'toggleCustomField', 
                                        this.options.pre+m[1], 
                                        ['id:'+m[1], mode], 
                                        this.options.pre+m[1]
                                );
                                
                                return;
                        }
                } else {
                        container = this.getContainer(field);
                }
                
                var displayer = container.get('tag').toLowerCase() == 'tr' ? 'table-row' : 'block';
                
                displayer = mode ? 
                        (mode == 'block' ? displayer : mode) : 
                        (container.getStyle('display') == 'none' ? displayer : 'none');
                
                container.setStyle('display', displayer);
        },

        isCustomField: function(field) {
                var container = this.getValueContainer(field);
                return document.id(container).hasClass('customField');
        },

        getValueContainer: function(field) {
                return document.id(field).getParent('.k2fvalue')
        },

        disposeValueHolder: function(except) {
                except = Array.from(except);
                
                var holder = this.getValueHolder(except[0]);
                
                holder.getChildren().each(function(e) {
                        if (except.length == 0 || !e.getChildren().some(function(el) {return except.contains(el);}, this)) {
                                e.dispose();
                        }
                });
                
                return holder;
        },
        
        getK2CustomFieldValue: function(valueHolder) {
                var fields = document.id(valueHolder).getElements('[customvalueholder=true]');
                var field, result = [], value, internalValueSeparator, position;
                
                for (var i = 0, n = fields.length; i < n; i++) {
                        field = fields[i];
                        internalValueSeparator = this.getOpt(field, 'internalValueSeparator');
                        position = this.getOpt(field, 'position');
                        value = field.get('disabled') ? '' : this.getValue(field, true, true);
                        
                        if (value == undefined) value = '';
                        
                        if (internalValueSeparator) {
                                result[position] = (result[position] != undefined ? result[position] + internalValueSeparator : '') + value;
                        } else {
                                result[position] = value;
                        }
                }
                
                result = result.join(this.options.valueSeparator);

                return result;
        },

        getK2FieldValue: function(field) {// field == valueHolder
                if (!this.isCustomField(field)) { 
                        return this.getValue(field, true, true);
                } else {
                        return this.getK2CustomFieldValue(field);
                }
        },
        
        getValueRow: function(proxyField) {
                return this.getProxyFieldContainer(proxyField).getNext();
        },
        
        getCell: function(proxyField, filter, type) {
                type = !type ? 1 : (type == 'label' ? 0 : 1);
                var cell = this.getValueRow(proxyField);
                if (!cell) return false;
                cell = cell.getChildren()[type];
                return filter ? cell.getElements(filter) : cell;
        },
        
        getProxyFieldContainer: function(proxyField) {
                return document.id(proxyField).getParent().getParent();
        },

        place: function(k2field, proxyField, k2fieldCondition, ignoreK2Field, isCustomField) {
                proxyField = document.id(proxyField);
                
                var
                        proxyFieldTr = this.getProxyFieldContainer(proxyField),
                        container = new Element('div', {'class':'k2fcontainer'}),
                        valueContainer = new Element('span', {'class':'k2fvalue'+(isCustomField ? ' customField' : '')}),
                        isFirst = !proxyFieldTr.retrieve('k2fieldadded'),
                        labelTag = proxyFieldTr.getChildren()[0].get('tag'), 
                        valueTag = proxyFieldTr.getChildren()[1].get('tag'),
                        td, fTd, tr, tip;
                        
                if (isFirst) {
                        tr = new Element(proxyFieldTr.get('tag'));

                        proxyFieldTr.setStyle('display', 'none');

                        if (ignoreK2Field === true) return;
                        
                        tr.inject(proxyFieldTr, "after");
                        tip = this.isMode('search') ? 'search' : 'edit';
                        tip = this.getOpt(proxyField, tip+'tip');
                        if (!tip) tip = this.getOpt(proxyField, 'tip');
                        fTd = new Element(labelTag, {
                                'class':'key',
                                'align':'right'
                        }).inject(tr);
                        var html = this.getOpt(proxyField, 'name') + (this.getOpt(proxyField, 'list') ? '<br/>' : '');
                        if (tip) {
                                new Element('span', {'class':'jptips','html':html,'jptips':this.getOpt(proxyField, 'name')+'::'+tip}).inject(fTd);
                        } else {
                                fTd.set('html', '<span>'+html+'</span>');
                        }
                        
                        td = new Element(valueTag);
                        td.inject(tr);
                        if (proxyFieldTr.get('tag') == 'li') new Element('div', {'class':'cf'}).inject(tr);
                        proxyFieldTr.store('k2fieldadded', true);
                } else {
                        td = this.getCell(proxyField);
                }

                if (this.getOpt(proxyField, 'list') == 'conditional') {
                        var condition = this.conditions[proxyField.get('id')];

                        condition = condition.clone();
                        condition.inject(this.containerEl());
                        
                        this.autoGrow(condition, true, condition.get('rows'));
                        this.placeHold(condition, true);

                        if (k2fieldCondition) {
                                if (condition.tagName == 'SELECT') {
                                        var options = condition.getElements('option');

                                        for (var i = 0; i < options.length; i++) {
                                                if (options[i].value == k2fieldCondition) {
                                                        options[i].set('selected', 'selected');
                                                        break;
                                                }
                                        }
                                } else if (condition.tagName == 'INPUT' || condition.tagName == 'TEXTAREA') {
                                        condition.set('value', k2fieldCondition);
                                }
                        }

                        var conditionContainer = new Element('span', {
                                'class':'k2fcondition'
                        });
                        
                        condition.inject(conditionContainer);
                        new Element('div', {'class':'cf'}).inject(conditionContainer);
                        conditionContainer.inject(container);

                        condition.addEvent('change', function(e) {
                                var tgt = this._tgt(e);
                                tgt = tgt.getParent().getParent().getElement('[valueholder=true]');
                                this.setProxyFieldValue(tgt.get('id'));
                        }.bind(this));
                }

                k2field.inject(valueContainer);
                valueContainer.inject(container);
                
                var lm = this.getOpt(proxyField, 'listmax');
                
                if (this.getOpt(proxyField, 'list') && lm > 1) {
                        var fieldId = k2field.get('id');

                        if (isFirst) {
                                (new Element('a', {
                                        'class': 'btn addbtn',
                                        'text': 'Add',
                                        'id': fieldId+'_btn',
                                        'href': '#',
                                        'tabindex':'-1',
                                        'events': {
                                                'click': function(e) {
                                                        e.stop();
                                                        this.addElement(this._tgt(e));
                                                }.bind(this)
                                        }
                                })).inject(fTd)
                                ;
                                
//                                if (lm) {
//                                        lm = '(Max '+lm+')';
//                                        new Element('div', {'class':'listmax', 'html':lm}).inject(fTd);
//                                }
                        }
                        
                        var btns = new Element('ul', {'class':'frmelbtns'}).inject(container);
                        
                        (new Element('a', {
                                'class': 'btn removebtn',
                                'text': 'Remove',
                                'id': fieldId+'_btn',
                                'href': '#',
                                'tabindex':'-1',
                                'events': {
                                        'click': function(e) {
                                                e.stop();
                                                this.removeElement(this._tgt(e));
                                        }.bind(this)
                                }
                        })).inject(new Element('li').inject(btns));
                        
                        (new Element('a', {
                                'class': 'btn movebtn moveupbtn',
                                'text': 'Move up',
                                'id': this.generateId(proxyField),
                                'href': '#',
                                'tabindex':'-1',
                                'events': {
                                        'click': function(e) {
                                                e.stop();
                                                this.moveListItem(e, 'before');
                                        }.bind(this)
                                }
                        })).inject(new Element('li').inject(btns));
                        
                        (new Element('a', {
                                'class': 'btn movebtn movedownbtn',
                                'text': 'Move down',
                                'id': this.generateId(proxyField),
                                'href': '#',
                                'tabindex':'-1',
                                'events': {
                                        'click': function(e) {
                                                e.stop();
                                                this.moveListItem(e, 'after');
                                        }.bind(this)
                                }
                        })).inject(new Element('li').inject(btns));
                        
                        btns.inject(container);
                }
                
                new Element('div', {'class':'cf'}).inject(container);

                container.inject(td);
                
                if (tip) new JPProcessor().tip(tr);
                
                if (isFirst) return tr;
        },
        
        wire: function(k2field, isAttachChangeEvent, preventAutoComplete, validType) {
                k2field = document.id(k2field);

                var proxyField = document.id(this.getProxyFieldId(k2field));
                
                if (isAttachChangeEvent == undefined) isAttachChangeEvent = true;

                if (isAttachChangeEvent) {
                        k2field.addEvent('change', function() { 
                                this.setProxyFieldValue(k2field);
                        }.bind(this));
                }
                
                if (!preventAutoComplete) {
                        this.autoComplete(k2field);
                }
                
                if (this.isMode('search')) return;
                
                var v = validType || this.getOpt(proxyField, 'valid'), vs = Object.keys(Form.Validator.validators), isReq = false;
                
                if (this.getOpt(proxyField, 'required') && String.from(this.getOpt(proxyField, 'required')) == 'true') {
                        k2field.addClass('required');
                        k2field.store('k2ftype', v);
                        isReq = true;
                }
                
                if (vs.contains('validate-'+v)) {
                        k2field.addClass('validate-'+v);
                }
                
                var c = this.getOpt(proxyField, 'min');
                if (c) {
                        if (typeof c != 'string') c = JSON.encode(c);
                        k2field.addClass("minValue:'"+c+"'");
                }
                
                c = this.getOpt(proxyField, 'max');
                if (c) {
                        if (typeof c != 'string') c = JSON.encode(c);
                        k2field.addClass("maxValue:'"+c+"'");
                }
                
                c = this.getOpt(proxyField, 'minlength') || this.getOpt(proxyField, 'minlen');
                if (c) {
                        if (typeof c != 'string') c = JSON.encode(c);
                        if (isReq || parseInt(c) != 1) k2field.addClass("minLength:'"+c+"'");
                }
                
                c = this.getOpt(proxyField, 'maxlength') || 
                        this.getOpt(proxyField, 'maxlen') || 
                        this.getOpt(proxyField, 'length')  || 
                        this.getOpt(proxyField, 'len')
                ;
                if (c) {
                        if (typeof v != 'string') c = JSON.encode(c);
                        k2field.addClass("maxLength:'"+c+"'");
                }
                
                c = this.getOpt(proxyField, 'interval');
                if (c) {
                        if (typeof v != 'string') c = JSON.encode(c);
                        k2field.addClass("interval:'"+c+"'");
                }
                
                c = this.getOpt(proxyField, 'regexp');
                if (c) {
                        k2field.addClass("regExp:'"+c+"'");
                }
                
                c = this.getOpt(proxyField, 'errormsg');
                if (c) {
                        k2field.store('errorMsg', c);
                }
                
                this.validator.watchField(k2field);
        },
        
        getProxyFieldId: function(k2field) {
                var id = (typeof k2field == "string" ? k2field : k2field.get('id') || k2field.get('name')).replace(/(_?\d+)_(\d+)(\_btn)?$/, '$1');
                var o = document.id(id);

                if (!o) {
                        if (this.fields[id]) {
                                o = $$('input[name='+id+']')[0];
                                if (o == null) {
                                        id = k2field.get('id');
                                        o = document.id(id);
                                }
                                id = o.get('id');
                        } else {
                                return;
                        }
                }

                return id;
        },

        moveListItem: function(item, rel) {
                item = this._tgt(item);
                
                if (item.get('tag') == 'a') {
                        item = item.getParent().getParent().getParent();
                }
                
                var oItem = rel == 'before' ? item.getPrevious() : item.getNext();

                if (oItem == null || !oItem.hasClass('k2fcontainer') || item.getParent().getElements('.kefel').length == 1) return;

                if (rel == 'after') {
                        item.inject(oItem, 'after');
                } else if (rel == 'before') {
                        item.inject(oItem, 'before');
                }
                
                this.setProxyFieldValue(item.getElement('.movebtn').get('id'));

                return false;
        },
        
        addElement: function(btn) {
                var proxyField = document.id(this.getProxyFieldId(btn.get('id').replace(/\_btn$/, '')));
                this.createFieldSub(proxyField);
        },

        removeElement: function(btn) {
                var k2field = btn.get('id').replace(/\_btn$/, '');
                btn.getParent('.k2fcontainer').dispose();
                this.setProxyFieldValue(k2field);
        },

        isExceeded: function(proxyField) {
                proxyField = document.id(proxyField);
                var listmax = this.getOpt(proxyField, 'listmax');
                var els = this.getCell(proxyField, '.k2fcontainer');
                return (!els ? [] : els).length >= listmax && listmax;
        },
        
        generateId: function(proxyField) {
                var id, name, o = (typeof proxyField == "string" ? document.id(proxyField) : proxyField);

                if (!o && typeof proxyField == "string" && this.fields[proxyField] != undefined) {
                        name = proxyField;
                } else if (o) {
                        if (this.isMode('search')) {
                                name = o.get('id') || o.get('name');
                        } else {
                                name = o.get('name') || o.get('id');
                        }
                        
                        name = name.replace(/\[\]$/, '');
                } else {
                        return;
                }
                
                if (this.options.pre.test(/_$/)) name = name.replace(/_(\d+)_(\d+)$/, '_$1');
                else name = name.replace(/(\d+)_(\d+)$/, '$1');
                
                if (typeof this.fields[name] == "undefined") this.fields[name] = [];

                id = name+'_'+this.fields[name].length;

                this.fields[name].push(id);

                return id;
        },

        _tgt:function(e) {
                if (typeOf(e) == 'event' || typeOf(e) == 'domevent') return document.id(e.target ? e.target : e.srcElement);
                else return e;
        },
        
        prt: function(val, varVal, varCond) { this.utility.dbg(val, varVal, varCond); }
});

