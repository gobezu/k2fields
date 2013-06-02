<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2FieldsMap extends K2fieldsFieldType {
        const MAP_MAPSTRACTION_FOLDER = 'lib/mxn2.1.0';
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
        const MAP_ICON_TYPE = '';

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
        private static $containerId = null, $loadResources = false;

        private static $staticMapProviders = array('googlev3', 'google');

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

                $view = JFactory::getApplication()->input->get('view', 'itemlist');

                if ($view == 'item' && K2FieldsModelFields::value($field, 'mapstatic')) {
                        $ui = self::renderStaticMap($field, $values, $item);
                } else {
                        self::renderDynamicMap($field, $values, $item, $view);
                        if ($view == 'item') $ui = self::finalizeMap('item');
                }

                self::loadResources($item, $field, false);

                return $ui;
        }

        public static function renderModuleMap($items, $fieldId) {
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $field = $model->getFieldsById($fieldId);

                foreach ($items as $item) {
                        $values = array();
                        $values[] = array($item);
                        self::renderDynamicMap($field, $values, $item, 'itemlist');
                }

                self::loadResources(null, $field, false, true);

                return self::finalizeMap('itemlist');

        }

        private static function renderStaticMap($field, $values, $item) {
                $uiId = self::containerId($field, $item);
                $data = array();

                foreach ($values as $i => $value) {
                        $data[] = array(
                            'lat'=>$value[0]->lat,
                            'lon'=>$value[0]->lng,
                            'label'=>count($value) > 1 ? $value[1]->value : $item->title
                        );
                }

                $color = self::v($field, 'mapiconcolor');

                $data = array(
                    'points'=>$data,
                    'container'=>$uiId,
                    'maptype'=>self::v($field, 'maptype'),
                    'zoom'=>self::v($field, 'mapzoom'),
                    'provider'=>self::v($field, 'mapprovider'),
                    'apikey'=>self::v($field, 'mapapikey'),
                    'iconcolor'=>self::v($field, 'mapiconcolor')
                );
                $data = json_encode($data);

                return
'
<div><div id="'.$uiId.'" class="staticMapContainer"></div></div>
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

        private static function renderDynamicMap($field, $values, $item, $view) {
                $proxyFieldId = K2FieldsModelFields::pre() . K2FieldsModelFields::value($field, 'id');

                if (!isset(self::$map[$proxyFieldId]))
                        self::$map[$proxyFieldId] = array('params' => self::getParameters($field), 'items' => array());

                $render = false;

                if (!isset(self::$map[$proxyFieldId]['items'][$item->id])) {
                        // TODO even if we are not in a map mode we can optionally render some info about the map
                        // 1. external map link with the provided label or a generic "MAP" label
                        // 2. provide only label
                        // 3. mini static map
                        if (isset($item->rendered_map)) {
                                self::$map[$proxyFieldId]['items'][$item->id]['rendered'] = $item->rendered_map;
                        } else {
                                $render = true;
                        }

                        self::$map[$proxyFieldId]['items'][$item->id] = array(
                                'points' => array(),
                                'title' => $item->title,
                                'link' => $item->link,
                                'category' => $view == 'item' ? $item->category->name : $item->categoryname,
                                'categoryid' => $view == 'item' ? $item->category->id : $item->categoryid,
                                'id' => $item->id
                        );
                }

                $rendered = '';
                foreach ($values as $i => $value) {
                        self::$map[$proxyFieldId]['items'][$item->id]['points'][] = array(
                            'lat' => $value[0]->lat,
                            'lon' => $value[0]->lng,
                            'lbl' => count($value) > 1 ? $value[1]->value : ''
                        );

                        if ($render) {
                                $mapAs = K2FieldsModelFields::value($field, 'showmapas');
                                $rendered .= ' '.JText::_(count($value) > 1 ? $value[1]->value : 'Map');
                        }
                }

                if (!isset(self::$map[$proxyFieldId]['items'][$item->id]['rendered'])) {
                        self::$map[$proxyFieldId]['items'][$item->id]['rendered'] = trim($rendered);
                }
        }

        public static function finalizeMap($view) {
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
                        '.K2FieldsModelFields::JS_VAR_NAME.'.drawMap("'.$proxyFieldId.'", "'.$view.'");
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

        public static function isMapPresent() {
                $params = self::getParameters();
                $id = K2FieldsModelFields::value($params, 'id');
                return !empty($id);
        }

        public static function showList() {
                return !(self::isMapPresent() && !self::v(null, 'mapshowitemlist'));
        }

        public static function getParameters($field = null, $options = null) {
                $fieldId = $field ? K2FieldsModelFields::value($field, 'id') : 'default';

                static $_options = array();

                if (isset($_options[$fieldId])) return $_options[$fieldId];

                if ($fieldId == 'default' && !isset($_options[$fieldId])) {
                        reset($_options);
                        $fieldId = key($_options);
                }

                if (isset($_options[$fieldId])) return $_options[$fieldId];

                if (empty($options)) $options = $field;

                if (empty($options)) return;

                if (is_object($options)) $options = get_object_vars ($options);

                $options['mapinputmethod'] = K2FieldsModelFields::setting('mapinputmethod', $options, K2FieldsMap::MAP_DEFAULT_METHOD);
                $options['showmapeditor'] = K2FieldsModelFields::setting('showmapeditor', $options);
                $options['locationprovider'] = K2FieldsModelFields::setting('locationprovider', $options, 'browser');
                $options['locationproviderfunction'] = K2FieldsModelFields::setting('locationproviderfunction', $options);
                $options['mapicontype'] = K2FieldsModelFields::setting('mapicontype', $options, K2FieldsMap::MAP_ICON_TYPE);
                $options['mapiconcolor'] = K2FieldsModelFields::setting('mapiconcolor', $options, K2FieldsMap::MAP_ICON_COLOR);
                $options['mapiconlocation'] = K2FieldsModelFields::setting('mapiconlocation', $options);
                $options['mapiconlocationhover'] = K2FieldsModelFields::setting('mapiconhover', $options);
                $options['mapgoto'] = K2FieldsModelFields::setting('mapgoto', $options);
                $options['mapshowitemlist'] = K2FieldsModelFields::setting('mapshowitemlist', $options, 0);

                $root = JPath::clean(JPATH_SITE, '/') . '/';

                if ($options['mapiconcolor']) {
                        jimport('joomla.filesystem.folder');

                        static $icons = array();

                        if (!isset($icons[$options['mapiconcolor']])) {
                                $icons[$options['mapiconcolor']] = array();
                        }

                        $icon = $iconSize = null;

                        if (!isset($icons[$options['mapiconcolor']][$options['mapicontype']])) {
                                $loc = $root.JprovenUtility::loc().'icons/themes/'.$options['mapiconcolor'].'/'.$options['mapicontype'];

                                if ($options['mapicontype'] == 'number_') {
                                        $loc .= '1';
                                } else if ($options['mapicontype'] == 'letter_') {
                                        $loc .= 'a';
                                }

                                $loc .= '.png';

                                if (JFile::exists($loc)) {
                                        $icon = JPath::clean($loc, '/');
                                        $iconSize = getimagesize($icon);
                                }

                                $icons[$options['mapiconcolor']][$options['mapicontype']] = array($icon, $iconSize);
                        } else {
                                $icon = $icons[$options['mapiconcolor']][$options['mapicontype']][0];
                                $iconSize = $icons[$options['mapiconcolor']][$options['mapicontype']][1];
                        }

                        if (!empty($icon)) {
                                $options['mapiconcolorfilesize'] = array($iconSize[0], $iconSize[1]);
                                $options['mapiconcolorfile'] = str_replace($root, JURI::root(), $icon);

                                if (K2FieldsModelFields::value($field, 'mapiconshadow')) {
                                        $loc = $root.JprovenUtility::loc().'icons/map.icon.shadow.png';

                                        if (JFile::exists($loc)) {
                                                $iconSize = getimagesize($loc);
                                                $options['mapiconshadow'] = str_replace($root, JURI::root(), $loc);
                                                $options['mapiconshadowsize'] = array($iconSize[0], $iconSize[1]);
                                        }
                                }
                        } else {
                                unset($options['mapiconcolor']);
                        }
                }

                if (!$options['mapiconlocation']) {
                        $options['mapiconlocation'] = $options['mapiconcolorfile'];
                        $options['mapiconlocationsize'] = $options['mapiconcolorfilesize'];
                } else {
                        $icon = JPath::clean($root . $options['mapiconlocation'], '/');

                        if (JFile::exists($icon)) {
                                $iconSize = getimagesize($icon);
                                $options['mapiconlocationsize'] = array($iconSize[0], $iconSize[1]);
                                if ($options['mapiconlocationhover']) {
                                        $options['mapiconlocationhover'] = preg_replace("#(\.\w+)$#", "-active$1", $options['mapiconlocation']);
                                }

                                $options['mapiconlocation'] = str_replace($root, JURI::root(), $icon);
                        } else {
                                unset($options['mapiconlocation']);
                        }
                }

                if ($options['mapiconlocationhover']) {
                        $icon = JPath::clean($root . $options['mapiconlocationhover'], '/');

                        if (JFile::exists($icon)) {
                                $iconSize = getimagesize($icon);
                                $options['mapiconlocationhoversize'] = array($iconSize[0], $iconSize[1]);
                                $options['mapiconlocationhover'] = str_replace($root, JURI::root(), $icon);
                        } else {
                                unset($options['mapiconlocationhover']);
                        }
                }

                $option = JFactory::getApplication()->input->get('option');
                $view = $option == 'com_k2' ? JFactory::getApplication()->input->get('view') : '';
                $app = JFactory::getApplication();

                $options['markerfixed'] = 1;

//                if ($app->isAdmin() || $view == 'item') {
//                        if ($app->isAdmin()) {
//                                $view = 'edit';
//                                $options['markerfixed'] = 0;
//                        } else {
//                                $task = JRequest::getCmd('task', false);
//                                $view = $task == 'add' || $task == 'edit' ? 'edit' : 'item';
//
//                                if ($view == 'item') {
//                                        $options['mapstatic'] = K2FieldsModelFields::setting('mapstatic', $options);
//                                } else {
//                                        $options['markerfixed'] = 0;
//                                }
//                        }
//                } else {
//                        $view = 'itemlist';
//                        $options['mapstatic'] = 0;
//                }
//
                if ($app->isAdmin() || $view == 'item') {
                        if ($app->isAdmin()) {
                                $view = 'edit';
                                $options['markerfixed'] = 0;
                        } else {
                                $task = JFactory::getApplication()->input->get('task', false);
                                $view = $task == 'add' || $task == 'edit' ? 'edit' : 'item';

                                if ($view == 'item') {
                                        $options['mapstatic'] = K2FieldsModelFields::setting('mapstatic', $options);
                                } else {
                                        $options['markerfixed'] = 0;
                                }
                        }
                } else {
                        $options['mapstatic'] = 0;
                }

                $options['view'] .= (isset($options['view']) ? K2FieldsModelFields::VALUE_SEPARATOR : ''). 'map';

                // TODO: use $view when extracting values

                $options['maxzoom'] = K2FieldsModelFields::setting('maxzoom'.$view, $options, 20, null, '::', 'all', 'maxzoom');
                $options['mapprovider'] = K2FieldsModelFields::setting('mapprovider'.$view, $options, K2FieldsMap::MAP_DEFAULT_PROVIDER, null, '::', 'all', 'mapprovider');
                $options['mapapikey'] = K2FieldsModelFields::setting('mapapikey'.$view, $options, '', null, '::', 'all', 'mapapikey');
                $options['mapcenter'] = K2FieldsModelFields::setting('mapcenter'.$view, $options, '', null, '::', 'all', 'mapcenter');
                $options['mapzoom'] = K2FieldsModelFields::setting('mapzoom'.$view, $options, K2FieldsMap::MAP_DEFAULT_ZOOM, null, '::', 'all', 'mapzoom');
                $options['mapcontainerid'] = K2FieldsModelFields::setting('mapcontainerid'.$view, $options, K2FieldsMap::MAP_CONTAINER_ID.'_'.$options['mapprovider'], null, '::', 'all', 'mapcontainerid');
                $options['maptype'] = K2FieldsModelFields::setting('maptype'.$view, $options, K2FieldsMap::MAP_TYPE, null, '::', 'all', 'maptype');
                $options['mapcontainerclass'] = K2FieldsModelFields::setting('mapcontainerclass'.$view, $options, K2FieldsMap::MAP_CONTAINER_CLASS, null, '::', 'all', 'mapcontainerclass');
                $options['mapcontrols'] = K2FieldsModelFields::setting('mapcontrols'.$view, $options, array(), null, '::', 'all', 'mapcontrols');

                $_options[$fieldId] = $options;

                if (self::$loadResources) self::loadResources(self::$loadResources[0], self::$loadResources[1], self::$loadResources[2]);

                return $options;
        }

        public static function loadResources($item = null, $field = null, $isForForm = true, $force = false) {
                $app = JFactory::getApplication();
                $view = $app->input->get('view');
                $option = $app->input->get('option');

                if (!$force && !in_array($option, array('com_k2', 'com_k2fields'))) return;

                $editorMode = false;

                if ($app->isAdmin()) {
                        if ($view != 'item') return;
                        else $editorMode = true;
                } else if ($app->isSite()) {
                        $task = $app->input->get('task');

                        if (in_array($task, array('add', 'edit'))) $editorMode = true;
                }

                static $isCoreLoaded = array();

                if (!$field)
                        $field = $item ? K2FieldsModelFields::isContainsType('map', $item->catid) : null;

                if ($isForForm) {
                        if (!$editorMode || !K2FieldsModelFields::isTrue($field, 'showmapeditor')) return;
                }

                // TODO: depends on view when fully implemented

                if ($option == 'com_k2fields') {
                        $view = 'itemlist';
                } else if ($option == 'com_k2') {
                        $view = $view;
                } else {
                        $view = 'itemlist';
                }

                $provider = self::v($field, 'mapprovider'.$view);

                if (empty($provider)) {
                        self::$loadResources = array($item, $field, $isForForm);
                        return;
                }

                if ($view == 'item' && !$editorMode && K2FieldsModelFields::isTrue($field, 'mapstatic')) return;

                if (isset($isCoreLoaded[$provider]) && $isCoreLoaded[$provider]) return;

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
                                JprovenUtility::loc(true, true, 'lib/mapproviders/leaflet.css', true, 'css');
                                JprovenUtility::loc(true, true, 'lib/mapproviders/leaflet.js', true, 'js');
//                                $providerSrcs['js'][]  = 'http://leaflet.cloudmade.com/dist/leaflet.js';
//                                $providerSrcs['css'][]  = 'http://leaflet.cloudmade.com/dist/leaflet.css';

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

                        $method = K2FieldsModelFields::value($field, 'mapinputmethod', K2FieldsMap::MAP_DEFAULT_METHOD);
                        // $params = array($provider);
                        // if ($method == 'geo') $params[] = '[geocoder]';
                        self::add($provider, $method == 'geo', K2FieldsModelFields::isTrue($field, 'maplazyload'));
                        $isCoreLoaded[$provider] = true;
                }

                self::$loadResources = false;
        }

        private static function add($module, $isGeocoder = false, $isLazy = true) {
                $path = K2FieldsMap::MAP_MAPSTRACTION_FOLDER.'/mxn';

                $script = $path . K2FieldsMap::MAP_MAPSTRACTION_DEV . '.js';

                if ($isLazy) {
                        $script .= '?(' . $module . ($isGeocoder ? '[geocoder]' : '') . ')';
                }

                JprovenUtility::loc(true, true, $script, true);

                if (!$isLazy) {
                        $script = $path . '.core' . K2FieldsMap::MAP_MAPSTRACTION_DEV. '.js';
                        JprovenUtility::loc(true, true, $script, true);

                        $script = $path . '.' . $module . '.core' . K2FieldsMap::MAP_MAPSTRACTION_DEV. '.js';
                        JprovenUtility::loc(true, true, $script, true);

                        if ($isGeocoder) {
                                $script = $path . '.' . $module . '.geocoder' . K2FieldsMap::MAP_MAPSTRACTION_DEV. '.js';
                                JprovenUtility::loc(true, true, $script, true);
                        }
                }
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