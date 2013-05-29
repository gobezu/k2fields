//$Copyright$

var k2fields_type_k2item = {
        createSearchK2item: function(holder, proxyField, value, condition) {
                //this.setOpt(proxyField, 'ui', 'autocomplete')
                return this.createK2item(holder, proxyField, value, condition);
        },

        setK2itemValue: function(field) {
                field = document.id(field);

                if (field.get('propagate') == 'false') return;

                var to = field.getNext();

                if (to) {
                        if (to.get('tag') != 'input') to = to.getElement('input');
                } else {
                        return;
                }

                var val = field.get('value');

                if (!val) {
                        to.set('value', '');
                        return;
                }

                var coId = this.getProxyFieldId(field), co = this.completers[coId], url = co.request.options.url;

                url += '&reverse=true&value='+val;

                if (to.retrieve('req') == url) return;

                to.store('req', url);

                new Request.JSON({
                        url:url,
                        async:this.options.async,
                        onComplete:function(data) {
                                to.set('value', data['value']);
                        }.bind(this)
                }).send();
        },

        createK2item: function(holder, proxyField, value, condition) {
                var values, ui = this.getOpt(proxyField, 'ui', null, 'autocomplete'), items = this.getOpt(proxyField, 'items');

                if (ui == 'autocomplete' || !items || items.length == 0) {
                        values = value ? value.split(this.options.valueSeparator) : ['', ''];

                        var vh = this.ccf(proxyField, values[0], 0, false, '', holder, 'input', 'hidden', false);

                        vh = vh[0];

                        if (!this.getOpt(proxyField, 'autocomplete')) this.setOpt(proxyField, 'autocomplete', 'm');

                        var tf = this.autoComplete(proxyField, true, holder, false, undefined, {to:vh,attr:'ovalue',event:'change'}, values[1]);

                        if (!tf) {
                                vh.set('type', 'text');
                                tf = vh;
                        }

                        vh.inject(tf.getParent());

                        return [vh, tf];
                }

                var
                        item,
                        typeOptions = {type:'select', valueName:'ovalue', textName:'value', first:'-- Select item --'},
                        multiple = this.getOpt(proxyField, 'multiple'),
                        catId = -1, values = [], title, catCnt = 0;
                        ;

                if (multiple == '1')
                        typeOptions['multiple'] = true;

                for (var i = 0, n = items.length; i < n; i++) {
                        item = items[i];

                        if (catId != item['catid']) {
                                catId = item['catid'];
                                values.push({'label':item['cattitle']});
                                catCnt++;
                        }

                        if (item['value'].length > 50)
                                item['value'] = item['value'].substr(0, 50) + '...';

                        values.push(item);
                }

                if (catCnt == 1) {
                        delete values[0];
                        values = values.clean();
                        typeOptions['first'] = '-- Select '+this.getOpt(proxyField, 'name')+' --';
                }

                typeOptions['values'] = values;

                if (value) {
                        value = value.split(this.options.valueSeparator);
                        value = value[0];
                }

                return this.ccf(proxyField, value, 0, false, '', holder, 'select', typeOptions);
        }
};