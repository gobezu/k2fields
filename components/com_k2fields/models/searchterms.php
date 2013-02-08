<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * @@note: naming conventions in order to avoid fnc name collisions
 * 
 * all top level funcs prefixed with sql
 * all type based criteria returning fncs prefixed with _
 * all operator based criteria returning fncs names the same as operators: eq, ge, le, gte, lte, in, ex, hi, pr, an
 * 
 * @@credit: not surprisingly lot of the code below expands on K2ModelItemlist::getData
 */
class K2FieldsModelSearchterms extends K2Model {
        private static $_fields, $_searchUrl, $_searchTerms, $_mapTbl, $_ITEMTBL = 'i', $_CATTBL = 'c', $cnt = 0, $wheres = array(), $_orders = array();
        var $_db, $_valueTbl, $_filter;
        
        const VALUE_SEPARATOR_REPLACEMENT = ':::';
        
        function __construct($config = array()) {
                self::$cnt += 1;
                parent::__construct($config);
                
                $db = JFactory::getDBO();
                $this->_db = $db;
                $this->_filter = JFilterInput::getInstance(null, null, 1, 1);
                
                if (!isset($config['parse']) || $config['parse'])
                        self::parseSearchTerms(isset($config['data']) ? $config['data'] : null);
        }
        
        public static function getFieldValue($field) {
                $id = K2FieldsModelFields::value($field, 'id');
                $value = null;
                if (isset(self::$_searchTerms[$id])) {
                        $value = self::$_searchTerms[$id];
                        $k = JprovenUtility::firstKey($value);
                        $value = isset($value[$k]['_val_']) ? $value[$k]['_val_'] : $value[$k]['val'];
                }
                return $value;
        }
        
        function getData($criterias = NULL, $ordering = NULL, $mode = 'data') {
                if (!empty($criterias)) {
                        self::parseSearchTerms($criterias);
                }
                
                if (empty(self::$_searchTerms)) 
                        return false;
                
                $input = JFactory::getApplication()->input;
                $layout = $input->get('layout', '', 'word');
                $query = '';
                $this->sqlFrom($query, $ordering);
                $this->sqlSelect($query, $ordering, $layout, $mode);
                $this->sqlWhere($query, $ordering);
                $this->sqlOrderby($query, $ordering);
                
                $limitstart = $input->get('limitstart', '', 'int');
                
                if ($mode == 'count') {
                        $this->_db->setQuery($query);
                        $result = $this->_db->loadResult();                        
                } else {
                        $catid = !isset(self::$_searchTerms['catid']) || self::$_searchTerms['catid'][0]['val'];
                        $limit = K2FieldsHelper::getItemlistLimit($catid);
                        
                        $this->_db->setQuery($query, $limitstart, $limit);
                        
//                        jdbg::pe($this->_db->getQuery());
                        
                        if ($mode == 'id') {
                                $result = $this->_db->loadResultArray();
                        } else {
                                $result = $this->_db->loadObjectList();                        

                                if ($layout == 'map') {
                                        $zoom = $input->get('zoom', K2FieldsModelFields::setting('mapzoom'), 'int');
                                        $result = self::cluster($result, $zoom);
                                }
                        }
                }
                
//                jdbg::pe($result);
                
                return $result;
        }
        
        function getTotal($criteria = NULL) { return $this->getData($criteria, NULL, 'count'); }
        
        function _freetext($field = null, $part = null, $value = null, $def = null, $tbl = null) {
                static $result = '';
                
                if ($value == JText::_("search")."...") $value = '';
                
                if (empty($value)) return '';
                
                if (empty($field)) return $result;
                
                $value = strtolower($value);
                $exclfldft = JFactory::getApplication()->input->get('exclfldft', false, 'bool');
                $cols = array('title', 'introtext', '`fulltext`');
                
//                if (!$exclfldft) $cols[] = 'extra_fields';
                if (!$exclfldft) $cols[] = 'extra_fields_search';
                
                $op = JFactory::getApplication()->input->get('searchmode', 'exact', 'word');
                $result = $this->__compare($op, $field, $part, $value, $def, self::$_ITEMTBL, $cols);
                
                return $result;
        }
        
        function _items($field, $part, $value, $def, $tbl) {
                self::$wheres[] = ' '.self::$_ITEMTBL.'.id IN ('.implode(',', $value).')';
                return $query;
        }
        
        function _catid($field, $part, $value, $def, $tbl) {
                $query = " LEFT JOIN #__k2_categories AS ".self::$_CATTBL." ON ".self::$_CATTBL.".id = ".self::$_ITEMTBL.".catid";
                
                if (!empty($value)) {
                        $value = explode(',', $value);
                        // @TODO: Is there any reason not to include children categories?
                        $cats = JprovenUtility::getK2CategoryChildren($value, -1, true);
                        $cats = $cats['cats'];
                        $cats = array_merge($cats, $value);
                        $cats = implode(',', $cats);
                        $query .= " AND ".self::$_CATTBL.".id IN (" . $cats . ")";
                }
                
                return $query;
        }
        
        private static function _sqlJoinOrder($field, $tbl) {
                if (!isset(self::$_orders[$field]) && isset(self::$_fields[$field])) {
                        $field = self::$_fields[$field];
                        $order = K2FieldsModelFields::setting('sortby', $field, false);
                        if ($order) {
                                $col = K2FieldsModelFields::isDatetimeType($field) ? 'datum' : 'value'; 
                                self::$_orders[$field['id']] = $tbl . '.' . $col . ' ' . $order;
                        }
                }
        }
        
        // @@TODO: should we consider requiring position to match, i.e. $part = $t.partindex?
        // @@TODO: if field not searched by it will neither be available as sorting field
        function sqlJoin(&$query, $searchTerms = null, $itemTbl = null) {
                $q = '';
                $qw = '';
                $tbl = 'v';
                $count = 0;
                $mapConstraint = true;
                
                if (empty($searchTerms)) {
                        $searchTerms = self::$_searchTerms;
                        $itemTbl = self::$_ITEMTBL;
                }
                
                foreach ($searchTerms as $field => $_terms) {
                        foreach ($_terms as $part => $term) {
                                if (method_exists($this, $term['def']['valid'])) {
                                        $func = $term['def']['valid'];
                                } else if (isset($term['def']['search']) && method_exists($this, $term['def']['search'])) {
                                        $func = $term['def']['search'];
                                } else if (method_exists($this, '_'.$term['def']['valid'])) {
                                        $func = '_'.$term['def']['valid'];
                                }
                                
                                if (isset($term['def']['search']) && isset($term['def']['search']) == $func && strpos($func, 'not_') === 0) {
                                       $term['def']['negate'] = true;
                                       $func = str_replace('not_', '', $func);
                                }
                                
                                $t = $tbl . $count;
                                
                                self::_sqlJoinOrder($field, $t);
                                
                                $queryPart = call_user_func_array(
                                        array($this, $func), 
                                        array($field, $part, $term['val'], $term['def'], $t)
                                );
                                
                                if ($term['def']['valid'] == 'map') {
                                        if (empty(self::$_mapTbl)) self::$_mapTbl = $t;
                                        
                                        if ($mapConstraint) {
                                                $queryPart .= ' AND '.$t.'.partindex = 0';
                                                $mapConstraint = false;
                                        }
                                }
                                
                                $qp = trim($queryPart);
                                $isJoin = strpos($qp, 'LEFT JOIN') === 0 || strpos($qp, 'INNER JOIN') === 0;
                                
                                if (!empty($qp)) {
                                        if (!$term['def']['complete'] || is_array($queryPart) && isset($queryPart['notcomplete'])) {
                                                $q .= '
                                                        INNER JOIN #__k2_extra_fields_values AS ' . $t . ' ON ' . $t .'.fieldid = ' . $field . ' AND ';

                                                if ($count == 0) {
                                                        $q .= self::$_ITEMTBL.'.id = '.$t.'.itemid AND ';
                                                } else {
                                                        $queryPart .= ' AND '.$tbl.($count - 1).'.itemid = '.$t.'.itemid ';
                                                }

                                                $q .= $queryPart;
                                        } else {
                                                $q .= ($count > 0 && !$isJoin ? ' AND ' : '') . $queryPart;
                                        }
                                        
                                        $count++;
                                }
                        }
                }
                
                if (!empty($q)) $query .= $q;
        }
        
        function _map($field, $part, $value, $def, $tbl) {
                if (empty($value)) return '';
                
                $value = explode(',', $value);
                $lat = $lng = $itemId = -1;
                
                if (count($value) == 1) {
                        $itemId = $value[0];
                } else { 
                        $lat = $value[0];
                        $lng = $value[1];
                }
                
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $options = $model->getFieldsById($field);
                $dist = K2FieldsModelFields::setting('nearbyDistance', $options, JFactory::getApplication()->input->get('dist', 10, 'int'));
                $lim = K2FieldsModelFields::setting('nearbyNum', $options, 10);
                //$sp = 'CALL #__k2_extra_fields_geodist('.$itemId.','.$lat.','.$lng.','.$field.','.$dist.','.$lim.')';
                $sp = 'CALL #__k2_extra_fields_geodist(54,'.$lat.','.$lng.','.$field.','.$dist.','.$lim.')';
                $this->_db->setQuery($sp);
                $items = $this->_db->loadResultArray();
                $this->_db->_resource->next_result();
                
                if (empty($items)) return '';
                
                return $tbl.'.itemid IN ('.implode(',', $items).')';
        }
        
        /**
         * @param array $markers lat and lon locations
         * @param int $zoom current map zoom level
         */
        private static function cluster($markers, $zoom) {
                if (
                        K2FieldsModelFields::setting('mapclusteringtreshold') >= count($markers) ||
                        K2FieldsModelFields::setting('mapclusteringtresholdzoom') >= $zoom
                   )
                        return array('clustered'=>array(), 'single'=>$markers);
                
                $singleMarkers = array();
                $clusterMarkers = array();

                // Minimum distance between markers to be included in a cluster, at diff. zoom levels
                $DISTANCE = (10000000 >> $zoom) / 100000;

                while (count($markers)) {
                        $marker  = array_pop($markers);
                        $cluster = array();

                        // Compare against all markers which are left.
                        foreach ($markers as $key => $target) {
                                $pixels = abs($marker['lat']-$target['lat']) + abs($marker['lng']-$target['lng']);

                                // If the two markers are closer than given distance remove target marker from array and add it to cluster.
                                if ($pixels < $DISTANCE) {
                                        unset($markers[$key]);
                                        $cluster[] = $target;
                                }
                        }

                        // If a marker has been added to cluster, add also the one we were comparing to.
                        if (count($cluster) > 0) {
                                $cluster[] = $marker;
                                $clusterMarkers[] = $cluster;
                        } else {
                                $singleMarkers[] = $marker;
                        }
                }  
                
                return array('clustered'=>$clusterMarkers, 'single'=>$singleMarkers);
        }        
        
        function _media($field, $part, $value, $def, $tbl) {
                return $this->ex($field, $part, $value, $def, $tbl);
        }
        
        function ex($field, $part, $value, $def, $tbl) {
                if (is_array($value)) $value = $value[0];
                if ($value != 'exists') return '';
                return $tbl.".value <> ''";
        }
        
        function any($field, $part, $value, $def, $tbl) {
                return $this->__compare('any', $field, $part, $value, $def, $tbl);
        }
        
        public function calcDistance($lat1, $lon1, $lat2, $lon2, $unit = 'k') {
                $r = 6371;
                $dLat = deg2rad($lat2 - $lat1);
                $dLon = deg2rad($lon2 - $lon1);
                $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat1)) * sin($dLon/2) * sin($dLon/2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $d = $r * $c * (strtolower($unit) == 'm' ? 1/1.609344 : 1);

                return $d;
        }     
        
        private static function getTolerance($field, $op, $default = '') {
                $tolerance = K2FieldsModelFields::value(
                        $field, 
                        ($op == 'g' ? 'lower' : 'upper') . 'tolerance'
                );

                if (!$tolerance) {
                        $tolerance = K2FieldsModelFields::value($field, 'tolerance', $default);

                        if (!$tolerance) return 0;
                }
                
                $tolerance = ($op == 'g' ? -1 : 1) * (int) $tolerance;
                
                return $tolerance;
        }
        
        function addTolerance($op, $value, $field) {
                $field = self::$_fields[$field];
                if (!K2FieldsModelFields::isNumeric($field) || !K2FieldsModelFields::isDatetimeType($field)) return $value;
                $op = substr($op, 0, 1);
                $tolerance = self::getTolerance($field, $op);
                
                if (K2FieldsModelFields::isDatetimeType($field)) {
                        $value = JprovenUtility::createDate($value)->format('U');
                        $value += $tolerance;
                        $value = JFactory::getDate($value);
                        $value = $value->toMySQL();
                } else {
                        $value += $tolerance;
                }
                
                return $value;
        }
        
        function q($value) {
                return $this->_db->Quote($value);
        }
        
        function nq($value) {
                return $this->_db->nameQuote($value);
        }
        
        function esc($value) {
                return $this->_db->getEscaped($value);
        }
        
        public static function convertDates($datesOrConstant, $field) {
                $result = array();
                // TODO: should be able to deduce that futureonly applies if expire is true???
                $onlyFuture = K2FieldsModelFields::isTrue($field, 'futureonly');
                
                switch ($datesOrConstant) {
                        case 'nextweek':
                                $d = JprovenUtility::createDate('next monday');
                                $diff = new DateInterval('P6D');
                                $_d = JprovenUtility::createDate('next monday');
                                $_d->add($diff);
                                $result = array('start' => $d, 'end' => $_d);
                                break;
                        case 'thisweek':
                                $weekStartsOn = K2FieldsModelFields::setting('weekstartson');
                                $weekDay = JprovenUtility::createDate('now');
                                $weekDay = $weekDay->format('w');
                                $weekEndsOn = $weekStartsOn == 0 ? 6 : $weekStartsOn - 1;
                                $result = array(
                                    'start' => $weekDay == $weekStartsOn ?
                                        JprovenUtility::createDate('today') : 
                                        JprovenUtility::createDate('last monday'),
                                    'end' => $weekDay == $weekEndsOn ? 
                                        JprovenUtility::createDate('today') : 
                                        JprovenUtility::createDate('next sunday')
                                );
                                break;
                        case 'thisweekend':
                                $weekDay = JprovenUtility::createDate('now');
                                $weekDay = $weekDay->format('w');
                                
                                if ($weekDay == 6) { // today is saturday
                                        $result['start'] = JprovenUtility::createDate('today');
                                        $result['end'] = JprovenUtility::createDate('sunday');
                                } else if ($weekDay == 0) { // today is sunday
                                        $result['start'] = JprovenUtility::createDate('last saturday');
                                        $result['end'] = JprovenUtility::createDate('today');
                                } else {
                                        $result['start'] = JprovenUtility::createDate('next saturday');
                                        $result['end'] = JprovenUtility::createDate('next sunday');
                                }
                                break;
                        case 'tomorrow':
                                $result = array(
                                    'start' => JprovenUtility::createDate('tomorrow') , 
                                    'end' => JprovenUtility::createDate('tomorrow')
                                );
                                break;
                        case 'thisevening':
                                $eveningStartsOnHour = (int) K2FieldsModelFields::value($field, 'eveningstartsat', 16);
                                $d = JprovenUtility::createDate('today');
                                $d->setTime($eveningStartsOnHour, 0, 0);
                                $result = array('start' => $d, 'end' => JprovenUtility::createDate('today'));
                                break;
                        case 'today':
                                $result = array(
                                    'start' => JprovenUtility::createDate('today'),
                                    'end' => JprovenUtility::createDate('today')
                                );
                                break;
                        case 'now':
                                $d = JprovenUtility::createDate();
                                $ut = K2FieldsModelFields::value($field, 'nowtoleranceupper', 3600);
                                $lt = K2FieldsModelFields::value($field, 'nowtolerancelower', 3600);
                                $ut = new DateInterval('PT'.$ut.'S');
                                $lt = new DateInterval('PT'.$lt.'S');
                                $result = array(
                                        'start' => JprovenUtility::createDate()->sub($lt), 
                                        'end' => JprovenUtility::createDate()->add($ut)
                                );
                                break;
                        default:
                                $datesOrConstant = explode(',', $datesOrConstant);
                                $result = array('start' => '', 'end' => '');
                                
                                if (count($datesOrConstant) == 1 || !empty($datesOrConstant[0])) {
                                        if (strpos($result['start'], '%days') !== FALSE) {
                                                $result['start'] = str_replace('%', ' ', $result['start']);
                                                $result['start'] = JprovenUtility::createDate($result['start']);
                                        } else {
                                                $result['start'] = JprovenUtility::createDate($datesOrConstant[0]);
                                        }
                                }
                                
                                if (count($datesOrConstant) == 2 || !empty($datesOrConstant[1])) {
                                        $result['start'] = $result['start']->format('U');
                                        $isTime = K2FieldsModelFields::isValue($field, 'secondoption', 'time');
                                        
                                        if ($isTime) {
                                                $d = explode(':', $datesOrConstant[1]);
                                                
                                                $result['end'] = $result['start'] + (int) $d[0] * 60 * 60;
                                                if (count($d) > 1) $result['end'] += $d[1] * 60;
                                                if (count($d) > 2) $result['end'] += $d[2];
                                        } else {
                                                if (strpos($result['end'], '%days') !== FALSE) {
                                                        $result['end'] = str_replace('%', ' ', $result['end']);
                                                        $result['end'] = JprovenUtility::createDate($result['end']);
                                                } else {
                                                        $result['end'] = JprovenUtility::createDate($datesOrConstant[1]);
                                                }                             
                                        }
                                }
                                
                                break;
                }
                
                if (in_array($datesOrConstant, array('today', 'thisweek', 'thisweekend', 'thisevening', 'nextweek', 'tomorrow'))) {
                        if ($result['end'] instanceof JDate) {
                                $result['end']->setTime(23, 59, 59);
                        } else {
                                $result['end'] += 23 * 60 * 60 + 59 * 60 + 59;
                        }
                }
                
                if ($result['start'] instanceof JDate) {
                        $result['start'] = $result['start']->format('U');
                }
                
                if ($result['end'] instanceof JDate) {
                        $result['end'] = $result['end']->format('U');
                }
                
                if ($onlyFuture) {
                        $result['start'] = JprovenUtility::createDate('now');
                }
                
                //$config = JFactory::getConfig();
                //$tzoffset = $config->getValue('config.offset');
                $tzoffset = 0;
                
                if ($result['start']) {
                        $result['start'] = JFactory::getDate($result['start'], $tzoffset)->toMySQL();
                }

                if ($result['end']) {
                        $result['end'] = JFactory::getDate($result['end'], $tzoffset)->toMySQL();
                }
                
                return $result;
        }
        
        function __compare($op, $field, $part, $value, $def, $tbl, $cols = array('value', 'txt')) {
                $tbl .= '.';
                
                if (empty($cols)) $cols = array('value', 'txt');
                
                if (in_array($op, array('eq', 'gt', 'lt', 'le', 'ge')) || K2FieldsModelFields::isNumeric($def) || K2FieldsModelFields::isDatetimeType($def)) {
                        $q = '';
                        $value = explode(',', $value);
                        
                        if (in_array($value[0], array('now', 'today', 'tomorrow', 'thisevening', 'nextweek', 'thisweek', 'thisweekend'))) {
                                $op = 'interval';
                        }
                        
                        $value = implode(',', $value);
                        if ($op == 'interval') {
                                if (K2FieldsModelFields::isNumeric($def)) {
                                        $value = explode(';', $value);
                                        if (is_numeric($value[0])) {
                                                $q = $tbl.'value >= '.(int)$value[0];
                                        }
                                        if (is_numeric($value[1])) {
                                                $q .= ($q ? ' AND ' : '') . $tbl.'value <= '.$value[1];
                                        }
                                } else if (K2FieldsModelFields::isDatetimeType($def)) {
                                        $d = self::convertDates($value, $field);
                                        
                                        if (!empty($d['start']))
                                                $q = $tbl.'datum >= '.$this->_db->Quote($d['start']);
                                        
                                        if (!empty($d['end']))
                                                $q .= ($q ? ' AND ' : '') . $tbl.'datum <= '.$this->_db->Quote($d['end']);
                                }
                                return $q;
                        }
                        $accOp = '';
                        if ($op[0] == 'l') {
                                $accOp = '<';
                        } else if ($op[0] == 'g') {
                                $accOp = '>';
                        } else {
                                $accOp = '=';
                        }
                        if ($accOp != '=' && is_array($value)) {
                                $value = $accOp == '>' ? min($value) : max($value);
                        }
                        if ($op[1] == 'e') {
                                $accOp .= '=';
                        }
                        $value = (array) $value;
                        $qp = '';
                        $col = K2FieldsModelFields::isDatetimeType($def) ? 'datum' : 'value';
                        foreach ($value as $i => $v) {
                                $v = $this->addTolerance($op, $v, $field);
                                $qp .= ($i == 0 ? '' : ' OR ') . $tbl.$col.' '.$accOp.' '.$this->q($v);
                        }
                        $q .= '(' . $qp . ')';
                        if (isset($def['negate'])) {
                                $q = 'NOT '.$q;
                                self::$wheres[] = ' NOT EXISTS (SELECT id FROM #__k2_extra_fields_values vv WHERE vv.itemid = '.self::$_ITEMTBL.'.id AND vv.fieldid = '.$field.' AND vv.value IN ('.implode(',', $value).'))';
                        }
                        return $q;
                } else {
                        if (is_array($value)) $value = implode(' ', $value);
                        
                        $value = trim(urldecode($value));
                        $length = strlen($value);
                        
                        if (empty($op) && (empty($rop) || !in_array($rop, array('exact', 'any')))) {
                                $op = 'exact';
                                //$op = substr($value, 0, 1) == '"' && substr($value, $length - 1, 1) == '"' ? 'exact' : 'any';
                        }
                        
                        if ($op == 'any') {
                                $value = str_ireplace('*', '', $value);
                                $value = explode(' ', $value);
                                for ($i = 0; $i < count($value); $i++) {
                                        $value[$i].= '*';
                                }
                                $value = implode(' ', $value);
                                $value = $this->q($this->esc($value, true), false);
                        } else {
                                $value = JString::trim($value, '"');
                                
                                if (is_object($field)) {
                                        $notWildcarded = K2FieldsModelFields::isFalse($field, 'wildcard');
                                } else if ($field == 'freetext') {
                                        $notWildcarded = false;
                                }
                                $value = $this->q('"'.$this->esc($value, true).(!$notWildcarded ? '*' : '').'"', false);
                        }
                        
                        foreach ($cols as &$col) $col = $tbl.$col;
                        
                        return " MATCH(".implode(',', $cols).") AGAINST ({$value} IN BOOLEAN MODE)";   
                }
        }
        
        function _list($field, $part, $value, $def, $tbl) {
                $list = new K2FieldsList();
                
                $fieldsModel = K2Model::getInstance('fields', 'K2FieldsModel');
                $fieldOptions = $fieldsModel->getFieldsById($field);
                
                if (K2FieldsModelFields::isType($fieldOptions, 'complex')) {
                        $fieldOptions = K2FieldsModelFields::value($fieldOptions, 'subfields');
                        $fieldOptions = $fieldOptions[$part];
                }
                
                $source = K2FieldsModelFields::value($fieldOptions, 'source');
                
                $query = $list->getTree($source, $value, null, null, true, false, array(), 'query');
                
                if ($query) $query = $tbl.'.value IN ('.$query.')';
                
                return $query;
        }
        
        function _k2item($field, $part, $value, $def, $tbl) {
                return $this->__compare('eq', $field, $part, $value, $def, $tbl);
        }
        
        function interval($field, $part, $value, $def, $tbl) {
                return $this->__compare('interval', $field, $part, $value, $def, $tbl);
        }
        
        function eq($field, $part, $value, $def, $tbl) {
                return $this->__compare('eq', $field, $part, $value, $def, $tbl);
        }
        
        function _days($field, $part, $value, $def, $tbl) {
                $d = self::convertDates($value, $field);
                $s = JprovenUtility::createDate($d['start'])->format('w');
                $e = JprovenUtility::createDate($d['end'])->format('w');
                
                if ($e < $s) {
                        $q = range($s, 6);
                        $q = array_merge($q, range(0, $e));
                } else {
                        $q = range($s, $e);
                }
                
                // Weekend or All days
                if (in_array(5, $q) || in_array(6, $q)) $q[] = 8;
                
                $q[] = 7; 
                $q = $tbl.'.value IN ('.implode(',', $q).')';
                
                return $q;
        }
        
        function _duration($field, $part, $value, $def, $tbl, $isSub = false) {
                $q = '';
                
                $nonDuration = array('today', 'thisweek', 'nextweek', 'thisweekend', 'tomorrow');
                
                if (in_array($value, $nonDuration)) return $q;
                
                $d = self::convertDates($value, $field);
                
                $s = (int) JprovenUtility::createDate($d['start'])->format('H');
                $e = (int) JprovenUtility::createDate($d['end'])->format('H');
                
                if ($s > $e) {
//                        $s = JprovenUtility::createDate($d['start']);
//                        $d['end'] = JprovenUtility::createDate($s->format('Y-m-d 23:59:59'));
                        $d['end'] = $d['start'];
                }
                
                if ($def['custom'] == 'start') {
                        $s = JprovenUtility::createDate($d['start']);
                        $s = $s->diff(JprovenUtility::createDate($s->format('Y-m-d 00:00:00')));
                        $s = $s->format('%h') * 3600 + $s->format('%m') * 60 + $s->format('%s');
                        $q = $tbl.'.duration <='.$s;
                } else {
                        $e = JprovenUtility::createDate($d['end']);
                        $e = $e->diff(JprovenUtility::createDate($e->format('Y-m-d 00:00:00')));
                        $e = $e->format('%h') * 3600 + $e->format('%m') * 60 + $e->format('%s');
                        $q = $tbl.'.duration >='.$e;
                }
                
                return $q;
        }
        
        function le($field, $part, $value, $def, $tbl) {
                return $this->__compare('le', $field, $part, $value, $def, $tbl);
        }
        
        function lt($field, $part, $value, $def, $tbl) {
                return $this->__compare('lt', $field, $part, $value, $def, $tbl);
        }
        
        function ge($field, $part, $value, $def, $tbl) {
                return $this->__compare('ge', $field, $part, $value, $def, $tbl);
        }
        
        function gt($field, $part, $value, $def, $tbl) {
                return $this->__compare('gt', $field, $part, $value, $def, $tbl);
        }
        
        public function createSearchRequest($values, $as = 'url') {
                K2Model::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/models/');
                
                $url = array();
                $keys = array_keys($values);
                $fieldsModel = K2Model::getInstance('fields', 'K2FieldsModel');
                $fields = null;
                
                if ($cid = array_search('cid', $keys)) {
                        $url['cid'] = (int) $values['cid'];
                        unset($keys[$cid]);
                        unset($values['cid']);
                        
                        if (!empty($url['cid'])) 
                                $fields = $fieldsModel->getFieldsByGroup($url['cid']);
                } else if ($cid = array_search('catid', $keys)) {
                        $url['cid'] = (int) $values['catid'];
                        unset($keys[$cid]);
                        unset($values['catid']);
                        
                        if (!empty($url['cid'])) 
                                $fields = $fieldsModel->getFieldsByGroup($url['cid']);
                }
                
                if ($ft = array_search('text', $keys)) {
                        $url['ft'] = $values['text'];
                        unset($keys[$ft]);
                        unset($values['text']);
                } else if ($ft = array_search('ft', $keys)) {
                        $url['ft'] = $values['ft'];
                        unset($keys[$ft]);
                        unset($values['ft']);
                }
                
                if ($limit = JFactory::getApplication()->input->get('limit', '', 'int')) {
                        $url['limit'] = $limit;
                }
                
                if (empty($fields)) {
                        foreach ($keys as $i => &$key) {
                                if (!is_numeric($key)) {
                                        $key = explode(',', $key);
                                        $key = $key[0];
                                }
                        }
                        
                        $keys = array_unique($keys);
                        $fields = $fieldsModel->getFieldsById($keys);
                }
                
                $pre = K2FieldsModelFields::pre('search');
                $labels = array();
                
                foreach ($values as $fieldId => $value) {
                        if (in_array($fieldId, array('cid', 'ft'))) {
                                $url['cid'] = $value;
                                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                                $tbl = JTable::getInstance('K2Category', 'Table');
                                $tbl->load($value);
                                $labels[] = array('name' => JText::_('In category'), 'value' => $tbl->name);
                                continue;
                        }
                        
                        $position = 0;
                        
                        if (!is_numeric($fieldId)) 
                                list($fieldId, $position) = explode(',', $fieldId);
                        
                        $field = $fields[$fieldId];
                        $valid = K2FieldsModelFields::value($field, 'valid');
                        
                        if ($valid == 'complex') {
                                $field = K2FieldsModelFields::value($field, 'subfields'); 
                                $field = $field[$position];
                                $valid = K2FieldsModelFields::value($field, 'valid');
                        }
                        
                        $url[$pre.$fieldId.'_'.$position.$pre] = 
                                K2FieldsModelFields::value($field, 'search') .
                                K2FieldsModelFields::VALUE_SEPARATOR .
                                $valid
                                ;

                        $url[$pre.$fieldId.'_'.$position.(is_array($value) && $as == 'url' ? '[]' : '')] = $value;
                        $name = K2FieldsModelFields::value($field, 'name');
                        
                        $labels[] = array('name' => $name, 'value' => $value);
//                        TODO: proper substitution of values
//                        $fieldValues = K2FieldsModelFields::value($field, 'values');
//                        if (!empty($fieldValues)) {
//                                if (!is_array($value)) 
//                                        $value = array($value);
//                                
//                                foreach ($value as $val) {
//                                        foreach ($fieldValues as $fieldValue) {
//                                                $_val = K2FieldsModelFields::value($fieldValue, 'text', K2FieldsModelFields::value($fieldValue, 'text'));
//                                        }
//                                }
//                        }
                }
                
                if ($as == 'url') {
                        $result = array();
                        
                        foreach ($url as $key => $value) {
                                if (is_array($value)) {
                                        foreach ($value as $val) {
                                                $result[] = $key . '=' . $val;
                                        }
                                } else {
                                        $result[] = $key . '=' . $value;
                                }
                        }

                        $url = implode('&', $result);
                }
                
                return array('ui' => $url, 'labels' => $labels);
        }
        
        public static function parseSearchTerms($requestData = null, $parseOnly = false, $returnAs = 'url') {
                $checkRequest = empty($requestData);
                
                if (isset(self::$_searchTerms) && $checkRequest) return;
                
                $pre = K2FieldsModelFields::pre('search');
                
                // TODO how do we get the entire request in new API
                if ($checkRequest) $requestData = JprovenUtility::getRequest();
                
                $pattern = '#^'.$pre.'(\d+)\_(\d+)$#';
                $terms = array();
                $url = array();
                
                foreach ($requestData as $name => $req) {
                        if (!JprovenUtility::isEmpty($req) && preg_match($pattern, $name, $m)) {
                                if (!isset($terms[$m[1]])) $terms[$m[1]] = array();
                                
                                $url[] = $m[0].'='.$req;
                                $terms[$m[1]][$m[2]] = array('val' => $req, 'def' => array('complete' => false));
                        }
                }
                
                $url = implode('&', $url);
                $fieldIds = array_keys($terms);
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                self::$_fields = $model->getFieldsById($fieldIds);
                
                foreach ($terms as $fieldId => &$_terms) {
                        foreach ($_terms as $ind => &$_term) {
                                $field = self::$_fields[$fieldId];
                                $valid = K2FieldsModelFields::value($field, 'valid');
                                
                                if ($valid == 'complex') {
                                        $subfields = K2FieldsModelFields::value($field, 'subfields');
                                        $isDaysDuration = K2FieldsModelFields::value($field, 'daysduration');
                                        
                                        if ($subfields) {
                                                $field = $subfields[$ind];
                                                $valid = K2FieldsModelFields::value($field, 'valid');
                                        }
                                        
                                        if ($isDaysDuration) {
                                                $_term['def']['valid'] = $valid;
                                                $_term['def']['search'] = 'default';
                                                
                                                $valid = $valid == 'days' ? 'duration' : 'days';

                                                $t = array(
                                                        'def' => array('valid'=>$valid, 'search'=>'default', 'complete'=>false, 'custom' => $valid == 'duration' ? 'start' : ''),
                                                        'val' => $_term['val']
                                                );

                                                $_terms[] = $t;
                                                
                                                if ($valid == 'duration') {
                                                        $t['def']['custom'] = 'end';
                                                        $_terms[] = $t;
                                                }
                                                
                                                break;
                                        }
                                }
                                
                                $_term['def']['valid'] = $valid;
                                $_term['def']['search'] = K2FieldsModelFields::value($field, 'search');
                                
                                if ($valid == 'list') {
                                        // hierarchy based input we only need leaf level
                                        $positions = array_keys($_terms);
                                        $position = max($positions);
                                        $_val_ = array();

                                        foreach ($positions as $pos) {
                                                $_val_[] = $_terms[$pos]['val'];
                                                if ($position != $pos) unset($_terms[$pos]);
                                        }
                                        if (!isset($_terms[$position]['_val_'])) {
                                                $_terms[$position]['_val_'] = $_val_;
                                        }
                                }
                        }
                }
                
                $input = JFactory::getApplication()->input;
                
                if ($checkRequest) {
                        $catid = $input->get('scid', '', 'int');
                        $catid = $input->get('cid', $catid, 'int');
                        
                        $items = $input->get('items', '', 'string');
                } else {
                        $catid = isset($requestData['cid']) ? $requestData['cid'] : '';
                        
                        $items = isset($requestData['items']) ? $requestData['items'] : '';
                }
                
                if ($catid) {
                        $terms['catid'] = array(
                            0 => array(
                                'val' => $catid, 
                                'def' => array(
                                    'search' => 'default',
                                    'valid' => '_catid',
                                    'complete' => true
                                )
                            )
                        );
                        
                        // cid prefixed by s as cid is mangled out during route
                        $url .= (!empty($url) ? '&' : '') . 'scid='.$catid;
                }
                
                if ($items) {
                        $items = explode(',', $items);
                        
                        JprovenUtility::toInt($items);
                        
                        $terms['items'] = array(
                            0 => array(
                                'val' => $items,
                                'def' => array(
                                    'search' => 'default',
                                    'valid' => '_items',
                                    'complete' => true
                                )
                            )
                        );
                        
                        $url .= (!empty($url) ? '&' : '') . 'items='.implode(',', $items);
                }
                
                $freetext = JFactory::getApplication()->input->get('ft', null, 'string');
                
                if (!empty($freetext)) {
                        $replace = array('#', '>', '<', '\\', JText::_("search")."...");
                        $freetext = trim(str_replace($replace, '', $freetext));
                        
                        if (!empty($freetext)) {
                                $terms['freetext'] = array(0 => array('val'=>$freetext,'def'=>array('search'=>'default','valid'=>'freetext','complete'=>true)));
                                $url .= (!empty($url) ? '&' : '') . 'ft='.urlencode($freetext);
                        }
                }
                
                if (!$parseOnly) {
                        self::$_searchTerms = $terms;
                        self::$_searchUrl = $url;
                } else {
                        return $returnAs == 'url' ? $url : $terms;
                }
        }
        
        function sqlSelect(&$query, $ordering, $layout, $mode = 'data') {
                $q = '';
                
                if ($mode == 'count')
                        $q = "SELECT COUNT(DISTINCT ".self::$_ITEMTBL.".id)";
                else if ($mode == 'id')
                        $q = "SELECT DISTINCT ".self::$_ITEMTBL.".id";
                else if ($mode == 'data') {
                        $q = 
                        "SELECT DISTINCT ".self::$_ITEMTBL.".*".
                        ", CASE WHEN CHAR_LENGTH(".self::$_ITEMTBL.".alias) THEN CONCAT_WS(':', ".self::$_ITEMTBL.".id, ".self::$_ITEMTBL.".alias) ELSE ".self::$_ITEMTBL.".id END as slug";
                        
                        if (isset(self::$_searchTerms['catid'])) {
                                $q .= ", ".self::$_CATTBL.".name as categoryname, ".self::$_CATTBL.".id as categoryid, ".self::$_CATTBL.".alias as categoryalias, ".self::$_CATTBL.".params as categoryparams"
                                . ", CASE WHEN CHAR_LENGTH(".self::$_CATTBL.".alias) THEN CONCAT_WS(':', ".self::$_CATTBL.".id, ".self::$_CATTBL.".alias) ELSE ".self::$_CATTBL.".id END as catslug";
                        }
                        
                        if ($ordering == 'best')
                                $q .= ", (r.rating_sum/r.rating_count) AS rating";
                }
                
                if ($layout == 'map') {
                        if (!empty(self::$_mapTbl)) $q .= ', '.self::$_mapTbl.".lat, ".self::$_mapTbl.".lng";
                        
                        $zoom = JFactory::getApplication()->input->get('zoom', K2FieldsModelFields::setting('mapzoom'), 'int');
                        $result = self::cluster($result, $zoom);
                }                
                
                $query = $q . ' ' . $query;
        }
        
        function sqlWhere(&$query, $ordering) {
                $now = JFactory::getDate()->toMySQL();
                $nullDate = $this->_db->getNullDate();                
                $query .= " WHERE ".self::$_ITEMTBL.".published = 1 AND ";
                $query .= " (".self::$_ITEMTBL.".publish_up = ".$this->q($nullDate)." OR ".self::$_ITEMTBL.".publish_up <= ".$this->q($now).") AND ";
                $query .= " (".self::$_ITEMTBL.".publish_down = ".$this->q($nullDate)." OR ".self::$_ITEMTBL.".publish_down >= ".$this->q($now).") AND ";
                                
                $user = JFactory::getUser();

                $access = '.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')';
                
                $query .= self::$_ITEMTBL.$access." AND ".self::$_ITEMTBL.".trash = 0";
                
                if (isset(self::$_searchTerms['catid'])) 
                        $query .= " AND ".self::$_CATTBL.".published = 1"
                                ." AND ".self::$_CATTBL.$access
                                ." AND ".self::$_CATTBL.".trash = 0";                        

                $input = JFactory::getApplication()->input;
                
                if ($input->get('featured', -1, 'int') === 0) {
                        $query .= " AND ".self::$_ITEMTBL.".featured != 1";
                } else if ($input->get('featured', -1, 'int') == '2') {
                        $query .= " AND ".self::$_ITEMTBL.".featured = 1";
                }       
                
                $ft = $this->_freetext();
                
                if (!empty($ft)) $query .= ' AND '.$ft;
                
                $module = $input->get('module', '', 'int');
                
                if (!empty($module)) {
                        $module = JprovenUtility::getModule($module);
                        
                        if ($module->module == 'mod_k2fields') {
                                $excluded = $module->params->get('excludecategories');
                                
                                if (!empty($excluded)) {
                                        $excluded = (array) $excluded;
                                        $query .= " AND ".self::$_CATTBL.".id NOT IN (".implode(',', $excluded).")";
                                }
                        }
                }
                
                if (!empty(self::$wheres)) $query .= ' AND '.implode(' AND ', self::$wheres);
        }
        
        function sqlFrom(&$query, $ordering) {
                $query .= " FROM #__k2_items AS ".self::$_ITEMTBL." ";
                $join = $this->sqlJoin($query);
//                $query .= " LEFT JOIN #__k2_categories AS c ON c.id = i.catid";
//
//                $catid = JFactory::getApplication()->input->get('cid', false, 'int');
//                
//                if ($catid) {
//                        $query .= " AND c.id = " . $catid;
//                }
                
                if ($ordering == 'best') {
                        $query .= " LEFT JOIN #__k2_rating r ON r.itemID = ".self::$_ITEMTBL.".id";
                }
        }
        
        private static function getOrderDirection($orderBy) {
                static $special = array('created', 'alpha', 'order', 'publish_down');
                
                $dir = JFactory::getApplication()->input->get('dir', '', 'word');
                
                if (empty($dir)) {
                        $dir = in_array($orderBy, $special) ? 'ASC' : 'DESC';
//                        static $noneFields = array('id', 'date', 'alpha', 'order', 'hits', 'rand', 'best');
//                        
//                        if (in_array($orderBy, $noneFields)) {
//                                $dir = in_array($dir, $special) ? 'ASC' : 'DESC';
//                        }
//                        else {
//                                $model = K2Model::getInstance('fields', 'K2FieldsModel');
//                                $options = $model->getFieldsById($orderBy);
//                                $dir = K2FieldsModelFields::value($options, 'dir');
//                        }
                }
                
                $reverse = JFactory::getApplication()->input->get('rdir', false, 'bool');
                
                if ($reverse) {
                        $dir = $dir == 'ASC' ? 'DESC' : 'ASC';
                }
                
                return $dir;
        }
        
        function sqlOrderby(&$query, $ordering) {
                $ord = JFactory::getApplication()->input->get('ord', $ordering, 'word');
                if (empty($ord)) $ord = 'id';
                
                $orderby = array_values(self::$_orders);
                
                switch ($ord) {
                        case 'rdate':
                        case 'date':
                                $orderby[] = self::$_ITEMTBL.'.created';
                                break;
                        case 'rmod':
                        case 'mod':
                                $orderby[] = self::$_ITEMTBL.'.created';
                                break;
                        case 'redate':
                        case 'edate':
                        case 'rpublish_down':
                        case 'publish_down':
                                $ord = (substr($ord, 0, 1) == 'r' ? 'r' : '').'publish_down';
                                $orderby[] = self::$_ITEMTBL.'.publish_down';
                                break;
                        case 'ralpha':
                        case 'alpha':
                                $orderby[] = self::$_ITEMTBL.'.title';
                                break;
                        case 'order':
                        case 'rorder':
                                if (JFactory::getApplication()->input->get('featured', '', 'int') == '2') {
                                        $orderby[] = self::$_ITEMTBL.'.featured_ordering';
                                } else {
                                        $orderby[] = self::$_ITEMTBL.'.ordering';
                                }
                                break;
                        case 'hits':
                                $orderby[] = self::$_ITEMTBL.'.hits';
                                break;
                        case 'rand':
                                $orderby[] = 'RAND()';
                                break;
                        case 'best':
                                // TODO : If needed to sort by each rating criteria then sort against rating table
                                $orderby[] = self::$_ITEMTBL.'rating';
                                break;
                        case 'id':
                        default:
                                $orderby[] = self::$_ITEMTBL.'.id';
                                break;
                }
                
                if ($ord != 'rand' && !empty($ord)) {
                        $dir = self::getOrderDirection($ord);
                        $orderby[count($orderby) - 1] .= ' '.$dir;
                }
                
                $query .= " ORDER BY " . implode(', ', $orderby);
        }
        
        public static function getSearchUrl() {
                $url = self::$_searchUrl;
                $url = str_replace(
                        array(self::VALUE_SEPARATOR_REPLACEMENT, '&scid=', '?scid='),
                        array(K2FieldsModelFields::VALUE_SEPARATOR, '&cid=', '?cid='),
                        $url
                );
                $url = str_replace('&cid=', '&scid=', $url);
                return $url;
        }
        
        public static function isMenuSearch($view) {
                $menus = JSite::getMenu();
                $menu = $menus->getItem(JFactory::getApplication()->input->get('Itemid', '', 'int'));
                $menuItems = parse_url($menu->link);
                parse_str($menuItems['query'], $menuItems);
                if ($view == 'item' && isset($menuItems['task']) && $menuItems['task'] == 'search') return true;
                $menuItems = self::parseSearchTerms($menuItems, true);
                parse_str($menuItems, $menuItems);
                parse_str(self::$_searchUrl, $searchItems);
                $isMenu = JprovenUtility::contains($searchItems, $menuItems, array('option', 'task', 'layout', 'view'));
                return $isMenu;
        }
        
        public static function addPathway() {
                $app = JFactory::getApplication();
                
                if (!$app->isSite()) return;
                
                self::parseSearchTerms();
                
                $input = JFactory::getApplication()->input;
                
                $option = $input->get('option', '', 'cmd');
                $view = $input->get('view', '', 'cmd');
                $task = $input->get('task', '', 'cmd');
                $Itemid = $input->get('Itemid', '', 'int');
                
                $pathway = $app->getPathway();
                $pathwayArr = $pathway->getPathway();
                
                if (empty($Itemid)) {
                        if ($option == 'com_k2fields' && $view == 'itemlist' && $task == 'search' && $input->get('layout') == 'compare') {
                                array_pop($pathwayArr);
                                $pathway->setPathway($pathwayArr);
                                $pathway->addItem(JText::_('Compare items'), '');
                                $pathwayArr = $pathway->getPathway();
                                JFactory::getDocument()->setTitle(JText::_('Compare items'));
                        }
                        return;
                }
                
                if (self::isMenuSearch($view)) {
                        $app = JFactory::getApplication();
                        
                        if ($view == 'item') {
                                $menus = JSite::getMenu();
                                $menu = $menus->getActive();
                                if (@$menu->query['option'] == 'com_k2fields') {
                                        $last = array_pop($pathwayArr);
                                        array_pop($pathwayArr);
                                        array_push($pathwayArr, $last);
                                }
                        } else if ($task == 'search') {
                                array_pop($pathwayArr);
                        }
                        
                        return;
                }
                
                $menus = JSite::getMenu();
                $k2Item = $option == 'com_k2' && $view == 'item';
                $k2fieldsSearch = 
                        $option == 'com_k2fields' && 
                        $view == 'itemlist' && 
                        $task == 'search'
                        ;
                
                if (!$k2Item && !$k2fieldsSearch) return;
                
                $last = array_pop($pathwayArr);
                
                // TODO: extend router class with custom k2fieldsItem method that 
                // is able to look up item id on the basis of provided category
                // need to provide category id correctly
                if (!empty(self::$_searchUrl)) {
                        $sUrl = '&'.preg_replace('/(^|\&)(scid=)(\d+)/', '$1cid=$3', self::$_searchUrl);
                        $link = urldecode(JRoute::_('index.php?option=com_k2fields&view=itemlist&task=search')).$sUrl;
                } else {
                        $link = '';
                }
                
                if ($k2Item) {
                        if ($input->get('k2item', 0, 'int') == 1) {
                                array_pop($pathwayArr);
                                if (empty($link)) $pathwayArr[] = $last;
                                $pathway->setPathway($pathwayArr);
                        }
                        
                        if (!empty($link)) {
                                $pathway->setPathway($pathwayArr);
                                $name = '('.JText::_('Back to search results').')';
                                $pathway->addItem($name, $link);
                                $pathway->addItem($last->name, $last->link);
                        }
                } else if ($k2fieldsSearch) {
                        // TODO: Add details about search fields based on self::$_searchTerms
                        $name = JText::_('Search results');
                        $pathway->addItem($name, $link);
                        
                        $document = JFactory::getDocument();
                        $menu = $menus->getActive();
                        $params = JComponentHelper::getParams('com_k2');
                        
                        if (is_object($menu)) {
                                $menu_params = new JRegistry($menu->params);
                                
                                if (!$menu_params->get('page_title'))
                                        $params->set('page_title', $name);
                        } else {
                                $params->set('page_title', $name);
                        }
                        
                        $document->setTitle($params->get('page_title'));                        
                }
        } 
}

?>
