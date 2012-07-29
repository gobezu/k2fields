//$Copyright$

var k2fieldseditor = new Class({
        Implements: [Options],
        
        options: {nameFldId: 'name', defFldId: 'k2fieldsDefinition', def: '', fieldSeparator:':::'},
        
        defFld: null,
        nameFld: null,
        isNew:true,
        specification: {},
        
        initialize: function(options) {
                this.setOptions(options);
                
                this.isNew = !this.existsParam('cid');
                
                window.addEvent('domready', function() {
                        new Element('div', {'id':'extraFieldsContainer'}).inject($$('form')[0], 'top');
                        this.createSpecification();
                        this.createUIWithSections();
                        document.id('type').set('value', 'textfield');
                        document.id('type').fireEvent('change', [document.id('type')]);
                }.bind(this));
                
                window.addEvent('load', function(){
                        this.nameFld = document.id(this.options.nameFldId);
                        this.nameFld.setStyle('display', 'none');
                        this.defFld = new Element('textarea', {
                                id:this.options.defFldId,
                                cols:50, 
                                rows:10,
                                value: this.options.def || this.nameFld.value,
                                events: {
                                        'change': function(e) {
                                                this.propagate(this._tgt(e));
                                        }.bind(this)
                                },
                                name:'definition'
                        }).injectAfter(this.nameFld);
                }.bind(this));
        },

        updateFieldDefinition:function() {
                var def = {}, skipped = {}, subfields = [], specification = Object.clone(this.specification), val, optName, i, vals, _vals;
                
                new Hash(specification).each (function(ps, id) {
                        optName = this.optName(ps);
                        
                        if (optName == 'values' && skipped['source'] == 'specify') {
                                ps = this.getProperties(optName, 4);
                        }                        
                        
                        id = k2fs.options.pre + id;
                        val = document.id(id).get('value');
                        
                        if (!val) return;
                        
                        if (ps.ui == 'checkbox' || ps.ui == 'select' && ps.multiple) {
                                val = val.split(k2fs.options.multiValueSeparator);
                        } else {
                                val = ps.list ? val.split(k2fs.options.listItemSeparator) : [val];
                        }
                        
                        vals = [];
                        
                        for (i = 0; i < val.length; i++) {
                                val[i] = val[i].split(k2fs.options.listConditionSeparator);
                                
                                if (val[i][0]) {
                                        if (ps.list && optName != 'subfields' || ps.valid == 'complex') {
                                                val[i][0] = val[i][0].split(k2fs.options.valueSeparator);
                                                _vals = this._filter(val[i][0]);
                                                
                                                if (_vals.length) vals.push(val[i][0]);
                                        } else {
                                                vals.push(val[i][0]);
                                        }
                                }
                        }
                        
                        if (vals.length == 0) return;
                        
                        switch (optName) {
                                case 'values':
                                        if (skipped['source'] != 'specify') {
                                                val = skipped['source']+':'+val[0][0];
                                        } else {
                                                val = [];
                                                var j;
                                                for (i = 0; i < vals.length; i++) {
                                                        for (j = 0; j < vals[i].length; j++)
                                                                if (!vals[i][j]) 
                                                                        vals[i][j] = undefined;
                                                        vals[i] = vals[i].clean();
                                                        if (vals[i].length) val.push(vals[i].join('=='));
                                                }
                                                val = val.join(ps.sep ? ps.sep : k2fs.options.valueSeparator);                                                
                                        }
                                        break;
                                case 'access':
                                        val = vals[0];
                                        if (val == k2fs.options.valueSeparator) val = '';
                                        break;
                                case 'conditions':
                                        val = vals.join(k2fs.options.valueSeparator);
                                        break;
                                case 'k2itemfilters':
                                        val = [];
                                        var _val;
                                        for (i = 0; i < vals.length; i++) {
                                                _val = vals[i][0]+'=='+vals[i][1]+(vals[i].length > 2 ? vals[i][2] : '');
                                                val.push(_val);
                                        }
                                        val = val.join(k2fs.options.valueSeparator);
                                        break;
                                case 'values:':
                                        val = 'values:'+val;
                                        optName = 'values';
                                        break;
                                case 'custom':
                                        val = [];
                                        for (i = 0; i < vals.length; i++) {
                                                if (i == 0) {
                                                        optName = vals[i][0];
                                                        val.push(vals[i][1]);
                                                } else {
                                                        val.push(vals[i][0]+'='+vals[i][1]);
                                                }
                                        }
                                        val = val.join(':::');
                                        break;
                                case 'levels':
                                        val = [];
                                        for (i = 0; i < vals.length; i++) val.push(vals[i][1]);  
                                        val = val.join(k2fs.options.valueSeparator);
                                        break;
                                default:
                                        if (ps.list && typeOf(vals[0]) == 'array') {
                                                val = [];
                                                var j;
                                                for (i = 0; i < vals.length; i++) {
                                                        for (j = 0; j < vals[i].length; j++)
                                                                if (!vals[i][j]) 
                                                                        vals[i][j] = undefined;
                                                        vals[i] = vals[i].clean();
                                                        if (vals[i].length) val.push(vals[i].join('=='));
                                                }
                                                val = val.join(ps.sep ? ps.sep : k2fs.options.valueSeparator);
                                        } else if (ps.list || ps.ui == 'checkbox' || ps.multiple == 'multiple' || ps.multiple == '1') {
                                                val = vals.join(ps.sep ? ps.sep : k2fs.options.valueSeparator);
                                        } else {
                                                val = vals[0];
                                        }
                                        break;
                        }
                        
                        if (!val) return;
                        
                        if (ps.skip) {
                                skipped[optName] = val;
                        } else {
                                def[optName] = val;
                        }
                }.bind(this));
                
                var _def = '';
                
                new Hash(def).each (function(value, name) {
                        if (!value) return;
                        
                        _def += (_def != '' ? ':::' : '') + name + '=' + value;
                });
                
                var sec = def['section'] ? def['section'] : this.options['emptysectionname'], search;
                
                sec = sec ? ' in '+sec : '';
                search = def['search'] ? def['search'] : 'NOSEARCH';
                
                if (def['search']) search = search == '1' ? 'SEARCH' : 'SEARCH:'+search;
                
                document.id('name').set('value', skipped['name']+sec+' / TYPE:'+def['valid']+' / '+search+' / '+(def['list'] ? 'LIST:'+def['list'] : 'NOLIST'));
                document.id(this.options.defFldId).set('value', 'k2f---' + subfields + _def + '---' + skipped['name']);
        },
        
        createUIWithSections: function() {
                if (document.id('extraFields')) document.id('extraFields').dispose();
                
                new Element('div', {'class':'clr'}).inject(document.id('extraFieldsContainer'));
                
                var uis = {}, specification = Object.clone(this.specification), _id, vals = this.parseValues(), val, optName, css, sectionName, ui, sectionID, uip;
                
                new Hash(specification).each(function(ps, id) {
                        optName = this.optName(ps);
                        sectionName = this.sectionName(ps);
                        sectionID = this.sectionId(ps, 'section_');
                        ui = uis[sectionID];
                        if (!ui) {
                                uis[sectionID] = new Element('ul', {'id':sectionID, 'class':'admintable extraFields', 'section':sectionName}).inject(document.id('extraFieldsContainer'));
                                ui = uis[sectionID];
                        }
                        css = 'prop'+id + ' ' +this.propCSS(id, optName);
                        uip = new Element('li', {'class':css}).inject(ui);
                        uip = new Element('span').inject(uip);
                        new Element('label', {'class':'key', 'html':ps.name}).inject(uip);
                        new Element('span').inject(uip);
                        _id = k2fs.options.pre + id;
                        val = vals[optName];
                        new Element('input', {'type':'text', 'id':_id, 'name':_id, 'value':val}).inject(uip.getElement('span'));
                        if (ps.valid == 'complex' && ps.subfields) {
                                for (var i = 0; i < ps.subfields.length; i++) 
                                        specification[id].subfields[i]['subfieldof'] = id;
                        }
                }.bind(this));
                
                k2fs.options.fieldsOptions = specification;
                k2fs.options.isNew = this.isNew;
                k2fs.utility = new JPUtility({base:k2fs.options.base,k2fbase:k2fs.options.k2fbase});
                k2fs.wireForm($$('form')[0]);
                k2fs.createFields();
                k2fs.containerEl(1);
                
                document.id('type').getParent('tr').setStyle('display', 'none');
                document.id('name').getParent('tr').setStyle('display', 'none');
                document.id('exFieldsTypesDiv').getParent('tr').setStyle('display', 'none');
        },
        
        createUI: function() {
                if (document.id('extraFields')) document.id('extraFields').dispose();
                
                var ui = new Element('ul', {'id':'extraFields', 'class':'admintable extraFields'}).inject(document.id('extraFieldsContainer')), uip;
                
                new Element('div', {'class':'clr'}).inject(document.id('extraFieldsContainer'));
                
                var specification = Object.clone(this.specification), _id;
                
                var vals = this.parseValues(), val, optName, css;
                
                new Hash(specification).each(function(ps, id) {
                        optName = this.optName(ps);
                        css = 'prop'+id + ' ' +this.propCSS(id, optName);
                        uip = new Element('li', {'class':css}).inject(ui);
                        uip = new Element('span').inject(uip);
                        new Element('label', {'class':'key', 'html':ps.name}).inject(uip);
                        new Element('span').inject(uip);
                        _id = k2fs.options.pre + id;
                        val = vals[optName];
                        new Element('input', {'type':'text', 'id':_id, 'name':_id, 'value':val}).inject(uip.getElement('span'));
                        if (ps.valid == 'complex' && ps.subfields) {
                                for (var i = 0; i < ps.subfields.length; i++) 
                                        specification[id].subfields[i]['subfieldof'] = id;
                        }
                }.bind(this));
                
                k2fs.options.fieldsOptions = specification;
                k2fs.options.isNew = this.isNew;
                k2fs.utility = new JPUtility({base:k2fs.options.base,k2fbase:k2fs.options.k2fbase});
                k2fs.wireForm($document.id('form')[0]);
                k2fs.createFields();
                
                document.id('type').getParent('tr').setStyle('display', 'none');
                document.id('name').getParent('tr').setStyle('display', 'none');
                document.id('exFieldsTypesDiv').getParent('tr').setStyle('display', 'none');
        },
        
        parseValues:function(def) {
                def = def || this.options.def || document.id('name').get('value');
                
                def = def.split("\n");
                def.each(function(s, i){ def[i] = s.trim(); }.bind(this));
                def = def.join('');
                
                if (!def) return {};
                
                def = def.replace(/^k2f---/, '');
                
                var _defs = {'name':def.substring(def.lastIndexOf('---')+3)};
                
                if (_defs['name']) {
                        def = def.substring(0, def.lastIndexOf('---'));
                        
                        if (!def) return {};
                } else {
                        return {};
                }
                
                var optName, val, i, n, j, m, v, re;
                
                def = def.split(':::');
                
                var props = (new Hash(this.specification).map(function(p) { return p.optName; })).getValues(), custom = [], t, _def;
                
                for (i = 0, n = def.length; i < n; i++) {
                        val = def[i];
                        optName = val.substr(0, val.indexOf('='));
                        val = val.replace(new RegExp('^'+optName+'='), '');
                        
                        if (!props.contains(optName)) {
                                custom.push(optName+k2fs.options.valueSeparator+val);
                                continue;
                        } else if (optName == 'values') {
                                var _val;
                                if (_val = val.match(/^(file|function|php|url|sql)\:/i)) {
                                        _defs['source'] = _val[1];
                                        val = val.replace(_val[1]+':', '');
                                } else {
                                        _defs['source'] = 'specify';
                                }
                        }
                        
                        if (optName == 'values' && _defs['source'] == 'specify') {
                                _def = this.getProperties(optName, 4);
                        } else {
                                _def = this.getProperties(optName);
                        }
                        
                        if (_def.list || _def.ui == 'checkbox' || _def.ui == 'select' && _def.multiple) {
                                val = val.split(k2fs.options.valueSeparator);
                                
                                if (optName == 'levels') {
                                        var levels = [], _levels = this.options.options['listslevels'];
                                        for (j = 0, m = _levels.length; j < m; j++) {
                                                if (_levels[j]['list'] && _levels[j]['list'] == _defs['source']) {
                                                        levels.push(_levels[j]);
                                                }
                                        }
                                }
                                
                                for (j = 0, m = val.length; j < m; j++) {
                                        val[j] = val[j].split('==');

                                        if (optName == 'k2itemfilters') {
                                                t = val[j][1].substr(-1, 1);
                                                if (t == '*') {
                                                        val[j].push(t);
                                                        val[j][1] = val[j][1].replace(t, '');
                                                }
                                        } else if (optName == 'levels') {
                                                val[j] = [levels[j]['value'], val[j][0]];
                                        }

                                        val[j] = val[j].join(k2fs.options.valueSeparator);
                                }
                                
                                val = val.join(
                                        _def.list ? 
                                                k2fs.options.listItemSeparator : 
                                                k2fs.options.multiValueSeparator
                                );
                        } else if (optName == 'search') {
                                if (val != '1' && val != 'true') val = '1'+k2fs.options.multiValueSeparator+val;;
                        }
                        
                        _defs[optName] = val;
                }
                
                if (custom.length > 0) _defs['custom'] = custom.join(k2fs.options.listItemSeparator);
                
                return _defs;
        },
        
        _revSpec:null,
        
        getProperties:function(optName, ind) {
                if (this._revSpec == null) {
                        var _optName = '';
                        this._revSpec = {};
                        (new Hash(this.specification).each(function(ps, id) { 
                                _optName = this.optName(ps);
                                if (this._revSpec[_optName]) {
                                        if (typeOf(this._revSpec[_optName]) != 'array') {
                                                this._revSpec[_optName] = [this._revSpec[_optName]];
                                        }
                                        this._revSpec[_optName].push(ps);
                                } else {
                                        this._revSpec[_optName] = ps;
                                }
                        }.bind(this)));
                }
                
                if (!this._revSpec[optName]) return {};
                
                if (typeOf(this._revSpec[optName]) == 'array') {
                        return this._revSpec[optName][ind !== undefined ? ind : 0];
                }
                
                return this._revSpec[optName]; 
        },
        
                _tgt:function(e) {
                return e.target ? e.target : e.srcElement;
        },
        
        propagate: function(ifK2f) {
                var val = ifK2f.value.split(this.options.fieldSeparator);
                if (val.length < 2 || val[0] != 'k2f') {
                        this.nameFld.set('value', val.join(this.options.fieldSeparator));
                        return;
                }
                this.nameFld.set('value', val[0]+this.options.fieldSeparator+val[val.length-1]);
                var os = document.id('type').options;
                for (var i = 0; i < os.length; i++) if (os[i].value == 'textfield') os[i].selected = true;
                document.id('type').fireEvent('change', [document.id('type')]);
        },
        
        params:function(from) { return (from || document.location.href).fromQueryString(); },
        param:function(name, from) {
                var p = this.params(from || document.location.href);
                return p[name];
        },
        existsParam:function(name, from) {
                var p = this.param(name, from);
                return p != undefined;
        },
        
        propCSS: function(propId, optName) {
                propId = propId.toInt();
                
                var css = 'prop' + optName + ' ' + (propId > 1000 ? 'proptype' : 'propgeneric');
                
                return css;
        },
        
        sectionName:function(ps) {
                return ps['section'] ? ps['section'] : 'Additional';
        },
        
        sectionId:function(ps, pre) {
                var s = (pre||'')+this.sectionName(ps);
                s = s.replace(/[^0-9a-z]/ig, '');
                return s;
        },
        
        optName:function(ps) {
                return ps['optName'] ? ps['optName'] : ps['name'].toLowerCase().replace(/[^a-z0-9]/g, '');
        },
        
        _filter: function(arr) { return arr.filter(function(v){return v!='';});},
        
        createSpecification: function() {
                this.specification = {
                        '1':{
                                'name':'Field name',
                                'optName':'name',
                                'valid':'text',
                                'required':'1',
                                'skip':true,
                                'section':'Basic'
                        },
                        '2':{
                                'name':'Type',
                                'optName':'valid',
                                'valid':'select',
                                'values':[
                                        {'value':'k2item','text':'k2item'},
                                        {'value':'list','text':'list'},
                                        {'value':'media','text':'media'},
                                        {'value':'map','text':'map'},
                                        {'value':'datetime','text':'datetime'},
                                        {'value':'date','text':'date'},
                                        {'value':'title','text':'title'},
                                        {'value':'rate','text':'rate'},
                                        {'value':'time','text':'time'},
                                        {'value':'duration','text':'duration'},
                                        {'value':'complex','text':'complex'},
                                        {'value':'alias','text':'alias'},
                                        {'value':'email','text':'email'},
                                        {'value':'numeric','text':'numeric'}, // from here and below check in basic module for parameters to be supported
                                        {'value':'text','text':'text'},
                                        {'value':'alpha','text':'alpha'},
                                        {'value':'alphanum','text':'alphanum'},
                                        {'value':'integer','text':'integer'},
                                        {'value':'url','text':'url'},
                                        {'value':'range','text':'range of integer values'},
                                        {'value':'days','text':'week days'},
                                        {'value':'yesno','text':'yesno (binary options)'},
                                        {'value':'verifybox','text':'verify (single option)'},
                                        {'value':'creditcards','text':'creditcards'},
                                        {'value':'phone','text':'phone (TBI: currently limited support)'}
                                ],
                                'deps': {
                                        'k2item':['id:11', 'id:1101', 'id:1102', 'id:1103', 'id:1104', 'id:1105', 'id:1106', 'id:1107'],
                                        'list':['id:11', 'id:1001', 'id:1002', 'id:1003', 'id:1004', 'id:1005'],
                                        'media':['id:34', 'id:35', 'id:1151', 'id:1152', 'id:1153', 'id:1154', 'id:1155', 'id:1156', 'id:1157', 'id:1158', 'id:1159', 'id:1160', 'id:1161', 'id:1162', 'id:1163', 'id:1164', 'id:1165', 'id:1166', 'id:1167', 'id:1168', 'id:1169', 'id:1170', 'id:1171', 'id:1172', 'id:1173', 'id:1174', 'id:1175', 'id:1176', 'id:1177'],
                                        'datetime':['id:1201', 'id:1204', 'id:1205', 'id:1206', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214'],
                                        'date':['id:1201', 'id:1203', 'id:1205', 'id:1206', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214'],
                                        'time':['id:1201', 'id:1202', 'id:1205', 'id:1206', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214'],
                                        'duration':['id:1201', 'id:1202', 'id:1205', 'id:1206', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214'],
                                        'email':['id:1251', 'id:1252', 'id:1253', 'id:1254', 'id:1255', 'id:1256', 'id:1257', 'id:1258'],
                                        'title':['id:1301', 'id:1302', 'id:1303'],
                                        'rate':['id:1301'],
                                        'complex':['id:1051', 'id:1052'],
                                        'map':['id:11', 'id:1351', 'id:1352', 'id:1353', 'id:1354', 'id:1355', 'id:1356', 'id:1357', 'id:1358', 'id:1359', 'id:1360', 'id:1361', 'id:1362', 'id:1363', 'id:1364', 'id:1365', 'id:1366', 'id:1367', 'id:1368', 'id:1369', 'id:1370', 'id:1371', 'id:1372', 'id:1373', 'id:1374', 'id:1375', 'id:1376', 'id:1377', 'id:1378', 'id:1379', 'id:1380', 'id:1381', 'id:1382', 'id:1383', 'id:1384', 'id:1385', 'id:1386', 'id:1387', 'id:1388', 'id:1389', 'id:1390', 'id:1391', 'id:1392', 'id:1393', 'id:1394', 'id:1395'],
                                        'alias':['id:1451', 'id:1452'],
                                        'range':['id:1501', 'id:1502', 'id:1503', 'id:1504', 'id:1505']
                                },
                                'required':'1',
                                'savevalues':'validtypes',
                                'sorted':true,
                                'section':'Basic'
                        },
                        '3':{
                                'name':'UI',
                                'optName':'ui',
                                'valid':'select',
                                'values':[
                                        {'value':'radio'},
                                        {'value':'checkbox'},
                                        {'value':'text'},
                                        {'value':'textarea'},
                                        {'value':'editor'},
                                        {'value':'select'}
                                ],
                                'deps':{
                                        'select':['id:7', 'id:12', 'id:14', 'id:15', 'id:28'],
                                        'radio':['id:7', 'id:14', 'id:15', 'id:39'],
                                        'checkbox':['id:7', 'id:14', 'id:15', 'id:28']
                                },
                                'savevalues':'uis',
                                'section':'Basic'
                        },
                        '4':{
                                'name':'Repeatable',
                                'optName':'list',
                                'valid':'radio',
                                'values':[
                                        {'value':'normal', 'text':'Normal'},
                                        {'value':'conditional', 'text':'Conditional'}
                                ],
                                'deps':{
                                        'normal':['id:5'],
                                        'conditional':['id:5','id:6','id:26']
                                },
                                'clearopt':'button',
                                'sorted':true,
                                'section':'Basic'
                        },
                        '5':{
                                'name':'Maximum repetitions',
                                'optName':'listmax',
                                'valid':'integer',
                                'min':1,
                                'max':300,
                                'section':'Basic',
                                'default':10
                        },
                        '6':{
                                'name':'Conditions',
                                'optName':'conditions',
                                'valid':'text',
                                'list':'normal',
                                'section':'Basic'
                        },
                        '10':{
                                'name':'Default',
                                'optName':'default',
                                'valid':'text',
                                'ph':'Provide suitable default value',
                                'size':'60',
                                'section':'Values'
                        },
                        '11':{
                                'name':'Autocompletion<br />(user provided string is searched in)',
                                'optName':'autocomplete',
                                'valid':'radio',
                                'values':[
                                        {'value':'','text':'None'},
                                        {'value':'m','text':'Anywhere In string'},
                                        {'value':'s','text':'Start of string'},
                                        {'value':'e','text':'End of string'},
                                ],
                                'section':'Type specific',
                                'sorted':true
                        },
                        '12':{
                                'name':'Multiple select',
                                'valid':'verifybox',
                                'optName':'multiple',
                                'deps':{
                                        '1':['id:13']
                                }
                        },
                        '13':{
                                'name':'Multiple size',
                                'optName':'size',
                                'valid':'integer'
                        },
                        '14':{
                                'name':'Save value as',
                                'optName':'savevalues',
                                'valid':'text',
                                'tip':'Fields following within this group can reuse the values by referring to the name you provide here',
                                'section':'Values'
                        },
                        '15':{
                                'name':'Use saved value',
                                'optName':'values:',
                                'valid':'text',
                                'tip':'Reuse values of earlier created fields by referring to given name',
                                'section':'Values'
                        },
                        '16':{
                                'name':'Section',
                                'optName':'section',
                                'valid':'text',
                                'section':'Layout'
                        },
                        '19':{
                                'name':'Section (list)',
                                'optName':'listsection',
                                'valid':'text',
                                'section':'Layout'
                        },
                        '20': {
                                'name':'Size',
                                'optName':'size',
                                'valid':'integer',
                                'section':'Validation'
                        },
                        '17':{
                                'name':'Folded',
                                'optName':'folded',
                                'valid':'verifybox',
                                'tip':'In case of using tabular layout',
                                'section':'Layout'
                        },
                        '18':{
                                'name':'Tabular column placement',
                                'optName':'col',
                                'valid':'range',
                                'ui':'radio',
                                'shift':1,
                                'low':0,
                                'high':7,
                                'tip':'In case of using tabular layout',
                                'section':'Layout'
                        },
                        '21':{
                                'name':'Format',
                                'optName':'format',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'tip':'Placeholders %value%, %txt% and %img% are available.',
                                'section':'Layout'
                        },
                        '22':{
                                'name':'Schema property',
                                'optName':'schemaprop',
                                'valid':'text',
                                'tip':'Based on selected schema type in your title field. If this is a field of type title please leave this empty as the property of it is given.',
                                'section':'SEO'
                        },
                        '23':{
                                'name':'Placeholder text',
                                'optName':'ph',
                                'valid':'textarea',
                                'section':'Tooltips...'
                        },
                        '24':{
                                'name':'Tooltip text',
                                'optName':'tip',
                                'valid':'textarea',
                                'section':'Tooltips...'
                        },
                        '25':{
                                'name':'Tooltip text (edit)',
                                'optName':'edittip',
                                'valid':'textarea',
                                'section':'Tooltips...'
                        },
                        '26':{
                                'name':'Placeholder text for condition',
                                'optName':'conditionlabel',
                                'valid':'textarea',
                                'section':'Tooltips...'
                        },
                        '27':{
                                'name':'Dependencies',
                                'optName':'deps',
                                'valid':'complex',
                                'list':'normal',
                                'subfields':[
                                        {'name':'Value','valid':'text'},
                                        {'name':'Field','valid':'text','ui':'select','values':this.options.options['fields']},
                                        {'name':'Negate','valid':'verifybox','tip':'If the above given value is not provided then field is toggled in.'}
                                ],
                                'tip':'Fields that depend on (toggled based on) the values of this field. For each value provided the corresponding selected field will be shown. Note that upon toggle assigned values of dependent fields are reset.',
                                'section':'Additional'
                        },
                        '28':{
                                'name':'Order of values',
                                'optName':'valuesorder',
                                'valid':'text',
                                'ui':'radio',
                                'values':[
                                        {'value':'value'},
                                        {'value':'text'}
                                ],
                                'section':'Values'
                        },
                        '29':{
                                'name':'Content tooltip text',
                                'optName':'contenttip',
                                'valid':'textarea',
                                'section':'Tooltips...'
                        },
                        '30':{
                                'name':'Default sort direction',
                                'optName':'sortby',
                                'valid':'text',
                                'ui':'radio',
                                'values':[{'value':'asc'}, {'value':'desc'}],
                                'section':'Search'
                        },
                        '31':{
                                'name':'Append to title',
                                'optName':'appendtotitle',
                                'valid':'verifybox',
                                'tip':'Value of this field will be appended to title with the glue character defined in k2fields plugin setting between the title and the value.',
                                'section':'SEO'
                        },
                        '32':{
                                'name':'Pre',
                                'optName':'pre',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'tip':'Fixed text to prepend value with',
                                'section':'Layout'
                        },
                        '33':{
                                'name':'Post',
                                'optName':'post',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'tip':'Fixed text to trail value with',
                                'section':'Layout'
                        },
                        '34':{
                                'name':'Layout',
                                'optName':'layout',
                                'valid':'text',
                                'ui':'text',
                                'section':'Layout'
                        },
                        '35':{
                                'name':'Layout (list)',
                                'optName':'listlayout',
                                'valid':'text',
                                'ui':'text',
                                'section':'Layout'
                        },
                        '36':{
                                'name':'Append to metatag keywords',
                                'optName':'appendtokeywords',
                                'valid':'verifybox',
                                'tip':'Value of this field will be appended to metatag keywords which is used for known SEO purpose as well as search capability among others by the k2fields content module.',
                                'section':'SEO'
                        },
                        '37':{
                                'name':'Append to metatag description',
                                'optName':'appendtodescription',
                                'valid':'verifybox',
                                'tip':'Value of this field will be appended to the metatag description',
                                'section':'SEO'
                        },
                        '38':{
                                'name':'Tab index',
                                'optName':'tabindex',
                                'valid':'integer',
                                'section':'Basic'
                        },
                        '39':{
                                'name':'Clear option',
                                'optName':'clearopt',
                                'valid':'text',
                                'values':['firstempty', 'lastempty', 'button'],
                                'ui':'select',
                                'section':'Basic'
                        },
                        '51':{
                                'name':'Search',
                                'optName':'search',
                                'valid':'verifybox',
                                'deps':{
                                        '1':['id:52','id:53','id:54','id:55', 'id:56', 'id:57', 'id:58', 'id:59', 'id:60', 'id:61']
                                },
                                'section':'Search'
                        },
                        '52':{
                                'name':'Search operator',
                                'optName':'search',
                                'valid':'radio',
                                'values':[
                                        {'value':'eq','text':'='},
                                        {'value':'ge','text':'>='},
                                        {'value':'gt','text':'>'},
                                        {'value':'le','text':'<='},
                                        {'value':'lt','text':'<'},
                                        {'value':'exact','text':'Exact (text index)'},
                                        {'value':'any','text':'Any (text index)'},
                                        {'value':'ex','text':'Existence check (media)'},
                                        {'value':'interval','text':'Interval'},
                                        {'value':'nearby','text':'Nearby (TBI:map)'}
                                ],
                                'sorter':true,
                                'section':'Search'
                        },
                        '53':{
                                'name':'Search default',
                                'optName':'searchdefault',
                                'valid':'text',
                                'section':'Search'
                        },
                        '54':{
                                'name':'Tolerance',
                                'optName':'tolerance',
                                'valid':'integer',
                                'section':'Search'
                        },
                        '55':{
                                'name':'Evening starts at the hour',
                                'optName':'eveningstartsat',
                                'valid':'integer',
                                'max':24,
                                'section':'Search'
                        },
                        '56':{
                                'name':'Tooltip text (search)',
                                'optName':'searchtip',
                                'valid':'text',
                                'ui':'textarea',
                                'section':'Search'
                        },
                        '57':{
                                'name':'Search UI',
                                'optName':'searchui',
                                'valid':'text',
                                'ui':'select',
                                'values':'values:uis',
                                'section':'Search'
                        },
                        '58':{
                                'name':'Future only (applicable to date/time searches)',
                                'optName':'futureonly',
                                'valid':'verifybox',
                                'section':'Search'
                        },
                        '59':{
                                'name':'Now tolerance (lower)',
                                'optName':'nowtolerancelower',
                                'valid':'integer',
                                'tip':'In seconds decreased from current time',
                                'section':'Search'
                        },
                        '60':{
                                'name':'Now tolerance (upper)',
                                'optName':'nowtoleranceupper',
                                'valid':'integer',
                                'tip':'In seconds added to current time',
                                'section':'Search'
                        },
                        '61':{
                                'name':'Exclude from search values',
                                'optName':'excludevaluessearch',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'%% separated list of values to be excluded',
                                'section':'Search'
                        },
                        '101':{
                                'name':'Required',
                                'optName':'required',
                                'valid':'verifybox',
                                'section':'Validation'
                        },
                        '102':{
                                'name':'Regular expression',
                                'optName':'regexp',
                                'valid':'text',
                                'section':'Validation'
                        },
                        '103':{
                                'name':'Minimum value',
                                'optName':'min',
                                'valid':'text',
                                'section':'Validation'
                        },
                        '104':{
                                'name':'Maximum value',
                                'optName':'max',
                                'valid':'text',
                                'section':'Validation'
                        },
                        '105':{
                                'name':'Minimum length',
                                'optName':'minlen',
                                'valid':'integer',
                                'section':'Validation'
                        },
                        '106':{
                                'name':'Maximum length',
                                'optName':'maxlen',
                                'valid':'integer',
                                'section':'Validation'
                        },
                        '151':{
                                'name':'Available in views',
                                'optName':'view',
                                'valid':'text',
                                'ui':'checkbox',
                                'values':['item', 'itemlist', 'module', 'map'],
                                'section':'Layout',
                                'default':['item']
                                // TODO: consistency in value separators
                        },
                        // TODO: fetch these groups from php or provided to editor when initiated
                        '152':{
                                'name':'Access based on ACL view groups',
                                'optName':'access',
                                'valid':'complex',
                                'subfields':[
                                        {'name':'Read', 'valid':'select', 'values':this.options.options['aclviewgroups']},
                                        {'name':'Edit', 'valid':'select', 'values':this.options.options['aclviewgroups']}
                                ],
                                'section':'Basic'
                        },
                        '201':{
                                'name':'Custom properties',
                                'optName':'custom',
                                'valid':'complex',
                                'list':'normal',
                                'subfields':[
                                        {'name':'Name', 'valid':'text', 'optName':'name'},
                                        {'name':'Value', 'valid':'text', 'optName':'value'},
                                ]
                        },
                        // Type specific properties
                        // Type::List
                        '1001':{
                                'name':'List source',
                                'optName':'source',
                                'valid':'integer',
                                'ui':'select',
                                'values':this.options.options['lists'],
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1002':{
                                'name':'Levels',
                                'optName':'levels',
                                'valid':'complex',
                                'list':'normal',
                                'tip':'Name of the various levels within the inherently hierarchical values',
                                'subfields':[
                                        {'name':'Indicator', 'optName':'indicator', 'valid':'text', 'ui':'select', 'values':this.options.options['listslevels']},
                                        {'name':'Name', 'optName':'level', 'valid':'text'}
                                ],
                                'section':'Type specific'
                        },
                        '1003':{
                                'name':'List format',
                                'optName':'listformat',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {value:'root',text:'root'},
                                        {value:'parent',text:'parent (of leaf)'},
                                        {value:'leaf',text:'leaf'},
                                        {value:'parent,leaft',text:'parent and leaf'}
                                ],
                                'tip':'View formats in item and itemlist modes. If nothing is provided it will be displayed with all parent elements. Available values are leaf, parent, root.',
                                'section':'Type specific',
                                'savevalues':'listformats'
                        },
                        '1004':{
                                'name':'List format (item view)',
                                'optName':'itemlistformat',
                                'valid':'text',
                                'ui':'select',
                                'tip':'View formats in item modes. If nothing is provided it will be displayed with all parent elements. Available values are leaf, parent, root.',
                                'section':'Type specific',
                                'values':'values:listformats'
                        },
                        '1005':{
                                'name':'Max list level',
                                'optName':'maxlevel',
                                'valid':'range',
                                'tip':'Maximum list depth',
                                'section':'Type specific',
                                'low':1,
                                'high':10,
                                'sorted':true
                        },
                        // Type::Complex
                        '1051':{
                                name:'Subfields',
                                optName:'subfields',
                                valid:'int',
                                list:'normal',
                                ui:'select',
                                values:this.options.options['fields'],
                                sorted:true,
                                'section':'Type specific'
                        },
                        '1052':{
                                name:'Override properties',
                                optName:'overrideSubfieldsProps',
                                valid:'int',
                                ui:'checkbox',
                                values:['view', 'folded'],
                                sorted:true,
                                'section':'Type specific'
                        },
                        // Type::k2item
                        '1101':{
                                'name':'Categories',
                                'optName':'categories',
                                'valid':'select',
                                'values':this.options.options['categories'],
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1102':{
                                // TODO: requires custom mapping
                                'name':'Field filters',
                                'optName':'k2itemfilters',
                                'valid':'complex',
                                'list':'normal',
                                'subfields':[
                                        {'name':'Field id', 'optName':'fieldid', 'valid':'integer', 'ui':'select', 'values':this.options.options['fields']},
                                        {'name':'Field value(s)', 'optName':'values', 'valid':'text', 'autocomplete':'m', 'autofield':function(){
                                                        var id = k2fs.getProxyFieldId(this.get('id'));
                                                        this.retrieve('_ac_').propagate = {'to':id, 'attr':'ovalue', 'event':'change'};
                                                        return {
                                                                'id':this.getParent('.k2fcontainer').getElement('select').get('value'),
                                                                'search':1
                                                        };
                                                }
                                        },
                                        {'name':'Hierarchy', 'optName':'hierarchy', 'ui':'checkbox', 'values':[{'value':'*','text':'Yes'}]}
                                ], 
                                'tip':'Comma separated values for each field',
                                'section':'Type specific'
                        },
                        '1103':{
                                'name':'Show k2items as',
                                'optName':'as',
                                'valid':'select',
                                'values':[
                                        {'value':'view', 'text':'view - item view embedded'},
                                        {'value':'jplist', 'text':'jplist - search link with this item set as criteria searching in categories provided in categories'},
                                        {'value':'jpajaxlist', 'text':'jpajaxlist - same as above but where result is opened within the hosting item through an ajax call'},
                                        {'value':'embedraw', 'text':'embedraw - embed item with only selected fields below included'},
                                        {'value':'embed', 'text':'embed - same as above but where guest item is shown when clicked upon its title in an accordion effect'},
                                        {'value':'title', 'text':'title - item title only'},
                                        {'value':'link', 'text':'link - link to item'}
                                ],
                                sorted:true,
                                'section':'Type specific'
                        },
                        '1104':{
                                'name':'Include fields',
                                'optName':'includefields',
                                'valid':'integer',
                                'ui':'select',
                                'values':this.options.options['fields'],
                                'list':'normal',
                                'section':'Type specific'
                                // TODO: make sure that the separator is correct in currently existing fields
                        },
                        '1105':{
                                'name':'Fold fields',
                                'optName':'foldfields',
                                'valid':'integer',
                                'ui':'select',
                                'values':this.options.options['fields'],
                                'list':'normal',
                                'tip':'In tabular itemlist view fields to be folded among the above included ones',
                                'section':'Type specific'
                                // TODO: make sure that the separator is correct in currently existing fields
                        },
                        '1106':{
                                'name':'Reverse field',
                                'optName':'reverse',
                                'valid':'integer',
                                'ui':'select',
                                'values':this.options.options['fields'],
                                'tip':'field of host field defined in guest item to connect back to the hosting k2item',
                                'section':'Type specific'
                        },
                        '1107':{
                                'name':'Reverse field name',
                                'optName':'reverse_name',
                                'valid':'text',
                                'tip':'defined in host field as a label of host field when embedded back in guest item',
                                'section':'Type specific'
                        },
                        // Type::Media
                        '1151':{
                                'name':'Media types',
                                'optName':'mediatypes',
                                'valid':'text',
                                'ui':'checkbox',
                                'values':[
                                        {'value':'pic', 'text':'Picture'},
                                        {'value':'video', 'text':'Video'}
                                ],
                                'deps':{
                                        'pic':['id:1155', 'id:1156'],
                                        'video':['id:1157', 'id:1158']
                                        
                                },
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1152':{
                                'name':'Media source',
                                'optName':'mediasources',
                                'valid':'text',
                                'ui':'checkbox',
                                'values':[
                                        {'value':'upload', 'text':'File upload'},
                                        {'value':'provider', 'text':'Service provider'},
                                        {'value':'embed', 'text':'Embed (TBI)'},
                                        {'value':'remote', 'text':'Remote file (TBI)'}
                                ],
                                'deps':{
                                        'provider':['id:1157', 'id:1158']
                                        
                                },
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1153':{
                                'name':'Mode',
                                'optName':'mode',
                                'valid':'text',
                                'ui':'checkbox',
                                'tip':'image to represent the gallery in itemlist',
                                'values':[
                                        {'value':'single', 'text':'Single'}
                                ],
                                'deps':{
                                        'single':['id:1154']
                                },
                                'section':'Type specific'
                        },
                        '1154':{
                                'name':'Single mode',
                                'optName':'singlemode',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'random', 'text':'Random'},
                                        {'value':'first', 'text':'First'}
                                ],
                                'section':'Type specific'
                        },
                        '1155':{
                                'name':'Picture plugin',
                                'optName':'picplg',
                                'valid':'text',
                                'ui':'select',
                                'values':this.options.options['mediaplugins']['pic'],
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1156':{
                                'name':'Picture plugin (itemlist)',
                                'optName':'itemlistpicplg',
                                'valid':'text',
                                'ui':'select',
                                'values':this.options.options['mediaplugins']['pic'],
                                'section':'Type specific'
                        },
                        '1157':{
                                'name':'Provider plugin',
                                'optName':'providerplg',
                                'valid':'text',
                                'ui':'select',
                                'values':this.options.options['mediaplugins']['provider'],
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1158':{
                                'name':'Provider plugin (itemlist)',
                                'optName':'itemlistproviderplg',
                                'valid':'text',
                                'ui':'select',
                                'values':this.options.options['mediaplugins']['provider'],
                                'section':'Type specific'
                        },
                        '1159':{
                                'name':'Main media',
                                'optName':'mainmedia',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'', 'text':'None'},
                                        {'value':'random', 'text':'Random'},
                                        {'value':'1', 'text':'1'},
                                        {'value':'2', 'text':'2'},
                                        {'value':'3', 'text':'3'},
                                        {'value':'4', 'text':'4'},
                                        {'value':'5', 'text':'5'},
                                        {'value':'6', 'text':'6'},
                                        {'value':'7', 'text':'7'},
                                        {'value':'8', 'text':'8'},
                                        {'value':'9', 'text':'9'},
                                        {'value':'10', 'text':'10'}
                                ],
                                'section':'Type specific',
                                'tip':'If you want to dictate image to be shown in modules and itemlist view...'
                        },
                        '1160':{
                                name:'Watermark fields',
                                optName:'watermark_fields',
                                valid:'int',
                                list:'normal',
                                ui:'select',
                                values:this.options.options['fields'],
                                sorted:true,
                                section:'Type specific',
                                tip:'Provide fields of which values to be used as watermark text.'
                        },                       
                        '1161':{
                                'name':'Watermark left position (horizontal)',
                                'optName':'watermark_field_left',
                                'valid':'text',
                                'section':'Type specific',
                                'tip':'Pixel value, %, center, right, left. If you want to apply the same watermark on different locations please provide a comma separated list of positions. Extensive explanation about how to position is provided in http://wideimage.sourceforge.net/documentation/smart-coordinates/'
                        },
                        '1162':{
                                'name':'Watermark top position (vertical)',
                                'optName':'watermark_field_top',
                                'valid':'text',
                                'section':'Type specific',
                                'tip':'Pixel value, %, center, top, bottom. If you want to apply the same watermark on different locations please provide a comma separated list of positions. Extensive explanation about how to position is provided in http://wideimage.sourceforge.net/documentation/smart-coordinates/'
                        },
                        '1163':{
                                'name':'Watermark image',
                                'optName':'watermark',
                                'valid':'text',
                                'size':100,
                                'section':'Type specific',
                                'tip':'Provide file location relative to site root to image to be used as watermark'
                        },                       
                        '1164':{
                                'name':'Watermark left position (horizontal)',
                                'optName':'watermark_left',
                                'valid':'text',
                                'section':'Type specific',
                                'tip':'Pixel value, %, center, right, left. If you want to apply the same watermark on different locations please provide a comma separated list of positions. Extensive explanation about how to position is provided in http://wideimage.sourceforge.net/documentation/smart-coordinates/'
                        },
                        '1165':{
                                'name':'Watermark top position (vertical)',
                                'optName':'watermark_top',
                                'valid':'text',
                                'section':'Type specific',
                                'tip':'Pixel value, %, center, top, bottom. If you want to apply the same watermark on different locations please provide a comma separated list of positions. Extensive explanation about how to position is provided in http://wideimage.sourceforge.net/documentation/smart-coordinates/'
                        },
                        '1166':{
                                'name':'Add &copy; in watermark',
                                'optName':'watermark_copy',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1167':{
                                'name':'Watermark colors',
                                'optName':'watermark_colors',
                                'valid':'text',
                                'tip':'Comma separated list of red, green, blue values. Ex. 0,0,0 or 255,255,255',
                                'section':'Type specific'
                        },
                        '1168':{
                                'name':'Image width (px)',
                                'optName':'picwidth',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':500
                        },
                        '1169':{
                                'name':'Image height (px)',
                                'optName':'picheight',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':500
                        },
                        '1170':{
                                'name':'Image quality (%)',
                                'optName':'picquality',
                                'valid':'range',
                                'section':'Type specific',
                                'low':0,
                                'high':100,
                                'step':10,
                                'shift':10,
                                'default':70,
                                'ui':'select'
                        },
                        '1171':{
                                'name':'Thumb - Image width (px)',
                                'optName':'picwidththumb',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':170
                        },
                        '1172':{
                                'name':'Thumb - Image height (px)',
                                'optName':'picheightthumb',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':170
                        },
                        '1173':{
                                'name':'Thumb - Image quality (%)',
                                'optName':'picqualitythumb',
                                'valid':'range',
                                'section':'Type specific',
                                'low':0,
                                'high':100,
                                'step':10,
                                'shift':10,
                                'default':70,
                                'ui':'select'
                        },
                        '1174':{
                                'name':'Media limit',
                                'optName':'medialimit',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':0,
                                'max':100,
                                'tip':'Maximum number of media items per K2 item (0 unlimited)',
                                'default':10
                        },
                        '1175':{
                                'name':'Image size (kb)',
                                'optName':'picsize',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':1,
                                'tip':'Maximum upload size for picture',
                                'default':100,
                                'required':0
                        },
                        '1176':{
                                'name':'Video size (kb)',
                                'optName':'videosize',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':1,
                                'tip':'Maximum upload size for video files',
                                'default':400
                        },
                        '1177':{
                                'name':'Audio size (kb)',
                                'optName':'audiosize',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':1,
                                'tip':'Maximum upload size for audio files',
                                'default':200
                        },
                        
                        // Type::Date
                        '1201':{
                                'name':'Picker theme',
                                'optName':'theme',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'datepicker_dashboard', 'text':'Dashboard'},
                                        {'value':'datepicker_jqui', 'text':'jqui'},
                                        {'value':'datepicker_minimal', 'text':'Minimal'},
                                        {'value':'datepicker_vista', 'text':'Vista'}
                                ],
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1202':{
                                'name':'Time format (including duration)',
                                'optName':'timeFormat',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'H:i:s', 'text':'18:11:59'},
                                        {'value':'H:i', 'text':'18:11'},
                                        {'value':'H.i.s', 'text':'18.11.59'},
                                        {'value':'H.i', 'text':'18.11'}                                  
                                ],
                                'section':'Type specific'
                        },
                        '1203':{
                                'name':'Date format',
                                'optName':'dateFormat',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'Y:m:d', 'text':'1998:08:27'},
                                        {'value':'Y-m-d', 'text':'1998-08-27'},
                                        {'value':'Y.m.d', 'text':'1998.08.27'},
                                        {'value':'Y/m/d', 'text':'1998/08/27'},
                                        {'value':'Y/M/d', 'text':'1998/Aug/27'},
                                        {'value':'Y/F/d', 'text':'1998/August/27'},
                                        {'value':'Ymd', 'text':'19980827'},
                                        {'value':'ymd', 'text':'980827'},
                                        {'value':'d/m/Y', 'text':'27/08/2008'},
                                        {'value':'d/M/Y', 'text':'27/Aug/2008'},
                                        {'value':'d/F/Y', 'text':'27/August/2008'},
                                        {'value':'dmy', 'text':'270898'}                                 
                                ],
                                'section':'Type specific'
                        },
/*
 *                                 <field name="datetimeFormat" type="list" default="Y-m-d H:i:s" label="Date/time format" description="Provide date/time format">
                                        <option value="Y:m:d H:i:s">2008:08:07 18:11:31</option>
                                        <option value="Y-m-d H:i:s">2008-08-07 18:11:31</option>
                                        <option value="Y.m.d H:i:s">2008.08.07 18:11:31</option>
                                        <option value="d/m/Y H:i:s">07/08/2008 18:11:31</option>
                                        <option value="d/M/Y H:i:s">07/Aug/2008 18:11:31</option>
                                        <option value="d/j/Y H:i:s">07/August/2008 18:11:31</option>
                                        <option value="F j, Y, g:i a">March 10, 2001, 5:16 pm</option>
                                        <option value="h-i-s, j-m-y, it is w Day">05-16-18, 10-03-01, 1631 1618 6 Satpm01</option>
                                        <option value="D M j G:i:s T Y">Sat Mar 10 17:16:18 MST 2001</option>
                                        <option value="D, M j Y G:i:s T">Fri, 24 Dec 2010 06:56:39 PST</option>
                                        <option value="D M j Y, G:i:s">Sat Mar 10 2001, 17:16:18</option>
                                        <option value="D, d M Y H:i:s \G\M\T">Mon, 19 Nov 2007 23:47:33 GMT</option>
                                        <option value="Y-m-d\TH:i:s\Z">2003-12-13T18:30:02Z</option>
                                </field>

 */                        
                        '1204':{
                                'name':'Datetime format',
                                'optName':'datetimeFormat',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'Y:m:d H:i:s', 'text':'1998:08:27 18:11:31'},
                                        {'value':'Y-m-d H:i:s', 'text':'1998-08-27 18:11:31'},
                                        {'value':'Y.m.d H:i:s', 'text':'1998.08.27 18:11:31'},
                                        {'value':'Y/m/d H:i:s', 'text':'1998/08/27 18:11:31'},
                                        {'value':'Y/M/d H:i:s', 'text':'1998/Aug/27 18:11:31'},
                                        {'value':'Y/F/d H:i:s', 'text':'1998/August/27 18:11:31'},
                                        {'value':'d/m/Y H:i:s', 'text':'27/08/2008 18:11:31'},
                                        {'value':'d/M/Y H:i:s', 'text':'27/Aug/2008 18:11:31'},
                                        {'value':'d/F/Y H:i:s', 'text':'27/August/2008 18:11:31'},
                                        {'value':'F j, Y, g:i a', 'text':'March 10, 2001, 5:16 pm'},
                                        {'value':'D M j G:i:s T Y', 'text':'Sat Mar 10 17:16:18 MST 2001'},
                                        {'value':'D, M j Y G:i:s T', 'text':'Fri, 24 Dec 2010 06:56:39 PST'},
                                        {'value':'D M j Y, G:i:s', 'text':'Sat Mar 10 2001, 17:16:18'},
                                        {'value':'D, d M Y H:i:s \G\M\T', 'text':'Mon, 19 Nov 2007 23:47:33 GMT'},
                                        {'value':'Y-m-d\TH:i:s\Z', 'text':'2003-12-13T18:30:02Z'}
                                ],
                                'section':'Type specific'
                        },
                        // TODO: testa hur detta beroende håller än idag
                        '1205':{
                                'name':'Start time',
                                'optName':'starttime',
                                'valid':'integer',
                                'tip':'Field id of start field',
                                'section':'Type specific'
                        },
                        '1206':{
                                'name':'End time',
                                'optName':'endtime',
                                'valid':'integer',
                                'tip':'Field id of end field',
                                'section':'Type specific'
                        },
                        '1207':{
                                'name':'Repeat end date mode',
                                'optName':'repeat',
                                'valid':'text',
                                'ui':'radio',
                                'values':[
                                        {'value':'enddate', 'text':'End date'},
                                        {'value':'number', 'text':'Number of days'}                                
                                ],
                                'tip':'end of repetition mode - limited by enddate or number of allowed repetitions',
                                'section':'Type specific'
                        },
                        '1208':{
                                'name':'Expire',
                                'optName':'expire',
                                'valid':'verifybox',
                                'tip':'When the last repetition datetime is passed item is marked as unpublished.',
                                'section':'Type specific'
                        },
                        '1211':{
                                'name':'Combine',
                                'optName':'combine',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1209':{
                                'name':'Repeat format',
                                'optName':'repeatformat',
                                'valid':'text',
                                'tip':'Format with which date/time will be shown, overriding the global k2fields plugin setting.',
                                'section':'Type specific'
                        },
                        '1210':{
                                'name':'Repetition limit',
                                'optName':'repeatlistmax',
                                'valid':'integer',
                                'tip':'number of instances to be shown directly, and eventually remaining repetitions will be folded in an accordion',
                                'section':'Type specific'
                        },
                        '1212':{
                                'name':'Show only future',
                                'optName':'repeatexpire',
                                'valid':'verifybox',
                                'tip':'Limits shown repetitions to only those in the future.',
                                'section':'Type specific'
                        },
                        '1213':{
                                'name':'Repeat list mode',
                                'optName':'repeatlist',
                                'valid':'checkbox',
                                'values':[ {'value':'word'}, {'value':'list'}],
                                'section':'Type specific'
                        },
                        '1214':{
                                'name':'Repeat combine',
                                'optName':'repeatcombine',
                                'valid':'verifybox',
                                'tip':'if we have a several repeating instances, in the case where we have a list valued field, and we would want to combine them all to create one single list of event date/times then we would need to provide this option as true',
                                'section':'Type specific'
                        },
                        '1251':{
                                'name':'Email as',
                                'optName':'emailformat',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'form', 'text':'form'},
                                        {'value':'link', 'text':'link with mailto:'},
                                        {'value':'image', 'text':'image rendered from the address and linked with mailto:'},
                                        {'value':'raw', 'text':'plain email'}
                                ],
                                'tip':'connected to a form defined through one of the supported extensions, simple mailto anchor, image rendered from the email address and linked to an email anchor or just the email value in raw. If using the form option form creator will be provided the following settings by calling K2FieldsModelFields::getEmailRecord(): item = sending item, field = sending field, itemid = sending items menu item id, title = sending items title. By controlling access one can create a field that is globally directed to certain default address only and thus by create a report kind feature.',
                                'deps':{
                                        'form':['id:1252']
                                },
                                'section':'Type specific'
                        },
                        '1252':{
                                'name':'Form',
                                'optName':'menu',
                                'valid':'integer',
                                'ui':'select',
                                'values':this.options.options['menuitems'],
                                'tip':'menu item for the form to connect',
                                'section':'Type specific'
                        },
                        '1253':{
                                'name':'Modal width(px)',
                                'optName':'width',
                                'valid':'integer',
                                'section':'Type specific'
                        },
                        '1254':{
                                'name':'Modal height(px)',
                                'optName':'height',
                                'valid':'integer',
                                'section':'Type specific'
                        },
                        '1255':{
                                'name':'Form button positioned absolute',
                                'optName':'absolute',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1256':{
                                'name':'Form title',
                                'optName':'formtitle',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'Placeholders %title% and %category% can be used and will be replaced automatically when providing the values to your form component by item title and category title respectively',
                                'section':'Type specific'
                        },
                        '1257':{
                                'name':'Form footer',
                                'optName':'formfooter',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'Placeholders %title% and %category% can be used and will be replaced automatically when providing the values to your form component by item title and category title respectively',
                                'section':'Type specific'
                        },
                        '1258':{
                                'name':'Link title',
                                'optName':'title',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'Placeholders %title% and %category% can be used and will be replaced automatically when providing the values to your form component by item title and category title respectively',
                                'section':'Type specific'
                        },
                        '1301':{
                                'name':'Label',
                                'optName':'label',
                                'valid':'text',
                                'tip':'Replacing the common label',
                                'section':'Type specific'
                        },
                        '1302':{
                                'name':'Schema type',
                                'optName':'schematype',
                                'valid':'text',
                                'tip':'refer to http://schema.org/docs/full.html',
                                'section':'Type specific'
                        },
                        '1303':{
                                'name':'Generate field',
                                'optName':'generatefield',
                                'valid':'yesno',
                                'default':0,
                                'section':'Type specific'
                        },
                        '1351':{
                                'name':'Method',
                                'optName':'mapinputmethod',
                                'valid':'text',
                                'ui':'radio',
                                'values':[
                                        {'value':'coord', 'text':'coord'},
                                        {'value':'geo', 'text':'geo'}
                                ],
                                'default':'coord',
                                'section':'Type specific'
                        },
                        '1352':{
                                'name':'Show map editor',
                                'optName':'showmapeditor',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1353':{
                                'name':'Location provider',
                                'optName':'locationprovider',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {'value':'maxmind', 'text':'maxmind'},
                                        {'value':'browser', 'text':'browser (tbi)'},
                                        {'value':'function', 'text':'function'}
                                ],
                                'deps':{
                                        'function':['id:1354']
                                },
                                'section':'Type specific'
                        },
                        '1354':{
                                'name':'Location provider function name',
                                'optName':'locationproviderfunction',
                                'valid':'text',
                                'ui':'text',
                                'section':'Type specific',
                                'tip':'You can provide a simple function name or a call of an existing objects method'
                        },
                        '1355':{
                                'name':'Static map? (item)',
                                'optName':'mapstatic',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1356':{
                                'name':'Maxzoom (default)',
                                'optName':'maxzoom',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1357':{
                                'name':'Maxzoom (edit)',
                                'optName':'maxzoomedit',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1358':{
                                'name':'Maxzoom (item)',
                                'optName':'maxzoomitem',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1359':{
                                'name':'Maxzoom (itemlist)',
                                'optName':'maxzoomitemlist',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1360':{
                                'name':'Map provider (default)',
                                'optName':'mapprovider',
                                'valid':'text',
                                'ui':'select',
                                'values':[
                                        {value:'cloudmade',text:'cloudmade'}, 
                                        {value:'google',text:'google'}, 
                                        {value:'googlev3',text:'google v3 (draggable)'}, 
                                        {value:'leaflet',text:'leaflet (draggable)'}, 
                                        {value:'mapquest',text:'mapquest'}, 
                                        {value:'cloudmade',text:'cloudmade'}, 
                                        {value:'microsoft',text:'microsoft'},
                                        {value:'yandex',text:'yandex'}
                                ],
                                'savevalues':'mapproviders',
                                'section':'Type specific'
                        },
                        '1361':{
                                'name':'Map provider (edit)',
                                'optName':'mapprovideredit',
                                'valid':'text',
                                'ui':'select',
                                'values':'values:mapproviders',
                                'section':'Type specific'
                        },
                        '1362':{
                                'name':'Map provider (item)',
                                'optName':'mapprovideritemlist',
                                'valid':'text',
                                'ui':'select',
                                'values':'values:mapproviders',
                                'section':'Type specific'
                        },
                        '1363':{
                                'name':'Map provider (item)',
                                'optName':'mapprovideritem',
                                'valid':'text',
                                'ui':'select',
                                'values':'values:mapproviders',
                                'section':'Type specific'
                        },
                        '1364':{
                                'name':'Map API key (default)',
                                'optName':'mapapikey',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'section':'Type specific'
                        },
                        '1365':{
                                'name':'Map API key (edit)',
                                'optName':'mapapikeyedit',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'section':'Type specific'
                        },
                        '1366':{
                                'name':'Map API key (item)',
                                'optName':'mapapikeyitem',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'section':'Type specific'
                        },
                        '1367':{
                                'name':'Map API key (itemlist)',
                                'optName':'mapapikeyitemlist',
                                'valid':'text',
                                'ui':'text',
                                'size':'100',
                                'section':'Type specific'
                        },
                        '1368':{
                                'name':'Map center (default)',
                                'optName':'mapcenter',
                                'valid':'complex',
                                'subfields':[
                                        {'name':'Lat','valid':'text'},
                                        {'name':'Lon','valid':'text'}
                                ],
                                'section':'Type specific'
                        },
                        '1369':{
                                'name':'Map center (edit)',
                                'optName':'mapcenteredit',
                                'valid':'complex',
                                'subfields':[
                                        {'name':'Lat','valid':'text'},
                                        {'name':'Lon','valid':'text'}
                                ],
                                'section':'Type specific'
                        },
                        '1370':{
                                'name':'Map center (item)',
                                'optName':'mapcenteritem',
                                'valid':'complex',
                                'subfields':[
                                        {'name':'Lat','valid':'text'},
                                        {'name':'Lon','valid':'text'}
                                ],
                                'section':'Type specific'
                        },
                        '1371':{
                                'name':'Map center (itemlist)',
                                'optName':'mapcenteritemlist',
                                'valid':'complex',
                                'subfields':[
                                        {'name':'Lat','valid':'text'},
                                        {'name':'Lon','valid':'text'}
                                ],
                                'section':'Type specific'
                        },
                        '1372':{
                                'name':'Map zoom (default)',
                                'optName':'mapzoom',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1373':{
                                'name':'Map zoom (edit)',
                                'optName':'mapzoomedit',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1374':{
                                'name':'Map zoom (item)',
                                'optName':'mapzoomitem',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1375':{
                                'name':'Map zoom (itemlist)',
                                'optName':'mapzoomitemlist',
                                'valid':'range',
                                'section':'Type specific',
                                'low':1,
                                'high':20,
                                'sorted':true
                        },
                        '1376':{
                                'name':'Map container ID (default)',
                                'optName':'mapcontainerid',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1377':{
                                'name':'Map container ID (edit)',
                                'optName':'mapcontaineridedit',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1378':{
                                'name':'Map container ID (item)',
                                'optName':'mapcontaineriditem',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1379':{
                                'name':'Map container ID (itemlist)',
                                'optName':'mapcontaineriditemlist',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1380':{
                                'name':'Map type (default)',
                                'optName':'maptype',
                                'valid':'integer',
                                'ui':'select',
                                'values':[
                                        {'value':1, text:'roadmap'}, 
                                        {'value':2, text:'satellite'}, 
                                        {'value':3, text:'hybrid'}, 
                                        {'value':4, text:'physical'}
                                ],
                                'savevalues':'maptypes',
                                'default':1,
                                'section':'Type specific',
                                'sorted':true
                        },
                        '1381':{
                                'name':'Map type (item)',
                                'optName':'maptypeitem',
                                'valid':'integer',
                                'ui':'select',
                                'values':'values:maptypes',
                                'default':1,
                                'section':'Type specific',
                                'sorted':true
                        },
                        '1382':{
                                'name':'Map type (editor)',
                                'optName':'maptypeedit',
                                'valid':'integer',
                                'ui':'select',
                                'values':'values:maptypes',
                                'default':1,
                                'section':'Type specific',
                                'sorted':true
                        },
                        '1383':{
                                'name':'Map type (itemlist)',
                                'optName':'maptypeitemlist',
                                'valid':'integer',
                                'ui':'select',
                                'values':'values:maptypes',
                                'default':1,
                                'section':'Type specific',
                                'sorted':true
                        },
                        '1384':{
                                'name':'Map container class (default)',
                                'optName':'mapcontainerclass',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'default':'mapContainer',
                                'section':'Type specific'
                        },
                        '1385':{
                                'name':'Map container class (edit)',
                                'optName':'mapcontainerclassedit',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1386':{
                                'name':'Map container class (item)',
                                'optName':'mapcontainerclassitem',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1387':{
                                'name':'Map container class (itemlist)',
                                'optName':'mapcontainerclassitemlist',
                                'valid':'text',
                                'ui':'text',
                                'size':50,
                                'section':'Type specific'
                        },
                        '1388':{
                                'name':'Map icon color (editor)',
                                'optName':'mapiconcolor',
                                'valid':'text',
                                'ui':'select',
                                'values':['orange'],
                                'section':'Type specific',
                                'tip':'Draggable numerical icons'
                        },
                        
                        '1389':{
                                'name':'Map icon location',
                                'optName':'mapiconlocation',
                                'valid':'text',
                                'ui':'text',
                                'size':100,
                                'section':'Type specific',
                                'tip':'Relative to site root. Used to designate items as interest points on map. If none is provided then based on mapiconcolor a default icon is used.'
                        },
                        '1390':{
                                'name':'Map icon use hover',
                                'optName':'mapiconlocationhover',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1391':{
                                'name':'Go to link title',
                                'optName':'mapgoto',
                                'valid':'text',
                                'section':'Type specific',
                                'default':'Go to %category%',
                                'tip':'Available placeholders are %category%, %item%, %categoryid%, %itemid%'
                        },
                        '1392':{
                                'name':'Pan events (to interest point)',
                                'optName':'mappanevents',
                                'valid':'text',
                                'ui':'checkbox',
                                'section':'Type specific',
                                'values':['click', 'mouseover'],
                                'default':'click',
                                'tip':'Events upon which map is to be moved to target interest point'
                        },
                        '1393':{
                                'name':'Create list of interest points',
                                'optName':'mapcreateips',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1394':{
                                'name':'Show itemlist',
                                'optName':'mapshowitemlist',
                                'valid':'verifybox',
                                'section':'Type specific',
                                'tip':'Indicate if normal itemlist should be shown below map'
                        },
                        '1395':{
                                'name':'Available actions in map itemlist view',
                                'optName':'mapactions',
                                'valid':'text',
                                'ui':'checkbox',
                                'values':['reset', 'nearby'],
                                'deps':{'nearby':['id:1396']},
                                'section':'Type specific'
                        },
                        '1396':{
                                'name':'Nearby distances (km)',
                                'optName':'nearbys',
                                'list':'normal',
                                'valid':'integer',
                                'section':'Type specific'
                        },
                        
                        '1401':{
                                'name':'Source',
                                'optName':'source',
                                'valid':'text',
                                'ui':'select',
                                'values':['specify', 'sql', 'php', 'function', 'url', 'file'],
                                'section':'Values',
                                'deps':{
                                        'specify':['id:1406'],
                                        'sql':['id:1402'],
                                        'php':['id:1403'],
                                        'function':['id:1407'],
                                        'url':['id:1404'],
                                        'file':['id:1405']
                                },
                                'skip':true,
                                tip:'Each source need to avail its values in suitable format as specified along with each source type, where value is the least possible and can also include text and image.'
                        },
                        '1402':{
                                'name':'SQL',
                                'optName':'values',
                                'ui':'textarea',
                                'section':'Values',
                                'valid':'text',
                                'tip':'SQL query rendering rows with columns labeled as value, text and img'
                        },
                        '1403':{
                                'name':'PHP',
                                'optName':'values',
                                'ui':'textarea',
                                'section':'Values',
                                'valid':'text',
                                'tip':'Array with objects which has the members value, text and img'
                        },
                        '1404':{
                                'name':'URL',
                                'optName':'values',
                                'ui':'text',
                                'section':'Values',
                                'valid':'text',
                                'size':100,
                                'tip':'JSON encoded array with objects which has the members value, text and img'
                        },
                        '1405':{
                                'name':'File',
                                'optName':'values',
                                'ui':'text',
                                'section':'Values',
                                'valid':'text',
                                'size':100,
                                'tip':'File need to be located on site and given relative to site root. Each row in file need to adhere to the following format where only value column is mandatory: value==text==img'
                        },
                        '1406':{
                                'name':'Specify values',
                                'optName':'values',
                                'valid':'complex',
                                'list':'normal',
                                'subfields':[
                                        {'name':'Value','valid':'text'},
                                        {'name':'Text','valid':'text'},
                                        {'name':'Image','valid':'text','tip':'File name of image located in media/k2fields/images'},
                                ],
                                'listmax':'100',
                                'section':'Values'
                        },
                        '1407':{
                                'name':'Function',
                                'optName':'values',
                                'ui':'text',
                                'section':'Values',
                                'valid':'text',
                                'size':100,
                                'tip':'Either a globally defined function name or a call to statically defined variable name as classname::functionname'
                        },
                        '1451':{
                                name:'Alias of field',
                                optName:'alias',
                                valid:'int',
                                ui:'select',
                                values:this.options.options['fields'],
                                sorted:true,
                                section:'Type specific'
                        },
                        '1452':{
                                name:'Render',
                                optName:'render',
                                valid:'verifybox',
                                section:'Type specific',
                                tip:'If not explicitly requested alias fields are not rendered in views'
                        },
                        '1501':{
                                name:'Low',
                                optName:'low',
                                valid:'integer',
                                section:'Type specific'
                        },
                        '1502':{
                                name:'High',
                                optName:'high',
                                valid:'integer',
                                section:'Type specific'
                        },
                        '1503':{
                                name:'Step',
                                optName:'step',
                                valid:'integer',
                                section:'Type specific'
                        },
                        '1504':{
                                name:'Shift',
                                optName:'shift',
                                valid:'integer',
                                section:'Type specific'
                        },
                        '1505':{
                                name:'Show as',
                                optName:'show',
                                valid:'text',
                                values:[{'text':'image', 'value':'img'}, 'text'],
                                'default':'txt',
                                ui:'radio',
                                section:'Type specific',
                                tip:'If image choosen: file needs to be located in media/k2fields/icon folder and named as follows: be of png type and have png as suffix and prefixed with "n" followed by the number it represents.'
                        }
             };
        }
});