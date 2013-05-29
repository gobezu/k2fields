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
mxn.register("leaflet",{Mapstraction:{init:function(b,c){if(typeof L.Map==="undefined"){throw new Error(c+" map script not imported")}var d=this;var e=new L.Map(b.id,{zoomControl:false});e.addEventListener("moveend",function(){d.endPan.fire()});e.on("click",function(f){d.click.fire({location:new mxn.LatLonPoint(f.latlng.lat,f.latlng.lng)})});e.on("popupopen",function(f){if(f.popup._source.mxnMarker){f.popup._source.mxnMarker.openInfoBubble.fire({bubbleContainer:f.popup._container})}});e.on("popupclose",function(f){if(f.popup._source.mxnMarker){f.popup._source.mxnMarker.closeInfoBubble.fire({bubbleContainer:f.popup._container})}});e.on("load",function(f){d.load.fire()});e.on("zoomend",function(f){d.changeZoom.fire()});this.layers={};this.features=[];this.maps[c]=e;this.controls={pan:null,zoom:null,overview:null,scale:null,map_type:null};this.road_tile={name:"Roads",attribution:'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">',url:"http://otile{s}.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.jpg"};this.satellite_tile={name:"Satellite",attribution:"Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency",url:"http://otile{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg"};var a=[1,2,3,4];this.addTileLayer(this.satellite_tile.url,1,this.satellite_tile.name,this.satellite_tile.attribution,0,18,true,a);this.addTileLayer(this.road_tile.url,1,this.road_tile.name,this.road_tile.attribution,0,18,true,a);this.currentMapType=mxn.Mapstraction.ROAD;this.loaded[c]=true},applyOptions:function(){if(this.options.enableScrollWheelZoom){this.maps[this.api].scrollWheelZoom.enable()}else{this.maps[this.api].scrollWheelZoom.disable()}return},resizeTo:function(b,a){this.currentElement.style.width=b;this.currentElement.style.height=a;this.maps[this.api].invalidateSize()},addControls:function(a){var b=this.maps[this.api];if("zoom" in a||("pan" in a&&a.pan)){if(a.pan||a.zoom||a.zoom=="large"||a.zoom=="small"){this.addSmallControls()}}else{if(this.controls.zoom!==null){b.removeControl(this.controls.zoom);this.controls.zoom=null}}if("scale" in a&&a.scale){if(this.controls.scale===null){this.controls.scale=new L.Control.Scale();b.addControl(this.controls.scale)}}else{if(this.controls.scale!==null){b.removeControl(this.controls.scale);this.controls.scale=null}}if("map_type" in a&&a.map_type){this.addMapTypeControls()}else{if(this.controls.map_type!==null){b.removeControl(this.controls.map_type);this.controls.map_type=null}}},addSmallControls:function(){var a=this.maps[this.api];if(this.controls.zoom===null){this.controls.zoom=new L.Control.Zoom();a.addControl(this.controls.zoom)}},addLargeControls:function(){return this.addSmallControls()},addMapTypeControls:function(){var a=this.maps[this.api];if(this.controls.map_type===null){this.controls.map_type=new L.Control.Layers(this.layers,this.features);a.addControl(this.controls.map_type)}},setCenterAndZoom:function(a,b){var d=this.maps[this.api];var c=a.toProprietary(this.api);d.setView(c,b)},addMarker:function(b,a){var d=this.maps[this.api];var c=b.toProprietary(this.api);d.addLayer(c);this.features.push(c);return c},removeMarker:function(a){var b=this.maps[this.api];b.removeLayer(a.proprietary_marker)},declutterMarkers:function(a){throw new Error("Mapstraction.declutterMarkers is not currently supported by provider "+this.api)},addPolyline:function(b,a){var c=this.maps[this.api];b=b.toProprietary(this.api);c.addLayer(b);this.features.push(b);return b},removePolyline:function(a){var b=this.maps[this.api];b.removeLayer(a.proprietary_polyline)},getCenter:function(){var b=this.maps[this.api];var a=b.getCenter();return new mxn.LatLonPoint(a.lat,a.lng)},setCenter:function(a,b){var d=this.maps[this.api];var c=a.toProprietary(this.api);if(b&&b.pan){d.panTo(c)}else{d.setView(c,d.getZoom(),true)}},setZoom:function(a){var b=this.maps[this.api];b.setZoom(a)},getZoom:function(){var a=this.maps[this.api];return a.getZoom()},getZoomLevelForBoundingBox:function(c){var b=this.maps[this.api];var a=new L.LatLngBounds(c.getSouthWest().toProprietary(this.api),c.getNorthEast().toProprietary(this.api));return b.getBoundsZoom(a)},setMapType:function(a){switch(a){case mxn.Mapstraction.ROAD:this.layers[this.road_tile.name].bringToFront();this.currentMapType=mxn.Mapstraction.ROAD;break;case mxn.Mapstraction.SATELLITE:this.layers[this.satellite_tile.name].bringToFront();this.currentMapType=mxn.Mapstraction.SATELLITE;break;case mxn.Mapstraction.HYBRID:break;case mxn.Mapstraction.PHYSICAL:break;default:this.layers[this.road_tile.name].bringToFront();this.currentMapType=mxn.Mapstraction.ROAD;break}},getMapType:function(){return this.currentMapType},getBounds:function(){var d=this.maps[this.api];var b=d.getBounds();var a=b.getSouthWest();var c=b.getNorthEast();return new mxn.BoundingBox(a.lat,a.lng,c.lat,c.lng)},setBounds:function(b){var e=this.maps[this.api];var a=b.getSouthWest().toProprietary(this.api);var d=b.getNorthEast().toProprietary(this.api);var c=new L.LatLngBounds(a,d);e.fitBounds(c)},addImageOverlay:function(g,f,b,a,d,c,e){throw new Error("Mapstraction.addImageOverlay is not currently supported by provider "+this.api)},setImagePosition:function(b,a){throw new Error("Mapstraction.setImagePosition is not currently supported by provider "+this.api)},addOverlay:function(a,b){throw new Error("Mapstraction.addOverlay is not currently supported by provider "+this.api)},addTileLayer:function(i,d,f,j,h,e,k,c){var b=this.maps[this.api];var g=this.tileLayers.length||0;var l={minZoom:h,maxZoom:e,name:f,attribution:j,opacity:d};if(typeof c!=="undefined"){l.subdomains=c}var a=mxn.util.sanitizeTileURL(i);this.layers[f]=new L.TileLayer(a,l);b.addLayer(this.layers[f]);this.tileLayers.push([i,this.layers[f],true,g]);if(this.controls.map_type!==null){this.controls.map_type.addBaseLayer(this.layers[f],f)}return this.layers[f]},toggleTileLayer:function(d){var b=this.maps[this.api];for(var a=0;a<this.tileLayers.length;a++){var c=this.tileLayers[a];if(c[0]==d){if(c[2]){c[2]=false;b.removeLayer(c[1])}else{c[2]=true;b.addLayer(c[1])}}}},getPixelRatio:function(){throw new Error("Mapstraction.getPixelRatio is not currently supported by provider "+this.api)},mousePosition:function(a){var b=this.maps[this.api];var c=document.getElementById(a);if(c!==null){b.on("mousemove",function(d){var f=d.latlng.lat.toFixed(4)+"/"+d.latlng.lng.toFixed(4);c.innerHTML=f});c.innerHTML="0.0000 / 0.0000"}},openBubble:function(a,d){var e=this.maps[this.api];var c=a.toProprietary(this.api);var b=new L.Marker(c);b.bindPopup(d);e.addLayer(b);b.openPopup()},closeBubble:function(){var a=this.maps[this.api];a.closePopup()}},LatLonPoint:{toProprietary:function(){return new L.LatLng(this.lat,this.lon)},fromProprietary:function(a){this.lat=a.lat();this.lon=a.lng()}},Marker:{toProprietary:function(){var d=this;var c=null;if(L.Icon.hasOwnProperty("Default")){c=L.Icon.Default}else{c=L.Icon}if(d.iconUrl){c=c.extend({options:{iconUrl:d.iconUrl}})}if(d.iconSize){c=c.extend({options:{iconSize:new L.Point(d.iconSize[0],d.iconSize[1])}})}if(d.iconAnchor){c=c.extend({options:{iconAnchor:new L.Point(d.iconAnchor[0],d.iconAnchor[1])}})}if(d.iconShadowUrl){c=c.extend({options:{shadowUrl:d.iconShadowUrl}})}if(d.iconShadowSize){c=c.extend({options:{shadowSize:new L.Point(d.iconShadowSize[0],d.iconShadowSize[1])}})}var a=new c();var b=new L.Marker(this.location.toProprietary("leaflet"),{icon:a});(function(f,e){e.on("click",function(g){f.click.fire()})})(d,b);return b},openBubble:function(){var a=this.proprietary_marker;if(this.infoBubble){a.mxnMarker=this;a.bindPopup(this.infoBubble);a.openPopup()}},closeBubble:function(){var a=this.proprietary_marker;a.closePopup()},hide:function(){var a=this.mapstraction.maps[this.api];a.removeLayer(this.proprietary_marker)},show:function(){var a=this.mapstraction.maps[this.api];a.addLayer(this.proprietary_marker)},isHidden:function(){var a=this.mapstraction.maps[this.api];if(a.hasLayer(this.proprietary_marker)){return false}else{return true}},update:function(){throw new Error("Marker.update is not currently supported by provider "+this.api)}},Polyline:{toProprietary:function(){var d=[];for(var b=0,c=this.points.length;b<c;b++){d.push(this.points[b].toProprietary("leaflet"))}var a={color:this.color,opacity:this.opacity,weight:this.width,fillColor:this.fillColor};if(this.closed){if(!(this.points[0].equals(this.points[this.points.length-1]))){d.push(d[0])}}else{if(this.points[0].equals(this.points[this.points.length-1])){this.closed=true}}if(this.closed){this.proprietary_polyline=new L.Polygon(d,a)}else{this.proprietary_polyline=new L.Polyline(d,a)}return this.proprietary_polyline},show:function(){this.map.addLayer(this.proprietary_polyline)},hide:function(){this.map.removeLayer(this.proprietary_polyline)},isHidden:function(){if(this.map.hasLayer(this.proprietary_polyline)){return false}else{return true}}}});