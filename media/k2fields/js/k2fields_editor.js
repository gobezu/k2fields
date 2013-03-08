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
                                        val = val.join(k2fs.options.valueSeparator);
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
                
                document.id('name').set('value', 
                        skipped['name']+sec+
                        ' / TYPE:'+def['valid']+
                        ' / '+search+' / '+(def['list'] ? 'LIST:'+def['list'] : 'NOLIST')+
                        (def['col'] != undefined ? ' / COL:' + (def['col'].toInt()+1)+(def['colwidth'] != undefined ? ',WIDTH:'+def['colwidth'] : '') : '')
                );
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
                        if (optName == 'values') {
                                var _val;
                                if (val != undefined && (_val = val.match(/^(file|function|php|url|sql)\:/i))) {
                                        if (_val[1].toLowerCase() != ps.name.toLowerCase()) {
                                                val = '';
                                        } else {
                                                val = val.replace(_val[0], '');
                                        }
                                } else if (ps.name.toLowerCase() != 'specify') {
                                        val = '';
                                }
                        }
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
                                'name':'Name',
                                'optName':'name',
                                'valid':'text',
                                'required':'1',
                                'skip':true,
                                'section':'Basic',
                                'size':60
                        },
                        '2':{
                                'name':'Type',
                                'optName':'valid',
                                'valid':'select',
                                'values':[
                                        {'label':'basic'},
                                        {'value':'text','text':'text *'},
                                        {'value':'alpha','text':'alpha *'},
                                        {'value':'alphanum','text':'alphanum *'},
                                        {'value':'integer','text':'integer *'},
                                        {'value':'numeric','text':'numeric *'},
                                        {'value':'range','text':'range of values *'},
                                        {'label':'verify'},
                                        {'value':'yesno','text':'yesno (binary options)'},
                                        {'value':'verifybox','text':'verify (single option)'},
                                        {'label':'item parts (view only)'},
                                        {'value':'title','text':'title'},
                                        {'value':'rate','text':'rate'},
                                        {'label':'advanced'},
                                        {'value':'k2item','text':'k2item'},
                                        {'value':'list','text':'list *'},
                                        {'value':'media','text':'media'},
                                        {'value':'map','text':'map'},
                                        {'value':'complex','text':'complex'},
                                        {'value':'alias','text':'alias'},
                                        {'label':'date/time'},
                                        {'value':'datetime','text':'datetime'},
                                        {'value':'date','text':'date'},
                                        {'value':'time','text':'time'},
                                        {'value':'duration','text':'duration'},
                                        {'value':'days','text':'week days *'},
                                        {'label':'internet'},
                                        {'value':'email','text':'email'},
                                        {'value':'form','text':'form'},
                                        {'value':'url','text':'url'},
                                        {'label':'social'},
                                        {'value':'facebook','text':'facebook'},
                                        {'value':'twitter','text':'twitter'},
                                        {'value':'linkedin','text':'linkedin'},
                                        {'value':'googleplus','text':'googleplus'},
                                        {'value':'pinterest','text':'pinterest'},
                                        {'value':'readability','text':'readability'},
                                        {'value':'flattr','text':'flattr'},
                                        {'label':'misc'},
                                        {'value':'creditcards','text':'creditcards'},
                                        {'value':'phone','text':'phone (TBI: currently limited support)'}
                                ],
                                'deps': {
                                        'text':['id:3'],
                                        'alpha':['id:3'],
                                        'alphanum':['id:3'],
                                        'integer':['id:3', 'id:1214', 'id:1218', 'id:1219', 'id:1220', 'id:1221', 'id:1224'],
                                        'numeric':['id:3', 'id:1214', 'id:1218', 'id:1219', 'id:1220', 'id:1221', 'id:1224'],
                                        'days':['id:3'],
                                        'k2item':['id:11', 'id:1101', 'id:1102', 'id:1103', 'id:1104', 'id:1105', 'id:1106', 'id:1107'],
                                        'list':['id:3', 'id:11', 'id:1001', 'id:1002', 'id:1003', 'id:1004', 'id:1005'],
                                        'media':['id:34', 'id:35', 'id:1151', 'id:1152', 'id:1153', 'id:1154', 'id:1155', 'id:1156', 'id:1157', 'id:1158', 'id:1159', 'id:1160', 'id:1161', 'id:1162', 'id:1163', 'id:1164', 'id:1165', 'id:1166', 'id:1167', 'id:1168', 'id:1169', 'id:1170', 'id:1171', 'id:1172', 'id:1173', 'id:1174', 'id:1175', 'id:1176', 'id:1177', 'id:1178'],
                                        'datetime':['id:1201', 'id:1204', 'id:1205', 'id:1206', 'id:1222', 'id:1223', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214', 'id:1215', 'id:1216', 'id:1217', 'id:1218', 'id:1219', 'id:1220', 'id:1221', 'id:1224'],
                                        'date':['id:1201', 'id:1203', 'id:1205', 'id:1206', 'id:1222', 'id:1223', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214', 'id:1215', 'id:1216', 'id:1217', 'id:1218', 'id:1219', 'id:1220', 'id:1221', 'id:1224'],
                                        'time':['id:1201', 'id:1202', 'id:1205', 'id:1206', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214', 'id:1215'],
                                        'duration':['id:1201', 'id:1202', 'id:1205', 'id:1206', 'id:1207', 'id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213', 'id:1214', 'id:1215'],
                                        'email':['id:1251', 'id:1252', 'id:1253', 'id:1254', 'id:1256', 'id:1257', 'id:1258'],
                                        'title':['id:1301', 'id:1302', 'id:1303'],
                                        'rate':['id:1301'],
                                        'complex':['id:1051', 'id:1052'],
                                        'map':['id:11', 'id:1351', 'id:1352', 'id:1353', 'id:1354', 'id:1355', 'id:1356', 'id:1357', 'id:1358', 'id:1359', 'id:1360', 'id:1361', 'id:1362', 'id:1363', 'id:1364', 'id:1365', 'id:1366', 'id:1367', 'id:1368', 'id:1369', 'id:1370', 'id:1371', 'id:1372', 'id:1373', 'id:1374', 'id:1375', 'id:1376', 'id:1377', 'id:1378', 'id:1379', 'id:1380', 'id:1381', 'id:1382', 'id:1383', 'id:1384', 'id:1385', 'id:1386', 'id:1387', 'id:1388', 'id:1389', 'id:1390', 'id:1391', 'id:1392', 'id:1393', 'id:1394', 'id:1395', 'id:1397', 'id:1398', 'id:1399', 'id:2001'],
                                        'alias':['id:1451', 'id:1452'],
                                        'range':['id:3', 'id:1501', 'id:15011', 'id:1502', 'id:15021', 'id:1503', 'id:15031', 'id:1504', 'id:1505'],
                                        'facebook':['id:1601', 'id:1602', 'id:1603', 'id:1604', 'id:1605', 'id:1606', 'id:1607', 'id:1608'],
                                        'twitter':['id:1651', 'id:1652', 'id:1653', 'id:1654', 'id:1655', 'id:1656'],
                                        'linkedin':['id:1701'],
                                        'pinterest':['id:1751', 'id:1752', 'id:1753'],
                                        'googleplus':['id:1801', 'id:1802'],
                                        'readability':['id:1851', 'id:1852', 'id:1853', 'id:1854', 'id:1855', 'id:1856', 'id:1857'],
                                        'flattr':['id:1901', 'id:1902', 'id:1903', 'id:1904', 'id:1905', 'id:1906'],
                                        'form':['id:1951', 'id:1952', 'id:1953', 'id:1954', 'id:1956', 'id:1957', 'id:1958']
                                },
                                'required':'1',
                                'savevalues':'validtypes',
                                'sorted':true,
                                'section':'Basic',
                                'tip':'Types with * allow you to alter UI. In most cases the type dictates the UI to be used.'
                        },
                        '3':{
                                'name':'User Interface (UI)',
                                'optName':'ui',
                                'valid':'select',
                                'values':[
                                        {'value':'radio'},
                                        {'value':'checkbox'},
                                        {'value':'text'},
                                        {'value':'textarea'},
                                        {'value':'editor'},
                                        {'value':'select'},
                                        {'value':'slider'},
                                        {'value':'rangeslider', 'text':'range slider (only numerical valued)'}
                                ],
                                'deps':{
                                        'select':['id:12', 'id:14', 'id:15', 'id:28', 'id:73'],
                                        'radio':['id:14', 'id:15', 'id:28', 'id:39'],
                                        'checkbox':['id:14', 'id:15', 'id:28'],
                                        'textarea':['id:48', 'id:49']
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
                                        'normal':['id:5', 'id:45', 'id:62'],
                                        'conditional':['id:5','id:6','id:26', 'id:45', 'id:62']
                                },
                                'clearopt':'button',
                                'sorted':true,
                                'section':'Basic',
                                'tip':'Repeatable fields are those where user repeats the instance of the field in order to assign multiple values to the field. Maximum number of repetetions are defined in maximum repetetions below. A conditional repeatable field is one with ability to comment on each repeated instance of the field. Ex. if you have an address field of an item and assuming that the establishment has several branches you would want a conditional repeatable field where as condition/comment you would set a description of each branches particularity.'
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
                                },
                                'section':'Basic'
                        },
                        '13':{
                                'name':'Multiple size',
                                'optName':'size',
                                'valid':'integer'
                        },
                        '14':{
                                'name':'Save values as',
                                'optName':'savevalues',
                                'valid':'text',
                                'tip':'Fields created after this field can reuse the values of this field by referring to the name you provide here',
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
                                'section':'Layout',
                                'tip':'Sections serve as means of organizing fields in various separate user interface parts. If for a given category the selected UI is tabs then we will create as many tab panels as there are sections and within each panel fields belonging to the same section will be placed. The heading of the tab will be set to the name of the section.'
                        },
                        '17':{
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
                        '18':{
                                'name':'Folded',
                                'optName':'folded',
                                'valid':'verifybox',
                                'tip':'Applicable only for tabular layout. In tabular layout you are able to place some of your fields in a collapsed layout and becomes visible upon click of any area containing the none folded fields. This is useful in cases where you would like to provide much information without leading the user to the item itself and yet want to maintain a list that is not overly crowded with content.',
                                'section':'Layout'
                        },
                        '69':{
                                'name':'Table column placement',
                                'optName':'col',
                                'valid':'range',
                                'ui':'radio',
                                'shift':1,
                                'low':0,
                                'high':10,
                                'section':'Layout'
                        },
                        '70':{
                                'name':'Table column width',
                                'optName':'colwidth',
                                'valid':'text',
                                'tip':'Provide unit. Ex. 40%. Available units are px (default) and %.',
                                'section':'Layout'
                        },
                        '71':{
                                'name':'Clear after this table column',
                                'optName':'colclearafter',
                                'valid':'verifybox',
                                'section':'Layout'
                        },
                        '72':{
                                'name':'Clear before this table column',
                                'optName':'colclearbefore',
                                'valid':'verifybox',
                                'section':'Layout'
                        },
                        '73':{
                                'name':'Chosen',
                                'optName':'selectchosen',
                                'valid':'verifybox',
                                'section':'Basic',
                                'deps':{1:['id:74', 'id:75', 'id:76', 'id:77', 'id:78', 'id:79', 'id:80']}
                        },
                        '74':{
                                'name':'Chosen width',
                                'optName':'chosen.width',
                                'valid':'integer',
                                'section':'Basic',
                                'tip':'Provide unit in pixels'
                        },
                        '75':{
                                'name':'Chosen No Results Text',
                                'optName':'chosen.no_results_text',
                                'valid':'text',
                                'size':'60',
                                'section':'Basic'
                        },
                        '76':{
                                'name':'Chosen Limit Selected Options',
                                'optName':'chosen.max_selected_options',
                                'valid':'integer',
                                'section':'Basic'
                        },
                        '77':{
                                'name':'Chosen Allow Deselect on Single Selects',
                                'optName':'chosen.allow_single_deselect',
                                'valid':'verifybox',
                                'section':'Basic'
                        },
                        '78':{
                                'name':'Chosen Template',
                                'optName':'chosen.template',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'When providing template use the placeholders %img%, %text%, %value%',
                                'section':'Basic'
                        },
                        '79':{
                                'name':'Chosen Template Selected',
                                'optName':'chosen.templateSelected',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'When providing template use the placeholders %img%, %text%, %value%',
                                'section':'Basic'
                        },
                        '80':{
                                'name':'Chosen Placeholder',
                                'optName':'chosen.data-placeholder',
                                'valid':'text',
                                'size':60,
                                'section':'Basic'
                        },
                        '81':{
                                'name':'Value image folder',
                                'optName':'imgFolder',
                                'valid':'text',
                                'size':60,
                                'section':'Values',
                                'tip':'Ex. images/site/%value%.png where %value% is replaced with actual value. You have the following placeholders at your disposal: %value%, %text%, %valuec%, %textc% where those appended with c are safe path alternatives of value and text respectively.'
                        },
                        '99':{
                                'name':'Note',
                                'optName':'field_notes',
                                'valid':'text',
                                'ui':'textarea',
                                'section':'Basic',
                                'tip':'When dealing with many fields over time you may forget the original intention of a field. Instead of going through a reverse engineering process to discover the idea you can put short notes about the actual intended use and other related notes as reference.'
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
                                'tip':'Based on selected schema type in your title field. If this is a field of type title please leave this empty as the property of title is given.',
                                'section':'SEO'
                        },
                        '67':{
                                'name':'Schema property value',
                                'optName':'schemapropvalue',
                                'valid':'text',
                                'ui':'radio',
                                'values':[
                                        {'value':'%value%', 'text':'value'},
                                        {'value':'%text%', 'text':'text'},
                                        {'value':'%img%', 'text':'image'}
                                ],
                                'default':'value',
                                'section':'SEO'
                        },
                        '68':{
                                'name':'Use meta tag for schema values',
                                'optName':'schemausemeta',
                                'valid':'yesno',
                                'default':0,
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
                                'tip':'Fields that depend on the values of this field, ie. fields that will be toggled in (shown) when the provided value is selected. For each value provided the corresponding selected field will be shown, unless negate is selected in which case the reverse applies.',
                                'section':'Additional'
                        },
                        '28':{
                                'name':'Order of values',
                                'optName':'valuesorder',
                                'valid':'text',
                                'ui':'radio',
                                'values':[
                                        {'value':'value'},
                                        {'value':'text'},
                                        {'value':'sorted', 'text':'do NOT order'}
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
                                'section':'Additional'
                        },
                        '39':{
                                'name':'Clear option',
                                'optName':'clearopt',
                                'valid':'text',
                                'values':['firstempty', 'lastempty', 'button'],
                                'ui':'select',
                                'section':'Basic'
                        },
                        '40':{
                                'name':'Show label (item)',
                                'optName':'showlabel',
                                'valid':'verifybox',
                                'section':'Basic',
                                'deps':{1:['id:41']},
                                'default':1
                        },
                        '41':{
                                'name':'Label (item)',
                                'optName':'label',
                                'valid':'text',
                                'ui':'text',
                                'size':60,
                                'section':'Basic'
                        },
                        '42':{
                                'name':'Show label (itemlist)',
                                'optName':'itemlistshowlabel',
                                'valid':'verifybox',
                                'section':'Basic',
                                'deps':{1:['id:43']}
                        },
                        '43':{
                                'name':'Label (itemlist)',
                                'optName':'itemlistlabel',
                                'valid':'text',
                                'ui':'text',
                                'size':60,
                                'section':'Basic'
                        },
                        '44':{
                                'name':'Absolutely positioned',
                                'optName':'absolute',
                                'valid':'verifybox',
                                'section':'Layout',
                                'tip':'If you choose to place a field absolute then you will also need to set the final position in your templates custom css file '
                        },
                        '45':{
                                'name':'Collapsible',
                                'optName':'collapsible',
                                'valid':'verifybox',
                                'section':'Basic',
                                'deps':{
                                        1:['id:46', 'id:65']
                                },
                                'tip':'Values with many repetitions might not look good when all values are listed explicitly. By making field collapsible repeated instances greater than the limit you provide below will be folded into a hidden holder and are made visible upon user click on the label defined below.'
                        },
                        '46':{
                                'name':'Collapse limit',
                                'optName':'collapselimit',
                                'valid':'integer',
                                'section':'Basic',
                                'default':3
                        },
                        '65':{
                                'name':'Collapse button label',
                                'optName':'collapselabel',
                                'valid':'text',
                                'section':'Basic',
                                'default':'Additional'
                        },
                        '62':{
                                'name':'Collapsible (itemlist)',
                                'optName':'collapsibleitemlist',
                                'valid':'verifybox',
                                'section':'Basic',
                                'deps':{
                                        1:['id:63', 'id:64']
                                },
                                'tip':'Values with many repetitions might not look good when all values are listed explicitly, especially in itemlist view. By making field collapsible repeated instances greater than the limit you provide below will be folded into a hidden holder and are made visible upon user click on the label defined below.'
                        },
                        '63':{
                                'name':'Collapse limit (itemlist)',
                                'optName':'collapselimititemlist',
                                'valid':'integer',
                                'section':'Basic',
                                'default':3
                        },
                        '64':{
                                'name':'Collapse button label (itemlist)',
                                'optName':'collapselabelitemlist',
                                'valid':'text',
                                'section':'Basic',
                                'default':'Additional'
                        },
                        '47':{
                                'name':'Exclude values from display',
                                'optName':'excludevalues',
                                'valid':'text',
                                'list':'normal',
                                'section':'Additional',
                                'size':70,
                                'tip':'The listed values'
                        },
                        '48':{
                                'name':'Cols',
                                'optName':'cols',
                                'valid':'integer',
                                'section':'Basic'
                        },
                        '49':{
                                'name':'Rows',
                                'optName':'rows',
                                'valid':'integer',
                                'section':'Basic'
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
                                'section':'Search',
                                'clearopt':'button'
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
                                'section':'Tooltips...'
                        },
                        '57':{
                                'name':'Search UI',
                                'optName':'search..ui',
                                'valid':'text',
                                'ui':'select',
                                'values':'values:uis',
                                'section':'Search',
                                'deps':{
                                        'select':['id:2100', 'id:2102', 'id:2103'],
                                        'radio':['id:2102'],
                                        'checkbox':['id:2102'],
                                        slider:['id:2111'],
                                        rangeslider:['id:2111']
                                },
                                'excludevalueseditfields':['textarea', 'editor']
                        },
                        '2100':{
                                'name':'Multiple select',
                                'valid':'verifybox',
                                'optName':'search..multiple',
                                'deps':{
                                        '1':['id:2101']
                                },
                                'section':'Search'
                        },
                        '2101':{
                                'name':'Multiple size',
                                'optName':'search..size',
                                'valid':'integer',
                                'section':'Search'
                        },
                        '2102':{
                                'name':'Order of values',
                                'optName':'search..valuesorder',
                                'valid':'text',
                                'ui':'radio',
                                'values':[
                                        {'value':'value'},
                                        {'value':'text'},
                                        {'value':'sorted', 'text':'do NOT order'}
                                ],
                                'section':'Search'
                        },
                        '2103':{
                                'name':'Chosen',
                                'optName':'search..selectchosen',
                                'valid':'verifybox',
                                'section':'Search',
                                'deps':{1:['id:2104', 'id:2105', 'id:2106', 'id:2107', 'id:2108', 'id:2109', 'id:2110']}
                        },
                        '2104':{
                                'name':'Chosen width',
                                'optName':'search..chosen.width',
                                'valid':'integer',
                                'section':'Search',
                                'tip':'Provide unit in pixels'
                        },
                        '2105':{
                                'name':'Chosen No Results Text',
                                'optName':'search..chosen.no_results_text',
                                'valid':'text',
                                'size':'60',
                                'section':'Search'
                        },
                        '2106':{
                                'name':'Chosen Limit Selected Options',
                                'optName':'search..chosen.max_selected_options',
                                'valid':'integer',
                                'section':'Search'
                        },
                        '2107':{
                                'name':'Chosen Allow Deselect on Single Selects',
                                'optName':'search..chosen.allow_single_deselect',
                                'valid':'verifybox',
                                'section':'Search'
                        },
                        '2108':{
                                'name':'Chosen Template',
                                'optName':'search..chosen.template',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'When providing template use the placeholders %img%, %text%, %value%',
                                'section':'Search'
                        },
                        '2109':{
                                'name':'Chosen Template Selected',
                                'optName':'search..chosen.templateSelected',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'When providing template use the placeholders %img%, %text%, %value%',
                                'section':'Search'
                        },
                        '2110':{
                                'name':'Chosen Placeholder',
                                'optName':'search..chosen.data-placeholder',
                                'valid':'text',
                                'size':60,
                                'section':'Search'
                        },
                        '2111':{
                                'name':'Adapt min/max',
                                'optName':'adaptminmax',
                                'valid':'verifybox',
                                'section':'Search',
                                'default':1
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
                                'values':['item', 'itemlist', 'module', 'map', 'compare'],
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
                                'section':'Basic',
                                'tip':'In addition to the defined ACL view groups we have added necessary restrictions such as owner, where if that value is set then only the creator of the item will be able to have the provided access.'
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
                                'section':'Type specific',
                                'default':'upload'
                        },
                        '1153':{
                                'name':'Mode',
                                'optName':'mode',
                                'valid':'text',
                                'ui':'checkbox',
                                'values':[
                                        {'value':'single', 'text':'Single'}
                                ],
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
                                'section':'Type specific',
                                'deps':{
                                        'widgetkit_k2':['id:1551', 'id:1552', 'id:1553', 'id:1554', 'id:1555', 'id:1556', 'id:1557', 'id:1558', 'id:1559', 'id:1560', 'id:1561', 'id:1562']
                                }
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
                                'name':'Mode (itemlist)',
                                'optName':'listmode',
                                'valid':'text',
                                'ui':'checkbox',
                                'values':[
                                        {'value':'single', 'text':'Single'}
                                ],
                                'section':'Type specific'
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
                                'tip':'Provide file location relative to site root to image to be used as watermark. Separate with %% for several images.'
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
                                'name':'Watermark font size',
                                'optName':'watermark_font_size',
                                'valid':'integer',
                                'section':'Type specific',
                                'tip':'If field choosen then this size will be applied to the value',
                                'default':12
                        },
                        '1169':{
                                'name':'Image width (px)',
                                'optName':'picwidth',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':500
                        },
                        '1170':{
                                'name':'Image height (px)',
                                'optName':'picheight',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':500
                        },
                        '1171':{
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
                        '1172':{
                                'name':'Thumb - Image width (px)',
                                'optName':'picwidththumb',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':170
                        },
                        '1173':{
                                'name':'Thumb - Image height (px)',
                                'optName':'picheightthumb',
                                'valid':'integer',
                                'section':'Type specific',
                                'default':170
                        },
                        '1174':{
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
                        '1175':{
                                'name':'Media limit',
                                'optName':'medialimit',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':0,
                                'max':100,
                                'tip':'Maximum number of media items per K2 item (0 unlimited)',
                                'default':10
                        },
                        '1176':{
                                'name':'Image size (kb)',
                                'optName':'picsize',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':1,
                                'tip':'Maximum upload size for picture',
                                'default':100,
                                'required':0
                        },
                        '1177':{
                                'name':'Video size (kb)',
                                'optName':'videosize',
                                'valid':'integer',
                                'section':'Type specific',
                                'min':1,
                                'tip':'Maximum upload size for video files',
                                'default':400
                        },
                        '1178':{
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
                                'name':'Date picker theme',
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
                                'section':'Type specific',
                                'required':1
                        },
                        // TODO: testa hur detta beroende håller än idag
                        '1205':{
                                'name':'Start time',
                                'optName':'starttime',
                                'valid':'int',
                                ui:'select',
                                'tip':'Field id of start field. If you want to restrict the start date of this field based on another fields selected date/time (ie. selectable dates will be greater than the constraining field value) provide here the field id of the constraining field.',
                                values:this.options.options['fields'],
                                'section':'Type specific'
                        },
                        '1206':{
                                'name':'End time',
                                'optName':'endtime',
                                'valid':'int',
                                ui:'select',
                                'tip':'Field id of start field. If you want to restrict the end date of this field based on another fields selected date/time (ie. selectable dates will be less than the constraining field value) provide here the field id of the constraining field.',
                                values:this.options.options['fields'],
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
                                'clearopt':'button',
                                'tip':'End of repetition mode - limited by enddate or number of allowed repetitions provided in repetition limit below',
                                'section':'Type specific',
                                'deps': {
                                        'enddate':['id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213'],
                                        'number':['id:1208', 'id:1209', 'id:1210', 'id:1211', 'id:1212', 'id:1213']
                                }
                        },
                        '1208':{
                                'name':'Combine',
                                'optName':'combine',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1209':{
                                'name':'Repeat format',
                                'optName':'repeatformat',
                                'valid':'text',
                                'tip':'Format with which repeated date/time will be shown.',
                                'section':'Type specific'
                        },
                        '1210':{
                                'name':'Repetition limit',
                                'optName':'repeatlistmax',
                                'valid':'integer',
                                'tip':'Number of instances to be shown directly, and eventually remaining repetitions will be collapsed and will be available upon click.',
                                'section':'Type specific'
                        },
                        '1211':{
                                'name':'Show only future',
                                'optName':'repeatexpire',
                                'valid':'verifybox',
                                'tip':'Limits shown repetitions to only those in the future.',
                                'section':'Type specific'
                        },
                        '1212':{
                                'name':'Repeat list mode',
                                'optName':'repeatlist',
                                'valid':'radio',
                                'values':[{'value':'descriptive'}, {'value':'list'}, {'value':'combined', 'text':'both'}],
                                'section':'Type specific',
                                'tip':'When displaying repeated values you might not want to display the list of repeated values rather provide a descriptive text telling the start and end date and intermediary repetition frequencies: ex. From 2013-01-01 repeated every 2 weeks until 2013-12-31. The option descriptive provides such rendering. You can combine both the list and descriptive too.'
                        },
                        '1213':{
                                'name':'Repeat combine',
                                'optName':'repeatcombine',
                                'valid':'verifybox',
                                'tip':'if we have a several repeating instances, in the case where we have a list valued field, and we would want to combine them all to create one single list of event date/times then we would need to provide this option as true',
                                'section':'Type specific'
                        },
                        '1214':{
                                'name':'Expire',
                                'optName':'expire',
                                'valid':'verifybox',
                                'tip':'If the field type is date - then the unpublishing date of the item is set to the value of this field (if several expiring fields are available then the latest date value is considered. If the type of field is a number then the unpublishing date is computed to be the publishing date offset by a number of days of the value of this field. Latest expiring field is considered.',
                                'section':'Type specific'
                        },
                        '1215':{
                                'name':'Week starts on',
                                'optName':'weekstartson',
                                'valid':'integer',
                                'values':[
                                        {'value':1,'text':'Monday'},
                                        {'value':2,'text':'Tueday'},
                                        {'value':3,'text':'Wedensday'},
                                        {'value':4,'text':'Thursday'},
                                        {'value':5,'text':'Friday'},
                                        {'value':6,'text':'Saturday'},
                                        {'value':0,'text':'Sunday'}
                                ],
                                'ui':'select',
                                'sorted':true,
                                'section':'Type specific'
                        },
                        '1216':{
                                'name':'Show count down (diff)',
                                'optName':'showdiff',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1217':{
                                'name':'Count down format',
                                'optName':'diffformat',
                                'valid':'text',
                                'size':60,
                                'section':'Type specific',
                                'tip':'%COUNT%, %DATE% are available placeholders'
                        },
                        '1218':{
                                name:'Adjust field',
                                optName:'adjustfield',
                                valid:'int',
                                ui:'select',
                                selectchosen:1,
                                values:this.options.options['fields'],
                                sorted:true,
                                section:'Type specific',
                                tip:'Same as expire except instead of unpublishing we adjust value of a field upon condition met to expire. You will need to either set the value in the following setting or indicate to be deleted.'
                        },
                        
                        '1219':{
                                'name':'Adjust field to value',
                                'optName':'adjustfieldvalue',
                                'valid':'text',
                                'ui':'textarea',
                                'section':'Type specific'
                        },
                        '1220':{
                                'name':'Adjust field (delete)',
                                'optName':'adjustfielddelete',
                                'valid':'verifybox',
                                'section':'Type specific'
                        },
                        '1221':{
                                'name':'Adjust field to statement',
                                'optName':'adjustfieldstatement',
                                'valid':'text',
                                'ui':'textarea',
                                'section':'Type specific',
                                tip:'Provide a set of PHP statements where the following variables can be assumed and the last statment must return the result'
                        },
                        '1222':{
                                'name':'Start time statement',
                                'optName':'starttimestatement',
                                'valid':'text',
                                ui:'textarea',
                                'tip':'Provide a set of javascript statements with return value being the last one. Statement has higher presedence, ie. if you provide both start time field and statement then statement will be chosen.',
                                'section':'Type specific'
                        },
                        '1223':{
                                'name':'End time statement',
                                'optName':'endtimestatement',
                                'valid':'text',
                                ui:'textarea',
                                'tip':'Provide a set of javascript statements with return value being the last one. Statement has higher presedence, ie. if you provide both start time field and statement then statement will be chosen.',
                                'section':'Type specific'
                        },
                        '1224':{
                                name:'Adjustment condition statement',
                                optName:'adjustmentcondition',
                                valid:'text',
                                ui:'textarea',
                                tip:'$val current value of field, $now is the current time, 2 fields that can be assumed in your PHP statement that must end with a return for the condition.',
                                section:'Type specific'
                        },
                        /*
                        '1225':{
                                name:'Adjustments',
                                optName:'adjustments',
                                subfields:[
                                        {name:'Field', valid:'int', ui:'select', values:this.options.options['fields'], sorted:true},
                                        {name:'Operator', valid:'text', ui:'radio', values:['>', '>=', '<', '<=', '='], clearopt:'button'},
                                        {name:'Value', valid:'text'},
                                        {name:'Value statement', valid:'text', ui:'textarea'},
                                        {name:'Delete', valid:'verifybox'}
                                ],
                                valid:'complex',
                                section:'Type specific',
                                list:'normal',
                                tip:'Fields to adjust as a result of time shift. Provide the field and the '
                        },*/
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
                                        {value:'openlayers',text:'openlayers'}, 
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
                                'name':'Map provider (itemlist)',
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
                        '1397':{
                                'name':'Map controls (item)',
                                'optName':'mapcontrols',
                                'valid':'text',
                                'section':'Type specific',
                                'values':['pan', 'zoom large', 'zoom small', 'maptype', 'overview'],
                                'ui':'checkbox'
                        },
                        '1398':{
                                'name':'Map controls (itemlist)',
                                'optName':'mapcontrolsitemlist',
                                'valid':'text',
                                'section':'Type specific',
                                'values':['pan', 'zoom large', 'zoom small', 'maptype', 'overview'],
                                'ui':'checkbox'
                        },
                        '1399':{
                                'name':'Show in map button',
                                'optName':'mapshowinmapbtn',
                                'valid':'verifybox',
                                'section':'Type specific',
                                'default':1
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
                                'tip':'Each source need to avail its values in suitable format as specified along with each source type. Value is a required part of each instance.'
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
                                'name':'File location',
                                'optName':'values',
                                'ui':'text',
                                'section':'Values',
                                'valid':'text',
                                'size':100,
                                'tip':'File location must be on site and relative to site root. Each row in file need to adhere to the following format where only value column is mandatory: value==text==img'
                        },
                        '1406':{
                                'name':'Specify',
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
                                'tip':'Globally accessible function or static class members with absolute calls'
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
                        '15011':{
                                name:'Low JavaScript statement',
                                optName:'low.statement',
                                valid:'text',
                                ui:'textarea',
                                section:'Type specific',
                                tip:'Provide a set of javascript statements with return value being the last one. Statement has higher presedence, ie. if you provide both value and statement then statement will be chosen.'
                        },
                        '1502':{
                                name:'High',
                                optName:'high',
                                valid:'integer',
                                section:'Type specific'
                        },
                        '15021':{
                                name:'High JavaScript statement',
                                optName:'high.statement',
                                valid:'text',
                                ui:'textarea',
                                section:'Type specific',
                                tip:'Provide a set of javascript statements with return value being the last one. Statement has higher presedence, ie. if you provide both value and statement then function will be chosen.'
                        },
                        '1503':{
                                name:'Step',
                                optName:'step',
                                valid:'integer',
                                section:'Type specific'
                        },
                        '15031':{
                                name:'Step JavaScript statement',
                                optName:'step.statement',
                                valid:'text',
                                ui:'textarea',
                                section:'Type specific',
                                tip:'Provide a set of javascript statements with return value being the last one. You can use the variables v which is the current value to be stepped and s which is the step value you may or may not provide above. Statement has higher presedence, ie. if you provide both value and statement then function will be chosen.'
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
                        },
                        '1551':{
                                name:'Autoplay',
                                optName:'widgetkit_k2_autoplay',
                                valid:'yesno',
                                section:'Type specific'
                        },
                        '1552':{
                                name:'Order',
                                optName:'widgetkit_k2_order',
                                valid:'text',
                                ui:'radio',
                                values:['default', 'random'],
                                section:'Type specific'
                        },
                        '1553':{
                                name:'Autoplay Interval (ms)',
                                optName:'widgetkit_k2_interval',
                                valid:'integer',
                                section:'Type specific',
                                'default':5000
                        },
                        '1554':{
                                name:'Effect Duration (ms)',
                                optName:'widgetkit_k2_duration',
                                valid:'integer',
                                section:'Type specific',
                                'default':500
                        },
                        '1555':{
                                name:'Start Index',
                                optName:'widgetkit_k2_index',
                                valid:'integer',
                                section:'Type specific',
                                'default':0
                        },
                        '1556':{
                                name:'Navigation',
                                optName:'widgetkit_k2_navigation',
                                valid:'integer',
                                values:[{'text':'Show', 'value':1}, {'text':'Hide', 'value':0}],
                                ui:'radio',
                                section:'Type specific',
                                'default':1
                        },
                        '1557':{
                                name:'Buttons',
                                optName:'widgetkit_k2_buttons',
                                valid:'integer',
                                values:[{'text':'Show', 'value':1}, {'text':'Hide', 'value':0}],
                                ui:'radio',
                                section:'Type specific',
                                'default':1
                        },
                        '1558':{
                                name:'Slices',
                                optName:'widgetkit_k2_slices',
                                valid:'integer',
                                section:'Type specific',
                                'default':0
                        },
                        '1559':{
                                name:'Effect',
                                optName:'widgetkit_k2_animated',
                                valid:'text',
                                values:[{'text':'Fade','value':'fade'}, {'text':'Slide','value':'slide'}, {'text':'Scroll','value':'scroll'}, {'text':'Swipe','value':'swipe'}, {'text':'SliceUp','value':'sliceUp'}, {'text':'SliceDown','value':'sliceDown'}, {'text':'SliceUpDown','value':'sliceUpDown'}, {'text':'Fold','value':'fold'}, {'text':'Puzzle','value':'puzzle'}, {'text':'Boxes','value':'boxes'}, {'text':'BoxesReverse','value':'boxesReverse'}, {'text':'KenBurns','value':'kenburns'}, {'text':'Rotate','value':'rotate'}, {'text':'Scale','value':'scale'}, {'text':'RandomSimple','value':'randomSimple'}, {'text':'RandomFx','value':'randomFx'}],
                                ui:'select',
                                section:'Type specific',
                                'default':'fade'
                        },
                        '1560':{
                                name:'Caption Animation Duration',
                                optName:'widgetkit_k2_caption_animation_duration',
                                valid:'integer',
                                section:'Type specific',
                                'default':500
                        },
                        '1561':{
                                name:'Lightbox',
                                optName:'widgetkit_k2_lightbox',
                                valid:'integer',
                                values:[{'text':'Show', 'value':1}, {'text':'Hide', 'value':0}],
                                ui:'radio',
                                section:'Type specific',
                                'default':0
                        },
                        '1562':{
                                name:'Style',
                                optName:'widgetkit_k2_style',
                                valid:'text',
                                values:['default', 'inside', 'inspire', 'radiance', 'revista_default', 'screen', 'showcase', 'showcase_box', 'slider', 'slideset', 'subway', 'wall'],
                                ui:'select',
                                section:'Type specific',
                                'default':0
                        },
                        '1601':{
                                name:'Show send button',
                                optName:'facebooksend',
                                valid:'yesno',
                                ui:'radio',
                                section:'Type specific',
                                'default':0,
                                tip:'The Send Button allows users to easily send content to their friends. People will have the option to send your URL in a message to their Facebook friends, to the group wall of one of their Facebook groups, and as an email to any email address. While the Like Button allows users to share content with all of their friends, the Send Button allows them to send a private message to just a few friends.'
                        },
                        '1602':{
                                name:'Layout style',
                                optName:'facebooklayout',
                                valid:'text',
                                ui:'radio',
                                section:'Type specific',
                                'default':'standard',
                                values:['standard', 'box_count', 'button_count'],
                                tip:'Standard - displays social text to the right of the button and friends profile photos below. Button count - displays the total number of likes to the right of the button. Box count - displays the total number of likes above the button.'
                        },
                        '1603':{
                                name:'Show faces (standard layout only)',
                                optName:'facebookshow_faces',
                                valid:'yesno',
                                ui:'radio',
                                section:'Type specific',
                                'default':0,
                                tip:'Display profile photos below the button.'
                        },
                        '1604':{
                                name:'Width',
                                optName:'facebookwidth',
                                valid:'integer',
                                ui:'text',
                                section:'Type specific',
                                'default':450
                        },
                        '1605':{
                                name:'Action',
                                optName:'facebookaction',
                                valid:'text',
                                ui:'radio',
                                values:['like', 'recommend'],
                                section:'Type specific',
                                'default':'like'
                        },
                        '1606':{
                                name:'Fonts',
                                optName:'facebookfont',
                                valid:'text',
                                ui:'radio',
                                values:['arial', 'lucida grande', 'segoe ui', 'tahoma', 'trebuchet ms', 'verdana'],
                                section:'Type specific',
                                'default':'',
                                'clearopt':'button'
                        },
                        '1607':{
                                name:'Color scheme',
                                optName:'facebookcolorscheme',
                                valid:'text',
                                ui:'radio',
                                values:['light', 'dark'],
                                section:'Type specific',
                                'default':'light'
                        },
                        '1608':{
                                name:'Appid',
                                optName:'facebookappid',
                                valid:'text',
                                size:'50',
                                section:'Type specific'
                        },
                        '1651':{
                                name:'Twitter text',
                                optName:'twittertext',
                                valid:'text',
                                section:'Type specific',
                                'default':'Tweet'
                        },
                        '1652':{
                                name:'Show counter',
                                optName:'twittercounter',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'','text':'Counter'}, {'value':'none','text':'None'}],
                                section:'Type specific',
                                'default':''
                        },
                        '1653':{
                                name:'Via twitter ID',
                                optName:'twittervia',
                                valid:'text',
                                section:'Type specific'
                        },
                        '1654':{
                                name:'Related twitter ID',
                                optName:'twitterrelated',
                                valid:'text',
                                section:'Type specific'
                        },
                        '1655':{
                                name:'Related twitter hash (without trailing #)',
                                optName:'twitterhash',
                                valid:'text',
                                section:'Type specific'
                        },
                        '1656':{
                                name:'Twitter button',
                                optName:'twitterbutton',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'','text':'Normal'}, {'value':'large','text':'Large'}],
                                section:'Type specific',
                                'default':''
                        },
                        '1701':{
                                name:'Counter position',
                                optName:'linkedincounter',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'right','text':'Right'}, {'value':'top','text':'Top'}, {'value':'none','text':'None'}],
                                section:'Type specific',
                                'default':'right'
                        },
                        '1751':{
                                name:'Counter position',
                                optName:'pinterestcounter',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'horizontal','text':'Right'}, {'value':'vertical','text':'Top'}, {'value':'none','text':'None'}],
                                section:'Type specific',
                                'default':'horizontal'
                        },
                        '1752':{
                                name:'Description',
                                optName:'pinterestdescription',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'text','text':'Text'}, {'value':'item','text':'Item'}, {'value':'image','text':'Image'}],
                                section:'Type specific',
                                'default':'text',
                                deps:{
                                        'text':['id:1753']
                                }
                        },
                        '1753':{
                                name:'Description text',
                                optName:'pinterestdescriptiontext',
                                valid:'text',
                                section:'Type specific',
                                size:50
                        },
                        '1801':{
                                name:'Annotation position',
                                optName:'googleplusannotation',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'inline','text':'Inline'}, {'value':'bubble','text':'Bubble'}, {'value':'none','text':'None'}],
                                section:'Type specific',
                                'default':'inline'
                        },
                        '1802':{
                                name:'Button size',
                                optName:'googleplusbuttonsize',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'small','text':'Small(15px)'}, {'value':'medium','text':'Medium(20px)'}, {'value':'standard','text':'Standard(24px)'}, {'value':'tall','text':'Tall(60px)'}],
                                section:'Type specific',
                                'default':'medium'
                        },
                        '1851':{
                                name:'Read Now/Later',
                                optName:'readabilityread',
                                valid:'yesno',
                                section:'Type specific',
                                'default':0
                        },
                        '1852':{
                                name:'Print',
                                optName:'readabilityprint',
                                valid:'yesno',
                                section:'Type specific',
                                'default':0
                        },
                        '1853':{
                                name:'Email',
                                optName:'readabilityemail',
                                valid:'yesno',
                                section:'Type specific',
                                'default':0
                        },
                        '1854':{
                                name:'Send to Kindle',
                                optName:'readabilitykindle',
                                valid:'yesno',
                                section:'Type specific',
                                'default':0
                        },
                        '1855':{
                                name:'Text color',
                                optName:'readabilitycolortext',
                                valid:'text',
                                section:'Type specific',
                                'default':'#5c5c5c'
                        },
                        '1856':{
                                name:'Background color',
                                optName:'readabilitycolorbg',
                                valid:'text',
                                section:'Type specific',
                                'default':'#5c5c5c'
                        },
                        '1857':{
                                name:'Orientation',
                                optName:'readabilityorientation',
                                valid:'text',
                                ui:'radio',
                                values:[{'value':'0','text':'Horizontal'}, {'value':'1','text':'Vertical'}],
                                section:'Type specific',
                                'default':'0'
                        },
                        '1901':{
                                name:'A Flattr username',
                                optName:'flattruid',
                                valid:'text',
                                section:'Type specific',
                                tip:'This is a required parameter for autosubmit but not for things that are already on flattr.com.'
                        },
                        '1902':{
                                name:'Title (replacing current item title)',
                                optName:'flattruid',
                                valid:'text',
                                section:'Type specific',
                                tip:'Will be used to describe your thing on Fattr. The title should be between 5-100 characters. All HTML is stripped. This is a required parameter for autosubmit but not for things that are already on flattr.com.'
                        },
                        '1903':{
                                name:'Description (replacing current item meta tag)',
                                optName:'flattrdescription',
                                valid:'text',
                                ui:'textarea',
                                section:'Type specific',
                                tip:'Will be used to describe your thing. The description should be between 5-1000 characters. All HTML is stripped except the &lt;br\&gt; character which will be converted into newlines (\n). This is a required parameter for autosubmit but not for things that are already on flattr.com.'
                        },
                        '1904':{
                                name:'Category',
                                optName:'flattrcategory',
                                valid:'text',
                                ui:'select',
                                values:[{'value':'text','text':'Text'}, {'value':'images','text':'Images'}, {'video':'standard','text':'Video'}, {'value':'audio','text':'Audio'}, {'value':'software','text':'Software'}, {'value':'people','text':'People'}, {'value':'rest','text':'Other'}],
                                section:'Type specific',
                                tip:'This parameter is used to sort things on Flattr and has no impact on the functionality of your button.',
                                'default':'text'
                        },
                        '1905':{
                                name:'Button type',
                                optName:'flattrbutton',
                                valid:'text',
                                ui:'select',
                                values:[{'value':'','text':'Normal'}, {'value':'compact','text':'Compact'}],
                                section:'Type specific',
                                tip:'This parameter is used to sort things on Flattr and has no impact on the functionality of your button.',
                                'default':''
                        },
                        '1906':{
                                name:'Hidden',
                                optName:'flattrhidden',
                                valid:'text',
                                ui:'select',
                                values:[{'value':'','text':'Listed'}, {'value':'1','text':'Hidden'}],
                                section:'Type specific',
                                tip:'Not all content is suitable for public listing. If you for one reason or another do not want your content to be listed on Flattr set this parameter to hidden.',
                                'default':''
                        },
                        '1951':{
                                'name':'E-mail address',
                                'optName':'email',
                                'valid':'email',
                                'section':'Type specific',
                                'size':50
                        },
                        '1952':{
                                'name':'Form',
                                'optName':'menu',
                                'valid':'integer',
                                'ui':'select',
                                'values':this.options.options['menuitems'],
                                'tip':'menu item for the form to connect',
                                'section':'Type specific'
                        },
                        '1953':{
                                'name':'Modal width(px)',
                                'optName':'width',
                                'valid':'integer',
                                'section':'Type specific'
                        },
                        '1954':{
                                'name':'Modal height(px)',
                                'optName':'height',
                                'valid':'integer',
                                'section':'Type specific'
                        },
                        '1956':{
                                'name':'Form title',
                                'optName':'formtitle',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'Placeholders %title% and %category% can be used and will be replaced automatically when providing the values to your form component by item title and category title respectively',
                                'section':'Type specific'
                        },
                        '1957':{
                                'name':'Form footer',
                                'optName':'formfooter',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'Placeholders %title% and %category% can be used and will be replaced automatically when providing the values to your form component by item title and category title respectively',
                                'section':'Type specific'
                        },
                        '1958':{
                                'name':'Link title',
                                'optName':'title',
                                'valid':'text',
                                'ui':'textarea',
                                'tip':'Placeholders %title% and %category% can be used and will be replaced automatically when providing the values to your form component by item title and category title respectively',
                                'section':'Type specific'
                        },
                        '2001':{
                                'name':'Show map as (TBI)',
                                'optName':'showmapas',
                                'valid':'text',
                                'values':['link', 'staticmap', 'map', 'label'],
                                'ui':'radio',
                                'tip':'When in itemlist view and field is available in itemlist mode and map layout is not set how should we render map',
                                'section':'Type specific'
                        }
             };
        }
});