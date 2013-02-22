//$Copyright$

var k2fieldsoptions = new Class({
        fieldsOptions: {},
        
        optid: function(field, traverse, opts, forKey) {
                field = document.id(field) || field;
                
                if (traverse) {
                        if (!opts) {
                                opts = this.fieldsOptions;
                        }

                        var _id, id, ids = [field.get('id'), field.get('name'), this.getProxyFieldId(field)];
                        
                        for (var i = 0, n = ids.length; i < n; i++) {
                                id = ids[i];
                                
                                if (id && opts.hasOwnProperty(id)) {
                                        _id = id;

                                        if (forKey != undefined) {
                                                if (opts[id] && opts[id].hasOwnProperty(forKey)) {
                                                        return id;
                                                }
                                        }
                                }
                        }
                        
                        return _id;
                }
                
                if (typeof field != 'string') {
                        return field.get('id') || field.get('name');
                }
                
                return field;
        },
        
        setOpts: function(field, opts, replace) {
                var id = this.optid(field);
                if (replace == undefined) replace = true;
                if (replace || !this.fieldsOptions[id]) this.fieldsOptions[id] = opts;
                else this.fieldsOptions[id] = Object.merge(this.fieldsOptions[id], opts);
        },

        getOpts: function(field, key) {
                var id = this.optid(field, true, this.fieldsOptions, key);
                return this.fieldsOptions[id];
        },
        
        getOpt: function(field, key, opts, def, defEmpty) {
                key = Array.from(key);
                
                var aKey = key[0];
                
                if (!opts) opts = this.getOpts(field, aKey);
                
                if (!opts || !opts.hasOwnProperty(aKey)) return arguments.length > 3 ? def : '';
                
                var val = opts[aKey];
                
                if (defEmpty && (val === undefined || val == '')) return defEmpty;
                
                if (key.length > 1) {
                        key.remove(key[0]);
                        return this.getOpt(field, key, val);
                }

                return val;
        },
        
        delOpt: function(field, key) {this.setOpt(field, key, undefined);},
        
        setOpt: function(field, key, value) {
                var id = this.optid(field);
                
                if (typeof key != "string" && key.length == 1) {
                        key = key[0];
                }

                if (typeof key == "string") {
                        if (!this.fieldsOptions.hasOwnProperty(id)) {
                                this.fieldsOptions[id] = {};
                        }
                        
                        if (value == undefined) {
                                if (this.fieldsOptions[id].hasOwnProperty(key)) {
                                        delete this.fieldsOptions[id][key];                                        
                                }
                        } else {
                                this.fieldsOptions[id][key] = value;
                        }
                } else if (key.length == 2) {
                        if (!this.fieldsOptions.hasOwnProperty(id)) {
                                this.fieldsOptions[id] = {};
                        }
                        
                        if (!this.fieldsOptions[id].hasOwnProperty(key[0])) {
                                this.fieldsOptions[id][key[0]] = {};
                        }

                        if (!this.fieldsOptions[id].hasOwnProperty(key[1])) {
                                this.fieldsOptions[id][key[1]] = {};
                        }

                        if (value == undefined) {
                                if (this.fieldsOptions[id].hasOwnProperty(key[0]) && this.fieldsOptions[id][key[0]].hasOwnProperty(key[1])) {
                                        delete this.fieldsOptions[id][key[0]][key[1]];
                                }
                        } else {
                                this.fieldsOptions[id][key[0]][key[1]] = value;
                        }
                }
        },

        chkOpt: function(proxyField, optionKey, chkValues) {
                if (arguments.length == 2) return $chk(this.getOpt(proxyField, optionKey));
                
                chkValues = Array.from(chkValues);
                
                var values = this.getOpt(proxyField, optionKey);
                
                values = Array.from(values);
                
                for (var i = 0; i < chkValues.length; i++) {
                        for (var j = 0; j < values.length; j++) {
                                if (values[j] === chkValues[i]) {
                                        return true;
                                }
                        }
                }
                
                return false;
        },
        
        createProxyField: function(afterThis) {
                if (!this.lastKnownId) {
                        var m, re = new RegExp('^'+this.options.pre+'(\\d+)$')
                        
                        this.lastKnownId = 0;
                        
                        $$('[name^='+this.options.pre+']').each(function(e) {
                                if (m = e.get('name').match(re)) {
                                        m = parseInt(m[1]);
                                        
                                        if (m > this.lastKnownId) {
                                                this.lastKnownId = m;
                                        }
                                }
                        }.bind(this));
                }
                
                this.lastKnownId++;

                new Element('input', {type:'hidden', 'id':this.options.pre+this.lastKnownId}).injectAfter(afterThis);
                
                return this.lastKnownId;
        },
        
        _filterOptions: function(opts) {
                var filter = this.modeFilter();
                
                if (!filter) return opts;
                
                var oFilters = opts['filters'];
                
                if (!oFilters) return false;
                
                oFilters = Array.from(oFilters);
                
                for (var i = 0, n = oFilters.length; i < n; i++) {
                        if (oFilters[i][filter]) return opts;
                }
                
                return false;
        },
        
        modeTranslation: {
                search: {
                        valid:
                                {
                                radio: 'checkbox'
                        }
                }
        },
        
        modize: function(opts) {
                if (!this.modeTranslation.hasOwnProperty(this.options.mode)) return opts;
                var tr = this.modeTranslation[this.options.mode], val, i, n;
                
                for (var name in opts) {
                        if (name == 'valid' && opts[name] == 'complex') {
                                for (i = 0, n = opts['subfields'].length; i < n; i++) {
                                        opts['subfields'][i] = this.modize(opts['subfields'][i]);
                                }
                        }
                        
                        if (tr[name]) {
                                val = Array.from(opts[name]);
                                
                                for (i = 0, n = val.length; i < n; i++) {
                                        if (tr[name][val[i]]) val[i] = tr[name][val[i]];
                                }
                                
                                opts[name] = typeOf(opts[name]) == 'array' ? val : val[0];
                        }
                }
                
                return opts;
        },
        
        isFieldAvailable: function(field) {
                var id = this.getProxyFieldId(field);
                
                if (!id) return false;
                
                id = id.replace(this.options.pre, '');
                
                if (!this.options.fieldsOptions.hasOwnProperty(id)) return false;
                
                var opts = this.options.fieldsOptions[id];
                
                return this._filterOptions(opts) !== false;
        },
        
        findFieldOptions: function(id) {
                var pId, sfs, i, n;
                for (pId in this.options.fieldsOptions) {
                        if (sfs = this.options.fieldsOptions[pId]['subfields']) {
                                for (i = 0, n = sfs.length; i < n; i++) {
                                        if (sfs[i]['id'] == id) return sfs[i];
                                }
                        }
                }
                return false;
        },
        
        parseFieldOptions: function(proxyField, opts) {
                var id = (document.id(proxyField).get('id') || document.id(proxyField).get('name')).replace(this.options.pre, '');
                
                if (!opts) opts = this.options.fieldsOptions[id];
                
                if (!opts) opts = this.findFieldOptions(id);
                
                if (!opts) opts = this.fieldsOptions[this.options.pre+id];
                
                if (!opts.hasOwnProperty('valid')) return false;
                
                if (opts['subfields']) {
                        var sfs = [], ind, sid, ivs = opts['ivs'];
                        for (var i = 0, n = opts['subfields'].length; i < n; i++) {
                                if (!this._filterOptions(opts['subfields'][i])) continue;
                                sfs.push(opts['subfields'][i]);
                                sid = this.createProxyField(proxyField);
                                ind = sfs.length-1;
                                sfs[ind].id = sid;
                                sfs[ind].position = sfs[ind].position == undefined ? i : sfs[ind].position;
                                sfs[ind].subfieldof = this.options.pre + sfs[ind].subfieldof;
                                if (!sfs[ind]['ivs']) {
                                        if (ivs) sfs[ind]['internalValueSeparator'] = ivs;
                                } else {
                                        sfs[ind]['internalValueSeparator'] = sfs[ind]['ivs'];
                                }
                                
                                this.setOpts(document.id(this.options.pre+sid), sfs[ind]);
                        }
                        opts['subfields'] = sfs;
                }
                
                var f = this._filterOptions(opts);
                
                if (!f) return false;
                
                opts = this.modize(opts);
                opts = this.initializeOptions(opts);
                opts = Object.clone(opts);
                
                this.setOpts(proxyField, opts);
                
                if (this.isMode('search')) this.delOpt(proxyField, 'list');
                
                return opts;
        },
        
        initializeOptions: function(opts) {
                if (opts.interval) {
                        if (opts.valid == 'integer' || opts.valid == 'numeric') {
                                if (opts.interval[0] == '-Infinity') {
                                        opts.interval[0] = -Infinity;
                                } else {
                                        opts.interval[0] = new Number(opts.interval[0])+0;
                                }

                                if (opts.interval[1] == 'Infinity') {
                                        opts.interval[1] = Infinity;
                                } else {
                                        opts.interval[1] = new Number(opts.interval[1])+0;
                                }
                        } else if (opts.valid == 'date' || opts.valid == 'datetime') {
                                if (opts.interval[0] == -1) opts.interval[0] = this.options.dateMin;
                                if (opts.interval[1] == -1) opts.interval[1] = this.options.dateMax;

                                opts.interval[0] = this.parseDateOperator(opts.interval[0]);
                                opts.interval[1] = this.parseDateOperator(opts.interval[1]);
                        } else if (opts.valid.test(/^alpha/)) {
                                if (opts.interval[0] == '-1') {
                                        opts.interval[0] = 0;
                                }

                                if (opts.interval[1] == '-1') {
                                        opts.interval[1] = this.options.maxFieldLength;
                                }
                        }
                } else if (opts.valid == 'date' || opts.valid == 'datetime') {
                        opts.interval = [this.options.dateMin, this.options.dateMax];

                        if (opts.low) {
                                opts.interval[0] = this.parseDateOperator(opts.low);
                                opts.interval[0] = Date.parse(opts.interval[0]);
                        }

                        if (opts.high) {
                                opts.interval[1] = this.parseDateOperator(opts.high);
                                opts.interval[1] = Date.parse(opts.interval[1]);
                        }
                } else if (opts.valid == 'integer' || opts.valid == 'real') {
                        opts.interval = [-1*Infinity, Infinity];
                } else if (opts.valid.test(/^alpha/)) {
                        opts.interval = [0, this.options.maxFieldLength];
                } else if (opts.valid.test(/^text/)) {
                        if (!opts['cols']) 
                                opts['cols'] = 60;
                        
                        if (!opts['rows']) {
                                opts['rows'] = 5;
                                
                                if (!opts['autogrow']) 
                                        opts['autogrow'] = true;
                        }
                        
                }
                
                if (opts.list) {
                        if (typeof opts.listmax == "undefined") opts.listmax = this.options.maxListItem;
                        
                        opts.listmax = parseInt(opts.listmax);
                }
                
                if (opts['searchui'] && this.isMode('search')) opts['ui'] = opts['searchui'];
                
                return opts;
        },
        
        modifyComplexOpts:function(proxyField, subfields) {
                var subfieldof = this.getOpt(proxyField, 'id');
                for (var i = 0, n = subfields.length; i < n; i++) subfields[i]['subfieldof'] = subfieldof;
                var opts = {'valid':'complex','type':'textfield','value':'[]','subfields':subfields};
                opts = this.copyOpts(proxyField, opts, ['id', 'name', 'section', 'list', 'listmax', 'type'], true);
                this.parseFieldOptions(proxyField, opts);
                return opts;
        },
        
        revertOpts: function(proxyField) { return this.parseFieldOptions(proxyField); },
        
        copyOpts: function(fromProxyField, toOpts, optNames, ignoreEmpty) {
                if (ignoreEmpty == undefined) ignoreEmpty = true;
                optNames = Array.from(optNames);
                var v, nm;
                for (var i = 0, n = optNames.length; i < n; i++) {
                        nm = optNames[i];
                        v = this.getOpt(fromProxyField, nm);
                        if (v || !ignoreEmpty) toOpts[nm] = v;
                }
                return toOpts;
        },
        
        parseDateOperator: function(value) {
                if (typeof value == 'string') {
                        value = this.parseDateNotations(value);
                        
                        var op = value.substr(0,1), delta;
                        
                        if (op == "-" || op == "+") {
                                // TODO: incorrect implementation, adapt to other standard for date notations
                                var currentYear = (new Date()).getFullYear();
                                delta = (op == "-" ? -1 : 1) * value.toInt();
                                value = currentYear + delta;

                                value = new Date(value);
                        } 
                }

                return value;
        },
        
        parseDateNotations: function(value) {
                if (typeof value == 'string') {
                        if (value == 'today' || value == 'now') {
                                value = new Date();
                        } else if (value == 'toyear') {
                                value = new Date(new Date().getFullYear(), 1, 1);
                        } else if (value == 'tomonth') {
                                var d = new Date();
                                value = new Date(d.getFullYear(), d.getMonth(), 1);
                        } else {
                                value = Date.parse(value);
                        }
                }
                
                return value.format(this.options.datetimeFormat);
        }
});
