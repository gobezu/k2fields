//$Copyright$

var k2fields_type_media = {
        createSearchMedia: function(holder, proxyField, value, condition) {
                value = value.split(this.options.valueSeparator);
                this.ccf(proxyField, value[3], 3, 'checkbox', '', holder, 'input', {type:'checkbox',values:[{text:'Contains',value:'exists'}]});
        },
        
        createMedia: function(holder, proxyField, value, condition) {
                var isNew = !value;
                
                value = isNew ? ['', '', '', ''] : value.split(this.options.valueSeparator);
                
                var sources = this.createMediaObject('sources', value, proxyField, holder);
                
                if (sources === false) return [];
                
                var result = sources[0];
                
                sources = sources[0];
                
                var tmp = this.createMediaObject('types', value, proxyField, holder);
                
                if (tmp) result.combine(tmp[0]);
                
                if (sources[1] || !isNew) {
                        tmp = this.createMediaDo(holder, proxyField, value, sources[0]);
                        if (tmp) result.combine(tmp);
                }
                
                return result.flatten();
        },
        
        createMediaObject: function(type, values, proxyField, holder) {
                var orig = this.getOpt(proxyField, 'media'+type);
                
                if (!orig || orig.length == 0) return false;
                
                var result = [];
                
                for (var i = 0; i < orig.length; i++) result.push({value:orig[i], text:orig[i]});
                
//                if (orig == 'all') {
//                        orig = this.options['media'+type.toLowerCase().capitalize()];
//                        
//                        for (var k in orig) result.push({value:k, text:orig[k]});
//                } else {
//                        var src = this.options['media'+type.toLowerCase().capitalize()];
//                        
//                        for (var i = 0; i < orig.length; i++) result.push({value:orig[i], text:src[orig[i]]});
//                }
                
                var position = type == 'sources' ? 0 : 1;
                var lbl = type.toLowerCase().capitalize().substr(0, type.length - 1);
                
                result = this.ccf(proxyField, values[position], position, false, lbl, holder, 'select', {values:result, first:'-- Select '+lbl.toLowerCase()+' --'}, undefined, undefined, undefined, false);
                
                if (orig.length == 1) {
                        result.each(function(el) {
                                el.getParent().setStyle('display', 'none');
                                this.setValue(el, orig[0]);
                        }.bind(this));
                }
                
                if (type == 'sources') {
                        result.each(function(el) {
                                el.addEvent('change', function(e) {
                                        e = this._tgt(e);
                                        this.createMediaDo(holder, proxyField, ['', '', '', ''], e);
                                }.bind(this));
                        }.bind(this));
                } else if (type == 'types') {
                        result.each(function(el) {
                                el.getParent().setStyle('display', 'none');
                        }.bind(this));
                }
                
                return [result, orig.length == 1];
        },

        createMediaDo: function(holder, proxyField, values, source) {
                var el;
                
                while (el = document.id(source).getParent().getNext().getNext()) {
                        el.dispose();
                }
                
                var src = this.getValue(source);
                
                if (!src) return [];
                
                var result = [];
                
                holder = new Element('span').inject(holder);
                
                var pos = 2, value = values[pos+1], isDefined = (value != undefined && value != '');
                
                if (src != 'embed' && src != 'provider') {
                        if (!isDefined) {
                                result.push(this.ccf(proxyField, '', pos, false, 'Use file name as caption', holder, 'input', {type:'checkbox', values:[{value:'filenameascaption',text:' '}]}));
                        } else {
                                result.push(this.ccf(proxyField, values[pos], pos, false, '', holder, 'input', 'hidden', true, undefined, true));
                        }
                        
                        pos += 1;
                }
                
                switch (src) {
                        case 'remote':
                                var allow = this.getOpt(proxyField, 'remotedlallowed');

                                if (allow && !isDefined) {
                                        result.push(this.ccf(proxyField, '', pos-1, false, 'Download remote file locally', holder, 'input', {type:'checkbox', values:['remotedl']}, undefined, ','));
                                }
                                
                                if (!isDefined) {
                                        result.push(this.ccf(proxyField, '', pos, false, 'Remote', holder, 'input', {size:80}));
                                } else {
                                        result.push(this.ccf(proxyField, value, pos, false, '', holder, 'input', 'hidden', true, undefined, true));
                                }
                                
                                break;
                        case 'provider':
                                var providers = this.getOpt(proxyField, 'avproviders');
                                if (!providers) return;
                                result.push(this.ccf(proxyField, values[pos], pos, false, 'Content provider', holder, 'select', {values:providers, first:'-- Select provider --'}));
                                result.push(this.ccf(proxyField, values[pos+1], pos+1, false, 'Content ID', holder, 'input', {size:50}));
                                break;
                        case 'embed':
                                result.push(this.ccf(proxyField, value, pos, false, 'Embed', holder, 'textarea', {cols:54, rows:6}));
                                break;
                        case 'browse':
                                var files = this.getOpt(proxyField, 'browsablefiles');
                                
                                if (!isDefined && files == undefined) return;
                                
                                if (!isDefined) {
                                        result.push(this.ccf(proxyField, '', pos, false, 'Browsable files', holder, 'select', {values:files, first:'-- Select file --'}));
                                } else {
                                        result.push(this.ccf(proxyField, value, pos, false, '', holder, 'input', 'hidden', true, undefined, true));
                                }
                                
                                break;
                        case 'upload':
                                if (!isDefined) {
                                        result.push(this.ccf(proxyField, '', pos, false, 'Upload', holder, 'input', 'file'));
                                } else {
                                        result.push(this.ccf(proxyField, value, pos, false, '', holder, 'input', 'hidden', true, undefined, true));
                                }
                                
                                break;
                }
                
                if (src == 'upload' || src == 'remote' || src == 'browse') {
                        pos += 1;
                        
                        if (!isDefined) {
                                if (src == 'upload' || src == 'remote') {
                                        result.push(this.ccf(proxyField, '', pos, false, 'Thumbnail', holder, 'input', {type:'file', thumb:true}, undefined, undefined, undefined, false));
                                } else {
                                        result.push(this.ccf(proxyField, '', pos, false, '', holder, 'input', 'hidden', true, undefined, true));
                                }
                        } else {
                                result.push(this.ccf(proxyField, values[pos], pos, false, '', holder, 'input', 'hidden', true, undefined, true));
                        }
                        
                        if (values[pos]) {
                                var thmb = new Element('img', {src:this.options.base+values[pos]});
                                thmb.inject(holder);
                        }
                }
                
                if (!this.chkOpt(proxyField, 'list', ['normal', 'conditional']) && isDefined) {
                        var fieldId = proxyField.get('id');
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
                                                this.createField(proxyField);
                                        }.bind(this)
                                }
                        })).inject(holder);
                }
                
                return result;
        },
        
        createThumb: function(mediaTypeSelector, value) {
                mediaTypeSelector = document.id(mediaTypeSelector);
                
                if (typeOf(mediaTypeSelector) == 'event') {
                        mediaTypeSelector = this._tgt(mediaTypeSelector);
                }
                
                var 
                        sourceSelector = mediaTypeSelector.getParent().getPrevious().getElement('select'),
                        source = this.getValue(sourceSelector);
                        
                if (source == 'embed' || source == 'provider') return;
                
                var                         
                        pos = 3,
                        proxyField = this.getProxyFieldId(mediaTypeSelector), 
                        holder = mediaTypeSelector.getParent().getNext();

                return [this.ccf(proxyField, value, pos, false, 'Thumbnail (optional, can be auto created)', holder, 'input', 'file')];
        }        
};