//$Copyright$
var k2fields_type_list = {
        createSearchList: function(holder, proxyField, value, condition) {
                var op = this.getOpt(proxyField, 'autocomplete');
                if (op) {
                        var tree = this.getOpt(proxyField, 'tree');
                        if (!tree) {
                                this.autoComplete(proxyField, true, holder);
                        } else {
                                var vh = this.ccf(
                                        proxyField, 
                                        '', 
                                        0, 
                                        'text', 
                                        '', 
                                        holder, 
                                        'input', 
                                        {type:'text', 'ignore':true}, 
                                        undefined, 
                                        undefined, 
                                        false, 
                                        false, 
                                        true
                                );
                                vh = vh[0];
                                var sfn = function(el) {
                                        el = this._tgt(el);
                                        var v = el.get('value');
                                        var proxyField = document.id(this.getProxyFieldId(el));
                                        var minLength = this.getOpt(proxyField, 'acminchars', null, 3);
                                        if (v.length < minLength) return;
                                        var pv = el.retrieve('_search_');
                                        if (pv == v) return;
                                        el.store('_search_', v);
                                        v = this._listSearch(tree, v, op);
                                        if (typeOf(v) == 'array' &&  v.length == 0) v = null;
                                        var holder = this.disposeValueHolder(el);
                                        this.createList(holder, proxyField, v);
                                }.bind(this);
                                vh.addEvents({
                                        change:function(el) {sfn(el);}.bind(this),
                                        blur:function(el) {sfn(el); 
                                                el = this._tgt(el);
                                                el.removeClass('progress');
                                        }.bind(this)
                                }).addEvent(
                                        ((Browser.ie || Browser.safari || Browser.chrome) ? 'keydown' : 'keypress'),
                                        function(el) { 
                                                el = this._tgt(el);
                                                el.addClass('progress');
                                                sfn(el); 
                                        }.bind(this)
                                );
                        }
                }
                
                return this.createList(holder, proxyField, value, condition);
        },
        
        createList: function(holder, proxyField, value, condition) {
                var isDefault = false;
                
                if (!value) {
                        value = this.getOpt(proxyField, 'default', null, false);
                        isDefault = value != undefined;
                }
                
                if (value) {
                        var values = typeOf(value) == 'string' ? value.split(this.options.valueSeparator) : value, i, n = values.length, leaf;
                        
                        for (i = n - 1; i >= 0; i--) {
                                leaf = values[i];
                                
                                if (leaf != null) {
                                        break;
                                }
                        }
                        
                        return this.createListUI('path', holder, proxyField, values, undefined, leaf, undefined, isDefault);
                } else {
                        return this.createListUI('', holder, proxyField);
                }
        },
        
        _listFindNodes:function(tree, col, val, strict) {
                var i, n = tree.length, r = [];
                for (i = 0; i < n; i++) {
                        if (tree[i][col] == val) {
                                if (col == 'value') {
                                        return tree[i];
                                } else if (col == 'parent_id') {
                                        if (tree[i]['value'] != val || !strict) r.push(tree[i]);
                                } else {
                                        r.push(tree[i]);
                                }
                        }
                }
                return col == 'value' ? r[0] : r;
        },
        _listFindNode:function(tree, node) {return this._listFindNodes(tree, 'value', node);},
        _listFindChildren:function(tree,node,strict) {return this._listFindNodes(tree, 'parent_id', node, strict);},
        _listFindRoot:function(tree) {return this._listFindNodes(tree, 'depth', 0);},
        _listFindSyblings:function(tree, node) {
                if (node['depth'] == 0) {
                        return this._listFindNodes(tree, 'depth', 0);
                } else {
                        return this._listFindChildren(tree, 'parent_id', node['parent_id']);
                }
        },
        _listSearch:function(tree, val, op, col, caseSensitive, resultCol, returnPath, exhaust) {
                if (op == undefined) op = 'm';
                if (col == undefined) col = 'text';
                if (caseSensitive == undefined) caseSensitive = false;
                if (resultCol == undefined) resultCol = 'value';
                if (returnPath == undefined) returnPath = true;
                if (exhaust == undefined) exhaust = false;
                var i, n = tree.length, r = [], c;
                if (!val) return r;
                var re = new RegExp(
                        (op == 'f' || op == 's' ? '^' : '') + val +
                        (op == 'f' || op == 'e' ? '$' : ''),
                        (caseSensitive ? '' : 'i')
                );
                for (i = 0; i < n; i++) {
                        c = tree[i];
                        if (re.test(c[col])) {
                                r.push(resultCol ? c[resultCol] : c);
                                if (returnPath) {
                                        while (c && c['depth'] > 0) {
                                                c = this._listFindNode(tree, c['parent_id']);
                                                if (c) r.push(resultCol ? c[resultCol] : c);
                                        }
                                        r = r.reverse();
                                }
                                if (!exhaust || returnPath) break;
                        }
                }
                return r;
        },
        
        createListUI: function(task, holder, proxyField, value, level, node, async, isUpdateProxy) {
                if (!node && level == undefined) level = 0;
                
                var tree = this.getOpt(proxyField, 'tree');
                
                if (tree) {
                        var r = [];
                        if (task == 'path' && value) {
                                var v = Array.from(value), mv;
                                for (var i = v.length - 1; i >= 0; i--) if (!v[i]) v.erase(v[i]);
                                v = v[v.length - 1];
                                if (this.chkOpt(proxyField, 'multiple', '1')) {
                                        v = v.split(this.options.multiValueSeparator);
                                        mv = v;
                                        v = v[0]; // Any will do as all multiple values should be on the same level
                                }                                
                                var c = this._listFindNode(tree, v);
                                
                                if (c) {
                                        if (c['depth'] > 0) {
                                                v = [];
                                                while (c && c['depth'] > 0) {
                                                        v[c['depth']] = c['value'];
                                                        c = this._listFindChildren(tree, c['parent_id']);
                                                        if (c) {
                                                                r.push(c);
                                                                c = this._listFindNode(tree, c[0]['parent_id']);
                                                        }
                                                }
                                                c = this._listFindRoot(tree);
                                                r.push(c);
                                                r = r.flatten();
                                                v[0] = c[0]['value'];
                                        } else {
                                                r = this._listFindSyblings(tree, c);
                                        }
                                        
                                        if (this.chkOpt(proxyField, 'multiple', '1')) v = value;
                                }
                        }
                        
                        if (task != 'path' || !value || !r) {
                                if (node) r = this._listFindChildren(tree, node, true);
                                else r = this._listFindRoot(tree);
                                v = value;
                        }
                        
                        c = this.getOpt(proxyField, 'root');
                        
                        if (c) c = this._listFindNode(tree, c);
                        
                        if (task == 'path') {
                                return this.createListUIPath(r, proxyField, v, holder, isUpdateProxy, c);
                        } else {
                                return this.createListUIBasic(r, proxyField, v, level, holder);
                        }
                }
                
                if (async && !this.options.async || async == undefined) {
                        async = this.options.async;
                }
                
                var 
                        fieldId = proxyField.get('id').match(new RegExp(this.options.pre+'(\\d+)'))[1],
                        url = 'index.php?option=com_k2fields&view=field&task=retrieve'+task+'&id='+fieldId;
                        
                if (node) {
                        url += '&node=' + node;
                }
                
                if (level != null) {
                        url += '&level='+level;
                }
                
                new Request.JSON({
                        url: url, 
                        async: async,
                        onComplete: function(response){
                                if (task == 'path') {
                                        return this.createListUIPath(response, proxyField, value, holder, isUpdateProxy);
                                } else {
                                        return this.createListUIBasic(response, proxyField, value, level, holder);
                                }
                        }.bind(this)
                }).send();
        },
        
        createListUIPath: function(options, proxyField, value, holder, isUpdateProxy, root) {
                if (!options || options.length == 0) return;
                
                if (value && typeOf(value) != 'array') value = value.split(this.options.valueSeparator);
                
                var opts = [], uis = [], _uis, depth, i, n = options.length;
                
                for (i = 0; i < n; i++) {
                        depth = options[i]['depth'];
                        
                        if (!opts[depth]) opts[depth] = [];
                        
                        opts[depth].push(options[i]);
                }
                
                holder = new Element('span').inject(holder);
                root = root ? root['depth'] + 1 : 0;
                
                for (i = root, n = opts.length; i < n; i++) {
                        _uis = this.createListUIBasic(opts[i], proxyField, value ? value[i] : '', i, holder, i == n-1);
                        
                        if (_uis) 
                                uis = uis.combine(_uis);
                }
                
                if (isUpdateProxy && uis.length > 0) this.setProxyFieldValue(uis[0]);
                
                return uis;
        },
        
        createListUIBasic: function(options, proxyField, value, level, holder, isLastFromPath) {
                if (typeof options == 'string') options = Json.evaluate(options);
                
                if (options.length == 0) return;
                
                var maxLevel = this.getOpt(proxyField, 'maxlevel');
                
                if (maxLevel && maxLevel <= level) {
                        return;
                }

                var 
                        ui = this.getOpt(proxyField, 'ui'), 
                        type = ui == 'checkbox' || ui == 'radio' ? 'input' : 'select',
                        typeOptions = {valid:type, values:options, valueName:'value', textName:'text', imageName:'img'}
                        ;
                     
                if (type == 'select') {
                        var levels = this.getOpt(proxyField, 'levels'), levelLabel;

                        if (levels && levels[level]) levelLabel = levels[level];
                        else levelLabel = this.getOpt(proxyField, 'name') + ' - ' + level;

                        typeOptions['label'] = levelLabel;
                }
                
                this.setOpts(proxyField, typeOptions, false);
                
                if (value && this.chkOpt(proxyField, 'multiple', 'true') && typeOf(value) != 'array') {
                        value = value.split(this.options.multiValueSeparator);
                }
                
                var uis = this.createBasic(holder, proxyField, value, null, level, undefined, {subfieldof:proxyField.get('id'),showlabel:!this.isMode('search')||this.getOpt(proxyField, 'autocomplete')==undefined});
                
                this.setOpts(proxyField, {valid:'list', values:undefined, sorted:undefined, label:undefined}, false);
                
                uis.each(function(ui) {
                        ui.addEvent('change', function(e) {
                                e = this._tgt(e);

                                var el = e.getParent('div').getNext(), proxy, _el;

                                while (el) {
                                        _el = el.getElement('[customvalueholder=true]');
                                        
                                        if (!_el || this.getProxyFieldId(_el) != this.getProxyFieldId(e)) break;
                                        
                                        proxy = null;
                                        
                                        if (el.getElement('select'))
                                                proxy = document.id(el.getElement('select').get('name')+this.options.pre);
                                        if (proxy) 
                                                proxy.dispose();
                                        el.dispose();
                                        
                                        el = e.getParent('div').getNext();
                                }

                                var selected = this.getValue(e);
                                
                                if (_el && this.getProxyFieldId(_el) != this.getProxyFieldId(e)) {
                                        _el = el.clone(false);
                                        _el.inject(el, 'before');
                                        holder = _el;
                                }
                                
                                if (selected) this.createListUI('', holder, proxyField, '', level + 1, selected);
                                
                                this.setProxyFieldValue(e);
                        }.bind(this));
                        
                        // since event was added after the value have been assigned
                        // we need to manually fire the change event if there was non-empty value
                        if (value && isLastFromPath) ui.fireEvent('change', [ui]);
                }.bind(this));
                
                return uis;                
        }
};