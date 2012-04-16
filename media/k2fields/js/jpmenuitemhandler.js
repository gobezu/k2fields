// TODO: elevate the limitation of input limit set to 255 in UI
var JPMenuItemHandler = new Class({
        Implements: [Events],
        k2f:null,
        initialValue:'',
        initiated:false,
        initialize:function(k2f) {
                this.k2f = k2f;
                this.k2f.options.async = false;
                var link = document.id('jform_link');
                this.initialValue = link.get('value');
                this.k2f.formSubmitButton().dispose();
                var srs = this.srs(), cid = srs['cid'];
                if (cid && cid != -1) {
                        srs.erase('cid');
                        srs = srs.toQueryString();
                        var c = this.k2f.categoryEl();
                        document.id(c.options[cid]).set('init-state', srs);
                        c.addEvent('processingEnd', function() {
                                document.id(c.options[cid]).set('init-state', '');
                        });
                }
        },
        init:function() {
                var c = this.k2f.categoryEl().get('value'), a = this.srs('cid');
                if (c != a) this.k2f.setValue('cid', a);
        },
        srs:function(optName) {
                var srs = document.id('jform_request_srs').get('value');
                if (!srs) return {};
                srs = JSON.decode(srs);
                if (optName) return srs[optName];
                return new Hash(srs);
        },
        build:function() {
                var 
                        srs = {}, val, 
                        els = this.k2f.containerEl().getElements('[name^='+this.k2f.options.pre+']'),
                        cat = this.k2f.categoryEl()
                        ;
                
                els.push(cat);
                
                els.each(function(el) {
                        val = this.k2f.getValue(el);
                        if (val == this.k2f.options.listConditionSeparator) val = '';
                        if (val) srs[el.get('name')] = val;
                }.bind(this));
                
                document.id('jform_request_srs').set('value', JSON.stringify(srs));
        }
});