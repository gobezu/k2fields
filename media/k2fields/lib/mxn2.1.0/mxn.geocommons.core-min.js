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
mxn.register("geocommons",{Mapstraction:{init:function(a,b){var c=this;if(typeof F1.Maker.Map==="undefined"){throw new Error(b+" map script not imported")}this.element=a;this.loaded[this.api]=false;this.maps[b]=new F1.Maker.Map({dom_id:this.element.id,map_id:143049,uiLayers:false,flashvars:{},onMapLoaded:function(e){c.loaded[c.api]=true;var f=c.onload[c.api].length;for(var d=0;d<f;d++){c.onload[c.api][d]()}c.load.fire()},onMapPanStop:function(){c.endPan.fire()},onMapZoomed:function(){c.changeZoom.fire()},onFeatureSelected:function(){c.click.fire()}})},applyOptions:function(){var a=this.maps[this.api]},resizeTo:function(b,a){var c=this.maps[this.api];c.setSize(b,a)},addControls:function(a){var b=this.maps[this.api];b.setMapStyle({zoom:{visible:a.zoom||false,expanded:(a.zoom=="large")}});b.setMapStyle({layers:{visible:a.layers||false}});b.setMapStyle({legend:{visible:a.legend||false,expanded:true}})},addSmallControls:function(){var a=this.maps[this.api];this.addControls({zoom:"small",legend:"open"})},addLargeControls:function(){var a=this.maps[this.api];this.addControls({zoom:"large",layers:true,legend:"open"})},addMapTypeControls:function(){var a=this.maps[this.api]},dragging:function(a){var b=this.maps[this.api]},setCenterAndZoom:function(a,b){var c=this.maps[this.api];c.setCenterZoom(a.lat,a.lon,b)},getCenter:function(){var b=this.maps[this.api];var a=b.getCenterZoom()[0];return new mxn.LatLonPoint(a.lat,a.lon)},setCenter:function(a,b){var c=this.maps[this.api];c.setCenter(a.lat,a.lon)},setZoom:function(a){var b=this.maps[this.api];b.setZoom(a)},getZoom:function(){var a=this.maps[this.api];return a.getZoom()},getZoomLevelForBoundingBox:function(e){var d=this.maps[this.api];var c=e.getNorthEast();var a=e.getSouthWest();var b;return b},setMapType:function(a){var b=this.maps[this.api];switch(a){case mxn.Mapstraction.ROAD:b.setBasemap("openstreetmap");break;case mxn.Mapstraction.SATELLITE:b.setBasemap("nasabluemarble");break;case mxn.Mapstraction.TERRAIN:b.setBasemap("acetateterrain");break;case mxn.Mapstraction.HYBRID:b.setBasemap("googlehybrid");break;default:b.setBasemap(a)}},getMapType:function(){var a=this.maps[this.api];switch(a.getBasemap().name){case"openstreetmap":return mxn.Mapstraction.ROAD;case"nasabluemarble":return mxn.Mapstraction.SATELLITE;case"acetateterrain":return mxn.Mapstraction.TERRAIN;case"googlehybrid":return mxn.Mapstraction.HYBRID;default:return null}},getBounds:function(){var b=this.maps[this.api];var a=b.getExtent();return new mxn.BoundingBox(a.northWest.lat,a.southEast.lon,a.southEast.lat,a.northWest.lon)},setBounds:function(b){var d=this.maps[this.api];var a=b.getSouthWest();var c=b.getNorthEast();d.setExtent(a.lon,a.lat,c.lon,c.lat)},addImageOverlay:function(c,a,e,i,f,g,d,h){var b=this.maps[this.api]},addOverlay:function(b,c){var d=this.maps[this.api];var a;if(typeof(b)==="number"){d.loadMap(b);return}a=b.match(/^(\d+)$/);if(a!==null){a=b.match(/^.*?maps\/(\d+)(\?\(\[?(.*?)\]?\))?$/)}d.loadMap(a[1])},addTileLayer:function(g,c,e,h,f,d,i,b){var a=this.maps[this.api];a.addLayer({source:"tile:"+g,styles:{fill:{opacity:c||1}}})},toggleTileLayer:function(d){var c=this.maps[this.api];var b=c.getLayers();for(var a=0;a<b.length;++a){if(b[a].source=="tile:"+d){c.showLayer(b[a].guid,!b[a].visible)}}},getPixelRatio:function(){var a=this.maps[this.api]},mousePosition:function(a){var b=this.maps[this.api]},addMarker:function(b,a){var f=this.maps[this.api];var c=b.toProprietary(this.api);var e=f.getLayers();for(var d=0;d<e.length;++d){if(e[d].title=="Edit Layer"){f.addFeatures(e[d].guid,[c],false);f.addLayerInfoWindowFilter(e[d].guid,{title:"$[title]",tabs:[{type:"text",title:"About",value:"$[infoBubble]"}]})}}return c},removeMarker:function(a){var b=this.maps[this.api]},declutterMarkers:function(a){var b=this.maps[this.api]},addPolyline:function(b,a){var d=this.maps[this.api];var c=b.toProprietary(this.api);return c},removePolyline:function(a){var b=this.maps[this.api]}},LatLonPoint:{toProprietary:function(){return{type:"Point",coordinates:[this.lon,this.lat]}},fromProprietary:function(a){this.lon=a.coordinates[0];this.lat=a.coordinates[1]}},Marker:{toProprietary:function(){return{title:this.labelText||"",infoBubble:this.infoBubble||"",geometry:this.location.toProprietary("geocommons")}},openBubble:function(){},closeBubble:function(){},hide:function(){},show:function(){},update:function(){}},Polyline:{toProprietary:function(){return{}},show:function(){},hide:function(){}}});