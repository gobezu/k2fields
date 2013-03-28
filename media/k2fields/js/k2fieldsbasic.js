//$Copyright$

var k2fields_type_basic = {
        _initBasic: function() {
                Form.Validator.addAllThese([
                        ['validate-phone', {
                                errorMsg: 'Invalid phone number. Allowed format is one of the followings:<ul><li>+251111616263</li><li>00251116616263</li><li>0116616263</li></ul>',
                                test: function(element) {
                                        if (element.get('value') == '') return true;
                                        var v = element.get('value').replace(/[\-\s]/g, ''), m;
                                        if (m = v.match(/^((\+|00)(\d{12}))|(0(\d{9}))/)) {
                                                if (m[2] == '00') v = '+'+m[3];
                                                else if (m[5]) v = '+251'+m[5];
                                                element.set('value', v);
                                                element.fireEvent('change', [element]);
                                                return true;
                                        }
                                        return false;
                                }
                        }],
                        ['required', {
                                errorMsg: function() {return Form.Validator.getMsg('required');},
                                test: function(element) {
                                        var k2fType = element.retrieve('k2ftype');
                                        if (k2fType && k2fType == 'duration') {
                                                return element.get('value') != '00:00';
                                        } else if (k2fType && k2fType == 'k2item') {
                                                var el = document.id(element).getParent('[valueholder=true]');
                                                el = el.getElement('[customvalueholder=true]');
                                                return !Form.Validator.getValidator('IsEmpty').test(el);
                                        } else if (element.get('disabled')) {
                                                return true; 
                                        } else {
                                                return !Form.Validator.getValidator('IsEmpty').test(element);
                                        }
                                }
                        }],
                        ['minValue', {
                                errorMsg: function(element, props){
                                        if (typeOf(props.minValue) != 'null')
                                                return 'Value is not allowed to be less than {minValue}. You entered {value}.'.substitute({minValue:props.minValue,value:element.get('value')});
                                        else return '';
                                },
                                test: function(element, props){
                                        if (typeOf(props.minValue) != 'null') return element.get('value') === "" || (Number.from(element.get('value')) >= (Number.from(props.minValue) || -Infinity));
                                        else return true;
                                }
                        }],
                        ['maxValue', {
                                errorMsg: function(element, props){
                                        if (typeOf(props.maxValue) != 'null')
                                                return 'Value is not allowed to be more than {maxValue}. You entered {value}.'.substitute({maxValue:props.maxValue,value:element.get('value')});
                                        else return '';
                                },
                                test: function(element, props){
                                        if (typeOf(props.maxValue) != 'null') return element.get('value') === "" || (Number.from(element.get('value')) <= (Number.from(props.maxValue) || Infinity));
                                        else return true;
                                }
                        }],
                        ['interval', {
                                errorMsg: function(element, props){
                                        if (typeOf(props.interval) == 'null') return '';
                                        
                                        var msg, interval = JSON.decode(element.retrieve('interval'));
                                        
                                        if (element.hasClass('validate-integer') || element.hasClass('validate-numeric')) {
                                                msg = 'Allowed value is';
                                                
                                                if (interval[0] != -Infinity) msg += ' higher than or equal to ' + interval[0];
                                                if (interval[0] != -Infinity && interval[1] != Infinity) msg += ' and ';
                                                if (interval[1] != Infinity) msg += ' less than or equal to ' + interval[1];
                                        } else {
                                                msg = 'Allowed input can not be';
                                                
                                                if (interval[0] > 0) msg += ' shorter than ' + interval[0];
                                                if (interval[0] > 0 && interval[1] != Infinity) msg += ' and ';
                                                if (interval[1] != Infinity) msg += ' longer than ' + interval[1];
                                        }
                                        
                                        return msg;
                                },
                                test: function(element, props){   
                                        if (typeOf(props.interval) == 'null') return true;
                                        
                                        var interval = this.normalizeInterval(props.interval);
                                        
                                        element.store('interval', JSON.encode(interval));
                                        
                                        if (element.hasClass('validate-integer') || element.hasClass('validate-numeric')) {
                                                var val = Number.from(element.get('value'));
                                                return val >= interval[0] && val <= interval[1];
                                        } else {
                                                var val = element.get('value');
                                                return val.length >= interval[0] && val.length <= interval[1];
                                        }
                                },
                                normalizeInterval: function(interval) {
                                        if (typeof interval == 'string') interval = interval.split(',');
                                        
                                        if (typeOf(interval) != 'array') interval = Array.from(interval);
                                        
                                        if (interval[0] == null) interval[0] = -Infinity;
                                        
                                        if (interval.length == 1 || interval[1] == null) interval[1] = Infinity;
                                        
                                        if (typeof interval[0] == 'string') interval[0] = JSON.decode(interval[0]);
                                        if (typeof interval[1] == 'string') interval[1] = JSON.decode(interval[1]);
                                        
                                        return interval;
                                }
                        }],
                        ['regExp', {
                                errorMsg: function(element, props){
                                        if (typeOf(props.regExp) != 'null') {
                                                var msg = 'Value does not match allowed pattern';
                                                
                                                if (typeOf(props.regExpRead) != 'null') {
                                                        msg += ' ' + props.regExpRead;
                                                }

                                                msg += '. You entered '+
                                                        (element.get('value').length > 50 ? element.get('value').substr(0, 50)+'...' : element.get('value')) +
                                                        '.';

                                                return msg;
                                        }
                                        else return '';
                                },
                                test: function(element, props){
                                        if (typeOf(props.regExp) != 'null') {
                                                var re = props.regExp.replace('&bs;', '\\');
                                                        
                                                re = new RegExp("^" + re + "$");

                                                re.ignoreCase = typeOf(props.ignoreCase) != 'null' && props.ignoreCase == "1";

                                                return re.test(element.get('value'));
                                        }
                                        else return true;
                                }
                        }],
                        ['excludeValue', {
                                errorMsg: function(element, props){
                                        if (typeOf(props.excludeValue) != 'null') {
                                                return 'Provided value not allowed.'
                                        }
                                        else return '';
                                },
                                test: function(element, props){
                                        if (typeOf(props.excludeValue) != 'null') {
                                                var e = props.excludeValue;
                                                
                                                if (typeOf(e) != 'array')
                                                        e = Array.from(e);
                                                
                                                var v = element.get('value');
                                                
                                                return !e.contains(typeof e[0] == 'string' ? v : parseInt(v));
                                        }
                                        else return true;
                                }
                        }],
                        ['onlyValue', {
                                errorMsg: function(element, props){
                                        if (typeOf(props.onlyValue) != 'null') {
                                                return 'Provided value not allowed.'
                                        }
                                        else return '';
                                },
                                test: function(element, props){
                                        if (typeOf(props.onlyValue) != 'null') {
                                                var o = props.onlyValue;
                                                
                                                if (typeOf(o) != 'array') o = Array.from(o);
                                                
                                                var v = element.get('value');
                                               
                                                return !o.contains(typeof o[0] == 'string' ? v : parseInt(v));
                                        }
                                        else return true;
                                }
                        }]
                ]);                               
        },
        
        createDays: function(holder, proxyField, value, condition) {
                var values;
                
                if (this.isMode('search')) {
                        var sf = this.getOpt(proxyField, 'subfieldof'), isDsD = false;
                        
                        if (sf) isDsD = this.getOpt(sf, 'daysduration');
                        
                        if (isDsD) {
                                values = [
                                        {value:'now', text:'Now'},
                                        {value:'today', text:'Today'},
                                        {value:'tomorrow', text:'Tomorrow'},
                                        {value:'specify', text:'Another time...'}
                                ];
                                this.setOpt(proxyField, 'values', values);
                                this.setOpt(proxyField, 'valid', 'date');
                                if (typeOf(value) == 'array') value = value[0];
                                value = value+'%%%%%%';
                                return this.createFieldSub(proxyField, value, condition, holder);
                        }
                }
                
                values = [
                        {value:7,text:'All days'},
                        {value:8,text:'Weekend'},
                        {value:9,text:'Weekdays'},
                        {value:1,text:'Monday'},
                        {value:2,text:'Tuesday'},
                        {value:3,text:'Wednesday'},
                        {value:4,text:'Thursday'},
                        {value:5,text:'Friday'},
                        {value:6,text:'Saturday'},
                        {value:0,text:'Sunday'}
                ];
                
                if (this.isMode('search')) {
                        var today = new Date().getDay(), tomorrow;
                        for (var i = 0, n = values.length; i < n; i++) {
                                if (values[i].value == today) {
                                        today = values[i];
                                        today.text = 'Today';
                                        tomorrow = i == n - 1 ? values[0] : values[i+1];
                                        tomorrow.text = 'Tomorrow';
                                        break;
                                }
                        }
                        values = [today, tomorrow].combine(values);
                        this.setOpt(proxyField, 'label', 'Open on');
                } else {
                        var lbl = this.getOpt(proxyField, 'label', null, this.getOpt(proxyField, 'name', null, 'Closed on'));
                        
                        this.setOpt(proxyField, 'label', lbl);
                }
                
                var oth = this.getOpt(proxyField, 'other');
                
                if (oth) values.push({value:-1, text:oth == 'true' ? 'Other' : oth});
                
                this.setOpt(proxyField, 'values', values);
                var ui = this.getOpt(proxyField, 'ui') || 'select';
                this.setOpt(proxyField, 'valid', ui);
                this.setOpt(proxyField, 'sorted', 'true');
                
                return this.createBasic(holder, proxyField, value, condition);
        },
        
        createCreditcards: function(holder, proxyField, value, condition) {
                var values = [{value:1,img:'visa.png',text:'Visa'}, {value:2,img:'mastercard.png',text:'Mastercard'}];
                
                var show = this.getOpt(proxyField, 'show');
                
                if (show && show != 'img') values[1]['img'] = values[2]['img'] = '';
                
                this.setOpt(proxyField, 'values', values);
                this.setOpt(proxyField, 'valid', 'checkbox');
                
                return this.createBasic(holder, proxyField, value, condition);
        },
        
        createVerifybox: function(holder, proxyField, value, condition) {
                var values = this.getOpt(proxyField, 'values');
                
                if (!values) {
                        values = [{value:1,img:'yes.png',text:'Yes'}];
                } else {
                        values = [{
                                        value:typeOf(values) == 'string' ? values : values['value'],
                                        img:typeOf(values) == 'string' ? '' : values['img'],
                                        text:typeOf(values) == 'string' ? values : values['text']
                                }];
                }

                var show = this.getOpt(proxyField, 'show');

                if (show && show != 'img') values[0]['img'] = '';

                if (this.getOpt(proxyField, 'valueas') == 'text') {
                        values[0]['value'] = values[0]['text'];
                }

                if (this.getOpt(proxyField, 'unknown') == 'true') {
                        values.push({
                                value:this.getOpt(proxyField, 'valueas') == 'text'?'unknown':-1,
                                img:show && show != 'img'?'':'unknown.png',
                                text:'Unknown'
                        });
                }
                
                this.setOpt(proxyField, 'values', values);
                this.setOpt(proxyField, 'valid', 'checkbox');
                
                return this.createBasic(holder, proxyField, value, condition);
        },
        
        createYesno: function(holder, proxyField, value, condition) {
                var values = [{value:1,img:'yes.png',text:'Yes'}, {value:0,img:'no.png',text:'No'}];
                
                var show = this.getOpt(proxyField, 'show');
                
                if (show && show != 'img') values[0]['img'] = values[1]['img'] = '';
                
                if (this.getOpt(proxyField, 'valueas') == 'text') {
                        values[0]['value'] = 'yes';
                        values[1]['value'] = 'no';
                }
                
                if (this.getOpt(proxyField, 'unknown') == 'true') {
                        values.push({
                                value:this.getOpt(proxyField, 'valueas') == 'text'?'unknown':-1,
                                img:show && show != 'img'?'':'unknown.png',
                                text:'Unknown'
                        });
                }
                
                this.setOpt(proxyField, 'values', values);
                this.setOpt(proxyField, 'valid', 'radio');
                
                return this.createBasic(holder, proxyField, value, condition);
        },
        
        createRange: function(holder, proxyField, value, condition) {
                var 
                        i, 
                        is = this.getOpt(proxyField, 'low.statement'), 
                        h, 
                        hs = this.getOpt(proxyField, 'high.statement'),
                        s,
                        ss = this.getOpt(proxyField, 'step.statement'),
                        va = this.getOpt(proxyField, 'show', false),
                        shift = Number(this.getOpt(proxyField, 'shift', 0)),
                        values = []
                        ;
                
                if (!ss) eval('ss = function() {return arguments[0]+arguments[1]; };');
                else eval('ss = function(v,s) {'+ss+'};');
                
                s = Number(this.getOpt(proxyField, 'step', undefined, 1));
                
                if (is) {
                        is = 'i = function() {'+is.trim()+'}();';
                        eval(is);
                }
                
                if (!i) i = Number(this.getOpt(proxyField, 'low', undefined, 1));
                
                if (hs) {
                        hs = 'h = function() {'+hs.trim()+'}();';
                        eval(hs);
                }
                
                if (!h) h = Number(this.getOpt(proxyField, 'high'));
                
                h -= shift;
                
                while (i <= h) {
                        values.push({value:i,img:va=='img'?'n'+i+'.png':'',text:i+shift});
                        i = ss(i,s);
                        //i += s;
                }
                
                if (values.length <= 0) return;
                
                this.setOpt(proxyField, 'values', values);
                
                var ui = this.getOpt(proxyField, 'ui'), valid;
                
                if (!ui) {
                        if (this.chkOpt(proxyField, 'single', ['false', false])) {
                                if (values.length > this.options.selectTreshold) {
                                        valid = 'multiselect';
                                } else {
                                        valid = 'checkbox';
                                }
                        } else {
                                if (values.length > this.options.selectTreshold) {
                                        valid = 'select';
                                } else {
                                        valid = 'radio';
                                }
                        }
                } else if (ui) {
                        if (values.length > this.options.selectTreshold) {
                                if (ui == 'radio') valid = 'select';
                                else valid = 'multiselect';
                        }
                }
                
                this.setOpt(proxyField, 'valid', valid);
                
                return this.createBasic(holder, proxyField, value, condition);
        },
        
        _createSlider: function(holder, proxyField, value, condition) {
                var result;
                
                this.utility.load(
                        'tag', [
                                this.options.base + this.options.k2fbase + 'lib/jslider/bin/jquery.slider.min.js', 
                                this.options.base + this.options.k2fbase + 'lib/jslider/bin/jquery.slider.min.css'
                        ],
                        false, 
                        false, 
                        '',
                        function() { 
                                result = this.__createSlider(holder, proxyField, value, condition);
                                
                                if (!this.getOpt(proxyField, 'subfieldof')) {
                                        if (this.initiateFieldDependency(result[0]))
                                                this.handleFieldDependency(result[0]);
                                }
                        }.bind(this),
                        true
                );                
                
                return result;
        },
        
        __createSlider: function(holder, proxyField, value, condition) {
                var 
                        isInterval = this.getOpt(proxyField, 'ui') == 'rangeslider', 
                        def = {smooth:false},
                        values = this.getOpt(proxyField, 'values'),
                        val
                        ;
                
                if (values) {
                        def.scale = [];
                        
                        var i, n = values.length;
                        
                        for (i = 0; i < n; i++) def.scale.push(values[i].text);
                        
                        def.from = values[0].value;
                        def.to = values[n-1].value;
                } else {
                        def.from = this.getOpt(proxyField, 'min');
                        def.to = this.getOpt(proxyField, 'max');
                }
                
                if (!value) {
                        value = this.getDefaultValue(proxyField);
                        
                        if (!value) value = def.from + (isInterval ? ';' + def.to : '');
                }
                
                // Assumes equidistant steps and thereby scale
                if (val = this.getOpt(proxyField, 'step')) def.step = val.toNumber();
                else if (values && Number(values[1].value) != NaN && Number(values[0].value) != NaN) def.step = Number(values[1].value) - Number(values[0].value);
                //else def.step = 1;
                
                def.from = Number(def.from);
                def.to = Number(def.to);
                
                if (val = this.getOpt(proxyField, 'post')) def.dimension = val;
                
                if (val = this.getOpt(proxyField, 'format')) def.format = val;
                
                if (val = this.getOpt(proxyField, 'intervalfunc')) def.calculate = val;
                
                if (val = this.getOpt(proxyField, 'intervalskin', 'round_plastic')) def.skin = val;
                
                // if ignore is set we need to invoke callback
                // def.callback = function(value) { this.setProxyFieldValue(proxyField); };
                
                var 
                        fieldOpts = this.getOpts(proxyField), 
                        fld = this.ccf(
                        proxyField, value, 
                        fieldOpts && fieldOpts.hasOwnProperty('position') ? fieldOpts['position'] : 0, 
                        false, '', holder
                );
                        
                def.onstatechange = function(value){ 
                        fld[0].set('value', value).fireEvent('change', [fld[0]]);
                }.bind(this);
                
                val = fld[0].get('id');
                jQuery('#'+val).attr('value', value);
                jQuery('#'+val).slider(def);
                
                return fld;
        },
        
        _updateSearchBasic: function(cont, dst) {
                var els = cont.getElements('.knob input');
                dst.value = els[0].value+','+els[1].value;
        },
        
        // basic = native HTML form elements (select, textarea, various types of input text, checkbox, radio where labels can be images)
        createBasic: function(holder, proxyField, value, condition, position, preferredType, typeOptions) {
                this._orderValues(proxyField);
                
                var field = this.getOpts(proxyField), type, fieldType, values, name = field['label'] || field['name'];
                
                if (typeOptions == undefined) typeOptions = {};
                
                if (position == undefined) {
                        position = this.getOpt(proxyField, 'position', null, false);
                        
                        if (position === false) position = 0;
                }
                
                if (field['ui'] && field['ui'] == 'editor') {
                        field['ui'] = 'textarea';
                        this.setOpt(proxyField, 'show_editor', 1);
                }
                
                fieldType = (preferredType || field['ui'] || field['valid']).toLowerCase();
                type = fieldType;
                values = field['values'];
                
                var m = typeOf(values) == 'string' && values.match(/^values\:(\w+)/);
                
                if (m && this._basicValues[m[1]]) values = this._basicValues[m[1]];
                
                switch (fieldType) {
                        case 'select':
                                typeOptions = Object.merge(typeOptions, {values:values, first:'-- Select '+name+' --', multiple:field['multiple']=='1'});
                                break;
                        case 'textarea':
                                typeOptions = Object.merge(typeOptions, {cols:field['cols'], rows:field['rows']});
                                break;
                        default:
                                if (['radio', 'checkbox'].contains(fieldType)) {
                                        typeOptions = Object.merge(typeOptions, {values:values,type:type});
                                }
                                
                                type = 'input';
                                //typeOptions = {required:field['required'],interval:field['interval']};
                                break;
                }
                
                var saveValues = this.getOpt(proxyField, 'savevalues');
                
                if (saveValues) {
                        this._basicValues[saveValues] = values;
                }
                
                switch (field['valid']) {
                        case 'url':
                                if (!field['size']) field['size'] = 70;
                                break;
                }
                
                
                if (field['size']) typeOptions['size'] = field['size'];
                
                var lbl = '', isSubFieldOf = field['subfieldof'];
                
                if (isSubFieldOf) {
                        typeOptions['subfieldof'] = field['subfieldof'];
                        lbl = name;
                        var sid, id = field['subfieldof'].replace(this.options.pre, ''), mainOpts = this.options.fieldsOptions[id] || this.fieldsOptions[field['subfieldof']], subfields = mainOpts['subfields'];
                        
                        if (subfields) {
                                sid = this.getProxyFieldId(proxyField).replace(this.options.pre, '');

                                for (var i = 0; i < subfields.length; i++) {
                                        if (subfields[i].id == sid && mainOpts.valid == 'complex') {
                                                position = subfields[i].position;
                                                break;
                                        }
                                }
                        }
                }
                
                if (fieldType == 'slider' || fieldType == 'rangeslider') {
                        field = this._createSlider(holder, proxyField, value, condition, isSubFieldOf);
                } else {
                        field = this.ccf(proxyField, value, position, fieldType, lbl, holder, type, typeOptions);
                
                        if (!isSubFieldOf) {
                                if (this.initiateFieldDependency(field[0]))
                                        this.handleFieldDependency(field[0]);
                        }
                }
                
                if (this.chkOpt(proxyField, 'selectchosen', '1')) {
                        this.utility.load('tag', this.options.base + this.options.k2fbase + 'lib/chosen-with-templates/chosen/chosen.css');

                        this.utility.load(
                                'tag', 
                                this.options.base + this.options.k2fbase + 'lib/chosen-with-templates/chosen/chosen.jquery.js', 
                                'js', 
                                false, 
                                '', 
                                function() { this._createChosen(field[0], proxyField); }.bind(this)
                        );
                }
                        
                return field;
        },
        
        _createChosen:function(field, proxyField) {
                var opts = {}, val;
                
                if (val = this.getOpt(proxyField, 'chosen.width'))
                        if (val = val.toInt())
                                field.setStyle('width', val);
                
                if (val = this.getOpt(proxyField, 'chosen.data-placeholder')) {
                        field.set('data-placeholder', val);
                        
                        var options = field.getElements('option'), i, n = options.length;
                        
                        for (i = 0; i < n; i++) {
                                if (!options[i].get('value')) {
                                        field.getElements('option')[i].dispose();
                                        break;
                                }
                        }
                }
                
                if (val = this.getOpt(proxyField, 'chosen.no_results_text')) opts['no_results_text'] = val;
                
                if (val = this.getOpt(proxyField, 'chosen.max_selected_options')) opts['max_selected_options'] = val;
                
                if (val = this.getOpt(proxyField, 'chosen.allow_single_deselect')) opts['allow_single_deselect'] = true;
                
                var k2f = this;
                
                if (val = this.getOpt(proxyField, 'chosen.template')) {
                        field.set('data-template', val);
                        opts['template'] = function(text, value, templateData) {
                                var img = templateData['img'], valuec = k2f.utility.makeSafePath(value), textc = k2f.utility.makeSafePath(text);
                                if (img) {
                                        img = k2f.utility.getImg(img);
                                        img = '<img src="'+img.get('src')+'"></img>';
                                } else {
                                        img = '';
                                }
                                var template = document.id(this).get('data-template');
                                template = template.replace(/%img%/g, img).replace(/%textc%/g, textc).replace(/%valuec%/g, valuec).replace(/%text%/g, text).replace(/%value%/g, value);
                                return [template].join('');
                        }.bind(field);                        
                }
                
                if (val = this.getOpt(proxyField, 'chosen.templateSelected')) {
                        field.set('data-templateSelected', val);
                        opts['templateSelected'] = function(text, value, templateData) {
                                var img = templateData['img'];
                                if (img) {
                                        img = k2f.utility.getImg(img);
                                        img = img.get('src').replace(/\.([^\.]+)$/, '_selected.$1');
                                        img = '<img src="'+img+'"></img>';
                                } else {
                                        img = '';
                                }
                                var template = document.id(this).get('data-template');
                                template = template.replace(/%img%/g, img).replace(/%text%/g, text).replace(/%value%/g, value);
                                return [template].join('');
                        }.bind(field);                        
                }
                
                jQuery(field).chosen(opts).change(function() {
                        var el = document.id(this);
                        el.fireEvent('change', [el]);
                });
        },
                
        _basicValues:{},
        
        _orderValues:function(proxyField) {
                if (this.chkOpt(proxyField, 'sorted', ['true', true, '1', 1])) return;
                
                var values = this.getOpt(proxyField, 'values');
                
                if (typeOf(values) != 'array' || values.length <= 0) return;
                
                if (typeOf(values[0]) != 'object') {
                        values = values.sort();
                } else {
                        var ord = this.getOpt(proxyField, 'valuesorder', undefined, 'text');

                        if (!values[0][ord]) return;

                        values = values.sortBy(ord);
                }
                
                this.setOpt(proxyField, 'values', values);
                this.setOpt(proxyField, 'sorted', true);
        },
        _depsOn:{},
        
        addDependsOn: function(field, dependsOnField, dependsOnValue) {
                var m, isNegative = false;
                
                if (typeof field == 'string' && (m = field.match(/^id\:(\d+)(|\:1)$/))) {
                        if (!this.isFieldAvailable(this.options.pre+m[1])) return;
                        
                        field = this.options.pre+m[1];
                        isNegative = m[2] != '';
                } else {
                        var depeeFields = dependsOnField.getParent('[valueholder=true]').getElements('[customvalueholder=true]'), found = false;
                        
                        for (var i = 0, n = depeeFields.length; i < n && !found; i++) {
                                if (this.getOpt(depeeFields[i], 'position') == field) {
                                        field = depeeFields[i];
                                        found = true;
                                }
                        }
                        if (!found) field = depeeFields[field];
                        if (field) field = field.get('id');
                }          
                
                if (!field) return;
                //dependsOnField = this.getProxyFieldId(dependsOnField);
                dependsOnField = dependsOnField.get('id');
                
                if (!this._depsOn[field]) this._depsOn[field] = {};
                if (!this._depsOn[field][dependsOnField]) this._depsOn[field][dependsOnField] = {'isnegative':isNegative,'values':[]};
                if (!this._depsOn[field][dependsOnField]['values'].contains(dependsOnValue)) {
                        this._depsOn[field][dependsOnField]['values'].push(dependsOnValue);
                }
                
//                var depson = this.getOpt(field, 'depson');
//                
//                if (!depson) depson = {};
//                
//                dependsOnField = this.getProxyFieldId(dependsOnField);
//                
//                if (!depson[dependsOnField]) depson[dependsOnField] = [];
//                
//                if (!depson[dependsOnField].contains(dependsOnValue)) depson[dependsOnField].push(dependsOnValue);
//                
//                this.setOpt(field, 'depson', depson);
        },
        
        _initiateFieldDependency: function(field, forSybs) {
                if (this.initiateFieldDependency(field, forSybs)) this.handleFieldDependency(field);
        },
        
        initiateFieldDependency: function(field, forSybs) {
                field.addEvent('change', function() {
                        this.handleFieldDependency(field); 
                }.bind(this));

                var k, i, n, t, deps, dep, depees = this.getOpt(field, 'depees', null, []);
                
                deps = this.getOpt(field, 'deps');
                
                if (deps) {
                        for (k in deps) {
                                dep = Array.from(deps[k]);
                                
                                for (i = 0, n = dep.length; i < n; i++) {
                                        t = dep[i].toInt() == dep[i] ? dep[i].toInt() - 1 : dep[i];
                                        
                                        if (depees.contains(t)) {
                                                this.addDependsOn(t, field, k);
                                                continue;
                                        }
                                        
                                        if (typeof t != 'number') {
                                                var tt = t.replace('id:', '');
                                                
                                                if (tt.test(/\:1$/)) {
                                                        tt = tt.replace(/\:1$/, '');
                                                }
                                                
                                                tt = this.options.pre+tt;
                                                
                                                if (!this.isFieldAvailable(tt)) {
                                                        this.addFormElementComplete(
                                                                '_initiateFieldDependency', 
                                                                tt, 
                                                                [field, false],
                                                                tt
                                                        );
                                                        
                                                        continue;
                                                }
                                        }
                                        
                                        this.addDependsOn(t, field, k);
                                        
                                        depees.push(t);
                                }
                        }
                        
                        this.setOpt(field, 'depees', depees);
                }
                
                if (!forSybs && field.get('tag') == 'input' && (field.get('type') == 'radio' || field.get('type') == 'checkbox')) {
                        var fields = this.getSyblings(field, true);
                        for (i = 0, n = fields.length; i < n; i++) {
                                if (fields[i].get('id') != field.get('id')) {
                                        this.initiateFieldDependency(fields[i], true);
                                }
                        }
                }
                
                return depees;
        },
        
        isDependent: function(id, fields, offSet) {
                var m;
                
                if (typeof id == 'string' && (m = id.match(/^id\:(\d+)(|\:1)$/))) {
                        if (!this.isFieldAvailable(this.options.pre+m[1])) return false;
                        
                        return id;
                }
                
                if (typeof offSet != 'number') offSet = 0;
                
                id -= offSet;
                
                for (var i = 0, n = fields.length; i < n; i++) {
                        if (this.getOpt(fields[i], 'position') == id) {
                                return fields[i];
                        }
                }
                
                return fields[id];
        },
        
        handleFieldDependency: function(field) {
                var deps = this.getOpt(field, 'deps');
               
                if (!deps) return;
                
                var 
                        depeeFields = field.getParent('[valueholder=true]').getElements('[customvalueholder=true]'),
                        depees = this.getOpt(field, 'depees'), 
                        vals = Array.from(this.getValue(field)),
                        depeesAvailable = [],
                        dep, i, n, j, m, t, valSelected;
                
                m = vals.length;
                
                for (i = 0, n = depees.length; i < n; i++) {
                        if (t = this.isDependent(depees[i], depeeFields, 0)) {
                                valSelected = false;
                                
                                for (j = 0; j < m; j++) {
                                        if (deps.hasOwnProperty(vals[j]) && deps[vals[j]].contains(t)) {
                                                valSelected = true;
                                                break;
                                        }
                                }
                                
                                if (!valSelected) 
                                        this.toggleCustomField(t, t.match(/^id\:\d+\:1$/) ? 'block' : 'none');
                        }
                }
                
                var d;
                
                for (i = 0, n = vals.length; i < n; i++) {
                        if (deps.hasOwnProperty(vals[i])) {
                                dep = Array.from(deps[vals[i]]);
                                
                                for (j = 0, m = dep.length; j < m; j++) {
                                        if (t = this.isDependent(dep[j], depeeFields, 1)) {
                                                this.toggleCustomField(
                                                        t, 
                                                        t.match(/^id\:\d+\:1$/) ? 'none' : 'block'
                                                );
                                                if (typeOf(t) == 'element') t = this.getOpt(t, 'position');
                                                depeesAvailable.push(t);
                                        }
                                }
                        }
                }
                
                for (i = 0, n = depees.length; i < n; i++) {
                        t = this.isDependent(depees[i], depeeFields, 1);
                        
                        if (!depeesAvailable.contains(t)) {
                                if (typeof t == 'string' && t.test(/^id\:\d+$/)) {
                                        m = t.match(/^id\:(\d+)$/);
                                        t = this.options.pre+m[1];
                                        t = this.getProxyFieldId(t);
                                        j = !document.id(t) || !t ? false : this.getValueRow(t);
                                        
                                        if (!j) {
                                                this.addFormElementComplete(
                                                        'resetElements', 
                                                        t,
                                                        function(e) { 
                                                                return this.getValueRow(e); 
                                                        }.bind(this), 
                                                        t,
                                                        t
                                                );

                                                continue;
                                        }
                                        
                                        this.resetElements(this.getValueRow(t));
                                } else this.resetValue(depeeFields[t]);
                        }
                }
        }
};