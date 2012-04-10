<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2FieldsMap {
        const MAP_MAPSTRACTION_FOLDER = 'mxn-2.0.15';
        const MAP_MAPSTRACTION_DEV = ''; // Add -min for production run
        const MAP_CONTAINER_ID = 'mapContainer';
        const MAP_JS_VARNAME = '_k2fs_map';
        const MAP_DEFAULT_PROVIDER = 'google';
        const MAP_DEFAULT_METHOD = 'coord';
        const MAP_DEFAULT_CENTER = '';
        const MAP_DEFAULT_ZOOM = 15;
        
        private static $renderedProviders = array(), $isCoreLoaded = false, $isJSCreated = false;
        
        private static function val($options, $name, $default) {
                return K2FieldsModelFields::value($options, $name, $default);
        }
        
        /**
         * Field definition:
         * provider = mapstraction compatible provider (default googlev3)
         * method = coord (default) | geo (if available for provider)
         * center = lat%%lon
         * zoom = specific to provider
         *
         * Field value:
         * coord = lat%%lon%%label%%info
         * geo = stree%%locality%%region%%country%%label%%info
         * 
         * @@todo
         * 1. add controller
         * 2. sources (json, csv, kml)
         * 3. varying marker icons
         * 4. interactive map based editing for both methods (in particular coord)
         */
        public static function render($item, $values, $field, $helper, $rule = null) {
                $mapType = $helper->value($field, 'itemmaptype');
                $ui = '';
                $proxyFieldId = 'mapview'.$helper->value($item, 'id').'_'.$helper->value($field, 'id');
                
                if ($mapType == 'static') {
                        $data = array();
                        
                        foreach ($values as $i => $value) {
                                $data[] = array('lat'=>$value[0]->lat, 'lon'=>$value[0]->lng, 'label'=>$value[1]->value);
                        }
                        
                        $data = json_encode($data);
                        
                        $mapType = $helper->value($field, 'staticmapmaptype');
                        $zoom = $helper->value($field, 'staticmapzoom');
                        
                        $ui = '
                                <div id="'.$proxyFieldId.'" class="mapContainerItem"></div>
                                <script type="text/javascript">
                                window.addEvent("load", function() {
                                        '.K2FieldsModelFields::JS_VAR_NAME.'.enqueueType(
                                                "map",
                                                null, null, null, null,
                                                function() {
                                                        '.K2FieldsModelFields::JS_VAR_NAME.'.getMapViewer($("'.$proxyFieldId.'"), '.$data.', "'.$mapType.'", '.$zoom.', "'.$helper->value($field, 'markercolor').'");
                                                }
                                        );
                                });
                                </script>
                                ';
                } else {
                        $ui = '
                                <table><tr><td>
                                <div><input type="hidden" id="'.$proxyFieldId.'" />';

                        $id = '';

                        // TODO: support geoencoding as well
                        foreach ($values as $i => $value) {
                                $id = $proxyFieldId.'_'.$i;

                                $ui .= '
                                        <span class="k2fcontainer">
                                        <span id="'.$id.'"></span>
                                        <input type="hidden" value="'.$value[0]->lat.'" id="'.$id.'0" customvalueholder="true" />
                                        <input type="hidden" value="'.$value[0]->lng.'" id="'.$id.'1" customvalueholder="true" />
                                        </span>';
                        }

                        $ui .= '
                                </div></td></tr></table>
                                <script type="text/javascript">
                                window.addEvent("load", function() {
                                        '.K2FieldsModelFields::JS_VAR_NAME.'.enqueueType(
                                                "map",
                                                null, null, null, null,
                                                function() {
                                                        var map = '.K2FieldsModelFields::JS_VAR_NAME.'.getEditorMap($("'.$proxyFieldId.'"), $("'.$id.'"), "item");
                                                        '.K2FieldsModelFields::JS_VAR_NAME.'.redrawMapEditor(map);
                                                }
                                        );
                                });
                                </script>
                                ';
                }
                
                return $ui;
                // infowindow
        }
        
        public static function getParameters($field = null, $options = null) {
                if (empty($options)) $options = $field;
                
                $options['mapprovider'] = K2FieldsModelFields::setting('mapprovider', $options, 'google');
                $options['mapcontainerid'] = K2FieldsModelFields::setting('mapcontainerid', $options, K2FieldsMap::MAP_CONTAINER_ID.'_'.$options['mapprovider']);
                $options['mapapikey'] = K2FieldsModelFields::setting('mapapikey', $options);
                $options['mapcenter'] = K2FieldsModelFields::setting('mapcenter', $options);
                $options['mapzoom'] = K2FieldsModelFields::setting('mapzoom', $options, K2FieldsMap::MAP_DEFAULT_ZOOM);
                $options['geocodingmethod'] = K2FieldsModelFields::setting('geocodingmethod', $options, K2FieldsMap::MAP_DEFAULT_METHOD);
                $options['locationprovider'] = K2FieldsModelFields::setting('locationprovider', $options);
                $options['locationproviderfunction'] = K2FieldsModelFields::setting('locationproviderfunction', $options);
                $options['simplegeojsonptoken'] = K2FieldsModelFields::setting('simplegeojsonptoken', $options);
                $options['itemmaptype'] = K2FieldsModelFields::setting('itemmaptype', $options);
                $options['staticmapmaptype'] = K2FieldsModelFields::setting('staticmapmaptype', $options);
                $options['staticmapzoom'] = K2FieldsModelFields::setting('staticmapzoom', $options);
                
                return $options;
        }
        
        public static function _render($item, $values, $field, $helper, $rule = null) {
                $provider = self::val($field, 'provider', plgk2k2fields::param('mapProvider', K2FieldsMap::MAP_DEFAULT_PROVIDER));

                if ($provider == 'google') {
                        $provider = 'googlev3';
                } else if ($provider == 'googlev2') {
                        $provider = 'google';
                }
                
                $mapMethod = self::val($field, 'method', plgk2k2fields::param('mapMethod', K2FieldsMap::MAP_DEFAULT_METHOD));
                $center = self::val($field, 'center', plgk2k2fields::param('mapCenter', K2FieldsMap::MAP_DEFAULT_CENTER));
                $zoom = self::val($field, 'zoom', plgk2k2fields::param('mapZoom', K2FieldsMap::MAP_DEFAULT_ZOOM));
                $apiKey = self::val($field, 'apikey', plgk2k2fields::param('mapAPIKey', K2FieldsMap::providers($provider, 'apikey')));
                
                $providerSrc = $ui = $container = '';
                
                if (!isset(K2FieldsMap::$renderedProviders[$provider])) {
                        switch ($provider) {
                                case 'google':
                                        $providerSrc = 'http://maps.google.com/maps?file=api&v=2&key=' . $apiKey;
                                        break;
                                case 'yahoo':
                                        $providerSrc = 'http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=' . $apiKey;
                                        break;
                                case 'microsoft':
                                        $providerSrc = 'http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6';
                                        break;
                                case 'mapquest':
                                        $providerSrc = 'http://btilelog.beta.mapquest.com/tilelog/transaction?transaction=script&key='.$apiKey.'&itk=true&v=5.3.0_RC5&ipkg=controls1';
                                case 'cloudmade':
                                case 'openlayers':
                                        $providerSrc = 'http://openlayers.org/api/OpenLayers.js';
                                        break;
                                case 'googlev3':
                                default:
                                        $providerSrc = 'http://maps.google.com/maps/api/js?sensor=false';
                                        break;
                        }

                        K2FieldsMap::$renderedProviders[$provider] = 1;
                        $container = plgk2k2fields::param('mapContainerID', K2FieldsMap::MAP_CONTAINER_ID.'_'.$provider);
                        $ui = '<div id="'.$container.'" style="width:500px;height:500px;"></div>';
                } else {
                        K2FieldsMap::$renderedProviders[$provider]++;
                }

                $document = JFactory::getDocument();

                if (!empty($providerSrc)) {
                        $document->addScript($providerSrc);
                        
                        if (!K2FieldsMap::$isCoreLoaded) {
                                self::add('mxn');
                                self::add('mxn.core');

                                if ($mapMethod == 'geo' && K2FieldsMap::providers($provider, 'geo')) self::add('mxn.geocoder');
                        }
                        
                        self::add('mxn.'.$provider.'.core');
                        
                        if (!K2FieldsMap::$isCoreLoaded) K2FieldsMap::$isCoreLoaded = true;
                }

                if (!K2FieldsMap::$isJSCreated && !empty($providerSrc)) {
                        JprovenUtility::loc(true, true, 'k2fieldsmap_view.js', true);
                        
                        $document->addScriptDeclaration('
                                var '.self::MAP_JS_VARNAME.' = new k2fieldsmap_view({
                                        method: "' . $mapMethod . '",
                                        provider: "' . $provider . '",
                                        container: "' . $container . '",
                                        center: ' . (empty($center) ? 'false' : '['. $center  . ']') . ',
                                        zoom: ' . (empty($zoom) ? false : $zoom) . ',
                                        dataSep: "' . K2FieldsModelFields::VALUE_SEPARATOR . '"
                                });
                        ');
                        
                        K2FieldsMap::$isJSCreated = true;
                }
                
                $document->addScriptDeclaration(K2FieldsMap::MAP_JS_VARNAME.'.addData(' . json_encode($values) . ');');
                
                return $ui;
        }
        
        private static function add($script) {
                JprovenUtility::loc(true, true, K2FieldsMap::MAP_MAPSTRACTION_FOLDER.'/'.$script.K2FieldsMap::MAP_MAPSTRACTION_DEV.'.js', true);
        }

        static function providers($provider, $attr) {
                $attrValues = array(
                    'google' => array('apikey'=>plgk2k2fields::param('mapAPIKey'), 'geo'=>true),
                    'googlev3' => array('apikey'=>'', 'geo'=>true),
                    'mapquest' => array('apikey'=>plgk2k2fields::param('mapAPIKey'), 'geo'=>true),
                    'yandex' => array('apikey'=>'', 'geo'=>true),
                    'yahoo' => array('apikey'=>plgk2k2fields::param('mapAPIKey'), 'geo'=>true),
                    'microsoft' => array('apikey'=>'', 'geo'=>true),
                    'yandex' => array('apikey'=>'', 'geo'=>true),
                    'openlayers' => array('apikey'=>'', 'geo'=>true),
                    'cloudmade' => array('apikey'=>'', 'geo'=>true)
                );

                return $attrValues[$provider][$attr];
        }  
        
        
}

?>