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
                
                tf.addEvent('change', function() { cb.checked = false; }.bind(this));
                
                this.loadMapAPI(proxyField);
        },
        
        createMap: function(holder, proxyField, value, condition) {
                var defs, method = this.getOpt(proxyField, 'mapinputmethod', null, 'coord');
                
                if (method == 'coord') {
                        defs = [
                                {'valid':'numeric', 'name':'Latitude', 'position':0, 'internalValueSeparator':','},
                                {'valid':'numeric', 'name':'Longitude', 'position':0, 'internalValueSeparator':','},
                                {'valid':'text', 'name':'Label', 'position':1, 'autocomplete':this.getOpt(proxyField, 'autocomplete')}
                        ];
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
                        var ui = this.createComplex(holder, proxyField, value, condition);
                        this.revertOpts(proxyField);

                        try {
                                this.loadMapAPI(proxyField);
                                if (google != undefined && this.chkOpt(proxyField, 'showmapeditor', ['true', '1'])) {
                                        var map = this.getEditorMap(proxyField, holder);
                                        this.redrawMapEditor(map);
                                }
                        } catch (exception) { }
                        
                        return ui;
                }
        },
        
        createMarker: function (proxyField, geo) {
                geo[0] = document.id(geo[0]);
                geo[1] = document.id(geo[1]);
                
                var latVal = geo[0].get('value'), lonVal = geo[1].get('value');
                var map = this.getEditorMap(proxyField);
                
                if (latVal == '' || lonVal == '') {
                        latVal = map.getCenter();
                        lonVal = latVal.lng();
                        latVal = latVal.lat();
                }
                
                var point = new google.maps.LatLng(parseFloat(latVal), parseFloat(lonVal));
                
                if (!this.options.hasOwnProperty('mapEditorMarkers')) {
                        this.options.mapEditorMarkers = [];
                }
                
                var ms = this.getMarkersInMap(map, false);
                var cnt = ms.length + 1;
                
                var icon = this.options.base+this.options.k2fbase+'icons/numbers/orange'+(cnt > 9 ? cnt : '0'+cnt)+'.png';
                
                var marker = new google.maps.Marker({
                        position: point,
                        map: map,
                        title: '',
                        icon: icon,
                        draggable: true
                });
                
                this.options.mapEditorMarkers.push([marker, geo[0].get('id'), geo[1].get('id')]);
                
                // Propagate drag results to fields
                google.maps.event.addListener(marker, 'drag', function(e) {
                        var ms = this.options.mapEditorMarkers, m, p;
                        for (var i = 0, n = ms.length; i < n; i++) {
                                m = ms[i];
                                if (!m || !m[0]) continue;
                                var p = m[0].getPosition();
                                if (e.latLng.equals(p)) {
                                        p = p.toUrlValue().split(',');
                                        document.id(m[1]).setProperty('value', p[0]);
                                        document.id(m[2]).setProperty('value', p[1]);
                                        break;
                                }
                        }
                }.bind(this));
                
                this.relocateMapMarker(geo, 'lat');
                this.relocateMapMarker(geo, 'lon');

                return marker;
        },
        
        relocateMapMarker: function(geo, pos) {
                document.id(geo[pos == 'lat' ? 0 : 1]).addEvent('change', function(e) {
                        if (typeOf(e) == 'event') {
                                e = this._tgt(e);
                        }
                        var ms = this.options.mapEditorMarkers, m, p, v;
                        var fldId = e.get('id');
                        for (var i = 0, n = ms.length; i < n; i++) {
                                m = ms[i];
                                if (!m || !m[0]) continue;                                
                                if (m[pos == 'lat' ? 0 : 1] == fldId) {
                                        p = m[0].getPosition();
                                        v = [
                                                pos == 'lat' ? parseFloat(e.get('value')) : p.lat(), 
                                                pos == 'lon' ? parseFloat(e.get('value')) : p.lng()
                                        ];
                                        p = new google.maps.LatLng(v[0], v[1]);
                                        m[0].setPosition(p);
                                        break;
                                }
                        }
                        
                        this.redrawMapEditor(m[0].getMap(), p);
                }.bind(this));                
        },
        
        getMarkersInMap: function(map, isDelete) {
                var ms = this.options.mapEditorMarkers;
                
                if (!ms) return isDelete ? false : [];
                
                var m, newms = [], result = []

                for (var i = 0, n = ms.length; i < n; i++) {
                        m = ms[i][0];
                        if (m.getMap() == map) {
                                result.push(m);
                                if (isDelete) {
                                        m.setMap(null);
                                }
                        } else {
                                newms.push(ms[i]);
                        }
                }
                
                if (isDelete) {
                        this.options.mapEditorMarkers = newms;
                        return true;
                }
                
                return result;
        },
        
        removeMarkersInMap: function(map) {
                return this.getMarkersInMap(map, true);
        },
        
        evts: {},
        
        redrawMapEditor: function(map, newPoint) {
                var bounds = map.getBounds();
                
                if (!bounds) bounds = new google.maps.LatLngBounds();
                
                if (newPoint != undefined) {
                        if (bounds.contains(newPoint)) {
                                return;
                        } else {
                                bounds.extend(newPoint);
                        }
                } else {
                        this.removeMarkersInMap(map);
                        var container = map.getDiv().getParent('td');
                        
                        // add all points to map
                        var els = container.getElements('.k2fcontainer'), geo, proxyField, marker;
                        geo = els[0].getElements('[customvalueholder=true]');
                        proxyField = this.getProxyFieldId(geo[0]);
                        
                        for (var i = 0, n = els.length; i < n; i++) {
                                geo = els[i].getElements('[customvalueholder=true]');
                                marker = this.createMarker(proxyField, [geo[0], geo[1]]);
                                bounds.extend(marker.getPosition());
                        }
                }
                
                this.evts[map.getDiv().get('id')] = google.maps.event.addListener(map, 'tilesloaded', function() {
                        map.fitBounds(bounds);
                        
                        var maxZoom = [
                                map.mapTypes.get(map.getMapTypeId()).maxZoom, 
                                map.getZoom(), 
                                this.getOpt(proxyField, 'maxzoom', null, map.getZoom()),
                                15
                        ].min();
                        
                        maxZoom = maxZoom.toInt();

                        if (map.getZoom() > maxZoom) {
                                map.setZoom(maxZoom);
                        }
                        
                        google.maps.event.removeListener(this.evts[map.getDiv().get('id')]);
                }.bind(this));
        },
        
        getMapId: function(proxyField) {
                var id = document.id(proxyField).get('id');
                if (this.getOpt(id, 'subfieldof')) id = this.getOpt(id, 'subfieldof');
                return 'mapEditor_'+id;
        },
        
        getMapInitialPoint: function() {
                var point = this.options.initialMapPoint;
                
                if (point == undefined) {
                        point = [9.022736, 38.746799];
                }
                
                if (typeof point == 'string')  {
                        point = point.split(','); 
                }
                
                if (typeOf(point) == 'array') {
                        point = new google.maps.LatLng(parseFloat(point[0]), parseFloat(point[1]));
                }
                
                if (this.options.initialMapPoint == undefined) {
                        this.options.initialMapPoint = point;
                }
                
                return point;
        },
        
        mapAPILoaded: false,
        /**
         * @@todo: try simplegeo
         */
        loadMapAPI: function(proxyField) {
                if (this.mapAPILoaded) return;
                // TODO: proxyField is not passed at all at type initiating time
                var api = this.getOpt(proxyField, 'locationprovider', null, 'maxmind');
                
                if (api == 'maxmind') 
                        this.utility.load('tag', 'http://j.maxmind.com/app/geoip_city', 'js');
                else if (api == 'simplegeo') 
                        this.utility.load('tag', 'http://cdn.simplegeo.com/js/1.4/simplegeo.places.min.js', 'js');
                
                this.mapAPILoaded = true;
        },
        
        getCurrentGeoValues: function(proxyField) {
                try {
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
                                navigator.geolocation.getCurrentPosition(
                                        function(data){ console.log('success:', data.coords.latitude, data.coords.longitude);},
                                        function(error) { console.log('error:', error); },
                                        {timeout:5000}
                                );                                
                        } else if (api == 'server') {
                                // data sent in a json package along with k2fs
                        }
                } catch (exception) { }            
                
                return false;
        },  
        
        getEditorMap: function(proxyField, container, mode) {
                if (!this.options.hasOwnProperty('mapEditors')) {
                        this.options.mapEditors = {};
                }
               
                var mapId = this.getMapId(proxyField), map = this.options.mapEditors[mapId];
                
                if (map != undefined) return map;
                
                var td = container.getParent('.k2fcontainer').getParent();
                
                mode = mode == undefined ? '' : mode.capitalize();
                
                container = new Element('div', {id:mapId, 'class':'mapContainer'+mode});
                container.inject(td, 'top');
                
                var point = this.getMapInitialPoint();
                this.options.initialMapZoom = 13;
                
                var options = {
                        center: point,
                        zoom: this.options.initialMapZoom,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        scaleControl: true,
                        mapTypeControl: true,
                        navigationControl: true,
                        mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
                        navigationControlOptions: {style: google.maps.NavigationControlStyle.DEFAULT},
                        scrollwheel: false
                };

                map = new google.maps.Map(container, options);
                
                this.options.mapEditors[mapId] = map;                
                
                return map;
        },

        getMapViewer: function(container, points, maptype, zoom, colors) {
                if (!maptype) maptype = 'hybrid';
                
                if (!zoom) zoom = 15;
                
                if (!colors) colors = false;
                
                var 
                        i, lbls = new Element('ol', {'class':'mapPointLabels'}),
                        size = container.getStyles('width', 'height'), 
                        n = points.length,
                        url = 'http://maps.google.com/maps/api/staticmap?sensor=false&size='+size['width'].toInt()+'x'+size['height'].toInt()+'&maptype='+maptype+'&zoom='+zoom;
                
                if (colors) {
                        if (typeOf(colors) != 'array') colors = [colors];
                        
                        if (colors.length < n) 
                                for (i = colors.length - 1; i < n; i++) {
                                        colors.push(colors[0]);
                                }
                }
                
                for (i = 0; i < n; i++) {
                        url += '&markers=label:'+(i+1)+'%7C'+
                                (colors ? 'color:'+colors[i]+'%7C' : '')+
                                points[i]['lat'] + ',' + points[i]['lon'];
                        new Element('li', {html:points[i]['label']}).inject(lbls);
                }
                
                new Element('image', {src:url}).inject(container);
                lbls.inject(container.getParent());
        }
};