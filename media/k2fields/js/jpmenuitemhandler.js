// TODO: elevate the limitation of input limit set to 255 in UI
var JPMenuItemHandler = new Class({
        Implements: [Events],
        k2f:null,
        initialValue:'',
        initiated:false,
        initialize:function(k2f) {
                this.k2f = k2f;
                this.k2f.options.async = false;
                var links = $$('input[name=link]');
                this.initialValue = links[0].get('value');
                links.each(function(link) { link.set('value', 'index.php?option=com_k2fields&view=itemlist'); })
        },
        init:function() {
                this.k2f.formSubmitButton().dispose();
                
                if (this.param('task', this.initialValue) == 'search') {
                        var cid = this.param('cid', this.initialValue);
                        if (!cid) return;
                        var cel = this.k2f.categoryEl();
                        for (var i = 0, n = cel.options.length; i < n; i++) {
                                if (cel.options[i]['value'] == cid) {
                                        cel.options[i].selected = true;
                                        break;
                                }
                        }                        
                        //this.k2f.setValue(cel, cid);                    
                }
        },
        params:function(from) {
                return (from || $$('input[name=link]')[0].get('value')).fromQueryString();
        },
        param:function(name, from) {
                var p = this.params(from);
                return p[name];
        },
        loadValues:function() {
                if (this.initiated) return;
                var ps = this.initialValue.fromQueryString();
                ps.each(function(v,k){
                        if (v && !['option', 'view', 'task', 'cid'].contains(k)) {
                                var el = $$('[name='+k+']')[0];
                                this.k2f.setValue(el, v);
                        }
                }.bind(this));
                this.build();
                this.initiated = true;
        },
        build:function() {
                while (document.id('urlparamstask').getNext())
                        document.id('urlparamstask').getNext().dispose();
                
                var
                        pt = document.id('urlparamstask').getParent(), 
                        els = this.k2f.containerEl().getElements('[name^='+this.k2f.options.pre+']'),
                        cat = this.k2f.categoryEl(),
                        ps = this.params();
                
                els.push(cat);
                
                els.each(function(el) {
                        new Element('input', {
                                'type':'hidden',
                                'value':this.k2f.getValue(el),
                                'name':'urlparams['+el.get('name')+']',
                                'id':'urlparams'+el.get('name')
                        }).inject(pt);
                }.bind(this));
        }
});