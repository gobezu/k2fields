//$Copyright$

var JPCompare = new Class({
        Implements: [Options],

        options: {
                compareMin:1
        },

        initialize: function(options) {
                options = Object.merge(this.options, options);
                this.setOptions(options);
        },

        init: function() {
                var items = document.getElements('input.comparer[type=checkbox]', items), i, n, chks = Cookie.read('items');
                
                if (chks) chks.split(',');
                
                for (i = 0, n = items.length; i < n; i++) {
                        items[i].addEvent('click', function(e) {
                                e = document.id(e.target ? e.target : e.srcElement);
                                this.comparizeCapture(e, '+');
                        }.bind(this));
                        
                        if (chks && chks.contains(items[i].get('value'))) {
                                items[i].set('checked', true);
                        }
                }
                
                document.id('comparecompare').addEvent('click', function() { this.doCompare(); }.bind(this));
                document.id('compareclear').addEvent('click', function() { this.clearCompare(); }.bind(this));
        },
        
        clearCompare: function() {
                var items = Cookie.read('items');
                
                if (items) {
                        items = items.split(',');
                        
                        var item, i, n = items.length;
                        
                        for (i = 0; i < n; i++) {
                                item = document.getElement('input.comparer[value='+items[i]+']');
                                if (item) item.set('checked', false);
                        }
                }
                
                Cookie.dispose('items');
        },
        
        doCompare:function() {
                var url = Cookie.read('compareurl');
                if (!url) Cookie.write('compareurl', document.location.href);
                var items = Cookie.read('items');
                if (!items) return;
                items = items.split(',');
                if (items.length <= this.options.compareMin) {
                        alert('Comparison requires selection of 2 or more items');
                        return;
                }
                var url = 'index.php?option=com_k2fields&view=itemlist&layout=compare&task=search&items='+items;
                document.location.href = url;
        },
        
        cancelCompare:function() {
                this.clearCompare();
                var url = Cookie.read('compareurl');
                Cookie.dispose('compareurl');
                document.location.href = url;
        },
        
        comparizeCapture: function(ckb, action) {
                var items = Cookie.read('items'), item = typeof ckb != 'object' ? ckb : ckb.get('value');
                
                if (typeof ckb == 'object' && ckb.get('checked') || action == '+') {
                        if (items) items += ',';
                        else items = '';
                        items += item;
                } else {
                        items = items.replace(','+item, '').replace(item+',', '').replace(item, '');
                        items = items.split(',');
                        
                        if (items.length <= this.options.compareMin) {
                                this.cancelCompare();
                                return;
                        }
                        
                        items = items.join(',');
                        
                        this.removeItem(item);
                }
                
                Cookie.write('items', items);
        },
       
        comparize:function() {
                document.getElement('input.comparecancel[type=button]').addEvent('click', function() { 
                        this.cancelCompare(); 
                }.bind(this));
                
                var btns = document.getElements('input.compareremove[type=button]');
                
                btns.each(function(btn) {
                        btn.addEvent('click', function(e) {
                                e = document.id(e.target ? e.target : e.srcElement);
                                this.comparizeCapture(e.get('item'), '-');                        
                        }.bind(this));
                }.bind(this));
                
                document.getElements('table.compare a.comparehidefield').addEvent('click', function() {
                        this.getParent('tr.comparefield').addClass('comparefieldhide');
                        return false;
                });
                
                document.getElements('table.compare tr.comparefield').addEvent('click', function(tr) {
                        tr = document.id(tr.target ? tr.target : tr.srcElement);
                        tr = tr.getParent('tr.comparefield') || tr;
                        if (tr.hasClass('comparetitle')) return;
                        tr.toggleClass('comparefieldselected');
                });
                
                this.checkFieldValueDiffs();
        },
        
        unhideFields: function() {
                document.getElements('table.compare > tbody > tr.comparefield.comparefieldhide').removeClass('comparefieldhide');
        },
        
        removeItem:function(itemId) {
                var colNo;
                
                document.getElements('table.compare input.compareremove').each(function(el, ind) {
                        if (el.get('item') == itemId) {
                                colNo = ind;
                                return;
                        }
                }.bind(this));
                
                if (colNo == undefined) return;
                
                colNo += 2;

                document.getElements('table.compare tbody tr td:nth-child('+colNo+'), table.compare thead tr th:nth-child('+colNo+')').each(function(el) {
                        if (el.getParent('tr.comparesection')) return;
                        el.dispose();
                });
                
                this.checkFieldValueDiffs();
        },
        
        checkFieldValueDiffs: function() {
                document.getElements('table.compare > tbody > tr.comparefield').each(function(tr) {
                        var diff = false, prev = '', curr;
                        
                        tr.getElements('> td').each(function(td) {
                                curr = td.get('html');
                                
                                if (diff) return;
                                
                                if (prev == '') {
                                        prev = curr;
                                        return;
                                }
                                
                                diff = prev != curr;
                                prev = curr;
                        }.bind(this));
                        
                        tr[diff ? 'addClass' : 'removeClass']('comparefielddiff');
                });
        }
});