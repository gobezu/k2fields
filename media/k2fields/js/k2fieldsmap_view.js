//$Copyright$

var k2fieldsmap_view = new Class({
        Implements: [Options],
        
        options: {
                zoom:15, center:[], method:'coord', provider:'', container:'',
                maps:{}, geoCodes:[],
                dataSep:'%%', autoCenterAndZoom:true
        },

        initialize: function(options, data) {
                this.setOptions(options);

                if (this.options.center) {
                        if (typeof this.options.center == 'string') {
                                this.options.center = this.options.center.split(',');
                        }
                        
                        this.options.center = new mxn.LatLonPoint(this.options.center[0], this.options.center[1]);
                }
                
                this.addData(data);
        },

        addData: function(data) {
                if (typeof data == 'string') {
                        new Request({
                                url: data,
                                method: 'get',
                                async: false,
                                onSuccess: function(response){
                                        if (response) {
                                                data = Json.evaluate(response);
                                                this._addData(data);
                                        }
                                }.bind(this)
                        }).send();                        
                } else {
                        this._addData(data);
                }
        },

        _addData: function(data) {
                if (!data) return;
                
                // 0-lat,lon|geodata/1-lbl/2-info/3-center/4-zoom/5-method/6-provider/7-container
                window.addEvent('domready', function(){
                        var d, map, center, zoom, centers = {}, zooms = {}, provider, method, container;

                        for (var i = 0; i < data.length; i++) {
                                d = data[i];
                                
                                method = d.length >= 6 ? d[5] : this.options.method;
                                provider = d.length >= 7 ? d[6] : this.options.provider;
                                container = d.length >= 8 ? d[7] : this.options.container;

                                if (d.length >= 4 && !centers[provider]) {
                                        centers[provider] = d[3];
                                }

                                if (d.length >= 5 && !zooms[provider]) {
                                        zooms[provider] = d[4];
                                }
                        }

                        for (provider in centers) {
                                map = this.options.maps[provider];
                                center = centers[provider] ? centers[provider] : false;
                                
                                if (center) {
                                        zoom = zooms[provider] ? zooms[provider] : this.options.zoom;
                                        
                                        if (zoom) {
                                                map.setCenterAndZoom(center, zoom);
                                        } else {
                                                center = false;
                                        }
                                }

                                if (!center && this.options.autoCenterAndZoom) {
                                        map.autoCenterAndZoom();
                                }
                        }
                        
                        for (var i = 0; i < data.length; i++) {
                                d = data[i];
                                
                                method = d.length >= 6 ? d[5] : this.options.method;
                                provider = d.length >= 7 ? d[6] : this.options.provider;
                                container = d.length >= 8 ? d[7] : this.options.container;

                                this.addMarker([d[0], d[1], d[2]], method, provider, container);
                        }
                        
                        for (provider in centers) {
                                map = this.options.maps[provider];
                                center = centers[provider] ? centers[provider] : false;

                                if (this.options.autoCenterAndZoom) {
                                        map.autoCenterAndZoom();
                                }
                        }
                }.bind(this));
        },

        addMarker: function(data, method, provider, container) {
                if (!this.options.maps.hasOwnProperty(provider)) {
                        var map = new mxn.Mapstraction(container, provider);
                        
                        if (this.options.center) {
                                map.setCenterAndZoom(this.options.center, this.options.zoom);
                        }
                        
                        this.options.maps[provider] = map;
                }

                if (typeof data == 'string') {
                        data = data.split(this.options.dataSep);
                }

                var d;

                if (method == 'coord') {
                        d = data[1].split(',');
                        this._addMarker(d[0], d[1], data[2], data[0], provider);
                } else if (method == 'geo') {
                        var geocoder = new MapstractionGeocoder(this.geoCodeCB, provider);

                        d = data[1].split(', ');

                        d = {
                                street:d[0],
                                locality:d[1],
                                region:d[2],
                                country:d[3]
                        };

                        this.options.geoCodes[this.geoCodeKey(d)] = [data[2], data[0], provider];

                        geocoder.geocode(d);
                }
        },

        _addMarker: function(lat,lon,lbl,info,provider) {
                this.options.maps[provider].addMarkerWithData(
                        new mxn.Marker(new mxn.LatLonPoint(lat, lon)),
                        {
                                infoBubble: info,
                                label: lbl,
                                date : "new Date()",
                                marker: 4,
                                iconShadow: "http://mapufacture.com/images/providers/blank.png",
                                iconShadowSize: [0,0],
                                icon: "http://assets1.mapufacture.com/images/markers/usgs_marker.png",
                                iconSize: [20,20],
                                draggable: false,
                                hover: true
                        }
                );
        },

        geoCodeCB: function(loc) {
                var info = this.options.geoCodes[this.geoCodeKey(loc)];
                this._addMarker(loc.point.lat, loc.point.lon, info[0], info[1], info[2]);
        },

        geoCodeKey: function(loc) {
                var key = loc.street + loc.locality + loc.region + loc.country;
                key = key.replace(/[^\w\d]/ig, '');
                return key;
        }
});

if (MooTools.version.substr(2, 1) == 1) {
        k2fieldsmap_view.implement(new Options);
}
