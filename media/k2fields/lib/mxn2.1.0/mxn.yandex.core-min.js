/*
MAPSTRACTION   v3.0.0   http://www.mapstraction.com

The BSD 3-Clause License (http://www.opensource.org/licenses/BSD-3-Clause)

Copyright (c) 2013 Tom Carden, Steve Coast, Mikel Maron, Andrew Turner, Henri Bergius, Rob Moran, Derek Fowler, Gary Gale
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * Neither the name of the Mapstraction nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
mxn.register("yandex",{Mapstraction:{init:function(b,c){var d=this;if(typeof YMaps.Map==="undefined"){throw new Error(c+" map script not imported")}this.controls={pan:null,zoom:null,overview:null,scale:null,map_type:null};var a=this.maps[c]=new YMaps.Map(b);YMaps.Events.observe(a,a.Events.Click,function(f,h){var e=h.getCoordPoint().getX();var g=h.getCoordPoint().getY();d.click.fire({location:new mxn.LatLonPoint(e,g)})});YMaps.Events.observe(a,a.Events.BoundsChange,function(f,e){d.changeZoom.fire()});YMaps.Events.observe(a,a.Events.ZoomRangeChange,function(f,e){d.changeZoom.fire()});YMaps.Events.observe(a,a.Events.Update,function(e){d.endPan.fire()});YMaps.Events.observe(a,a.Events.AddLayer,function(f,e){d.load.fire()});this.loaded[c]=true},applyOptions:function(){var a=this.maps[this.api];if(this.options.enableScrollWheelZoom){a.enableScrollZoom(true)}if(this.options.enableDragging){a.enableDragging()}else{a.disableDragging()}},resizeTo:function(b,a){this.currentElement.style.width=b;this.currentElement.style.height=a;this.maps[this.api].redraw()},addControls:function(a){var b=this.maps[this.api];if("pan" in a&&a.pan){if(this.controls.pan!==null){this.controls.pan=new YMaps.ToolBar();b.addControl(this.controls.pan)}}else{if(this.controls.pan!==null){b.removeControl(this.controls.pan);this.controls.pan=null}}if("zoom" in a){if(a.zoom||a.zoom=="small"){this.addSmallControls()}else{if(a.zoom=="large"){this.addLargeControls()}}}else{if(this.controls.zoom!==null){b.removeControl(this.controls.zoom);this.controls.zoom=null}}if("overview" in a){if(this.controls.overview===null){if(typeof(a.overview)!="number"){a.overview=5}this.controls.overview=new YMaps.MiniMap(a.overview);b.addControl(this.controls.overview)}}else{if(this.controls.overview!==null){b.removeControl(this.controls.overview);this.controls.overview=null}}if("scale" in a&&a.scale){if(this.controls.scale===null){this.controls.scale=new YMaps.ScaleLine();b.addControl(this.controls.scale)}}else{if(this.controls.scale!==null){b.removeControl(this.controls.scale);this.controls.scale=null}}if("map_type" in a&&a.map_type){this.addMapTypeControls()}else{if(this.controls.map_type!==null){b.removeControl(this.controls.map_type);this.controls.map_type=null}}},addSmallControls:function(){var a=this.maps[this.api];if(this.controls.zoom!==null){a.removeControl(this.controls.zoom)}this.controls.zoom=new YMaps.SmallZoom();a.addControl(this.controls.zoom)},addLargeControls:function(){var a=this.maps[this.api];if(this.controls.zoom!==null){a.removeControl(this.controls.zoom)}this.controls.zoom=new YMaps.Zoom();a.addControl(this.controls.zoom)},addMapTypeControls:function(){var a=this.maps[this.api];if(this.controls.map_type===null){this.controls.map_type=new YMaps.TypeControl();a.addControl(this.controls.map_type)}},setCenterAndZoom:function(a,b){var d=this.maps[this.api];var c=a.toProprietary(this.api);d.setCenter(c,b)},addMarker:function(b,a){var d=this.maps[this.api];var c=b.toProprietary(this.api);d.addOverlay(c);return c},removeMarker:function(a){var b=this.maps[this.api];b.removeOverlay(a.proprietary_marker)},declutterMarkers:function(a){throw new Error("Mapstraction.declutterMarkers is not currently supported by provider "+this.api)},addPolyline:function(b,a){var d=this.maps[this.api];var c=b.toProprietary(this.api);d.addOverlay(c);return c},removePolyline:function(a){var b=this.maps[this.api];b.removeOverlay(a.proprietary_polyline)},getCenter:function(){var c=this.maps[this.api];var b=c.getCenter();var a=new mxn.LatLonPoint(b.getLat(),b.getLng());return a},setCenter:function(a,b){var d=this.maps[this.api];var c=a.toProprietary(this.api);d.setCenter(c)},setZoom:function(a){var b=this.maps[this.api];b.setZoom(a)},getZoom:function(){var b=this.maps[this.api];var a=b.getZoom();return a},getZoomLevelForBoundingBox:function(e){var d=this.maps[this.api];var c=e.getNorthEast().toProprietary(this.api);var a=e.getSouthWest().toProprietary(this.api);var b=new YMaps.GeoBounds(c,a).getMapZoom(d);return b},setMapType:function(a){var b=this.maps[this.api];switch(a){case mxn.Mapstraction.ROAD:b.setType(YMaps.MapType.MAP);break;case mxn.Mapstraction.SATELLITE:b.setType(YMaps.MapType.SATELLITE);break;case mxn.Mapstraction.HYBRID:b.setType(YMaps.MapType.HYBRID);break;default:b.setType(a||YMaps.MapType.MAP)}},getMapType:function(){var b=this.maps[this.api];var a=b.getType();switch(a){case YMaps.MapType.MAP:return mxn.Mapstraction.ROAD;case YMaps.MapType.SATELLITE:return mxn.Mapstraction.SATELLITE;case YMaps.MapType.HYBRID:return mxn.Mapstraction.HYBRID;default:return null}},getBounds:function(){var d=this.maps[this.api];var b=d.getBounds();var c=b.getLeftBottom();var a=b.getRightTop();return new mxn.BoundingBox(c.getLat(),c.getLng(),a.getLat(),a.getLng())},setBounds:function(d){var g=this.maps[this.api];var b=d.getSouthWest();var f=d.getNorthEast();var a=new YMaps.GeoPoint(b.lon,b.lat);var c=new YMaps.GeoPoint(f.lon,f.lat);var e=new YMaps.GeoBounds(a,c);g.setZoom(e.getMapZoom(g));g.setCenter(e.getCenter())},addImageOverlay:function(c,a,g,l,h,i,e,k){var b=this.maps[this.api];var f=this;var j=function(m){var n;this.onAddToMap=function(p,o){n=o;n.appendChild(m);this.onMapUpdate()};this.onRemoveFromMap=function(){if(n){n.removeChild(m)}};this.onMapUpdate=function(){f.setImagePosition(c)}};var d=new j(k.imgElm);b.addOverlay(d);this.setImageOpacity(c,g);this.setImagePosition(c)},setImagePosition:function(g,d){var f=this.maps[this.api];var c=new YMaps.GeoPoint(d.latLng.left,d.latLng.top);var b=new YMaps.GeoPoint(d.latLng.right,d.latLng.bottom);var e=f.converter.coordinatesToMapPixels(c);var a=f.converter.coordinatesToMapPixels(b);d.pixels.top=e.y;d.pixels.left=e.x;d.pixels.bottom=a.y;d.pixels.right=a.x},addOverlay:function(b,c){var d=this.maps[this.api];var a=new YMaps.KML(b);d.addOverlay(a);YMaps.Events.observe(a,a.Events.Fault,function(e,f){throw new Error("Mapstraction.addOverlay. KML upload error: "+f+" for provider "+this.api)})},addTileLayer:function(m,f,j,n,l,g,o,d){var b=this.maps[this.api];var a=new YMaps.TileDataSource(m,true,true);a.getTileUrl=function(p,q){return this._tileUrlTemplate.replace(/\{X\}/gi,p.x).replace(/\{Y\}/gi,p.y).replace(/\{Z\}/gi,q)};var k=new YMaps.Layer(a);k._$element.css("opacity",f);if(o){var i=Math.round(Math.random()*Date.now()).toString();YMaps.Layers.add(i,k);var c=new YMaps.MapType([i],copyright_text,{textColor:"#706f60",minZoom:l,maxZoom:g});var h;for(var e in b.__controls){if(b.__controls[e] instanceof YMaps.TypeControl){h=b.__controls[e];break}}if(!h){h=new YMaps.TypeControl();b.addControl(h)}h.addType(c)}else{b.addLayer(k);b.addCopyright(n)}this.tileLayers.push([m,k,true]);return k},toggleTileLayer:function(c){var b=this.maps[this.api];for(var a=0;a<this.tileLayers.length;a++){if(this.tileLayers[a][0]==c){if(this.tileLayers[a][2]){this.maps[this.api].removeLayer(this.tileLayers[a][1]);this.tileLayers[a][2]=false}else{this.maps[this.api].addLayer(this.tileLayers[a][1]);this.tileLayers[a][2]=true}}}},getPixelRatio:function(){throw new Error("Mapstraction.getPixelRatio is not currently supported by provider "+this.api)},mousePosition:function(a){var c=document.getElementById(a);if(c!==null){var b=this.maps[this.api];YMaps.Events.observe(b,b.Events.MouseMove,function(e,g){var d=g.getGeoPoint();var f=d.getY().toFixed(4)+" / "+d.getX().toFixed(4);c.innerHTML=f});c.innerHTML="0.0000 / 0.0000"}}},LatLonPoint:{toProprietary:function(){return new YMaps.GeoPoint(this.lon,this.lat)},fromProprietary:function(a){this.lat=a.getLat();this.lon=a.getLng();return this}},Marker:{toProprietary:function(){var c={hideIcon:false,draggable:this.draggable};if(this.iconUrl){var e=new YMaps.Style();var d=e.iconStyle=new YMaps.IconStyle();d.href=this.iconUrl;if(this.iconSize){d.size=new YMaps.Point(this.iconSize[0],this.iconSize[1]);var b;if(this.iconAnchor){b=new YMaps.Point(this.iconAnchor[0],this.iconAnchor[1])}else{b=new YMaps.Point(0,0)}d.offset=b}if(this.iconShadowUrl){d.shadow=new YMaps.IconShadowStyle();d.shadow.href=this.iconShadowUrl;if(this.iconShadowSize){d.shadow.size=new YMaps.Point(this.iconShadowSize[0],this.iconShadowSize[1]);d.shadow.offset=new YMaps.Point(0,0)}}c.style=e}var a=new YMaps.Placemark(this.location.toProprietary("yandex"),c);if(this.hoverIconUrl){var f=this;YMaps.Events.observe(a,a.Events.MouseEnter,function(h,i){var g=a.getOptions();if(!f.iconUrl){f.iconUrl=a._icon._context._computedStyle.iconStyle.href;g.style=a._icon._context._computedStyle}g.style.iconStyle.href=f.hoverIconUrl;a.setOptions(g)});YMaps.Events.observe(a,a.Events.MouseLeave,function(h,i){var g=a.getOptions();g.style.iconStyle.href=f.iconUrl;a.setOptions(g)})}if(this.labelText){a.name=this.labelText}if(this.infoBubble){a.setBalloonContent(this.infoBubble)}YMaps.Events.observe(a,a.Events.DragEnd,function(h){var g=new mxn.LatLonPoint().fromProprietary("yandex",h.getGeoPoint());this.mapstraction_marker.location=g;this.mapstraction_marker.dragend.fire(g)});return a},openBubble:function(){this.proprietary_marker.openBalloon()},closeBubble:function(){this.proprietary_marker.closeBalloon()},hide:function(){this.proprietary_marker._$iconContainer.addClass("YMaps-display-none")},show:function(){this.proprietary_marker._$iconContainer.removeClass("YMaps-display-none")},update:function(){point=new mxn.LatLonPoint();point.fromProprietary("yandex",this.proprietary_marker.getGeoPoint());this.location=point}},Polyline:{toProprietary:function(){var d=[];for(var b=0,c=this.points.length;b<c;b++){d.push(this.points[b].toProprietary("yandex"))}var a={style:{lineStyle:{strokeColor:this.color.replace("#",""),strokeWidth:this.width}}};if(this.closed||d[0].equals(d[c-1])){a.style.polygonStyle=a.style.lineStyle;if(this.fillColor){a.style.polygonStyle.fill=true;var e=(Math.round((this.opacity||1)*255)).toString(16);a.style.polygonStyle.fillColor=this.fillColor.replace("#","")+e}return new YMaps.Polygon(d,a)}else{return new YMaps.Polyline(d,a)}},hide:function(){this.proprietary_polyline._container._$container.addClass("YMaps-display-none")},show:function(){this.proprietary_polyline._container._$container.removeClass("YMaps-display-none")}}});