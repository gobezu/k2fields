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
mxn.register("yahoo",{Mapstraction:{init:function(a,b){throw new Error("The Yahoo! Maps API is now obsolete and no longer supported by Mapstraction")},applyOptions:function(){},resizeTo:function(b,a){this.maps[this.api].resizeTo(new YSize(b,a))},addControls:function(a){var b=this.maps[this.api];if(a.pan){b.addPanControl()}else{b.addPanControl();b.removePanControl()}if(a.zoom=="large"){b.addZoomLong()}else{if(a.zoom=="small"){b.addZoomShort()}else{b.removeZoomScale()}}},addSmallControls:function(){var a=this.maps[this.api];a.addPanControl();a.addZoomShort();this.addControlsArgs.pan=true;this.addControlsArgs.zoom="small"},addLargeControls:function(){var a=this.maps[this.api];a.addPanControl();a.addZoomLong();this.addControlsArgs.pan=true;this.addControlsArgs.zoom="large"},addMapTypeControls:function(){var a=this.maps[this.api];a.addTypeControl()},dragging:function(a){var b=this.maps[this.api];if(a){b.enableDragMap()}else{b.disableDragMap()}},setCenterAndZoom:function(a,c){var e=this.maps[this.api];var d=a.toProprietary(this.api);var b=18-c;e.drawZoomAndCenter(d,b)},addMarker:function(b,a){var d=this.maps[this.api];var c=b.toProprietary(this.api);d.addOverlay(c);YEvent.Capture(c,EventsList.MouseClick,function(){b.click.fire()});YEvent.Capture(c,EventsList.openSmartWindow,function(){b.openInfoBubble.fire()});YEvent.Capture(c,EventsList.closeSmartWindow,function(){b.closeInfoBubble.fire()});return c},removeMarker:function(a){var b=this.maps[this.api];b.removeOverlay(a.proprietary_marker)},declutterMarkers:function(a){throw"Not supported"},addPolyline:function(b,a){var d=this.maps[this.api];var c=b.toProprietary(this.api);d.addOverlay(c);return c},removePolyline:function(a){var b=this.maps[this.api];b.removeOverlay(a.proprietary_polyline)},getCenter:function(){var c=this.maps[this.api];var b=c.getCenterLatLon();var a=new mxn.LatLonPoint(b.Lat,b.Lon);return a},setCenter:function(a,b){var d=this.maps[this.api];var c=a.toProprietary(this.api);d.panToLatLon(c)},setZoom:function(b){var c=this.maps[this.api];var a=18-b;c.setZoomLevel(a)},getZoom:function(){var a=this.maps[this.api];return 18-a.getZoomLevel()},getZoomLevelForBoundingBox:function(a){throw"Not implemented"},setMapType:function(a){var b=this.maps[this.api];switch(a){case mxn.Mapstraction.ROAD:b.setMapType(YAHOO_MAP_REG);break;case mxn.Mapstraction.SATELLITE:b.setMapType(YAHOO_MAP_SAT);break;case mxn.Mapstraction.HYBRID:b.setMapType(YAHOO_MAP_HYB);break;default:b.setMapType(YAHOO_MAP_REG)}},getMapType:function(){var b=this.maps[this.api];var a=b.getCurrentMapType();switch(a){case YAHOO_MAP_REG:return mxn.Mapstraction.ROAD;case YAHOO_MAP_SAT:return mxn.Mapstraction.SATELLITE;case YAHOO_MAP_HYB:return mxn.Mapstraction.HYBRID;default:return null}},getBounds:function(){var b=this.maps[this.api];var a=b.getBoundsLatLon();return new mxn.BoundingBox(a.LatMin,a.LonMin,a.LatMax,a.LonMax)},setBounds:function(a){var c=this.maps[this.api];var h=a.getSouthWest();var e=a.getNorthEast();if(h.lon>e.lon){h.lon-=360}var b=new YGeoPoint((h.lat+e.lat)/2,(e.lon+h.lon)/2);var d=c.getContainerSize();for(var i=1;i<=17;i++){var f=mxn.util.convertLatLonXY_Yahoo(h,i);var g=mxn.util.convertLatLonXY_Yahoo(e,i);if(f.x>g.x){f.x-=(1<<(26-i))}if(Math.abs(g.x-f.x)<=d.width&&Math.abs(g.y-f.y)<=d.height){c.drawZoomAndCenter(b,i);break}}},addImageOverlay:function(h,g,b,a,e,d,f,c){throw"Not implemented"},setImagePosition:function(a){throw"Not implemented"},addOverlay:function(a,b){var c=this.maps[this.api];c.addOverlay(new YGeoRSS(a))},addTileLayer:function(h,d,c,b,f,g,e,a){throw"Not implemented"},toggleTileLayer:function(a){throw"Not implemented"},getPixelRatio:function(){throw"Not implemented"},mousePosition:function(a){throw"Not implemented"}},LatLonPoint:{toProprietary:function(){return new YGeoPoint(this.lat,this.lon)},fromProprietary:function(a){this.lat=a.Lat;this.lon=a.Lon}},Marker:{toProprietary:function(){var b,d;var f,c,a,e;if(this.iconSize){d=new YSize(this.iconSize[0],this.iconSize[1])}if(this.iconUrl){if(this.iconSize){b=new YMarker(this.location.toProprietary("yahoo"),new YImage(this.iconUrl,d))}else{b=new YMarker(this.location.toProprietary("yahoo"),new YImage(this.iconUrl))}}else{if(this.iconSize){b=new YMarker(this.location.toProprietary("yahoo"),null,d)}else{b=new YMarker(this.location.toProprietary("yahoo"))}}if(this.labelText){b.addLabel(this.labelText)}if(this.infoBubble){f=this.infoBubble;if(this.hover){c=EventsList.MouseOver}else{c=EventsList.MouseClick}YEvent.Capture(b,c,function(){b.openSmartWindow(f)})}if(this.infoDiv){a=this.infoDiv;e=this.div;if(this.hover){c=EventsList.MouseOver}else{c=EventsList.MouseClick}YEvent.Capture(b,c,function(){document.getElementById(e).innerHTML=a})}return b},openBubble:function(){var a=this.proprietary_marker;a.openSmartWindow(this.infoBubble)},closeBubble:function(){var a=this.proprietary_marker;a.closeSmartWindow()},hide:function(){this.proprietary_marker.hide()},show:function(){this.proprietary_marker.unhide()},update:function(){throw"Not implemented"}},Polyline:{toProprietary:function(){var d;var c=[];for(var a=0,b=this.points.length;a<b;a++){c.push(this.points[a].toProprietary("yahoo"))}d=new YPolyline(c,this.color,this.width,this.opacity);return d},show:function(){throw"Not implemented"},hide:function(){throw"Not implemented"}}});