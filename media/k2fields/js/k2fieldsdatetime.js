//$Copyright$

var k2fields_type_datetime = {
        createSearchDatetime: function(holder, proxyField, value, condition) {
                return this.createSearchDatetimerange(holder, proxyField, value, condition);
        },
        
        createSearchDate: function(holder, proxyField, value, condition) {
                return this.createSearchDatetimerange(holder, proxyField, value, condition);
        },
        
        createSearchDuration: function(holder, proxyField, value, condition) {
                return this.createSearchDatetimerange(holder, proxyField, value, condition);
        },
        
        createSearchDatetimerange: function(holder, proxyField, value, condition) {
                var 
                        values = !value ? ['', '', '', ''] : value.split(this.options.valueSeparator),
                        selValues = this.getOpt(proxyField, 'values') || [
                                        {value:'now', text:'Now'},
                                        {value:'today', text:'Today'},
                                        {value:'thisevening', text:'This evening'},
                                        {value:'tomorrow', text:'Tomorrow'},
                                        {value:'thisweekend', text:'This weekend'},
                                        {value:'thisweek', text:'This week'},
                                        {value:'nextweek', text:'Next week'},
                                        {value:'specify', text:'Another time...'}
                                ],
                        sel = this.ccf(proxyField, values[0], 0, false, '', holder, 'select', {values:selValues});
                     
                sel = sel[0];
                
                if (values[0]) {
                        var i, n;

                        for (i = 0, n = sel.options.length; i < n; i++) {
                                if (sel.options[i].value == values[0]) {
                                        break;
                                }
                        }
                        
                        if (values[0] && !(i < n && sel.options[i].value == values[0])) {
                                sel.selectedIndex = sel.options.length - 1;
                        }
                }
                        
                var pickerHolder = new Element('div', {
                        'class':'dpinputholder',
                        'styles':{'display':(sel.selectedIndex == sel.options.length - 1 ? 'block' : 'none')}
                }).inject(holder), pickers, isRange = this.getOpt(proxyField, 'valid').indexOf('range') != -1;
                
                //isRange = !this.isType(proxyField, ['datetime', 'time', 'date', 'duration']);
                
                if (isRange) {
                        pickers = this.createDatetimerange(pickerHolder, proxyField, value, condition, false);
                } else {
                        pickers = this.createDatetime(pickerHolder, proxyField, value, condition, false, false);
                }
                
                sel.addEvent('change', function() {
                        var vis = sel.selectedIndex == sel.options.length - 1; // specify is the last element
                        var fx = new Fx.Tween(pickerHolder, {'property':'opacity','duration':'short'});

                        fx.start(vis ? 1 : 0).chain(function(){
                                this.element.setStyle('display', vis ? 'block' : 'none');
                                if (vis) {
                                        var now = (new Date()).format(pickers[0].format);
                                        this.element.getElements('input.dpinput').set('value', now);
                                }
                        });
                }.bind(this));
                
                var picker = this.datePickers[pickers[0].get('id')];
                
                picker.addEvent('select', function(date) {
                        sel.options[sel.options.length - 1]['value'] =
                                pickers[0].get('value')+(pickers.length > 1 ? ','+pickers[1].get('value') : '')
                }.bind(this));
                
                var res = [sel, pickers[0]];
                
                if (isRange) {
                        picker = this.datePickers[pickers[1].get('id')];

                        picker.addEvent('select', function(date) {
                                sel.options[sel.options.length - 1]['value'] =
                                        pickers[0].get('value')+(pickers.length > 1 ? ','+pickers[1].get('value') : '')
                        }.bind(this));
                        
                        res.push(pickers[1]);
                }
                
                return res;
        },
        
        createDaterange: function(holder, proxyField, value, condition) {
                return this.createDatetimerange(holder, proxyField, value, condition);
        },
        
        createDatetimerange: function(holder, proxyField, value, condition, includeLength) {
                var result, lbl;
                
                if (value) value = value.split(this.options.valueSeparator);
                else value = ['', '', '', ''];
                
                lbl = this.getOpt(proxyField, 'startlabel', null, 'Start');
                result = this.createDatetime(holder, proxyField, value[0], condition, 0, lbl);
                
                var secondOption = this.getOpt(proxyField, 'secondoption', null, 'datetime');
                
                this.setOpt(proxyField, 'valid', secondOption);
                
                lbl = this.getOpt(proxyField, 'endlabel', null, secondOption == 'time' ? 'Length' : 'End');
                
                result.combine(this.createDatetime(holder, proxyField, value[1], condition, 1, lbl));
                
                var picker = this.datePickers[result[0].get('id')];
                
                picker.addEvent('select', function(date) {
                        this.enforceDateDependency(date, result[1].get('id'), 'min');
                }.bind(this));
                
                this.enforceDateDependency(
                        Date.parse(result[0].get('value')), 
                        result[1].get('id'), 
                        'min'
                );
                
                var valid = this.getOpt(proxyField, 'valid').replace(/range/i, '');
                
                if (includeLength == undefined)
                        includeLength =  this.getOpt(proxyField, 'includelength')
                
                if (includeLength) {
                        var shortUnits = this.getOpt(proxyField, 'shortunits', null, false), lengthInput, lengthInputValue;
                                
                        lbl = this.getOpt(proxyField, 'lengthlabel', null, 'Length');
                        
                        var opt = secondOption == 'time' ? 'hidden' : {'disabled':true, 'size':value[2] ? value[2].length+5:1, 'class':'timeinterval'};
                        
                        lengthInput = this.ccf(
                                proxyField, 
                                value[2], 
                                2, 
                                'alphanum', 
                                lbl, 
                                holder, 
                                undefined, 
                                opt, 
                                false
                        );
                                
                        lengthInputValue = this.ccf(
                                proxyField, 
                                value[3], 
                                3, 
                                undefined, 
                                undefined, 
                                holder, 
                                'input', 
                                'hidden', 
                                false, 
                                undefined, 
                                undefined, 
                                false // avoiding double value propagation
                        );
                        
                        lengthInput = lengthInput[0];
                        lengthInputValue = lengthInputValue[0];
                        
                        result.push(lengthInput);
                        result.push(lengthInputValue);
                        
                        var includeTime = valid.indexOf('time') >= 0;
                        
                        picker.addEvent('select', function(dateTime) {
                                var diff = this.calculateDateDifference(
                                        dateTime, result[1], includeTime, 'string', false, shortUnits, secondOption == 'time'
                                );
                                
                                lengthInputValue.set('value', diff[0]);
                                lengthInput.set('value', diff[1]);
                                lengthInput.set('size', diff[1].length+5);
                                
                                if (!this.isMode('search')) 
                                        this.setProxyFieldValue(proxyField);
                        }.bind(this));
                        
                        picker = this.datePickers[result[1].get('id')];
                        
                        picker.addEvent('select', function(dateTime) {
                                var diff = this.calculateDateDifference(
                                        result[0], dateTime, includeTime, 'string', false, shortUnits, secondOption == 'time'
                                );
                                
                                lengthInputValue.set('value', diff[0]);
                                lengthInput.set('value', diff[1]);
                                lengthInput.set('size', diff[1].length+5);
                                
                                if (!this.isMode('search')) 
                                        this.setProxyFieldValue(proxyField);
                        }.bind(this));                        
                }
                
                return result;
        },
        
        timize: function(millisecs, includeTime, resultAs, showEmpty, shortUnits) {
                return this.calculateDateDifference(millisecs, null, includeTime, resultAs, showEmpty, shortUnits);
        },
        
        calculateDateDifference: function(start, end, includeTime, resultAs, showEmpty, shortUnits, endIsLength) {
                var diff, lbls = shortUnits ? ['d', 'h', 'min', 'sec'] : ['day', 'hour', 'minute', 'second'];
                
                if (typeof start == 'number') {
                        diff = start;
                } else {
                        if (typeOf(start) == 'element') start = start.get('value');
                        if (typeOf(end) == 'element') end = end.get('value');
                        
                        if (typeOf(start) == 'string') start = Date.parse(start);
                        if (typeOf(end) == 'string') end = Date.parse(end);
                        
                        if (endIsLength) {
                                diff = (end.getHours() * 60 * 60 + end.getMinutes() * 60 + end.getSeconds()) * 1000 + end.getMilliseconds();

                                end = start;
                                end.setTime(start.getTime() + diff);
                        } else {
                                diff = end - start;
                        }
                        
                        diff /= 1000;
                }
                
                if (!resultAs) resultAs = 'string';
                
                var rdiff = diff, factor = 24 * 60 * 60, r = (diff / factor).toInt(), rs = [r];

                if (includeTime) {
                        diff -= r * factor;

                        factor = 60 * 60;
                        r = (diff / factor).toInt();
                        rs.push(r);
                        diff -= r * factor;

                        factor = 60;
                        r = (diff / factor).toInt();
                        rs.push(r);
                        diff -= r * factor;

                        r = diff;
                        rs.push(r);
                }
                
                showEmpty = showEmpty || false;
                
                var result = [];
                
                for (var i = 0, n = rs.length; i < n; i++) {
                        if (showEmpty || rs[i] > 0) {
                                result.push(rs[i] + (shortUnits ? '' : ' ') + lbls[i] + (rs[i] > 1 && !shortUnits ? 's' : ''));
                        }
                }
                
                if (resultAs == 'string') {
                        if (result.length > 0) {
                                var last = result.pop();
                                result = result.join(' ') + (result.length >= 1 ? ' and ' : '') + last;
                        } else {
                                result = '';
                        }
                }
                
                return [rdiff, result];
        },
        
        datePickers: {},
        
        createDatetime: function(holder, proxyField, value, condition, pos, lbl) {
                return this._createDatetime(holder, proxyField, value, condition);
                
                var result, theme = this.getOpt(proxyField, 'theme', null, 'dashboard');
                
                theme = 'datepicker/'+theme+'/'+theme+'.css';
                
                this.utility.load('request', this.options.base + this.options.k2fbase + 'lib/'+theme, 'css');
                
                this.utility.load(
                        'tag', 
                        this.options.base + this.options.k2fbase + 'lib/datepicker.js', 
                        'js', 
                        false, 
                        '', 
                        function() { 
                                result = this._createDatetime(holder, proxyField, value, condition);
                        }.bind(this)
                );
                
                return result;
        },
        
        _createDatetime: function(holder, proxyField, value, condition, pos, lbl) {
                var 
                        range = this.getOpt(proxyField, 'interval'), 
                        valid = this.getOpt(proxyField, 'valid'),
                        theme = this.getOpt(proxyField, 'theme', null, 'dashboard'),
                        timePicker = valid.indexOf('time') >= 0 || valid.indexOf('duration') >= 0,
                        format = this.getOpt(proxyField, valid + 'format', null, this.options[valid + 'format']),
                        //format = this.getOpt(proxyField, this.getOpt(proxyField, 'valid') + 'format'),
//                        format = this.getOpt(
//                                proxyField, 
//                                (!timePicker ? 'date' : (valid == 'duration' ? 'time' : valid).replace(/range/i, ''))+'Format', 
//                                null, 
//                                this.options[(!timePicker ? 'date' : (valid == 'duration' ? 'time' : valid).replace(/range/i, ''))+'Format']
//                        ),
                        minDep = this.getOpt(proxyField, 'starttime'),
                        maxDep = this.getOpt(proxyField, 'endtime'),
                        label = this.getOpt(proxyField, 'label', null, this.getOpt(proxyField, 'name', null, '')),
                        el,
                        now = (new Date()).format(format),
                        opts = {'class':'dpinput', 'size':now.length+3},
                        isInitial = false,
                        position = this.getOpt(proxyField, 'position')
                        ;
                
                format = this.convertPHPToJSDatetimeFormat(format);
                
                if (this.isMode('search')) {
                        opts['ignore'] = true;
                        timePicker = this.getOpt(proxyField, 'searchtime', null, 'true') == 'true';
                }
                
                var dValue = value, repeat = this.getOpt(proxyField, 'repeat');
                
                if (!dValue) {
//                        dValue = this.getDefaultValue(proxyField);
//                        
//                        if (!dValue) {
//                                dValue = valid == 'duration' ? '00:00' : now;
//                        }
                        
                        isInitial = true;
                } else {
                        dValue = value.split(this.options.valueSeparator);
                        dValue = dValue[0];
                }
                
                el = this.ccf(
                        proxyField, 
                        dValue,
                        pos || position || 0, 
                        valid, 
                        lbl === false ? '' : (lbl || label), 
                        holder, 
                        undefined, 
                        opts,
                        repeat ? false : pos == undefined,
                        undefined, undefined, !this.isMode('search')
                );

                el = el[0];
                
                if (holder.getParent().getElements('.resettimebtn').length == 0) {
                        new Element('a', {
                                'text':'Reset',
                                'class':'resettimebtn',
                                'href':'#',
                                'events':{
                                        'click':function(e) {
                                                e = this._tgt(e);
                                                this.resetElements(e.getParent());
                                                return false;
                                        }.bind(this)
                                }
                        }).inject(holder, 'after');
                }
                                
                if (isInitial) this.setProxyFieldValue(el);
                
                if (theme.indexOf('datepicker_') != 0) theme = 'datepicker_'+theme;
                
                var options = {
			pickerClass: theme,
			useFadeInOut: !Browser.ie,
                        format: format,
                        minDate: range[0],
                        maxDate: range[1],
/*                        onSelect: function(date){
                                this.enforceDateDependency(date, minDep, 'min');
                                this.enforceDateDependency(date, maxDep, 'max');
                        }.bind(this),*/
                        timeWheelStep: 5,
                        timePicker: timePicker,
                        startDay: this.getOpt(proxyField, 'weekstartson', null, 1).toInt()
		};
                
                if (!isNaN(el.getStyle('width').toInt())) {
                        options['position'] = {x:(el.getStyle('width').toInt() - 185)/2, y:0};
                }
                
                if (valid.indexOf('date') < 0) {
                        options['pickOnly'] = 'time';
                        options['format'] = '';
                }
                
                this.datePickers[el.get('id')] = new Picker.Date(el, options);
                
                var repeatUI = [];
                
                if (repeat && !this.isMode('search')) {
                        repeatUI = this.createDatetimeRepeatableUI(holder, proxyField, value, condition, pos, el);
                }
                
                if (minDep)
                        this.addFormElementComplete(
                                'enforceDateDependency',
                                this.options.pre+minDep,
                                [Date.parse(el.get('value')), minDep, 'min'],
                                this.options.pre+minDep
                        );
                
                if (maxDep)
                        this.addFormElementComplete(
                                'enforceDateDependency',
                                this.options.pre+maxDep,
                                [Date.parse(el.get('value')), maxDep, 'max'],
                                this.options.pre+maxDep
                        );
                                
                if (repeat == 'enddate' && !this.isMode('search')) {
                        minDep = repeatUI[repeatUI.length - 1].get('id');
                        this.enforceDateDependency(el.get('value') ? Date.parse(el.get('value')) : new Date(), minDep, 'min');
                        this.datePickers[el.get('id')].setOptions({
                                onSelect: function(date) {
                                        this.enforceDateDependency(date, minDep, 'min');
                                }.bind(this)
                        });
                }
                
                return [el].combine(repeatUI);
        },
        
        createDatetimeRepeatableUI: function(holder, proxyField, value, condition, pos, minDep) {
                if (pos == undefined) pos = 1;
                else pos++;
                
                value = !value ? ['', '', '', ''] : value.split(this.options.valueSeparator);
                
                if (value.length == 1) value = [value[0], '', '', ''];
                
                var interval = this.getOpt(proxyField, 'interval');
                this.setOpt(proxyField, 'interval', '');
                
                // Every X U (U = D, W, M, Y)
                var rep = this.ccf(proxyField, value[pos], pos, 'checkbox', '', holder, 'input', {
                        type:'checkbox',
                        values:[{text:'Repeat',value:'repeat'}]
                }, false);
                rep = rep[0];
                
                var repeatOn = value[pos] != '';
                
                rep.addEvent('change', function(e) {
                        e = document.id(e.target);
                        
                        // freq
                        var el = e.getParent('ul').getNext('select'), disabled = !e.checked, to = disabled ? 'none' : 'inline';
                        if (!e.checked) el.selectedIndex = -1;
                        el.disabled = disabled;
                        el.setStyle('display', to);
                        
                        // unit
                        el = el.getNext('select');
                        if (disabled) el.selectedIndex = -1;
                        el.disabled = disabled;
                        el.setStyle('display', to);
                        
                        // times
                        el = el.getNext('select') || el.getNext('input');
                        if (disabled) {
                                if (el.get('tag') == 'select') el.selectedIndex = -1;
                                else el.set('value', '');
                        }
                        if (el.get('tag') == 'input') el.getPrevious('label').setStyle('display', to);
                        
                        el.disabled = disabled;
                        el.setStyle('display', to);
                });
                
                pos++;
                var required = this.getOpt(proxyField, 'required');
                this.setOpt(proxyField, 'required', '1');
                var freq = this.ccf(proxyField, value[pos], pos, false, '', holder, 'select', {
                        values:[{value:'', text:'Repeat on'}].combine([].range(1, 10))
                }, false);
                freq = freq[0];

                pos++;
                
                var unit = this.ccf(proxyField, value[pos], pos, false, '', holder, 'select', {
                        values:[
                                {value:'', text:'Every'},
                                {value:'d', text:'Day'},
                                {value:'w', text:'Week'},
                                {value:'m', text:'Month'},
                                {value:'y', text:'Year'}
                        ]
                }, false);
                unit = unit[0];
                
                pos++;
                
                var times, repeatType = this.getOpt(proxyField, 'repeat');
                        
                if (repeatType == 'enddate') {
                        this.setOpt(proxyField, 'repeat', null);
                        times = this.createDatetime(holder, proxyField, value[pos], condition, pos, 'End date');
                        this.setOpt(proxyField, 'repeat', repeatType);
                } else {
                        times = this.ccf(proxyField, value[pos], pos, false, '', holder, 'select', {
                                values:[{value:'', text:'Number of times'}].combine([].range(1, this.getOpt(proxyField, 'maxrepeat', null, 50)))
                        }, false);
                }
                
                times = times[0];
                
                if (!repeatOn) {
                        freq.disabled = unit.disabled = times.disabled = true;
                        freq.setStyle('display', 'none');
                        unit.setStyle('display', 'none');
                        times.setStyle('display', 'none');
                        
                        if (times.get('tag') == 'input') 
                                times.getPrevious('label').setStyle('display', 'none');
                }
                        
                this.setOpt(proxyField, 'interval', interval);
                this.setOpt(proxyField, 'required', required);
                
                return [rep, freq, unit, times];
        },
        
        createTimerange: function(holder, proxyField, value, condition) { return this.createDatetimerange(holder, proxyField, value, condition); },
        
        createDaterange: function(holder, proxyField, value, condition) { return this.createDatetimerange(holder, proxyField, value, condition); },
        
        createTime: function(holder, proxyField, value, condition) { return this.createDatetime(holder, proxyField, value, condition); },
        
        createDuration: function(holder, proxyField, value, condition) { return this.createDatetime(holder, proxyField, value, condition); },

        createDate: function(holder, proxyField, value, condition) { return this.createDatetime(holder, proxyField, value, condition); },
        
        enforceDateDependency: function(date, dep, minOrMax) {
                if (dep) {
                        dep = document.id(dep) 
                        || document.id(this.options.pre+dep)
                        || this.getCell(this.options.pre+dep).getElement('input[customvalueholder=true]')
                        ;
                        
                        var picker = this.datePickers[dep.get('id')], curr, input;
                        
                        picker.options[minOrMax == 'min' ? 'minDate' : 'maxDate'] =
                                new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0);

                        if (picker.options.pickOnly != 'time') {
                                input = (!picker.options.updateAll && picker.input) ? [picker.input] : picker.inputs;
                                input = input[0];

                                if (input.get('value'))
                                        curr = Date.parse(input.get('value'));

                                if (!curr || minOrMax == 'min' && curr < date || minOrMax == 'max' && curr > date) {
                                        picker.select(date);
                                }
                        }
                }
        },
        convertPHPToJSDatetimeFormat: function(fmt) {
                return fmt.replace(/F/, 'B').replace(/M/, 'b').replace(/i/, 'M').replace(/s/, 'S').replace(/j/, 'e').replace(/([a-zA-Z])/g, '%$1');
        }
};