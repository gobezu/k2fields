<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2FieldsMap {
        const MAP_MAPSTRACTION_FOLDER = 'lib/mxn2.0.0';
        const MAP_MAPSTRACTION_DEV = ''; // Add -min for production run
        const MAP_CONTAINER_ID = 'mapContainer';
        const MAP_JS_VARNAME = '_k2fs_map';
        const MAP_DEFAULT_PROVIDER = 'googlev3';
        const MAP_DEFAULT_METHOD = 'coord';
        const MAP_DEFAULT_CENTER = '';
        const MAP_DEFAULT_ZOOM = 15;
        const MAP_TYPE = 1;     // road
        const MAP_CONTAINER_CLASS = 'mapContainer';
        const MAP_ICON_COLOR = 'orange';
        
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
        
        // TODO: clarify that only one field / category is assumed to be of type map
        private static $containerId = null;
        
        public static function containerId($field = null, $item = null) {
                if (self::$containerId) return self::$containerId;
                
                $uiId = self::v($field, 'mapcontainerid');
                $item = $item ? K2FieldsModelFields::value($item, 'id') : '';
                $field = K2FieldsModelFields::value($field, 'id');
                $uiId = str_replace(array('%item%', '%id%'), array($item, $field), $uiId);
                
                return $uiId;
        }
        
        public static function render($item, $values, $field, $helper, $rule = null) {
                $ui = '';
                
                $view = JRequest::getCmd('view', 'itemlist');
                
                if ($view == 'item' && K2FieldsModelFields::value($field, 'mapstatic')) {
                        $ui = self::renderStaticMap($field, $values, $item);
                } else {
                        self::renderDynamicMap($field, $values, $item);
                        if ($view == 'item') $ui = self::finalizeMap();
                }
                
                return $ui;
        }
        
        private static function renderStaticMap($field, $values, $item) {
                $uiId = self::containerId($field, $item);
                $data = array();

                foreach ($values as $i => $value) {
                        $data[] = array('lat'=>$value[0]->lat, 'lon'=>$value[0]->lng, 'label'=>$value[1]->value);
                }

                $mapType = self::v($field, 'maptype');
                $zoom = self::v($field, 'mapzoom');
                $color = self::v($field, 'mapiconcolor');

                $data = array('points'=>$data, 'container'=>$uiId, 'maptype'=>$mapType, 'zoom'=>$zoom, 'color'=>$color);
                $data = json_encode($data);

                return 
'
<div id="'.$uiId.'" class="staticMapContainer"></div>
<script type="text/javascript">
window.addEvent("load", function() {
        '.K2FieldsModelFields::JS_VAR_NAME.'.enqueueType(
                "map",
                null, null, null, null,
                function() {
                        '.K2FieldsModelFields::JS_VAR_NAME.'.drawStaticMap('.$data.');
                }
        );
});
</script>
';                
        }
        
        static $ui = array('pre'=>'', 'post'=>'', 'data'=>array()), $map = array();
        
        private static function renderDynamicMap($field, $values, $item) {
                $proxyFieldId = K2FieldsModelFields::pre() . K2FieldsModelFields::value($field, 'id');
                
                if (!isset(self::$map[$proxyFieldId])) 
                        self::$map[$proxyFieldId] = array('params' => self::getParameters($field), 'items' => array());
                
                if (!isset(self::$map[$proxyFieldId]['items'][$item->id])) 
                        self::$map[$proxyFieldId]['items'][$item->id] = array(
                                'points' => array(),
                                'title' => $item->title,
                                'rendered' => $item->rendered,
                                'link' => $item->link
                        );
                
                foreach ($values as $i => $value) {
                        self::$map[$proxyFieldId]['items'][$item->id]['points'][] = array(
                            'lat' => $value[0]->lat,
                            'lon' => $value[0]->lng,
                            'lbl' => $value[1]->value
                        );
                }
                
                return;
                
                if (!isset(self::$ui['data'][$item->id])) 
                        self::$ui['data'][$item->id] = array('item'=>$item, 'points'=>array());
                
                $id = $anchorId = '';
                
                // TODO: support geoencoding as well
                
                foreach ($values as $i => $value) {
                        $id = $proxyFieldId.'_'.$i;
                        
                        if (empty($anchorId)) $anchorId = $id;

                        self::$ui['data'][$item->id]['points'][] = 
                                '
                                <span class="k2fcontainer">
                                <span id="'.$id.'"></span>
                                <input type="hidden" value="'.$value[0]->lat.'" id="'.$id.'0" customvalueholder="true" />
                                <input type="hidden" value="'.$value[0]->lng.'" id="'.$id.'1" customvalueholder="true" />
                                </span>
                                ';
                }

                if (empty(self::$ui['post'])) {
                        self::$ui['pre'] = 
'
<table><tr><td>
<div style="display:none;">
<input type="hidden" id="'.$proxyFieldId.'" />
<input type="hidden" id="'.$proxyFieldId.'" />        
'
                                ;

                        $params = self::getParameters($field);
                        $params = json_encode($params);

                        self::$ui['post'] = 
'
</div></td></tr></table>
<script type="text/javascript">
window.addEvent("load", function() {
        '.K2FieldsModelFields::JS_VAR_NAME.'.enqueueType(
                "map",
                null, null, null, null,
                function() {
                        '.K2FieldsModelFields::JS_VAR_NAME.'.fieldsOptions["'.$proxyFieldId.'"] = '.$params.';
                        var map = '.K2FieldsModelFields::JS_VAR_NAME.'.getEditorMap(document.id("'.$proxyFieldId.'"), document.id("'.$anchorId.'"), "item");
                        '.K2FieldsModelFields::JS_VAR_NAME.'.redrawMapEditor("'.$proxyFieldId.'");
                }
        );
});
</script>
';               
                }
        }
        
        public static function finalizeMap() {
                $ui = '';
                
                foreach (self::$map as $proxyFieldId => $m) {
                        $params = json_encode($m['params']);
                        $items = json_encode($m['items']);
                        
                        $ui .= '
<div id="'.$m['params']['mapcontainerid'].'" class="'.$m['params']['mapcontainerclass'].'"></div>
<input type="hidden" id="'.$proxyFieldId.'" name="'.$proxyFieldId.'" />
<script type="text/javascript">
window.addEvent("load", function() {
        '.K2FieldsModelFields::JS_VAR_NAME.'.enqueueType(
                "map",
                null, null, null, null,
                function() {
                        '.K2FieldsModelFields::JS_VAR_NAME.'.fieldsOptions["'.$proxyFieldId.'"] = '.$params.';
                        '.K2FieldsModelFields::JS_VAR_NAME.'.mapItems["'.$proxyFieldId.'"] = '.$items.';
                        '.K2FieldsModelFields::JS_VAR_NAME.'.drawMap("'.$proxyFieldId.'");
                }
        );
});
</script>
';
                }
                
                return $ui; 
        }
        
        public static function v($field, $name) {
                $params = self::getParameters($field);
                return $params[$name];
        }
        
        public static function getParameters($field = null, $options = null) {
                $fieldId = $field ? K2FieldsModelFields::value($field, 'id') : 'default';
                
                static $_options = array();
                
                if (isset($_options[$fieldId])) return $_options[$fieldId];
                
                if (empty($options)) $options = $field;
                
                $options['mapinputmethod'] = K2FieldsModelFields::setting('mapinputmethod', $options, K2FieldsMap::MAP_DEFAULT_METHOD);
                $options['showmapeditor'] = K2FieldsModelFields::setting('showmapeditor', $options);
                $options['locationprovider'] = K2FieldsModelFields::setting('locationprovider', $options, 'browser');
                $options['locationproviderfunction'] = K2FieldsModelFields::setting('locationproviderfunction', $options);
                $options['mapiconcolor'] = K2FieldsModelFields::setting('mapiconcolor', $options, K2FieldsMap::MAP_ICON_COLOR);
                
                $option = JRequest::getCmd('option');
                $view = $option == 'com_k2' ? JRequest::getCmd('view') : '';
                $app = JFactory::getApplication();
                
                $options['markerfixed'] = 1;
                
                if ($app->isAdmin() || $view == 'item') {
                        if ($app->isAdmin()) {
                                $view = 'edit';
                                $options['markerfixed'] = 0;
                        } else {
                                $task = JRequest::getCmd('task', false);
                                $view = $task == 'add' || $task == 'edit' ? 'edit' : 'item';
                                
                                if ($view == 'item') {
                                        $options['mapstatic'] = K2FieldsModelFields::setting('mapstatic', $options);
                                } else {
                                        $options['markerfixed'] = 0;
                                }
                        }
                } else {
                        $view = 'itemlist';
                        $options['mapstatic'] = 0;
                }
                
                $options['view'] = $view . K2FieldsModelFields::VALUE_SEPARATOR . 'map';
                
                // TODO: use $view when extracting values
                
                $options['maxzoom'] = K2FieldsModelFields::setting('maxzoom'.$view, $options, 20, null, '::', 'all', 'maxzoom');
                $options['mapprovider'] = K2FieldsModelFields::setting('mapprovider'.$view, $options, K2FieldsMap::MAP_DEFAULT_PROVIDER, null, '::', 'all', 'mapprovider');
                $options['mapapikey'] = K2FieldsModelFields::setting('mapapikey'.$view, $options, '', null, '::', 'all', 'mapapikey');
                $options['mapcenter'] = K2FieldsModelFields::setting('mapcenter'.$view, $options, '', null, '::', 'all', 'mapcenter');
                $options['mapzoom'] = K2FieldsModelFields::setting('mapzoom'.$view, $options, K2FieldsMap::MAP_DEFAULT_ZOOM, null, '::', 'all', 'mapzoom');
                $options['mapcontainerid'] = K2FieldsModelFields::setting('mapcontainerid'.$view, $options, K2FieldsMap::MAP_CONTAINER_ID.'_'.$options['mapprovider'], null, '::', 'all', 'mapcontainerid');
                $options['maptype'] = K2FieldsModelFields::setting('maptype'.$view, $options, K2FieldsMap::MAP_TYPE, null, '::', 'all', 'maptype');
                $options['mapcontainerclass'] = K2FieldsModelFields::setting('mapcontainerclass'.$view, $options, K2FieldsMap::MAP_CONTAINER_CLASS, null, '::', 'all', 'mapcontainerclass');
                
                $_options[$fieldId] = $options;
                
                return $options;
        }
        
        public static function loadResources($item = null) {
                $field = $item ? K2FieldsModelFields::isContainsType('map', $item->catid) : null;
                
                //$provider = self::v($field, 'mapprovider'.$view);
                
                // TODO: depends on view when fully implemented
                $provider = self::v($field, 'mapprovider');
                $apiKey = self::v($field, 'mapapikey');
                $providerSrcs = array('js'=>array(), 'css'=>array());
                
                switch ($provider) {
                        case 'google':
                                $providerSrcs['js'][] = 'http://maps.google.com/maps?file=api&v=2&key=' . $apiKey;
                                break;
                        case 'yahoo':
                                $providerSrcs['js'][]  = 'http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=' . $apiKey;
                                break;
                        case 'microsoft':
                                $providerSrcs['js'][]  = 'http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6';
                                break;
                        case 'mapquest':
                                $providerSrcs['js'][]  = 'http://btilelog.beta.mapquest.com/tilelog/transaction?transaction=script&key='.$apiKey.'&itk=true&v=5.3.0_RC5&ipkg=controls1';
                        case 'leaflet':
                                $providerSrcs['js'][]  = 'http://leaflet.cloudmade.com/dist/leaflet.js';
                                $providerSrcs['css'][]  = 'http://leaflet.cloudmade.com/dist/leaflet.css';
                                break;
                        case 'cloudmade':
                        case 'openlayers':
                                $providerSrcs['js'][]  = 'http://openlayers.org/api/OpenLayers.js';
                                break;
                        case 'googlev3':
                        default:
                                $providerSrcs['js'][]  = 'http://maps.google.com/maps/api/js?sensor=false';
                                break;
                }
                
                $document = JFactory::getDocument();
                
                static $isCoreLoaded = array();

                if (!empty($providerSrcs)) {
                        foreach ($providerSrcs as $type => $_providerSrcs) {
                                foreach ($_providerSrcs as $providerSrc) {
                                        if ($type == 'css') {
                                                $document->addStyleSheet($providerSrc);
                                        } else if ($type == 'js') {
                                                $document->addScript($providerSrc);
                                        }
                                }
                        }
                        
                        if (!isset($isCoreLoaded[$provider]) || !$isCoreLoaded[$provider]) {
                                $method = K2FieldsModelFields::value($field, 'mapinputmethod', K2FieldsMap::MAP_DEFAULT_METHOD);
                                $params = array($provider);
                                if ($method == 'geo') $params[] = '[geocoder]';
                                self::add('mxn', $params);
                                $isCoreLoaded[$provider] = true;
                        }
                }
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
        
        private static function add($script, $params = null) {
                $script = K2FieldsMap::MAP_MAPSTRACTION_FOLDER.'/'.$script.K2FieldsMap::MAP_MAPSTRACTION_DEV.'.js';
                
                if ($params) $script .= '?('.implode(',', $params).')';
                
                JprovenUtility::loc(true, true, $script, true);
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