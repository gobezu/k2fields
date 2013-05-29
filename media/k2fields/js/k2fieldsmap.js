//$Copyright$

var k2fields_type_map = {
        _initMap: function() {
                //this.utility.load('tag', 'http://maps.google.com/maps/api/js?sensor=false', 'js');
        },

        createSearchMap: function(holder, proxyField, value, condition) {
                var vh = this.ccf(proxyField, value, 0, false, '', holder, 'input', 'hidden', true, undefined, true);

                vh = vh[0];

                var tf = this.autoComplete(proxyField, true, holder, false, undefined, {to:vh,attr:'ovalue'});

                if (!tf) {
                        vh.set('type', 'text');
                        tf = vh;
                }

                var cb = this.ccf(proxyField, value, 1, 'checkbox', '', holder, 'input', {ignore:true,type:'checkbox',values:[{text:'Nearby me',value:'nearby'}]});

                cb = cb[0];
                cb.addEvent('change', function(el) {
                        el = this._tgt(el);
                        if (el.checked) {
                                vh.set('value', this.getCurrentGeoValues(proxyField));
                        } else {
                                vh.set('value', vh.retrieve('ovalue'));
                        }
                }.bind(this));

                tf.addEvent('change', function() {cb.checked = false;}.bind(this));

                this.loadMapAPI(proxyField);
        },

        mapAPILoaded:false,
        currentGEO:null,
        mapIconSize:[32,37],
        mapIconHoverSize:[32,37],
        mapEditors:{},

        getMapIcon:function(proxyField, iconType, alterWith) {
                var icon = this.getOpt(proxyField, 'mapicon'+iconType);

                if (!icon) return false;

                if (!alterWith) return icon;

                var it = this.getOpt(proxyField, 'mapicontype');

                if (!it) return icon;

                var re = new RegExp('(' + it + ')(' + (it == 'number_' ? '[0-9]+' : '[a-z]+') + ')(|-active)\.(png|jpg|gif|jpeg)$');

                icon = icon.replace(re, '$1' + alterWith + '.$3');

//                var m = icon.match(new RegExp(pre));
//
//                var sep = icon.lastIndexOf('_') > -1 ? '_' : '.';
//
//                icon = icon.substring(0, icon.lastIndexOf(sep) + 1) + alterWith + icon.substring(icon.lastIndexOf('.'));
//
                return icon;
        },

//        getMapIconColor:function(proxyField, icon, alterWith) {
//                icon = this.getOpt(proxyField, 'mapicon'+icon);
//
//                if (!icon) return false;
//
//                if (!alterWith) alterWith = '';
//
//                var ind = icon.lastIndexOf('.');
//
//                return icon.substring(0, ind)+alterWith+icon.substring(ind);
//        },
//
        getMapIconSize:function(proxyField, icon) {
                if (!this.getMapIcon(proxyField, icon)) return;

                return this.getOpt(proxyField, 'mapicon'+icon+'size');
        },

        createMap: function(holder, proxyField, value, condition) {
                var defs, method = this.getOpt(proxyField, 'mapinputmethod', null, 'coord');

                if (method == 'coord') {
                        defs = [
                                {'valid':'numeric', 'name':'Latitude', 'position':0, 'internalValueSeparator':','},
                                {'valid':'numeric', 'name':'Longitude', 'position':0, 'internalValueSeparator':','},
                                {'valid':'text', 'name':'Label', 'position':1, 'autocomplete':this.getOpt(proxyField, 'autocomplete')}
                        ];
                        if (!value) {
                                value = this.getOpt(proxyField, 'mapcenter', null, null, '9.022736,38.746799,');
                                if (typeof value != 'string') value = value.join(this.options.valueSeparator);
                        }
                } else if (method == 'geo') {
                        defs = [
                                {'valid':'text', 'name':'Street', 'position':0, 'internalValueSeparator':','},
                                {'valid':'text', 'name':'Locality', 'position':0, 'internalValueSeparator':','},
                                {'valid':'text', 'name':'Region', 'position':0, 'internalValueSeparator':','},
                                {'valid':'text', 'name':'Country', 'position':0, 'internalValueSeparator':','},
                                {'valid':'text', 'name':'Label', 'position':1, 'autocomplete':'m'}
                        ];
                }

                if (defs) {
                        this.modifyComplexOpts(proxyField, defs);
                        if (typeOf(value) == 'array') value = value.join(this.options.valueSeparator);
                        var ui = this.createComplex(holder, proxyField, value, condition);
                        this.revertOpts(proxyField);

                        this.loadMapAPI(proxyField);

                        if (this.chkOpt(proxyField, 'showmapeditor', ['true', '1'])) {
                                try {
                                        this.getEditorMap(proxyField, holder);
                                        this.redrawMapEditor(proxyField);

                                } catch (e) { }
                        }

                        return ui;
                }
        },

        createMarker: function(proxyField, geo, map) {
                geo = [document.id(geo[0]), document.id(geo[1])];

                var lat = geo[0].get('value'), lon = geo[1].get('value');

                if (!map) map = this.getEditorMap(proxyField);

                if (lat == '' || lon == '') {
                        lat = map.getCenter();
                        lon = lat.lon;
                        lat = lat.lat;
                }

                var
                        point = new mxn.LatLonPoint(parseFloat(lat), parseFloat(lon)),
                        ms = map.markers,
                        cnt = ms.length + 1,
                        icon = this.getMapIcon(proxyField, 'colorfile', cnt),
                        marker = new mxn.Marker(point)
                        ;

                if (!this.options.hasOwnProperty('mapEditorMarkers')) {
                        this.options.mapEditorMarkers = [];
                }

                if (icon) {
        		map.addMarkerWithData(marker, {
                                'draggable':this.chkOpt(proxyField, 'markerfixed', 0),
                                'icon':icon,
                                'iconSize':this.getMapIconSize(proxyField, 'colorfile'),
                                'editor':[geo[0], geo[1]]
                        });
                } else {
                        map.addMarkerWithData(marker, {
                                'draggable':this.chkOpt(proxyField, 'markerfixed', 0),
                                'editor':[geo[0], geo[1]]
                        });
                }

                marker.dragend.addHandler(function(name, source, args) {
                        var geo = source.getAttribute('editor');
                        geo[0].set('value', args.lat);
                        geo[1].set('value', args.lon);
                        this.setProxyFieldValue(geo[1]);
		}.bind(this));

                geo[0].addEvent('change', function() {this.redrawMapEditor(proxyField);}.bind(this));
                geo[1].addEvent('change', function() {this.redrawMapEditor(proxyField);}.bind(this));

                var btn = geo[0].getParent('.k2fcontainer').getElement('.removebtn');

                if (btn) btn.addEvent('click', function() {
                        this.redrawMapEditor(proxyField);
                }.bind(this));

                return marker;
        },

        redrawMapEditor: function(proxyField) {
                var
                        map = this.getEditorMap(proxyField),
                        container = map.currentElement.getParent('td'),
                        els = container.getElements('.k2fcontainer'),
                        n = els.length,
                        geo
                        ;

                map.removeAllMarkers();

                for (var i = 0; i < n; i++) {
                        geo = els[i].getElements('[customvalueholder=true]');
                        this.createMarker(proxyField, [geo[0], geo[1]], map);
                }

                this.autoCenterAndZoom(map, proxyField);
        },

        autoCenterAndZoom:function(map, proxyField) {
                if (map.markers.length == 1) {
                        var zoom = this.getOpt(proxyField, 'mapzoom', null, 13).toInt();
                        map.setCenterAndZoom(map.markers[0].location, zoom);
                } else {
                        map.autoCenterAndZoom();
                }
        },

        getMapClass: function(proxyField) {return this.getMapAttr(proxyField, 'class');},
        getMapId: function(proxyField) {return this.getMapAttr(proxyField, 'id');},

        getMapAttr: function(proxyField, attr) {
                var id = this.getOpt(proxyField, '_mapcontainer'+attr);

                if (id) return id;

                id = document.id(proxyField).get('id');

                if (this.getOpt(id, 'subfieldof')) id = this.getOpt(id, 'subfieldof');

                var itemid = $$('[name=id]');
                itemid = itemid.length > 0 ? itemid[0].get('value') : '';

                id = this.getOpt(proxyField, 'mapcontainer'+attr).replace(/\%id\%/, id).replace(/\%item\%/, itemid);
                this.setOpt(proxyField, '_mapcontainer'+attr, id);

                return id;
        },

        getMapInitialPoint: function(proxyField) {return this.getMapPoint(proxyField, 'mapcenter');},

        getMapPoint: function(proxyField, name, point) {
                if (name) {
                        point = this.getOpt(
                                proxyField,
                                name,
                                null,
                                null,
                                point ? point : [9.022736, 38.746799] // we just need a point
                        );
                }

                if (typeof point == 'string')  point = point.split(',');

                point = new mxn.LatLonPoint(parseFloat(point[0]), parseFloat(point[1]));

                return point;
        },

        loadMapAPI: function(proxyField) {
                if (this.mapAPILoaded) return;
                // TODO: proxyField is not passed at all at type initiating time
                var api = this.getOpt(proxyField, 'locationprovider', null, 'maxmind');

                if (api == 'maxmind') {
                        this.utility.load('tag', 'http://j.maxmind.com/app/geoip_city', 'js');
                } else if (api == 'browser') {
                        this.getCurrentGeoValues(proxyField);
                }

                this.mapAPILoaded = true;
        },

        getCurrentGeoValues: function(proxyField) {
                var api = this.getOpt(proxyField, 'locationprovider', null, 'maxmind');

                if (api == 'maxmind' && typeof geoip_latitude == 'function' && typeof geoip_longitude == 'function') {
                        return [geoip_latitude(), geoip_longitude()];
                } else if (api == 'function') {
                        var fn = this.getOpt(proxyField, 'locationproviderfunction');

                        if (fn) {
                                fn = fn.split('.');

                                if (fn.length > 1) fn = fn[0]+'["'+fn[1]+'"](this.getValue(proxyField))';
                                else fn = fn[0]+'(this.getValue(proxyField))';

                                eval('fn='+fn);

                                return fn;
                        }
                } else if (api == 'browser') {
                        if (this.currentGEO === null) {
                                navigator.geolocation.getCurrentPosition(
                                        function(data){this.currentGEO = [data.coords.latitude, data.coords.longitude];}.bind(this),
                                        function(error) {console.log('error:', error);},
                                        {timeout:5000}
                                );
                        } else {
                                return this.currentGEO;
                        }
                } else if (api == 'server') {
                        // data sent in a json package along with k2fs
                }

                return false;
        },

//        redraws:[],
//        redraw:function() {
//                for (var i = 0, n = this.redraws.length; i < n; i++) {
//                       if (this.redraws[i][0].isVisible()) {
//                               this.refreshMap(this.redraws[i][1]);
//                               clearInterval(this.redraws[i][2]);
//                               delete this.redraws[i];
//                       }
//                }
//                this.redraws = this.redraws.clean();
//        },

        refreshMap:function(proxyField) {
                var
                        map = this.getEditorMap(proxyField),
                        w = map.currentElement.getStyle('width').toInt(),
                        h = map.currentElement.getStyle('height').toInt()
                        ;

                map.resizeTo(w, h);
                this.redrawMapEditor(proxyField);
        },

        getEditorMap: function(proxyField, container) {
                var mapId = this.getMapId(proxyField), map = this.mapEditors[mapId];

                if (map != undefined) return map;

                var
                        td = container.getParent('td') || container.getParent('.k2fmapcontainer'),
                        css = this.getMapClass(proxyField),
                        point = this.getMapInitialPoint(proxyField),
                        zoom = this.getOpt(proxyField, 'mapzoom', null, 13).toInt(),
                        provider = this.getOpt(proxyField, 'mapprovider'),
                        maptype = this.getOpt(proxyField, 'maptype')
                        ;

                css += css != 'mapContainer' ? ' mapContainer' : '';
                container = new Element('div', {id:mapId, 'class':css});
                container.inject(td, 'top');

                new Element('a', {
                        'text':'Refresh',
                        'class':'refreshmapbtn',
                        'href':'#',
                        'events':{
                                'click':function(e) {
                                        e = this._tgt(e);
                                        e = this.getProxyFieldId(e.getParent('td').getElement('[customvalueholder=true]'));
                                        e = this.getOpt(e, 'subfieldof');
                                        this.refreshMap(e);
                                        return false;
                                }.bind(this)
                        }
                }).inject(container, 'after');

                map = new mxn.Mapstraction(mapId, provider);

                map.setMapType(maptype);
                map.setCenterAndZoom(point, zoom);

		map.addControls({
			pan: true,
			zoom: 'small',
			map_type: true
		});

                this.mapEditors[mapId] = map;

//                if (!container.isVisible()) {
//                        this.redraws.push([container, proxyField, this.redraw.periodical(500, this)]);
//                }

                return map;
        },

        drawStaticMap: function(data) {
                // TODO: if field is in a tab and not in focus at page load the size provided is incorrect
                var urls = {
                        'googlev3':'http://maps.google.com/maps/api/staticmap?sensor=false&size=%width%x%height%&maptype=%maptype%&zoom=%zoom%',
                        'leaflet':'http://www.mapquestapi.com/staticmap/v3/getmap?key=%apikey%&amp;size=%width%,%height%&amp;zoom=%zoom%&amp;type=%maptype%',
                        'other':this.options.base+'/media/k2fields/lib/staticmaplite/staticmap.php?size=%width%x%height%&zoom=%zoom%&maptype=mapnik'
                };

                var
                        i, lbls = new Element('ol', {'class':'mapPointLabels'}),
                        container = document.id(data['container']),
                        size = container.getStyles('width', 'height'),
                        n = data['points'].length,
                        url = urls[data['provider']] || urls['other'],
                        pois = '',
                        iconColor = data['iconcolor'] || 'orange'
                        ;

                if (n == 1 && data['provider'] != 'googlev3') {
                        url += '&center='+data['points'][0]['lat']+','+data['points'][0]['lon'];
                }

                url = url.
                        replace('%apikey%', data['apikey']).
                        replace('%width%', size['width'].toInt()).
                        replace('%height%', size['height'].toInt()).
                        replace('%zoom%', data['zoom']).
                        replace('%maptype%', data['maptype']).
                        replace('%centerlat%', data['points'][0]['lat']).
                        replace('%centerlon%', data['points'][0]['lon'])
                ;

                for (i = 0; i < n; i++) {
                        if (data['provider'] == 'googlev3') {
                                pois += pois == '' ? '&amp;markers=' : '|';
                                pois += '&markers=label:'+(i+1)+'%7C'+data['points'][i]['lat'] + ',' + data['points'][i]['lon'];
                        } else if (data['provider'] == 'leaflet') {
                                pois += pois == '' ? '&amp;pois=' : '|';
                                pois += iconColor+'-'+(i+1)+','+data['points'][i]['lat'] + ',' + data['points'][i]['lon'];
                        } else {
                                pois += pois == '' ? '&markers=' : '|';
                                pois += data['points'][i]['lat'] + ',' + data['points'][i]['lon']+','+iconColor;
                        }
                        new Element('li', {html:data['points'][i]['label']?data['points'][i]['label']:'Interest point '+(i+1)}).inject(lbls);
                }
                url += pois;
                new Element('image', {src:url}).inject(container);
                lbls.inject(container.getParent());
        },

        mapItems: {},
        drawMap: function(proxyField, view) {
                var
                        container = this.getOpt(proxyField, 'mapcontainerid'),
                        provider = this.getOpt(proxyField, 'mapprovider'+view)
                        ;
                var
                        map = new mxn.Mapstraction(container, provider);

                var
                        maptype = this.getOpt(proxyField, 'maptype'),
                        itemPoints, m, el, items = this.mapItems[proxyField], i, n, itemId, item, a,
                        ips = new Element(view == 'item' ? 'ol' : 'ul', {'class':'mapips'}), agoto, preIp, ipsItem, ipsItemC, attrs,
                        createIPs = this.getOpt(proxyField, 'mapcreateips'),
                        ctrls = this.getOpt(proxyField, 'mapcontrols'),
                        showInMapBtn = this.getOpt(proxyField, 'mapshowinmapbtn'),
                        cont,
                        lbl,
                        icon, data
                        ;

                map.setMapType(maptype);

                if (ctrls) {
                        var mCtrls = {};
                        if (ctrls.contains('zoom')) mCtrls['zoom'] = 'large';
                        if (ctrls.contains('zoom small')) mCtrls['zoom'] = 'small';
                        if (ctrls.contains('pan')) mCtrls['pan'] = true;
                        if (ctrls.contains('maptype')) mCtrls['map_type'] = true;
                        if (ctrls.contains('overview')) mCtrls['overview'] = true;
                        map.addControls(mCtrls);
                }

                this.loadMapAPI(proxyField);

                for (itemId in items) {
                        item = items[itemId];
                        itemPoints = item['points'];
                        n = itemPoints.length;

                        preIp = n > 1 || view == 'item' ? '' : item.title;

                        if (createIPs) {
                                ipsItem = new Element('li').inject(ips);

                                agoto  = this.getOpt(proxyField, 'mapgoto', null, '» Go to %category%').
                                        replace('%category%', item.category).
                                        replace('%categoryid%', item.categoryid).
                                        replace('%item%', item.title).
                                        replace('%category%', item.id)
                                ;

                                if (n > 1) {
                                        if (view != 'item') {
                                                new Element('span', {'text':item.title}).inject(ipsItem);
                                                new Element('a', {'text':agoto, 'href':item.link}).inject(ipsItem);
                                        }

                                        ipsItem = new Element('ul').inject(ipsItem);
                                }
                        } else if (view != 'item') {
                                ips = new Element(n > 1 ? 'ol' : 'span', {'class':'showinmapbtns'});
                        }

                        for (i = 0; i < n; i++) {
                                m = new mxn.Marker(new mxn.LatLonPoint(itemPoints[i].lat, itemPoints[i].lon));
                                el = new Element('div', {'html':item.rendered});
                                attrs = {'href':'#', 'text':preIp};

                                if (!itemPoints[i].lbl && view == 'item') itemPoints[i].lbl = item.title;

                                if (itemPoints[i].lbl) attrs['text'] += (preIp ? ' - ' : '') + itemPoints[i].lbl;

                                if (view == 'item') el.set('html', '');

                                lbl = itemPoints[i].lbl ? itemPoints[i].lbl : attrs['text'];

                                if (createIPs) {
                                        ipsItemC = n == 1 ? ipsItem : new Element('li').inject(ipsItem);

                                        if (this.chkOpt(proxyField, 'mappanevents', 'click')) {
                                                if (!attrs['events']) attrs['events'] = {};
                                                attrs['events']['click'] = function(a){this.openIP(a);return false;}.bind(this);
                                        }

                                        if (this.chkOpt(proxyField, 'mappanevents', 'mouseover')) {
                                                if (!attrs['events']) attrs['events'] = {};

                                                attrs['events']['mouseover'] = function(a){
                                                        this.openIP(a);
                                                        return false;
                                                }.bind(this);
                                        } else {
                                                attrs['events']['mouseover'] = function(a){
                                                        this.highlightIP(a, true);
                                                        return false;
                                                }.bind(this);

                                                attrs['events']['mouseout'] = function(a){
                                                        this.highlightIP(a, false);
                                                        return false;
                                                }.bind(this);
                                        }

                                        new Element('a', attrs).inject(ipsItemC).store('ip', [proxyField, itemId, i]);

                                        if (view != 'item' && n == 1) {
                                                new Element('a', {'text':agoto, 'href':item.link}).inject(ipsItemC);
                                        }
                                }

                                if (view == 'item') {
                                        cont = document.id(map.currentElement);
                                } else {
                                        cont = view == 'item' ? $$('.item'+itemId) : $$('.catitem'+itemId);
                                        cont = cont && cont.length > 0 ? cont[0] : false;
                                }

                                if (cont) {
                                        cont.store('ip', [proxyField, itemId, i]);
                                        cont.addClass('ipstore');

                                        if (view != 'item') {
                                                if (this.chkOpt(proxyField, 'mappanevents', 'mouseover')) {
                                                        cont.addEvent('mouseover', function(e) { this.openIP(e); }.bind(this));
                                                } else {
                                                        cont.
                                                                addEvent('mouseover', function(e) { this.highlightIP(e, true); }.bind(this)).
                                                                addEvent('mouseout', function(e) { this.highlightIP(e, false); }.bind(this))
                                                        ;
                                                }
                                        }

                                        if (showInMapBtn) {
                                                new Element('a', {
                                                        'text':lbl,
                                                        'title':'Click to see '+lbl+' in map',
                                                        'href':'#',
                                                        'events':{ 'click':function(e) { this.showInMap(e); return false; }.bind(this) }
                                                }).inject(ips.get('tag') == 'ul' || ips.get('tag') == 'ol' ? new Element('li').inject(ips) : ips);
                                        }
                                }

                                new Element(view != 'item'? 'a' : 'span', {
                                        'href':item.link,
                                        'text':lbl
                                }).inject(view != 'item' && el.getElement('.k2fmap') ? el.getElement('.k2fmap') : el, 'top');

                                m.setInfoBubble(el);
                                m.click.addHandler(function(name, source, args) {
                                        if (this.currentIp) this.currentIp.closeBubble();
                                        this.currentIp = source;
                                }.bind(this));

                                m.proxyField = proxyField;

                                if (icon = this.getMapIcon(proxyField, 'location', view == 'item' ? i+1 : null)) {
                                        data = {'icon':icon};
                                        if (icon = this.getMapIcon(proxyField, 'locationhover')) {
                                                data['iconHover'] = icon;
                                        }

                                        iconSize = this.getMapIconSize(proxyField, 'location');
                                        data['iconSize'] = iconSize;
                                        data['iconAnchor']= [iconSize[0]/2, iconSize[1]];
                                        if (icon = this.getOpt(proxyField, 'mapiconshadow')) {
                                                data['iconShadow'] = icon;
                                                data['iconShadowSize'] = this.getOpt(proxyField, 'mapiconshadowsize');
                                        }
                                        map.addMarkerWithData(m, data);
                                } else {
                                        map.addMarker(m);
                                }

                                new JPProcessor().accordion(m.infoBubble);

                                this.mapItems[proxyField][itemId]['points'][i]['marker'] = m;
                        }

                        if (view != 'item' && !createIPs && cont) ips.inject(cont);
                }

                this.autoCenterAndZoom(map, proxyField);

                if (createIPs) ips.inject(container, 'after');
                else if (view == 'item') ips.inject(cont, 'after').addClass('showinmapbtns').setStyle('padding-left', '50px');

                var actions = this.getOpt(proxyField, 'mapactions');

                if (actions) {
                        var actionsC = new Element('ul', {'class':'k2fmapactions'}).inject(document.id(map.currentElement), 'after');

                        if (actions.contains('reset')) {
                                new Element('a', {
                                        'href':'#',
                                        'text':'Reset map',
                                        'events':{
                                                'click':function() {
                                                        this.autoCenterAndZoom(map, proxyField);
                                                        return false;
                                                }.bind(this)
                                        }
                                }).inject(new Element('li').inject(actionsC));
                        }

                        if (view != 'item' && actions.contains('nearby') && this.getOpt(proxyField, 'nearbys')) {
                                new Element('span', {'text':'Show nearby me at:'}).inject(new Element('li').inject(actionsC));

                                var nearbys = this.getOpt(proxyField, 'nearbys');
                                nearbys = nearbys.split(this.options.valueSeparator);

                                for (i = 0, n = nearbys.length; i < n; i++) {
                                        new Element('a', {
                                                'href':'#',
                                                'text':nearbys[i]+'km'+(nearbys[i]>1?'s':''),
                                                'events':{
                                                        'click':function(e) {
                                                                e = this._tgt(e);
                                                                this.showNearbyMeIPs(proxyField, map, e);
                                                                return false;
                                                        }.bind(this)
                                                }
                                        }).store('distance', nearbys[i]).inject(new Element('li').inject(actionsC));
                                }

                                new Element('a', {
                                        'href':'#',
                                        'text':'All',
                                        'events':{
                                                'click':function() {
                                                        this.showNearbyMeIPs(proxyField, map, 0);
                                                        return false;
                                                }.bind(this)
                                        }
                                }).inject(new Element('li').inject(actionsC));
                        }
                }
        },

        currentIp:null,

        openIP:function(a) {
                this.closeIP(a);
                var ip = this.getIP(a);
                ip.openBubble();
                this.currentIp = ip;
        },

        closeIP: function(a) {
                if (this.currentIp) {
                        this.currentIp.closeBubble();
                        this.currentIp = null;
                }
        },

        getIP: function(a) {
                var ip = this._tgt(a);
                ip = ip.retrieve('ip');
                if (ip) return this.mapItems[ip[0]][ip[1]]['points'][ip[2]]['marker'];
                ip = this._tgt(a);
                if (ip.getParent('.ipstore')) {
                        ip = ip.getParent('.ipstore');
                } else {
                        ip = $$('.ipstore');
                        if (ip) {
                                ip = ip[0];
                        } else return;
                }
                ip = ip.retrieve('ip');
                ip = this.mapItems[ip[0]][ip[1]]['points'][ip[2]]['marker'];
                return ip;
        },

        showInMap: function(a) {
                var ip = this.getIP(a), map = ip.mapstraction, mapEl = document.id(map.element);
                // NOTE: assuming jQuery UI is in use
                // TODO: devise similar methods for other offered UIs
                if (mapEl.getParent('.ui-tabs-hide') || mapEl.getParent('.ui-accordion-hide')) {
                        var i = mapEl.getParent('.k2f-pane').getElements('.k2f-panel').indexOf(mapEl.getParent('.k2f-panel'));
                        if (mapEl.getParent('.k2f-jquery-ui-tab')) {
                                jQuery(mapEl.getParent('.k2f-jquery-ui-tab')).tabs('select', i);
                        } else if (mapEl.getParent('.k2f-jquery-ui-accordion')) {
                                jQuery(mapEl.getParent('.k2f-jquery-ui-tab')).accordion('activate', i);
                        }
                }
                new Fx.Scroll(window).toElement(document.id(map.element));
                map.setCenterAndZoom(ip.location, map.getZoom());
                this.openIP(a);
        },

        highlightIP: function(a, isOn) {
                var ip = this.getIP(a);

                var icon = this.getMapIcon(ip.proxyField, 'location' + (!isOn ? '' : 'hover'));
                var size = this.getMapIconSize(ip.proxyField, 'location' + (!isOn ? '' : 'hover'));

                ip.mapstraction.removeMarker(ip);
                ip.mapstraction.addMarkerWithData(ip, {'icon':icon, 'iconSize':size});

                return;
//
//                if (ip.api != 'googlev3') return;
//
//                var icon = this.getMapIcon(ip.proxyField, 'location' + (!isOn ? '' : 'hover'));
//                var size = this.getMapIconSize(ip.proxyField, 'location' + (!isOn ? '' : 'hover'));
//
//                size = new google.maps.Size(size[0], size[1]);
//                var zerozero = new google.maps.Point(0,0);
//
//                icon = new google.maps.MarkerImage(
//                        icon,
//                        size,
//                        zerozero,
//                        zerozero
//                );
//
//                ip.proprietary_marker.setIcon(icon);
        },

        showNearbyMeIPs: function(proxyField, map, distance) {
                map.removeAllPolylines();
                map.removeAllFilters();

                if (!distance) {
                        this.autoCenterAndZoom(map, proxyField);
                        return;
                }

                distance = distance.retrieve('distance').toInt();

                var loc = this.getCurrentGeoValues(proxyField);

                loc = new mxn.LatLonPoint(loc[0], loc[1]);

                map.setCenter(loc);

                var radius = new mxn.Radius(loc, distance);

                map.addPolyline(radius.getPolyline(distance, '#00F'));
                map.addFilter('distance', 'le', distance);
                map.doFilter();
        }
};