<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

class K2FieldsModelFields extends K2Model {
        const JS_VAR_NAME = 'k2fs';
        const USERADDED = 'useradded:';
        const FILTER_ADDED_BY_USER = 1;
        const FILTER_NOT_ADDED_BY_USER = 2;
        const LIST_CONDITION_SEPARATOR = '=:=:=';
        const LIST_ITEM_SEPARATOR = '-:-:-';
        const FIELD_OPTIONS_SEPARATOR = ':::';
        const FIELD_SEPARATOR = '---';
        const VALUE_SEPARATOR = '%%';
        const VALUE_COMP_SEPARATOR = '==';
        const MULTI_VALUE_SEPARATOR = '-%-%-';
        const TABLE_COL_SEPARATOR = '|';
        const TABLE_ROW_SEPARATOR = "\r\n";
        const DEFAULT_UI = 'list';
        const DEFAULT_UI_SECTION = 'Data';
        
        private static $editableK2Types = array('select', 'multipleselect', 'radio');
        private static $extendedTypes = array('map', 'list', 'media', 'k2item');
        public static $dontSend = array('view', 'access', 'definition', 'isK2NativeList', 'isK2Field', 'isEditableList', 'isMedia', 'isMap', 'isList', 'group', 'published', 'ordering', 'picplg', 'itemlistpicplg', 'providerplg', 'itemlistproviderplg', 'videoplg', 'itemlistvideoplg', 'audioplg', 'itemlistaudioplg', 'mediafileexts');
        private static $defaultSection = self::DEFAULT_UI_SECTION;
        public static $autoFieldTypes = array('title', 'rate', 'facebook', 'form', 'pinterest', 'linkedin', 'twitter', 'googleplus', 'readability', 'form');
        private static $autoFieldTypesRendered = array();
        
        function __construct($config = array()) {
                parent::__construct($config);
                
                // JprovenUtility::checkPluginActive('k2fields', 'k2', 'PLG_K2FIELDS_PLUGIN_INACTIVE');
                
                foreach (self::$extendedTypes as $type) $this->loadType($type);
                
                self::$defaultSection = JText::_('DEFAULT_SECTION');
        }
        
        private function loadType($type) {
                $cls = 'K2Fields'.ucfirst($type);
                
                JLoader::register($cls, dirname(__FILE__) . '/types/'.$type.'.php');
                
                if (JLoader::load($cls) !== false) return $cls;
                
                return false;
        }
        
        public static function pre($tab = null) {
                static $rtab;
                
                if (empty($rtab)) $rtab = JRequest::getCmd('type', '');

                if ($rtab == 'searchfields') $rtab = 'search';
                
                if (empty($tab)) $tab = $rtab;
                
                return $tab == 'search' ? 's' : 'K2ExtraField_';
        }
        
        public function preSave(&$item) {
                $variables = JRequest::get('post');
                
                foreach ($variables as $key => $value) {
                        if (($field = $this->getField($key)) !== false && $field->isEditableList) {
                                if (!JprovenUtility::isPrefixed($value, self::USERADDED)) continue;

                                $valueToSave = substr($value, strlen(self::USERADDED));

                                if (empty($valueToSave)) continue;

                                $valueFound = false;
                                $existingValues = $field->value;

                                foreach ($existingValues as $existingValue) {
                                        if (strtolower(trim($existingValue->value)) == strtolower(trim($valueToSave))) {
                                                $valueFound = true;
                                                break;
                                        }
                                }

                                // @@TODO: Applies to K2 native fields and should be removed and replaced with support for
                                // k2fields values
                                if (!$valueFound) {
                                        $object = new JObject;

                                        $object->set('name', $valueToSave);
                                        $valueToSave = sizeof($existingValues) + 1;
                                        JRequest::setVar($key, $valueToSave);
                                        $object->set('value', $valueToSave);
                                        $object->set('target', null);
                                        unset($object->_errors);
                                        $existingValues[] = $object;

                                        // save edittable values
                                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                                        $field = JTable::getInstance('K2ExtraField', 'Table');
                                        $field->load($field->id);
                                        $field->value = json_encode($existingValues);

                                        if (!$field->check()) {
                                                $item->setError($field->getError());
                                                return false;
                                        }

                                        if (!$field->store()) {
                                                $item->setError($field->getError());
                                                return false;
                                        }                                        
                                }
                        }
                }
                
                return true;
        }
        
        /**
         * postprocessing of k2 item saving where
         * 1. media files are saved
         * 2. field_values table are refreshed with this new set of data
         * 3. assign proper expiry setting
         */
        public function save(&$item, $isNew) {
                $fields = $this->getFieldsByItem($item->id);
                
                $this->maintain($item, $fields, 1);
                
                if ($this->loadType('media')) $result = K2FieldsMedia::save($item, $fields);
                
                $this->maintain($item, $fields, 2);
                
                $item->extra_fields_search = str_ireplace(
                        array(self::LIST_ITEM_SEPARATOR, self::LIST_CONDITION_SEPARATOR), 
                        ' ', 
                        $item->extra_fields_search
                );

                $this->adjustUnpublishDate($item);
                
                $result = $item->store();
        }
        
        public function adjustUnpublishDates($on = 'notunpublished') {
                $categories = self::setting('checkandexpire');
                
                if (empty($categories)) return false;
                
                $categories = JprovenUtility::toIntArray($categories, true);
                
                $query = 'SELECT id, catid, publish_up, publish_down FROM #__k2_items WHERE catid IN ('.$categories.')';
                
                //$on = self::setting('checkandexpireon');
                
                $db = JFactory::getDbo();
                
                if ($on == 'notunpublished') {
                        $query .= ' AND publish_down = '.$db->quote($db->getNullDate());
                }
                
                $db->setQuery($query);
                $items = $db->loadObjectList();
                
                foreach ($items as $id => $item) {
                        if ($d = $this->adjustUnpublishDate($item)) {
                                $query = 'UPDATE #__k2_items SET publish_down = '.$db->quote($d).' WHERE id = '.$item->id;
                                $db->setQuery($query);
                                $db->query();
                        }
                }
        }
        
        private function adjustUnpublishDate(&$item) {
                $db = JFactory::getDbo();
                $nullDate = $db->getNullDate();
                $itemId = $item->id;
                $publishUp = $item->publish_up != $nullDate ? JprovenUtility::createDate($item->publish_up) : null;
                
                // Expire based on field values
                $fields = $this->getFieldsByItem($itemId);
                $limit = null;
                
                foreach ($fields as $fieldId => $field) {
                        if (K2FieldsModelFields::isTrue($field, 'expire')) {
                                $isDatetime = K2FieldsModelFields::isDatetimeType($field);
                                $query = $isDatetime ? 'datum' : 'value';
                                $query = 'SELECT max('.$query.') FROM #__k2_extra_fields_values WHERE itemid = '.$itemId.' AND fieldid = '.$fieldId;
                                $db->setQuery($query);
                                $max = $db->loadResult();
                                
                                if (!$max) continue;
                                
                                if ($isDatetime) {
                                        $limit = JprovenUtility::createDate($max);
                                } else if (K2FieldsModelFields::isNumeric($field)) {
                                        $diff = new DateInterval('P'.$max.'D');
                                        $limit = $publishUp->add($diff);
                                }
                        }
                }
                
                // If not expired by field value then try based on k2fields plugin setting
                if (!$limit) {
                        $colsValue = 2;
                        $colsUnit = 3;

                        $exp = K2FieldsModelFields::categorySetting($item->catid, 'expirerecords');

                        if ($exp) {
                                $exp = JprovenUtility::first($exp);
                                $exp = $exp[0];
                                $unit = self::_v($exp, $colsUnit);
                                $unit = substr(strtoupper($unit), 0, 1);
                                $value = self::_v($exp, $colsValue);

                                if ($unit == 'A') {
                                        $limit = JprovenUtility::createDate($value);
                                } else {
                                        if ($unit == 'M' || $unit == 'H') $unit = 'T'.$unit;
                                        
                                        $diff = new DateInterval('P'.$value.$unit);
                                        $limit = $publishUp->add($diff);
                                }
                        }
                }
                
                if ($limit) {
                        //$limit->add(new DateInterval('PT5M'));
                        $item->publish_down = $limit->toMySQL();
                        return $item->publish_down;
                }
                
                return false;
        }
        
        private static function expire($item, $isNew) {
                return;
                
                $app = JFactory::getApplication();
                $user = JFactory::getUser();
                
                $db = JFactory::getDBO();
                $nullDate = $db->getNullDate();
                
                $publishDown = $item->publish_down != $nullDate ? JprovenUtility::createDate($item->publish_down) : null;
                
                $itemId = $item->id;
                $isExpire = false;
                $now = JprovenUtility::createDate();
                
                $publishUp = $item->publish_up != $nullDate ? JprovenUtility::createDate($item->publish_up) : null;
                
                /** Auto expire based on field values **/
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $fields = $model->getFieldsByItem($itemId);
                $fieldsValues = $model->itemValues($itemId);
                $field = null;
                
                foreach ($fields as $fieldId => $field) {
                        $exp = K2FieldsModelFields::isTrue($field, 'expire');
                        $diff = null;
                        
                        if ($exp === true) {
                                $fieldValues = (array) $fieldsValues[$fieldId];
                                $fieldValues = JprovenUtility::getColumn($fieldValues, 'value');
                                $val = false;
                                
                                if (K2FieldsModelFields::isDatetimeType($field)) {
                                        $initialDate = JprovenUtility::createDate('1970-01-01');
                                        $maxDate = $initialDate;

                                        for ($i = 0, $n = count($fieldValues); $i < $n; $i++) {
                                                if (!empty($fieldValues[$i]->datum) && ($val = JprovenUtility::createDate($fieldValues[$i]->datum))) {
                                                        if ($maxDate < $val) 
                                                                $maxDate = $val;
                                                }
                                        }

                                        if ($maxDate == $initialDate) continue;
                                        
                                        $limit = $maxDate;
                                        $diff = $limit->diff($now);
                                } else if (K2FieldsModelFields::isNumeric($field)) {
                                        $maxVal = 0;
                                        
                                        for ($i = 0, $n = count($fieldValues); $i < $n; $i++) {
                                                if ($val = (int) $fieldValues[$i]) {
                                                        if ($maxVal < $val) $maxVal = $val;
                                                }
                                        }
                                        
                                        if ($maxVal == 0) continue;
                                        
                                        $diff = new DateInterval('P'.$maxVal.'D');
                                        $limit = $publishUp->add($diff);
                                        $diff = $limit->diff($now);
                                }
                        }
//                        else {
//                                $exp = K2FieldsModelFields::isValue($field, 'expire', 'true');
//                                
//                                if ($exp === true) {
//                                        // expires on the basis of the current time
//                                        
//                                        if (!empty($publishDown)) {
//                                                // finish publishing date available
//                                                $diff = $publishDown->diff($now);
//                                        } else {
//                                                $interval = K2FieldsModelFields::value($field, 'autoexpireinterval');
//                                                
//                                                if (!empty($interval)) {
//                                                        $diff = new DateInterval('P'.$fieldValues[$fieldId]->value.'D');
//                                                        $down = $publishUp->add($diff);
//                                                        $diff = $down->diff($now);
//                                                }
//                                        }                                   
//                                }
//                        }
                        
                        if (!empty($diff) && $diff->days <= 0) {
                                $isExpire = true;
                                $expireMtd = 'field';
                                break;
                        }
                }
                
                /** 
                 * If not expired by field values then
                 * - expire based on publish down value or
                 * - plugin settings
                 **/
                if (!$isExpire) {
                        if (!empty($publishDown)) {
                                // finish publishing date available
                                $diff = $publishDown->diff($now);
                                $expireMtd = 'publishdown';
                        } else {
                                /**
                                 * 
                                 * catid
                                 * excluded child catids (comma separated)
                                 * value
                                 * unit for provided value (a = absolute|y = # of years|m = # of months|d = # of days)
                                 * action when expired (t|trash|u|unpublish)
                                 * send notification to = (all|user|admin|add)
                                 * 
                                 */
                                $colsExclude = 1;
                                $colsValue = 2;
                                $colsUnit = 3;
                                $colsAction = 4;
                                $colsNotify = 5;
                                
                                $exp = K2FieldsModelFields::categorySetting($item->catid, 'expirerecords');
                                
                                if ($exp) {
                                        $exp = JprovenUtility::first($exp);
                                        $exp = $exp[0];
                                        $unit = self::_v($exp, $colsUnit);
                                        $unit = substr(strtoupper($unit), 0, 1);
                                        $value = self::_v($exp, $colsValue);
                                        $action = self::_v($exp, $colsAction, 'u');
                                        $notify = self::_v($exp, $colsNotify);
                                        
                                        if ($unit == 'A') {
                                                $limit = JprovenUtility::createDate($value);
                                                $diff = $limit->diff($now);
                                        } else {
                                                $diff = new DateInterval('P'.$value.$unit);
                                        }
                                }
                                
                                $expireMtd = 'setting';
                        }
                        
                        if (!empty($diff) && $diff->days <= 0) {
                                $isExpire = true;
                        }
                }
                
                if ($isExpire) {
                        if ($expireMtd == 'field') {
                                $action = K2FieldsModelFields::setting('expireaction', $field, 'u');
                        } else if ($expireMtd == 'publishdown') {
                                $action = 'u';
                        }
                        
                        $col = $val = '';
                        
                        switch ($action) {
                                case 'trash':
                                case 't':
                                        $col = 'trash';
                                        $val = 1;
                                        break;
                                case 'unpublish':
                                case 'u':
                                default:
                                        $col = 'published';
                                        $val = 0;
                                        break;
                        }
                        
                        $now = JFactory::getDate();
                        $now = $db->Quote($now->toMySQL());
                        $query = 'UPDATE #__k2_items SET '.$col.' = '.$val.', modified = '.$now.' WHERE id = '.$itemId;
                        $db->setQuery($query);
                        
                        if (!$db->query()) {
                                JError::raiseError(500, $db->stderr(true));
                                return;
                        }                        
                        
                        $cache = JFactory::getCache('com_k2');
                        $cache->clean();  
                }
                
                return $isExpire;
        } 
        
        public static function notifyExpiry() {
                $df = 'Y-n-j';
                $d = K2FieldsModelFields::setting('expirynotificationsent');
                
                if ($d == date($df)) return;
                
                $d = JprovenUtility::createDate();
                $notificationOffset = K2FieldsModelFields::setting('expirynoticeoffset', null, '1D');
                $d = $d->sub(new DateInterval('P'.strtoupper($notificationOffset)));
                
                $db = JFactory::getDBO();
                
                $dayStart = $db->Quote(JFactory::getDate($d->setTime(0, 0, 0)->format('Y-m-d H:i:s'))->toMySQL());
                $dayEnd = $db->Quote(JFactory::getDate($d->setTime(23, 59, 59)->format('Y-m-d H:i:s'))->toMySQL());
                
                $nullDate = $db->Quote($db->getNullDate());
                $query = 'SELECT i.id, i.title, i.alias, i.catid, c.alias as catalias FROM #__k2_items i LEFT JOIN #__k2_categories c ON i.catid = c.id '.
                        'WHERE i.published = 0 AND publish_down <> '.$nullDate.' AND publish_down >= '.$dayStart.' AND publish_down <= '.$dayEnd;
                $db->setQuery($query);
                $items = $db->loadObjectList();
                
                if (!empty($items)) {
                        foreach ($items as $item) {
                                $notify = false;
                                
                                $exp = K2FieldsModelFields::categorySetting($item->catid, 'expirerecords');
                                
                                if ($exp) {
                                        $exp = JprovenUtility::first($exp);
                                        $exp = $exp[0];
                                        $colsNotify = 5;
                                        $notify = self::_v($exp, $colsNotify, false);
                                }
                                
                                if ($notify !== false) {
                                        $link = K2FieldsHelperRoute::getItemRoute($item->id.':'.urlencode($item->alias),$item->catid.':'.urlencode($item->catalias));
                                        $link = urldecode(JRoute::_($link));
                                        $recipients = ($notify == 'all' || $notify == 'owner') ? '' : $item->created_by;
                                        $recipients = (array) $recipients;

                                        if ($notify == 'all' || $notify == 'add') {
                                                $notifyAdditional = self::setting('expirenotifyadditional');

                                                if ($notifyAdditional) {
                                                        $notifyAdditional = explode(',', $notifyAdditional);
                                                }

                                                $recipients = array_merge($recipients, $notifyAdditional);
                                        }

                                        $subject = JText::sprintf('K2FIELDS_EXPIRATION_NOTICE_SUBJECT', $action, $item->title, $link);
                                        $msg = JText::sprintf('K2FIELDS_EXPIRATION_NOTICE_MESSAGE', $action, $item->title, $link);

                                        JprovenUtility::sendMail($subject, $msg, $recipients, ($notify == 'all' || $notify == 'admin'));
                                }
                        }
                }
                
                JprovenUtility::plgParam('k2fields', 'k2', 'expirynotificationsent', date($df), 'set');
        }
        
        public static function implodeValues($values, $field) {
                $isK2Field = self::isTrue($field, 'isK2Field');
                
                if (!$isK2Field) return $values;
                
                if (empty($values)) return '';
                
                $strFieldValue = '';
                $isItemImploded = is_string($values[0]);
                
                foreach ($values as $ind => $value) {
                        $strFieldValue .= 
                                (!empty($strFieldValue) ? self::LIST_ITEM_SEPARATOR : '') .
                                ($isItemImploded ? $value : self::implodeValuesPerListitem($value, $field));
                }
                
                return $strFieldValue;
        }
        
        public static function implodeValuesPerListitem($value, $field) {
                $isK2Field = self::isTrue($field, 'isK2Field');
                
                if (!$isK2Field) return $value;
                
                $condition = array_shift($value);
                
                return implode(self::VALUE_SEPARATOR, $value) . 
                        self::LIST_CONDITION_SEPARATOR . 
                        $condition;
        }
        
        public static function explodeValues($strFieldValue, $field) {
                $isK2Field = self::isTrue($field, 'isK2Field');
                
                if (!$isK2Field) return $strFieldValue;
                
                if (empty($strFieldValue)) return array();
                
                $values = explode(self::LIST_ITEM_SEPARATOR, $strFieldValue);
                
                foreach ($values as $ind => $value) {
                        $els = explode(self::LIST_CONDITION_SEPARATOR, $value);
                        $value = $els[0];
                        $condition = count($els) == 1 ? '' : $els[1];
                        $parts = explode(self::VALUE_SEPARATOR, $value);
                        array_unshift($parts, $condition);
                        $values[$ind] = $parts;
                }                
                
                return $values;
        }
        
        private function maintain(&$item, $fields, $pass = 1) {
                jimport('joomla.filesystem.file');
                jimport('joomla.filesystem.folder');
                
                $itemFields = $item->extra_fields;
                
                if (empty($itemFields)) return;
                
                $itemFields = json_decode($itemFields);
                $cnt = 1;
                
                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/tables/');
                $r = JTable::getInstance('K2ExtraFieldValue', 'Table');
                $r->itemid = $item->id;
                $db = $this->_db;
                $query = 'SELECT * FROM #__k2_extra_fields_values WHERE itemid = '.(int)$item->id . ' ORDER BY fieldid, listindex, partindex, '.$db->nameQuote('index');
                $db->setQuery($query);
                $_earliers = $db->loadObjectList('id');
                $earliers = array();
                
                foreach ($_earliers as $e) {
                        if (!isset($earliers[$e->fieldid])) {
                                $earliers[$e->fieldid] = array();
                        }
                        if (!isset($earliers[$e->fieldid][$e->listindex])) {
                                $earliers[$e->fieldid][$e->listindex] = array();
                        }
                        if (!isset($earliers[$e->fieldid][$e->listindex][$e->partindex])) {
                                $earliers[$e->fieldid][$e->listindex][$e->partindex] = array();
                        }
                        if (!isset($earliers[$e->fieldid][$e->listindex][$e->partindex][$e->index])) {
                                $earliers[$e->fieldid][$e->listindex][$e->partindex][$e->index] = array();
                        }
                        $earliers[$e->fieldid][$e->listindex][$e->partindex][$e->index] = $e->id;
                }
                
                $list = new K2FieldsList();
                
                foreach ($itemFields as $f => $field) {
                        $fieldData = $fields[$field->id];
                        
                        if (is_object($fieldData)) $fieldData = get_object_vars($fieldData);
                        
                        $r->fieldid = $field->id;
                        
                        if ($fieldData['isK2Field']) {
                                $values = self::explodeValues($field->value, $fieldData);
                                
                                if ($fieldData['isMedia']) {
                                        foreach ($values as $i => &$val) {
                                                if (empty($val[K2FieldsMedia::SRCPOS])) {
                                                        unset($values[$i]);
                                                }
                                        }
                                        
                                        if (empty($values)) continue;
                                }
                        } else {
                                $values = array($field->value);
                        }
                        
                        if (!empty($values)) {
                                foreach ($values as $r->listindex => $parts) {
                                        if ($fieldData['isK2Field']) {
                                                $isUpload = $isBrowse = $isRemote = false;
                                                $browseFiles = null;
                                                $caption = '';
                                                $startPart = -1;
                                                $endPart = count($parts) - 1;
                                                $condition = $parts[0];
                                        } else {
                                                $condition = '';
                                                $parts = array($field->value);
                                                $startPart = 0;
                                                $endPart = count($parts);
                                        }
                                        
                                        $mediaRemoved = false;
                                        if ($pass == 2 && self::isDatetimeType($fieldData))
                                                $this->maintainDates($parts, $fieldData, $item, $r->listindex);
                                        
                                        for ($r->partindex = $startPart, $p = count($parts); $r->partindex < $endPart; $r->partindex++) {
                                                $part = $parts[$r->partindex + 1];
                                                
                                                if ($fieldData['isK2Field']) {
                                                        $multiParts = explode(self::MULTI_VALUE_SEPARATOR, $part);
                                                } else {
                                                        $multiParts = is_array($field->value) ? $field->value : array($field->value);
                                                }
                                                
                                                for ($r->index = 0, $mp = count($multiParts); $r->index < $mp; $r->index++) {
                                                        $r->value = $multiParts[$r->index];
                                                        
                                                        if (self::isType($fieldData, 'map') && $r->partindex == 0) {
                                                                list($r->lat, $r->lng) = explode(',', $r->value);
                                                        } else {
                                                                $r->lat = $r->lng = null;
                                                        }
                                                        
                                                        $isDatetime = self::isDatetimeType($fieldData);
                                                        $isDuration = false;
                                                        
                                                        if (!($isList = self::isType($fieldData, 'list')) && $r->partindex >= 0) {
                                                                if (self::isType($fieldData, 'complex')) {
                                                                        $subfields = self::value($fieldData, 'subfields');
                                                                        $subfield = $subfields[$r->partindex];
                                                                        $isList = self::isType($subfield, 'list');
                                                                        $isDuration = self::isType($subfield, 'duration');
                                                                        $isDatetime = self::isDatetimeType($subfield);
                                                                }
                                                        }
                                                        
                                                        //$r->datum = $isDatetime && $r->partindex <= 1 ? $r->value : null;
                                                        if ($isDuration) {
                                                                $r->duration = self::createDuration($r->value);
                                                                $r->datum = null;
                                                        } else if ($isDatetime) {
                                                                $r->datum = $isDatetime ? $r->value : null;
                                                                $r->duration = null;
                                                        } else {
                                                                $r->duration = $r->datum = null;
                                                        }
                                                        
                                                        if ($isList && $r->partindex >= 0 && !empty($r->value)) {
                                                                $_v = $list->getValue($r->value);
                                                                
                                                                if ($_v) {
                                                                        $r->lat = $_v->lat;
                                                                        $r->lng = $_v->lng;
                                                                        $r->txt = $_v->value;
                                                                        $r->img = $_v->img;
                                                                } else {
                                                                        $r->lat = $r->lng = $r->txt = $r->img = null;
                                                                }
                                                        } else {
                                                                $_v = $this->lookUp($r->value, 'value', $fieldData, $r->partindex);
                                                                $r->txt = $_v !== false && $_v['text'] ? $_v['text'] : null;
                                                                $r->img = $_v !== false && $_v['img'] ? $_v['img'] : null;
                                                        }
                                                        
                                                        if ($fieldData['isMedia'] && $r->index == 0) {
                                                                if ($r->partindex == K2FieldsMedia::SRCTYPEPOS - 1) {
                                                                        $isUpload = $part == 'upload';
                                                                        $isBrowse = $part == 'browse';
                                                                        $isRemote = $part == 'remote';
                                                                        
                                                                        // TODO: move to K2FieldsMedia::save
                                                                        if ($isBrowse) {
                                                                                $vId = $parts[K2FieldsMedia::SRCPOS];

                                                                                if (!is_numeric($vId)) continue;

                                                                                $query = '
                                                                                        SELECT DISTINCT vv.partindex, vv.value, vv.condition
                                                                                        FROM #__k2_extra_fields_values v, #__k2_extra_fields_values vv
                                                                                        WHERE v.itemid = vv.itemid AND v.fieldid = vv.fieldid AND v.listindex = vv.listindex AND v.id = '.(int)$vId.'
                                                                                        ORDER BY vv.partindex'
                                                                                        ;

                                                                                $db->setQuery($query);

                                                                                $browseFiles = $db->loadObjectList('partindex');

                                                                                if (!empty($condition)) {
                                                                                        $caption = $condition;
                                                                                } else if ($parts[K2FieldsMedia::CAPTIONNAMINGPOS] == 'filenameascaption') {
                                                                                        $file = JPATH_SITE . '/' . $browseFiles[K2FieldsMedia::SRCPOS]->value;
                                                                                        $caption = K2FieldsMedia::getFileNameBasedCaption($file);
                                                                                } else {
                                                                                        $caption = $browseFiles[K2FieldsMedia::SRCPOS]->condition;
                                                                                }                                                                        
                                                                        }
                                                                }
                                                                
                                                                // TODO when isRemote download is support add check
//                                                                if ($pass == 2 && $isUpload && $r->partindex == K2FieldsMedia::SRCPOS - 1) {
//                                                                        if ($files !== false) $ind = array_search($part, $files);
//                                                                        
//                                                                        if ($files === false || $ind === false) {
//                                                                                $mediaRemoved = true;
//                                                                                unset($values[$r->listindex]);
//
//                                                                                $query = 'DELETE FROM #__k2_extra_fields_values WHERE itemid = ' . $item->id . ' AND fieldid = ' . $field->id . ' AND listindex = ' . $r->listindex;
//                                                                                $db->setQuery($query);
//
//                                                                                if (!$db->query()) {
//                                                                                        $item->setError($db->getError());
//                                                                                        return;
//                                                                                }
//                                                                                
//                                                                                break;
//                                                                        }
//
//                                                                        $founds[$ind] = true;
//                                                                }

                                                                if ($isBrowse) {
                                                                        if (isset($browseFiles) && $r->partindex > 0) {
                                                                                $r->value = $browseFiles[$r->partindex]->value;
                                                                                $parts[$r->partindex] = $browseFiles[$r->partindex]->value;
                                                                        }
                                                                }

                                                                if ($caption) {
                                                                        $condition = $caption;
                                                                }
                                                        }
                                                        
                                                        if ($fieldData['valid'] == 'k2item' && !empty($r->value)) {
                                                                $db->setQuery('SELECT title FROM #__k2_items WHERE id = '.(int) $r->value);
                                                                $r->txt = $db->loadResult();
//                                                                $cls = $this->loadType('k2item');
//                                                                $v = new stdClass();
//                                                                $v->value = $r->value;
//                                                                $v->partindex = 0;
//                                                                $v->listindex = 0;
//                                                                $v->itemid = $item->id;
//                                                                $vals = array(0 => array($v));
//                                                                $r->txt = call_user_func(array($cls, 'render'), $item, $vals, $fieldData, $this, array());
                                                        }
                                                        
                                                        if (isset($earliers[$r->fieldid][$r->listindex][$r->partindex][$r->index])) {
                                                                if (!empty($r->value) || $fieldData['isMedia']) {
                                                                        $r->id = $earliers[$r->fieldid][$r->listindex][$r->partindex][$r->index];

                                                                        unset($_earliers[$r->id]);
                                                                }
                                                        } else {
                                                                $r->id = null;
                                                        }
                                                        
                                                        if ((!empty($r->value) || $r->value === 0 || $r->value === '0' || $fieldData['isMedia']) && !$r->store()) {
                                                                $item->setError($r->getError());
                                                                return;
                                                        }
                                                }
                                                
                                                if ($mediaRemoved && $fieldData['isMedia']) break;
                                        }
                                        
                                        if ($mediaRemoved && $fieldData['isMedia']) continue;
                                        
                                        if ($fieldData['isK2Field'])
                                                $values[$r->listindex] = self::implodeValuesPerListitem($parts, $fieldData);
                                }
                        }
                        
                        if ($fieldData['isK2Field']) {
                                $itemFields[$f]->value = self::implodeValues($values, $fieldData);
                        }
                }
                
                if (!empty($_earliers)) {
                        $query = 'DELETE FROM #__k2_extra_fields_values WHERE id IN ('.implode(',', array_keys($_earliers)).')';
                        $db->setQuery($query);
                        
                        if (!$db->query()) {
                                $item->setError($db->getError());
                                return;
                        }
                }
                
                if ($pass == 2) {
                        $item->extra_fields = json_encode($itemFields);
                
                        $query = "
                                SELECT GROUP_CONCAT(IF(value <> '', value, ''), ' ', IF(txt <> '', txt, '') SEPARATOR ' ') as efvalue
                                FROM #__k2_extra_fields_values 
                                WHERE ((value IS NOT NULL AND value <> '') OR (txt IS NOT NULL AND txt <> '')) AND itemid = ".$item->id                        
                                ;

                        $db->setQuery($query);
                        $ef = $db->loadResult();
                        
                        $ef = str_ireplace(
                                array(self::LIST_ITEM_SEPARATOR, self::LIST_CONDITION_SEPARATOR), 
                                ' ', 
                                $ef
                        );
                        
                        $item->extra_fields_search = $ef;
                }
        }
        
        private static function createDateInterval($delta, $unit) {
                $delta = (int) $delta;
                
                $unit = strtoupper($unit);
                
                if ($unit == 'W') {
                        $unit = 'D';
                        $delta *= 7;
                } else if ($unit == 'H' || $unit == 'M') {
                        $unit = 'T'.$unit;
                }
                
                return new DateInterval('P'.$delta.$unit);
        }
        
        private function maintainDates($values, $fieldData, $item, $listIndex) {
                $type = self::value($fieldData, 'repeat');
                
                if (!in_array($type, array('true', 'enddate', 'limit'))) return;
                
                if ($values[2] != 'repeat') return;
                
                $startOn = JprovenUtility::createDate($values[1]);
                $freq = (int)$values[3];
                $unit = $values[4];
                $max = $values[5];
                $repeatMax = self::value($fieldData, 'repeatmax', 50);
                
                if ($type == 'enddate') $max = JprovenUtility::createDate($max);
                else $max = (int) $max;
                
                $fieldId = self::value($fieldData, 'id');
                
                $db = $this->_db;
                $query = 'DELETE FROM #__k2_extra_fields_values WHERE itemid = '.(int)$item->id.' AND fieldid = '.(int)$fieldId.' related = '.(int)$fieldId.' listindex = '.(int)$listIndex;
                $db->setQuery($query);
                $db->query();
                
                $r = JTable::getInstance('K2ExtraFieldValue', 'Table');
                
                $r->itemid = $item->id;
                $r->fieldid = $r->related = $fieldId;
                $r->partindex = 0;
                $r->listindex = $listIndex;
                
                $continue = true;
                $i = 0;
                
                while ($continue && $i < $repeatMax) {
                        $d = clone $startOn;
                        $interval = self::createDateInterval($freq*($i+1), $unit);
                        $d->add($interval);
                        $ds = JFactory::getDate($d->format('Y-m-d H:i:s'))->toMySQL();
                        
                        $r->id = null;
                        $r->value = $r->datum = $ds;
                        $r->index = $i + 1;
                        
                        $r->store();
                        
                        $i++;
                        $continue = $type == 'enddate' ? $d < $max : $i < $max;
                }
        }
        
        private function lookUp($lookFor, $what, $options, $partIndex = -1) {
                if ($options['valid'] == 'complex' && $partIndex != -1) $options = $options['subfields'][$partIndex];
                
                // TODO: predefined basic extending types. Need a better way of commonly sharing values with js.
                switch ($options['valid']) {
                        case 'days':
                                $options['values'] = array(
                                        array('img'=>'', 'value'=>7, 'text'=>'All days'),
                                        array('img'=>'', 'value'=>8, 'text'=>'Weekend'),
                                        array('img'=>'', 'value'=>1, 'text'=>'Monday'),
                                        array('img'=>'', 'value'=>2, 'text'=>'Tuesday'),
                                        array('img'=>'', 'value'=>3, 'text'=>'Wednesday'),
                                        array('img'=>'', 'value'=>4, 'text'=>'Thursday'),
                                        array('img'=>'', 'value'=>5, 'text'=>'Friday'),
                                        array('img'=>'', 'value'=>6, 'text'=>'Saturday'),
                                        array('img'=>'', 'value'=>0, 'text'=>'Sunday')
                                ); 
                                break;
                        case 'yesno':
                                $options['values'] = array(
                                        array('img'=>'yes.png', 'value'=>1, 'text'=>'Yes'),
                                        array('img'=>'no.png', 'value'=>0, 'text'=>'No')
                                );
                                break;
                        case 'range':
                                $i = $options['low'];
                                $h = $options['high'];
                                $s = $options['step'] ? $options['step'] : 1;
                                $va = isset($options['show']) ? $options['show'] : false;
                                $options['values'] = array();

                                while ($i <= $h) {
                                        $options['values'][] = array('value'=>$i,'img'=>$va=='img'?'n'.$i.'.png':'','text'=>$i);
                                        $i += $s;
                                }
                                break;
                              
                        case 'creditcards':
                                $options['values'] = array(
                                        array('img'=>'visa.png', 'value'=>1, 'text'=>'Visa'),
                                        array('img'=>'mastercard.png', 'value'=>2, 'text'=>'Mastercard')
                                );
                                break;
                        default:
                                break;
                }
                
                if (!isset($options['values'])) return false;
                
                $values = $options['values'];
                
                foreach ($values as $value) {
                        if ($value[$what] == $lookFor) return $value;
                }
                
                return false;
        }
        
        /**
         * 1. call maintainer method for each extendedtype
         * 2. remove orphan entries (no item available in item table)
         *
         * Note:
         * 1. item need to be removed from (including trash)
         * 2. This is achieved through the k2fields system plugin invoking this
         *    method when the admin view items is detected for k2
         */
        public function maintainExtended() {
                foreach (self::$extendedTypes as $type) {
                        $cls = $this->loadType($type);
                        
                        if ($cls === false) continue;
                        
                        $mtd = 'maintain';
                        
                        if (method_exists($cls, $mtd)) call_user_func(array($cls, $mtd));
                }
                
                $query = 'DELETE FROM #__k2_extra_fields_values WHERE itemid NOT IN (SELECT id FROM #__k2_items)';
                $this->_db->setQuery($query);
                
                if (!$this->_db->query()) {
                        return JError::raiseError(
                                'ERROR_CODE', 
                                JText::_('Maintenance failed')
                        );
                }
        }
        
        public function retrieveList($id) {
                if (empty($id)) return '';
                
                $list = new K2FieldsList();
                $result = $list->processRequest('field', $id);
                
                return $result;
        }
        
        public function autocomplete($id, $value, $type, $pos, $method, $isSearch, $isReverse) {
                if (empty($id) || empty($value)) return '';
                
                $db = $this->_db;
                
                if (!$isReverse) {
                        if ($method == 'm' || $method == 's') $value = $value.'%';
                        if ($method == 'm' || $method == 'e') $value = '%'.$value;
                        
                        $value = $db->Quote($value);
                }
                
                $completions = null;
                
                if ($isSearch) {
                        $field = $this->getFieldsById($id);
                        $type = self::value($field, 'valid', $type);
                        
                        if ($type == 'list') {
                                $list = new K2FieldsList();
                                $listId = self::value($field, 'source');
                                
                                if ($isReverse) {
                                        $completions = $list->getValue($value);
                                        $completions->ovalue = $value;
                                        $completions = array($completions);
                                } else {
                                        $query = $list->completePath($value, $listId, true, true);
                                }
                        } else if ($type == 'k2item') {
                                $query = $isReverse ? 
                                        K2fieldsK2Item::reverseComplete($value) : 
                                        K2fieldsK2Item::completeItems($field, $value, $pos);
                                
                                if (is_array($query)) $completions = $query;
                        } else {
                                $typeQuery = '';
                                $typeCols = '';

                                if ($type == 'map') {
                                        $typeQuery = '#__k2_extra_fields_values vv ON v.itemid = vv.itemid AND v.fieldid = vv.fieldid AND v.listindex = vv.listindex AND vv.partindex = 0 INNER JOIN';
                                        $typeCols = ', vv.value AS ovalue';
                                }

                                $query = "
                                        SELECT 
                                                DISTINCT IF(v.txt IS NULL OR TRIM(v.txt) = '', v.value, v.txt) AS value {$typeCols}
                                        FROM 
                                                #__k2_extra_fields_values v INNER JOIN 
                                                {$typeQuery}
                                                #__k2_items i ON v.itemid = i.id AND i.published = 1 ".(!empty($pos) ? " AND v.partindex = ".$pos : "")." INNER JOIN 
                                                #__k2_extra_fields f ON v.fieldid = f.id AND f.published = 1 LEFT JOIN 
                                                #__k2_categories AS c ON c.id = i.catid AND c.published = 1 
                                        WHERE 
                                                v.fieldid = {$id} AND (v.value LIKE {$value} OR v.txt LIKE {$value})
                                        ";
                        }
                } else {
                        $query = "SELECT DISTINCT v.val AS val FROM #__k2_extra_fields_list_values v INNER JOIN #__k2_extra_fields f ON v.fieldid = f.id AND f.published = 1 WHERE v.fieldid = {$id} AND v.value LIKE {$value}"; 
                }
                
                if (empty($completions)) {
                        $db->setQuery($query);
                        $completions = $db->loadObjectList();
                }
                
                if ($type == 'k2item' && $isReverse && !empty($completions)) $completions = $completions[0];
                
                return $completions;
        }
        
        public function getField($field) {
                if (is_object($field)) return $field;
                
                if (!is_numeric($field)) {
                        if (preg_match("/^".self::pre()."_(\d+)$/", $field, $m)) {
                                $field = $m[1];
                        } else {
                                $field = false;
                        }
                }
                
                if ($field === false) return false;

                $fields = $this->getFields();
                
                return $fields[$field];
        }
        
        public function isK2Field($field, $isName = false) {
                if (!$isName) {
                        $field = $this->getField($field);
                        
                        if ($field === false) return false;
                        
                        $name = $field->definition;
                } else {
                        $name = $field;
                }

                return $this->isK2FieldDefinition($name);
        }
        
        public function isK2FieldDefinition($str) {
                return JprovenUtility::isPrefixed($str, "k2f" . self::FIELD_SEPARATOR);
        }
        
        public function isK2NativeList($field) {
                return
                        in_array(
                                strtolower(is_object($field) ? $field->type : $field['type']),
                                self::$editableK2Types
                        );
        }

        public function isEditableList($field) {
                return $this->isK2NativeList($field) && self::value($field, 'editable');
        }
        
        public static function setting($name, $options = null, $default = '', $assertedKeys = null, $sep = '::', $allKey = 'all', $alternativeName = '') {
                return JprovenUtility::setting($name, 'k2fields', 'k2', $options, $default, $assertedKeys, $sep, $allKey, $alternativeName);
        }        
        
        public static function categorySetting($catId, $name, $sep = K2FieldsModelFields::VALUE_SEPARATOR, $allKey = 'all') {
                require_once JPATH_SITE.'/components/com_k2fields/helpers/utility.php';
                $path = JprovenUtility::getK2CategoryPath($catId);
                return self::setting($name, null, array(), $path, $sep, $allKey);
        }
        
        public static function isFieldTypePresent($fieldType, $catId) {
                if (($options = self::isContainsType($fieldType, $catId, 'view')) !== false) {
                        $filterView = JFactory::getApplication()->input->get('view');
                        $fields = array($options);
                        self::filterBasedOnView($fields, $filterView);
                        return !empty($fields);
                }
                
                return false;
        }
        
        public static function isAutoField($field) {
                $valid = self::value($field, 'valid');
                return in_array($valid, self::$autoFieldTypes);
        }
        
        public static function isAbsoluteField($field) {
                return self::isTrue($field, 'absolute');
        }
        
        protected static function addAutoFieldRendered($item, $field) {
                if (!isset(self::$autoFieldTypesRendered[$item->id])) self::$autoFieldTypesRendered[$item->id] = array();
                self::$autoFieldTypesRendered[$item->id][] = self::value($field, 'valid');
        }
        
        public static function isAutoFieldRendered($item, $valid) {
                if (!isset(self::$autoFieldTypesRendered[$item->id])) return false;
                return in_array($valid, self::$autoFieldTypesRendered[$item->id]);
        }
        
        public static function isFormField($field) {
                $attrs = array('reverse');
                
                foreach ($attrs as $attr) {
                        if (self::value($field, $attr)) {
                                return false;
                        }
                }
                                
                return true;
        }
        
        public static function value($options, $name, $default = '', $view = '') {
                if (!empty($view)) $name = $view . $name;
                return JprovenUtility::value($options, $name, $default);
        }
        
        public static function setValue(&$options, $name, $value) {
                return JprovenUtility::setValue($options, $name, $value);
        }
        
        public static function isFalse($options, $name, $includeImplicit = true, $view = '') {
                $vals = array('false', '0');
                $def = 'K2FieldsModelFields::isFalse::plch';
                
                if ($includeImplicit) $vals[] = $def;
                
                $val = self::value($options, $name, $def, $view);
                
                return in_array($val, $vals);
        }
        
        public static function isTrue($options, $name, $view = '') {
                return self::isValue($options, $name, array('true', '1'), $view);
        }
        
        public static function isValue($options, $name, $assertedValues, $view = '') {
                return in_array(self::value($options, $name, $view), (array) $assertedValues);
        }
        
        public static function isDatetimeType($options) {
                return self::isType(
                        $options, 
                        array('datetime', 'time', 'date', 'datetimerange', 'daterange', 'duration', 'days')
                );
        }
        
        public static function createDuration($duration) {
                $duration = explode(':', $duration);
                return $duration[0] * 3600 + $duration[1] * 60 + (count($duration) > 2 ? $duration[2] : 0);
        }
        
        public static function isType($options, $assertedType) {
                return self::isValue($options, 'valid', $assertedType);
        }   
        
        public static function isAlias($options) {
                $aliasFieldId = self::value($options, 'alias');
                return !empty($aliasFieldId);
        }
                
        public static function isContainsType($assertedType, $catId = null, $mode = null) {
                if (!empty($catId)) {
                        $model = new K2FieldsModelFields();
                        $fieldsOptions = $model->getFieldsByGroup($catId, $mode);
                } else return false;
                
                foreach ($fieldsOptions as $options) {
                        if (self::isType($options, $assertedType)) {
                                return $options;
                                break;
                        }                        
                }
                
                return false;
        }
        
        public static function isAccessible($options) {
                $isRestricted = self::value($options, 'restricted', false);

                if ($isRestricted) {
                        $user = JFactory::getUser();
                        return !$user->guest;
                } else {
                        return true;
                }
        }    
        
        public function getFieldsBasedOnRequest() {
                $type = JRequest::getWord('type');
                $item = JRequest::getInt('id', 0);
                $cat = JRequest::getInt('cid', JRequest::getInt('catid'));
                
                if ($type == 'searchfields') {
                        if (empty($cat) && empty($item)) {
                                $moduleId = JRequest::getInt('module');
                                $fields = $this->getFieldsByModule($moduleId, 'search');
                                
                                if (empty($fields)) $fields = array();
                        } else {
                                if (!empty($cat)) {
                                        $fields = $this->getFieldsByGroup($cat, 'search');
                                } else {
                                        $fields = $this->getFieldsByItem($item, 'search');
                                }
                        }
                } else {
                        if ($cat) {
                                $fields = $this->getFieldsByGroup($cat, 'edit');
                        } else if ($item) {
                                $fields = $this->getFieldsByItem($item, 'edit');                                
                        }
                }
                
                return $fields;
        }
        
        public function getFieldsByModule($module, $modeFilter = null) {
                $module = JprovenUtility::getModule($module);
                $fields = $module->params->get('defaultfields');
                
                if (!is_array($fields)) 
                        $fields = array($fields);
                
                return $this->getFields($fields, 'id', $modeFilter, true);
	}        
        
        public function getFieldsByGroup($group, $modeFilter = null) {
                return $this->getFields($group, 'group', $modeFilter, true);
	}
        
        public function getFieldsById($fieldId, $modeFilter = null, $preserveOrder = false) {
                return $this->getFields($fieldId, 'id', $modeFilter, false, $preserveOrder);
        }
        
        public function getFieldsByItem($itemId, $modeFilter = null) {
                return $this->getFields($itemId, 'item', $modeFilter, true);
        }
        
        public function getFields($value = null, $mode = 'group', $modeFilter = null, $objectify = false, $preserveOrder = false, $onlyDefinitions = false) {
//                $cache = JFactory::getCache('com_k2fields');
//                $result = $cache->call(array($this, '_getFields'), $value, $mode, $modeFilter, $objectify, $preserveOrder); 
                
                $result = $this->_getFields($value, $mode, $modeFilter, $objectify, $preserveOrder);
                return $result;
        }
        
        function _getFields($value = null, $mode = 'group', $modeFilter = null, $objectify = false, $preserveOrder = false, $onlyDefinitions = false) {
                if (empty($value) || $mode != 'id' && $mode != 'group' && $mode != 'item') return array();
                
                $query = "
                        SELECT 
                                ef.`id`, 
                                CASE WHEN LOCATE('k2f---', ef.`name`) = 1 AND efd.`definition` IS NOT NULL THEN efd.`definition` ELSE ef.`name` END AS `name`,
                                ef.`value`, 
                                efd.`definition`, 
                                ef.`type`, 
                                ef.`group`, 
                                ef.`published`, 
                                ef.`ordering`,
                                GROUP_CONCAT(c.id SEPARATOR ',') AS cats
                        FROM 
                                #__k2_extra_fields AS ef LEFT JOIN 
                                #__k2_extra_fields_definition AS efd 
                                ON ef.`id` = efd.`id` LEFT JOIN
                                #__k2_categories AS c ON c.extraFieldsGroup = ef.`group`
                        WHERE 
                                ef.`published` = 1
                        ";
                
                $newMode = '';
                
                if ($mode == 'item') {
                        $value = JprovenUtility::toIntArray($value);
                        $value = implode(',', $value);
                        $value = 'SELECT catid FROM #__k2_items WHERE id IN ('.$value.')';
                        $newMode = 'group';
                }
                
                $col = $mode;
                
                if ($mode == 'group' || $newMode == 'group') {
                        if ($mode == 'group') {
                                $value = JprovenUtility::toIntArray($value);
                                $value = implode(',', $value);
                        }
                        
                        $colVal = "SELECT extraFieldsGroup FROM #__k2_categories WHERE id IN (" . $value . ")";
                        $col = 'group';
                } else {
                        $colVal = JprovenUtility::toIntArray($value);
                        $colVal = implode(',', $colVal);
                }

                if (isset($col) && isset($colVal)) 
                        $query .= " AND ef.`$col` IN ($colVal)";
                
                $query .= " GROUP BY ef.`id`";
                
                if (!$preserveOrder) $query .= " ORDER BY ef.`ordering`";
                
                $this->_db->setQuery($query);
                
                $fields = $this->_db->loadObjectList('id');
                
                if ($onlyDefinitions) return $fields;
                
                $fields = $this->mapFieldsOptions($fields, $modeFilter == 'search');
                $fields = $this->__getFields($fields, $mode, $value, $modeFilter, $objectify, $preserveOrder);
                
                return $fields;
        }
        
        function reinstateReadOnlyFields() {
                $task = JRequest::getCmd('task');
                $view = JRequest::getCmd('view');
                $option = JRequest::getCmd('option');
                $id = JRequest::getInt('id');
                
                if ($option == 'com_k2' && $id && $task == 'save' && $view == 'item') {
                        $user = JFactory::getUser();
                        $row = JTable::getInstance('K2Item', 'Table');
                        $row->load($id);
                        $fieldsValues = json_decode($row->extra_fields);
                        $fieldsValues = JprovenUtility::indexBy($fieldsValues, 'id');
                        $fields = $this->getFieldsByItem($id, 'read');
                        $found = array();
                        foreach ($fields as $i => $field) {
                                $access = self::value($field, 'access');
                                
                                if ($access && isset($access['edit'])) {
                                        $allowed = in_array($access['edit'], $user->getAuthorisedViewLevels());
                                        
                                        if (!$allowed) continue;
                                                                                
                                        $id = self::value($field, 'id');
                                        $found['K2ExtraField_'.$id] = $fieldsValues[$id][0]->value;
                                        JRequest::setVar('K2ExtraField_'.$id, $fieldsValues[$id][0]->value);
                                }                        
                        }
                }
        }
        
        private function __getFields($fields, $mode, $value, $modeFilter, $objectify = false, $preserveOrder = false) {
                if (isset($modeFilter) && $modeFilter != 'edit') {
                        if (is_string($modeFilter) && $modeFilter == 'view') {
                                $modeFilter = array('view' => JRequest::getCmd('view', ''));
                        }
                        
                        $accessMode = 'read';
                } else if (!isset($modeFilter)) {
                        $app = JFactory::getApplication();
                        $task = JRequest::getCmd('task');
                        $type = JRequest::getCmd('type');
                        
                        if ($app->isAdmin() || $task == 'edit' || $task == 'retrieve' && $type == 'fields') {
                                $accessMode = 'edit';
                        } else {
                                $accessMode = 'read';
                        }
                } else {
                        $accessMode = $modeFilter;
                }
                
                $user = JFactory::getUser();
                
                foreach ($fields as $i => &$field) {
                        if (!empty($modeFilter) && !empty($field)) {
                                $field['filters'] = $this->filterFieldOptions($field, $modeFilter);
                                
                                if ($modeFilter == 'search') {
                                        if ($field['filters'] === false) {
                                                unset($fields[$i]);
                                        } else if (is_array($field['filters'])) {
                                                foreach ($field['filters'] as $j => $f) {
                                                        if ($f === false || isset($f[$modeFilter]) && $f[$modeFilter] === false) {
                                                                unset($field['subfields'][$j]);
                                                        }
                                                }
                                        }
                                }
                                
                                if (isset($field['subfields'])) {
                                        $sfs = &$field['subfields'];
                                        
                                        foreach ($sfs as &$sf) {
                                                $sf['filters'] = $this->filterFieldOptions($sf, $modeFilter);
                                        }
                                }
                        }
                        
                        $access = self::value($field, 'access');
                        
                        if ($access && isset($access[$accessMode]) && !empty($access[$accessMode])) {
                                $notAllowed = !in_array($access[$accessMode], $user->getAuthorisedViewLevels());
                                
                                if ($notAllowed) unset($fields[$i]);
                        }
                }
                
                if ($mode == 'id' && is_numeric($value)) {
                        reset($fields);
                        $key = key($fields);
                        $fields = $fields[$key];
                }
                
                if ($objectify)
                        foreach ($fields as &$field) $field = JprovenUtility::toObject($field);
                
                if ($preserveOrder && is_array($fields) && $mode == 'id') {
                        $result = array();
                        foreach ($value as $val) {
                                if (isset($fields[$val]) && $fields[$val]) {
                                        $result[$val] = $fields[$val];
                                }
                        }
                        return $result;
                }
                
                return $fields;                
        }
        
        private function mapFieldsOptions($fields, $useFilter = false) {
                foreach ($fields as $id => &$field) {
                        $field->isK2Field = $this->isK2Field($field->definition, true);
                        $field->isEditableList = $this->isEditableList($field);
                        $field->isK2NativeList = $this->isK2NativeList($field);

                        if ($field->isK2NativeList) {
                                $field->value = json_decode($field->value);
                        }
                        
                        if (!$field->isK2Field) {
                                $field = get_object_vars($field);
                                continue;
                        }
                        
                        $field = $this->mapFieldOptions($field, $id, $useFilter);
                        
                        $field['isMedia'] = $field['valid'] == 'media';
                        $field['isMap'] = $field['valid'] == 'map';
                        $field['isList'] = $field['valid'] == 'list';
                }
                
                return $fields;
        }
        
        public function itemValues($itemId, $fieldIds = null, $filters = array()) {
                $fieldIds = (array) $fieldIds;
                
                $f = array('itemid = '.$itemId, !empty($fieldIds) ? 'fieldid IN ('.implode(',', $fieldIds).')' : '');
                
                foreach ($filters as $key => $filter) {
                        $op = is_array($filter) && count($filter) > 1 ? $filter[0] : ' = ';
                        $val = $this->_db->quote(is_array($filter) && count($filter) > 1 ? $filter[1] : $filter);
                        $key = $this->_db->nameQuote($key);
                        $f[] = $key.$op.$val;
                }
                
                $f = array_filter($f);
                $f = implode(' AND ', $f);
                
                $query = '
                        SELECT * FROM #__k2_extra_fields_values 
                        WHERE '.$f.'
                        ORDER BY itemid, fieldid, listindex, partindex, `index`
                        '
                        ;
                
                $this->_db->setQuery($query);
                
                $fieldsValues = $this->_db->loadObjectList();
                $fieldsValues = JprovenUtility::indexBy($fieldsValues, 'fieldid');
                
                return $fieldsValues;
        }
        
        public function renderK2fsearch($item, $itemRules, $itemText) {
                return 'renderK2fsearch';
                if (!is_object($item)) {
                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                        $tbl = JTable::getInstance('K2Item', 'Table');
                        $tbl->load($item);
                        $item = $tbl;
                }
                
                $itemId = (int) $item->id;
                $catId = (int) $item->catid;
                $searches = JprovenUtility::first($itemRules);
                $searches = JprovenUtility::first($searches);
                $as = JprovenUtility::value($searches, 'as', 'url');
                
                if ($as == 'link') $as = 'url';
                
                $label = '';
                
                if (isset($searches['label'])) {
                        $label = $searches['label'];
                        unset($searches['label']);
                }

                unset($searches['_plg_']);
                unset($searches['as']);
                unset($searches['item']);
                
                $_limit = $limit = -1;
                
                if (isset($searches['limit'])) {
                        $limit = $searches['limit'];
                        unset($searches['limit']);
                        $_limit = JRequest::getInt('limit', -1);
                        JRequest::setVar('limit', $limit);
                }
                
                K2Model::addIncludePath(JPATH_SITE.'/components/com_k2fields/models/');
                $sts = K2Model::getInstance('searchterms', 'K2FieldsModel', array('parse'=>false));
                
                $ui = $sts->createSearchRequest($searches, $as);
                $labels = $ui['labels'];
                $ui = $ui['ui'];
                
                if ($as == 'url') {
                        // TODO: substitute %fields% or %fieldX%
                        if (empty($label)) 
                                $label = JText::_('Suggested search');
                        
                        $labelUI = '<div class="searchinsert">'.$label.':<ul>';
                        
                        foreach ($labels as $lbl) {
                                $value = $lbl['value'];
                                
                                $labelUI .= '<li><span class="name">' . $lbl['name'] . '</span><span class="value">' . 
                                        (is_array($value) ? implode(',', $value) : $value) . 
                                        '</span></li>'
                                ;
                        }
                        
                        $labelUI .= '</ul></div>';
                        
                        if (!empty($ui)) {
                                $needle = array('item' => $itemId, 'itemlist' => $catId);
                                $itemId = '';
                                
                                if ($menuItem = K2FieldsHelperRoute::_findItem($needle)) {
                                        $itemId = $menuItem->id;
                                } else {
                                        $modules = JModuleHelper::_load();
                                        
                                        foreach ($modules as $module) {
                                                if ($module->module == 'mod_k2fields') {
                                                        $params = new JRegistry($module->params);
                                                        
                                                        if ($params->get('useitemid') == 'current') {
                                                                $itemId = JRequest::getInt('Itemid');
                                                        } else {
                                                                $itemId = $params->get('menuitemid');
                                                        }
                                                        
                                                        if (!empty($itemId)) break;
                                                }
                                        }
                                }
                                
                                if (!empty($itemId)) 
                                        $ui .= '&Itemid='.$itemId;
                                
                                $ui = JUri::root().'index.php?option=com_k2fields&view=itemlist&task=search&'.$ui;
                                $ui = '<a href="'.$ui.'" title="'.JprovenUtility::html($label).'">'.$labelUI.'</a>';
                        }
                } else if ($as == 'list') {
                        $module = 0;
                        
                        if (isset($searches['module'])) {
                                $module = $searches['module'];
                                unset($searches['module']);
                        }
                        
                        if (empty($module)) {
                                $module = self::setting('relateditemlistmodule');
                        }
                        
                        if (empty($module)) {
                                // TODO: provide a fall back simple itemized listing
                        }
                        
                        $module = JprovenUtility::getModule($module);
                        $params = $module->params;

                        $items = $sts->getData($ui, null, 'id');
                        $items = implode('|', $items);

                        $params->set('items', $items);

                        // Avoid loop of plugin parsing, inveitably plugin codes will be removed without proper processing
                        // TODO: any solution?
                        $params->set('K2Plugins', 0);
                        $params->set('JPlugins', 0);

                        $module->params = $params->toString();

                        jimport('joomla.application.module.helper');

                        $ui = JModuleHelper::renderModule($module);
                }

                if ($_limit != -1 && $limit != -1) {
                        JRequest::setVar('limit', $_limit);
                }
                
                $itemRules['all'][0]['rendered'] = $ui;
                
                return $itemRules;
        }
        
        public function generateDescription($item) {
                return $this->generateMeta($item, 'appendtodescription', ',');
        }
        
        public function generateKeywords($item) {
                return $this->generateMeta($item, 'appendtokeywords', ',');
        }
        
        public function generateTitle($item, $glue) {
                return $this->generateMeta($item, 'appendtotitle', $glue);
        }
        
        public function generateMeta($item, $property, $glue) {
                $fields = $this->getFieldsByItem($item->id);
                $mFields = array();
                
                foreach ($fields as $field) {
                        if (K2FieldsModelFields::isTrue($field, $property)) {
                                $mFields[] = $field;
                        }
                }
                
                if (!empty($mFields)) {
                        $meta = array();
                        $values = $this->itemValues($item->id, JprovenUtility::getColumn($mFields, 'id'));
                        
                        if ($values) {
                                foreach ($mFields as $field) {
                                        $_values = $values[K2FieldsModelFields::value($field, 'id')];
                                        $value = array();
                                        // TODO: k2item need to be rendered but has some difficulties related to K2ItemModel provided
                                        // being the backend one thus not having prepareItem and execplugin methods required
                                        foreach ($_values as $_value) {
                                                $v = !empty($_value->txt) ? $_value->txt : $_value->value;
                                                $value[] = $v;
                                        }
                                        //$value = $this->renderFieldValuesRaw($value, $field, null);
                                        //$values = implode(' - ', $values);
                                        $value = implode($glue, $value);
                                        $value = preg_replace("#\<span class=[\"\']lbl[\"\']>(.+)\<\/span\>#U", '', $value);
                                        $value = trim(html_entity_decode(htmlspecialchars_decode(strip_tags($value))));
                                        $meta[] = $value;
                                }
                                
                                return implode($glue, $meta);
                        }
                }
                
                return false;
        }
        
        /**
         * @@todo: if (!$item->published) remove all values for all rules 
         */
        public function renderK2f(&$item, $itemRules, $itemText, $itemObj, $additionalRules = array()) {
                $isItemObj = false;
                $isK2item = isset($itemRules['all'][0]['k2item']) && $itemRules['all'][0]['k2item'] == 'true';
                $view = JRequest::getCmd('view');
                $task = JRequest::getCmd('task');
                
                if (is_object($item)) {
                        $itemId = $item->id;
                        $item->k2item = $isK2item;
                        if ($isK2item) $item->k2cat = $itemRules['all'][0]['k2cat'];
                        $isItemObj = true;
                } else if (!empty($itemObj) && is_object($itemObj) && $itemObj->id == $item) {
                        $itemId = $item;
                        $item = $itemObj;
                        $item->k2item = $isK2item;
                        if ($isK2item) $item->k2cat = $itemRules['all'][0]['k2cat'];
                } else {
                        $itemId = $item;
                        $this->_db->setQuery('SELECT i.*, c.name AS cattitle, c.alias as catalias FROM #__k2_items i LEFT JOIN #__k2_categories c ON i.catid = c.id  WHERE i.id = '.(int)$itemId);
                        $item = $this->_db->loadObject();
                        $item->k2item = $isK2item;
                        if ($isK2item) $item->k2cat = $itemRules['all'][0]['k2cat'];
                        if (!class_exists('K2HelperUtilities')) {
                                JLoader::register('K2HelperUtilities', JPATH_SITE.'/components/com_k2/helpers/utilities.php');
                        }
                        K2Model::addIncludePath(JPATH_SITE . '/components/com_k2/models');
                        $itemModel = K2Model::getInstance('item', 'K2Model');
                        $item = $itemModel->prepareItem($item, $view, $task);
                        $item = $itemModel->execPlugins($item, $view, $task);
                }
                
                if (!$item) {
                        $itemRules['all'][0]['rendered'] = JText::_('Item missing.');
                        return $itemRules;
                }
                
                if (!isset($item->catalias) && $item->category instanceof TableK2Category) 
                        $item->catalias = $item->category->alias;
                
                $rules = $itemRules;
                
                $position = isset($itemRules['all']) ? $itemRules['all'] : false;
                
                if ($position) {
                        unset($itemRules['all']);
                        // only one position is accepted, the last one among specified
                        $position = array_pop($position);
                }
                
                // treat fields provided as comma separated list (ex. field1,field2,...)
                // by breaking it down to individual fields and merge to existing, if rule
                // for that field already exists or create new field rule
                foreach ($itemRules as $fieldId => $fieldRules) {
                        if (!is_numeric($fieldId)) {
                                $fieldIds = explode(',', $fieldId);
                                $rules[$fieldId]['fields'] = $fieldIds;
                                
                                foreach ($fieldIds as $id) {
                                        foreach ($fieldRules as $i => $r) $fieldRules[$i]['field'] = $id;
                                        
                                        $id = trim($id);
                                        
                                        if (isset($itemRules[$id])) {
                                                $itemRules[$id] = array_merge($itemRules[$id], $fieldRules);
                                        } else {
                                                $itemRules[$id] = $fieldRules;
                                        }
                                }
                                
                                unset($itemRules[$fieldId]);
                        }
                }
                
                // normalize excluded fields so that none of available rules for that field is affected
                foreach ($itemRules as $fieldId => &$fieldRules) {
                        $isExcluded = false;
                        
                        foreach ($fieldRules as $fieldRule)
                                if (isset($fieldRules['mode']) && $fieldRules['mode'] = 'exclude') {
                                        $isExcluded = true;
                                        break;
                                }
                        
                        if ($isExcluded)
                                foreach ($fieldRules as &$fieldRule)
                                        $fieldRules['mode'] = 'exclude';
                }
                
                // rule applicable to all other rules is created
                if ($allRules = ($position && (!isset($position['mode']) || $position['mode'] != 'exclude'))) {
                        $allRules = $position;
                        unset($allRules['mode']);
                        unset($allRules['_plg_']);
                        unset($allRules['ui']);
                }
                
                $fields = array_keys($itemRules);
                $option = JRequest::getCmd('option');
                $inModule = $item->params->get('parsedInModule') || 
                        isset($additionalRules['parsedInModule']) && $additionalRules['parsedInModule'];
                $inMap = $view == 'itemlist' && isset($item->isItemlistMap) && $item->isItemlistMap;
                
                if ($inModule) {
                        $filterView = 'module';
                        $modeFilter = array('view' => 'module');
                } else if ($inMap && !K2FieldsMap::showList()) {
                        $filterView = 'map';
                        $modeFilter = array('view' => 'map');
                } else {
                        $filterView = strpos($option, 'com_k2') !== false ? $view : null;
                        $modeFilter = $filterView ? 'view' : null;
                }
                
                if (isset($position['fields'])) {
                        $fields = explode(',', $position['fields']);
                        JprovenUtility::toInt($fields);
                        $fields = $this->getFieldsById($fields, $modeFilter, true);
                } else if ($allRules && isset($item)) {
                        $fields = $this->getFieldsByGroup($item->catid, $modeFilter);
                } else {
                        $fields = $allRules ? $this->getFieldsByItem($itemId, $modeFilter) : $this->getFieldsById($fields, $modeFilter);
                }
                
                if ($item->k2item && isset($position['fold']) && !(bool) $position['fold'] && isset($position['foldfields'])) {
                        $foldFields = explode(',', $position['foldfields']);
                        foreach ($foldFields as $field) {
                                if (isset($fields[$field])) 
                                        unset($fields[$field]);
                        }
                }
                
                self::filterBasedOnView($fields, $filterView);
//                $mapFields = JprovenUtility::getRow($fields, array('isMap'=>true), false);
//                $mapFields = JprovenUtility::indexBy($mapFields, 'id', 'all', null, true, true);
//                if (!empty($mapFields)) {
//                        $fields = array_merge($fields, $mapFields);
//                        $fields = JprovenUtility::indexBy($fields, 'id', 'all', null, true, true);
//                }
                
                $fieldIds = (array) JprovenUtility::getColumn($fields, 'id', true);
                
                if ($inMap) {
                        $mapFieldsToShow = $fields;
                        if (K2FieldsMap::showList()) self::filterBasedOnView($mapFieldsToShow, $filterView, 'map');
                        $mapFieldsToShow = JprovenUtility::getColumn($mapFieldsToShow, 'id', true);
                        
                }
//                $mapFieldIds = (array) JprovenUtility::getColumn($mapFields, 'id', true);
                
                // all rules is merged to all other non-excluding fields
                // newly fetched fields are placed with all rules
                if ($allRules) {
                        $allRules['_all_'] = true;
                        
                        for ($i = 0, $n = count($fieldIds); $i < $n; $i++) {
                                $fieldId = $fieldIds[$i];
                                
                                if (isset($itemRules[$fieldId])) { // is placed
                                        $fieldRules = &$itemRules[$fieldId];
                                        $exclude = isset($fieldRules[0]['mode']) && $fieldRules[0]['mode'] == 'exclude';
                                        
                                        if (!$exclude) {
                                                $tmp = $fieldRules[0];
                                                
                                                foreach ($allRules as $key => $allRule)
                                                        $tmp[$key] = $allRule;
                                                
                                                $fieldRules[] = $tmp;
                                        }
                                } else {
                                        $tmp = $allRules;
                                        $tmp['field'] = $fieldId;
                                        $itemRules[$fieldId] = array($tmp);
                                }
                        }
                }
                
                if ($inMap) {
                        $mapFields = JprovenUtility::getRow($fields, array('isMap'=>true), false);
                        $mapFields = JprovenUtility::indexBy($mapFields, 'id', 'all', null, true, true);
                        $mapFieldIds = (array) JprovenUtility::getColumn($mapFields, 'id', true);
                        $mapItemRules = JprovenUtility::removeValuesFromArray($itemRules, $mapFieldIds, false, true, true);
                }
                
                $fieldsValues = $this->itemValues($itemId, $fieldIds);
                $isTabular = isset($item->isItemlistTabular) && $item->isItemlistTabular;
                $schemaType = false;
                
                foreach ($itemRules as $fieldId => &$fieldRules) {
                        if (!isset($fields[$fieldId])) continue;
                        
                        $fld = $fields[$fieldId];
                        
                        if (self::isFormField($fld) && !self::isAutoField($fld) && !isset($fieldsValues[$fieldId])) {
                                // skip field because it is a form element and has no value
                                if ($isTabular) {
                                        if (!isset($position['rendered'])) $position['rendered'] = array();

                                        $fieldRule = $fieldRules[0];
                                        $fieldRule['rendered'] = $this->renderFieldValues(array(''), $fld, $fieldRule);
                                        $position['rendered'][] = $fieldRule;
                                }
                                
                                continue;
                        }
                        
                        if (self::isAlias($fld) && !self::isTrue($fld, 'render')) {
                                continue;
                        }
                        
                        if (isset($fieldsValues[$fieldId])) $fieldValues = JprovenUtility::chunkArray($fieldsValues[$fieldId], 'listindex');
                        else $fieldValues = array();
                        
                        if (self::isFormField($fld) && !self::isAutoField($fld) && empty($fieldValues)) continue;
                        
                        $section = self::value($fld, 'section', false);
                        
                        if ($filterView == 'itemlist' || $filterView == 'module') 
                                $section = self::value($fld, 'listsection', $section);
                        else 
                                $section = self::value($fld, 'itemsection', $section);
                        
                        $renderer = $this->getRenderer($fld);
                        
                        foreach ($fieldRules as $frCount => &$fieldRule) {
                                if (!isset($fieldRule['section'])) {
                                        $fieldRule['section'] = $section ? $section : self::setting('emptysectionname');
                                }
                                
                                if (!isset($fieldRule['alt'])) {
                                        if (isset($rule) && isset($rule['alt']))
                                                $fieldRule['alt'] = (int) $rule['alt'];
                                        else 
                                                $fieldRule['alt'] = 2;
                                }
                                
                                $rendered = '';

                                if ($this->isAggregateType($fld)) {
                                        $rendered = call_user_func($renderer, $item, $fieldValues, $fld, $this, $fieldRule);
                                } else {
                                        $renderedValues = array();
                                        
                                        if (self::isAutoField($fld)) {
                                                $renderedValues[] = call_user_func($renderer, $item, $fieldValues, $fld, $this, $fieldRule);
                                                self::addAutoFieldRendered($item, $fld);
                                        } else {
                                                for ($i = 0, $n = count($fieldValues); $i < $n; $i++) {
                                                        $fieldValue = $fieldValues[$i];

                                                        if (is_array($fieldValue)) {
                                                                $listIndex = $fieldValue[0]->listindex;
                                                        } else {
                                                                $listIndex = $fieldValue->listindex;
                                                        }

                                                        $renderedValues[] = call_user_func($renderer, $item, $fieldValue, $fld, $this, $fieldRule);
                                                }
                                        }
                                        
                                        $rendered = $this->renderFieldValues($renderedValues, $fld, $fieldRule, is_array($renderer) && $renderer[1] == 'renderGeneric');
                                }
                                
                                $fieldRule['rendered'] = $rendered;
                                
                                if (!$schemaType && ($schemaType = self::value($fld, 'schematype'))) {
                                        $item->schemaType = $schemaType;
                                }
                                
                                if (isset($fieldRule['_all_']) && $fieldRule['_all_']) {
                                        if (self::isAbsoluteField($fld)) {
                                                if (!isset($position['absolute'])) 
                                                        $position['absolute'] = array();

                                                $position['absolute'][] = $fieldRule;
                                        } else {
                                                if (!isset($position['rendered'])) 
                                                        $position['rendered'] = array();

                                                $position['rendered'][] = $fieldRule;
                                                
                                                if ($inMap && in_array($fieldId, $mapFieldsToShow)) {
                                                        if (!isset($position['rendered_map'])) 
                                                                $position['rendered_map'] = array();

                                                        $position['rendered_map'][] = $fieldRule;
                                                }
                                        }
                                        
                                        unset($fieldRules[$frCount]);
                                        
                                        continue;
                                }
                        }
                }
                
                $sectionsOrder = self::categorySetting($item->catid, 'sectionsorder');
                
                if (isset($position['rendered']) && !$isTabular && !empty($sectionsOrder)) {
                        $sectionsOrder = JprovenUtility::first($sectionsOrder);
                        $sectionsOrder = $sectionsOrder[0];
                        $rendered = array();
                        
                        foreach ($sectionsOrder as $ord) 
                                $rendered[$ord] = array();
                        
                        foreach ($sectionsOrder as $ord) {
                                foreach ($position['rendered'] as $i => $ren) {
                                        if ($ren['section'] == $ord) {
                                                $rendered[$ord][] = $ren;
                                                unset($position['rendered'][$i]);
                                        }
                                }
                        }
                        
                        foreach ($position['rendered'] as $i => $ren) {
                                if (!isset($rendered[$ren['section']])) {
                                        $rendered[$ren['section']] = array();
                                }
                                
                                $rendered[$ren['section']][] = $ren;
                        }
                        
                        $position['rendered'] = array();
                        
                        foreach ($rendered as $ren) 
                                $position['rendered'] = array_merge($position['rendered'], $ren);
                }
                
                foreach ($rules as $fieldId => &$_rules) {
                        if (isset($_rules['fields'])) {
                                $fields = $_rules['fields'];
                                $values = array();
                                
                                foreach ($_rules as &$rule) {
                                        foreach ($fields as $field) {
                                                foreach ($itemRules[$field] as $i => $itemRule) {
                                                        if ($itemRule['_plg_'] == $rule['_plg_']) {
                                                                $values[] = $itemRule;
                                                                unset($itemRules[$field][$i]);
                                                        }
                                                }
                                        }
                                }
                                
                                $_rules['rendered'] = $values;
                        }
                }
                
                $_plgSettings = array('merge'=>'', 'mergesection'=>'', 'sectiontitle'=>'');
                
//                $review = '';
//                if (false && !$isK2item && $item->params->get('itemComments') && JprovenUtility::checkPluginActive('jcomments', 'k2')) {
//                        $dispatcher = JDispatcher::getInstance();
//                        JPluginHelper::importPlugin ('k2');
//                        $limitstart = JRequest::getInt('limitstart', 0);
//                        $results = $dispatcher->trigger('onK2CommentsCounter', array ( & $item, &$params, $limitstart));
//                        $item->event->K2CommentsCounter = trim(implode("\n", $results));
//                        if ($view == 'item') {
//                                $results = $dispatcher->trigger('onK2CommentsBlock', array ( & $item, &$params, $limitstart));
//                                $item->event->K2CommentsBlock = '';
//                                $review = trim(implode("\n", $results));
//                                $item->params->set('itemComments', false);
//                        }
//                }

                foreach ($rules as $fieldId => &$_rules) {
                        $ui = '';
                        
                        if ($fieldId == 'all') {
                                if (!isset($position['rendered'])) {
                                        foreach ($_rules as $i => &$rule) $rule['rendered'] = '';
                                        continue;
                                }
                                
                                $values = $position['rendered'];
                                $absoluteRendered = '';
                                
                                if (isset($position['absolute'])) {
                                        foreach ($position['absolute'] as $absolute) 
                                                $absoluteRendered .= $absolute['rendered'];
                                        
                                        if (!empty($absoluteRendered)) {
                                                $absoluteRendered = '<div class="k2fabsolute">'.$absoluteRendered.'</div>';
                                        }
                                }
                                
                                $plgSettings = array_intersect_key($position, $_plgSettings);
                                
                                $sections = (array) JprovenUtility::getColumn($values, 'section');
                                $sections = array_unique($sections);
                                $sections = count($sections);
                                
                                if ($sections > 1) {
                                        if (isset($position['ui'])) $ui = $position['ui'];
                                        
                                        $qualifier = $filterView != 'item' ? $filterView : '';
                                        
                                        if (empty($ui)) {
                                                $ui = self::categorySetting($item->catid, 'catsui'.$qualifier);
                                                $ui = JprovenUtility::first($ui);
                                                $ui = $ui[0][0];
                                        }
                                        
                                        if (empty($ui)) $ui = self::setting('defaultui'.$qualifier, null, 'plain');
                                } else {
                                        $ui = 'plain';
                                }
                                
                                if (isset($item->isItemlistTabular) && $item->isItemlistTabular) $ui = 'k2ftable';
                                
                                if (count($fieldIds) > 1) {
                                        $rendered = call_user_func(
                                                array($this, 'renderUI'.ucfirst($ui)),
                                                $values, $fields, $item, $plgSettings
                                        );
                                } else {
                                        $rendered = $values[0]['rendered'];
                                }
                                
                                $title = '';
                                
                                if ($title = self::value($position, 'title')) {
                                        if ($title == 'true') $title = $item->title;
                                        
                                        $title = self::autoTitle(
                                                $item, 
                                                $title, 
                                                self::value($position, 'titlecollapse') == 'true' ? 'collapse' : 'link'
                                        );
                                }
                                
                                $schema = '';
                                
                                if ($schemaType) {
                                        $schemaType = ucfirst(strtolower($schemaType));
                                        $schema = ' itemscope itemtype="http://schema.org/'.$schemaType.'"';
                                }
                                
                                $rendered = 
                                        '<div class="k2f'.$filterView.'"'.$schema.'>'.
                                                $title.
                                                $rendered.
                                                $absoluteRendered.
                                        '</div>'
                                        ;
                                
                                if ($inMap && !empty($mapFields)) {
                                        if (count($mapFieldsToShow) > 1) {
                                                $renderedMap = call_user_func(
                                                        array($this, 'renderUI'.ucfirst($ui)),
                                                        $position['rendered_map'], $fields, $item, $plgSettings
                                                );
                                        } else {
                                                $renderedMap = $position['rendered_map'][0]['rendered'];
                                        }
                                        
                                        $item->rendered_map = 
                                                '<div class="k2f'.$filterView.'">'.
                                                        $title.
                                                        $renderedMap.
                                                '</div>'
                                                ;
                                        
                                        foreach ($mapItemRules as $fieldId => &$fieldRules) {
                                                $fld = $mapFields[$fieldId];

                                                foreach ($fieldRules as $frCount => &$fieldRule) {
                                                        $renderer = $this->getRenderer($fld);
                                                        if (isset($fieldsValues[$fieldId])) $fieldValues = JprovenUtility::chunkArray($fieldsValues[$fieldId], 'listindex');
                                                        else $fieldValues = array();                
                                                        call_user_func($renderer, $item, $fieldValues, $fld, $this, $fieldRule);
                                                }
                                        }
                                }
                                
                                foreach ($_rules as $i => &$rule) {
                                        if ($position['_plg_'] == $rule['_plg_']) {
                                                $rule['rendered'] = $rendered;
                                                
                                                if ($isItemObj) {
                                                        $fieldsRendered = JprovenUtility::indexBy($position['rendered'], 'field', 'all', null, true, true);
                                                        
                                                        foreach ($item->extra_fields as &$ef) {
                                                                $id = self::value($ef, 'id');
                                                                
                                                                if (!isset($fields[$id])) {
                                                                        self::setValue($ef, 'k2fields', false);
                                                                        continue;
                                                                }
                                                                
                                                                $name = self::value($fields[$id], 'name');
                                                                
                                                                self::setValue($ef, 'value', !isset($fieldsRendered[$id]) ? '' : $fieldsRendered[$id]['rendered']);
                                                                self::setValue($ef, 'name', $name);
                                                                self::setValue($ef, 'k2fields', true);
                                                        }
                                                }
                                        } else {
                                                $rule['rendered'] = '';
                                        }
                                }
                                
                                continue;
                        }
                        
                        if (isset($_rules['fields'])) {
                                $values = $_rules['rendered'];
                                $values = (array) JprovenUtility::getColumn($values, 'rendered');
                                $values = implode('', $values);
                                unset($_rules['fields']);
                                unset($_rules['rendered']);
                                reset($_rules);
                                $_rules[key($_rules)]['rendered'] = $values;
                        } else {
                                $values = $itemRules[$fieldId];
                                
                                foreach ($_rules as $i => &$rule) {
                                        $rule['rendered'] = isset($values[$i]['rendered']) ? $values[$i]['rendered'] : '';
                                }
                        }
                }
                
                return $rules;
        }
        
        public static function filterBasedOnView(&$fields, $filterView, $filterValue = null) {
                if (!$filterView) return;
                
                foreach ($fields as $f => &$field) {
                        if (self::isType($field, 'complex')) {
                                $filters = self::value($field, 'filters');
                                $subfields = self::value($field, 'subfields');

                                foreach ($filters as $i => $filter) {
//                                        if ($filterView == 'item') {
//                                                if (isset($filter['view']) && !isset($filter['view'][$filterView])) {
//                                                        unset($subfields[$i]);
//                                                        unset($filters[$i]);                                                        
//                                                }
//                                        } else {
                                                if (!isset($filter['view']) || !isset($filter['view'][$filterView])) {
                                                        unset($subfields[$i]);
                                                        unset($filters[$i]);
                                                }
                                                if (!empty($filterValues) && !in_array($filterValue, $filter['view'][$filterView])) {
                                                        unset($subfields[$i]);
                                                        unset($filters[$i]);
                                                }                                                
//                                        }                                        
                                }
                                
                                if (empty($subfields)) {
                                        unset($fields[$f]);
                                } else {
                                        self::setValue($field, 'subfields', $subfields);
                                        self::setValue($field, 'filters', $filters);
                                }
                        } else {
                                $filter = self::value($field, 'filters');

//                                if ($filterView == 'item') {
//                                        if (isset($filter['view']) && !isset($filter['view'][$filterView])) 
//                                                unset($fields[$f]);
//                                } else {
                                        if (!isset($filter['view']) || !isset($filter['view'][$filterView])) {
                                                unset($fields[$f]);
                                        }
                                        if (!empty($filterValue) && !in_array($filterValue, $filter['view'][$filterView])) {
                                                unset($fields[$f]);
                                        }
//                                }
                        }
                }
        }
        
        public function renderFieldValuesRaw($values, $field, $fieldRule) {
                $rendered = array();
                foreach ($values as $value) {
                        $rendered[] = $value->txt ? $value->txt : $value->value;
                }
                return implode(', ', $rendered);
        }
        
        public function renderFieldValues($values, $field, $fieldRule, $isFormatted = false) {
                if (empty($values)) return '';
                
                $valid = self::value($field, 'valid');
                $list = self::value($field, 'list') || is_array($values) && count($values) > 1 ? ' lst' : '';
                $view = JRequest::getCmd('view');
                $lbl = '';
                $id = self::value($field, 'id', '');
                $v = $id;
                $isPart = false;
                
                if (!$id || $id <= 0) {
                        $isPart = true;
                        $partIndex = self::value($field, 'partindex', -1);
                        $id = $partIndex != -1 ? 'fp fp'.$partIndex : '';
                } else {
                        $id = 'fv'.$id;
                }
                
                if ($view != 'item') $lbl = self::value($field, 'itemlistlabel', '');
                if (empty($lbl)) $lbl = self::value($field, 'label', '');
                if (empty($lbl) && isset($fieldRule['label'])) $lbl = $fieldRule['label'];

                $tips = self::value($field, 'contenttip');

                if (!$tips) $tips = self::value($field, 'tip');

                if (empty($lbl)) $lbl = self::value($field, 'name');

                if ($tips) {
                        JHTML::_('behavior.tooltip');

                        if (strpos($tips, '::') === false) {
                                $tips = $lbl.'::'.$this->_tipItemize($tips);
                        } else if (strpos($tips, '::') !== false) {
                                $tips = explode('::', $tips);
                                $tips[0] = $this->_tipItemize($tips[0]);
                                $tips[1] = $this->_tipItemize($tips[1]);
                                $tips = implode('::', $tips);
                        } else {
                                $tips = $this->_tipItemize($tips);
                        }

                        $tips = ' jptips" jptips="'.htmlspecialchars($tips, ENT_COMPAT);
                }
                
                if (!empty($lbl) && self::isTrue($field, ($view != 'item' ? '' : '') . 'showlabel')) {
                        $lbl = '<span class="lbl'.$tips.'">'.$lbl.'</span>';
                } else {
                        $lbl = '';
                }
                
                $replace = self::value($field, 'replace');
                
                if ($list != '') {
                        $rendered = array();
                        
                        if (!isset($fieldRule['alt'])) $fieldRule['alt'] = 2;
                        
                        $mod = $fieldRule['alt'];
                        $n = count($values);
                        
                        $collapsible = self::isTrue($field, 'collapsible');
                        $collapseLimit = self::value($field, 'collapselimit', $collapsible ? 3 : 0);
                        $excludeValues = (array) self::value($field, 'excludevalues', array());
                        $j = 0;
                        for ($i = 0; $i < $n; $i++) {
                                $isExclude = false;
                                if (!empty($excludeValues)) {
                                        foreach ($excludeValues as $excludeValue) {
                                                if (strpos($excludeValue, 'reg:') === 0) {
                                                        $excludeValue = str_replace('reg:', '', $excludeValue);
                                                        if (preg_match('#'.$excludeValue.'#i', $values[$i])) {
                                                                $isExclude = true;
                                                                break;
                                                        }
                                                } else if ($excludeValue == $values[$i]) {
                                                        $isExclude = true;
                                                        break;
                                                }
                                        }
                                        if ($isExclude) continue;
                                }
                                if ($collapsible && $j == $collapseLimit) {
                                        $rendered[] = '<a href="javascript:void(0)" class="jpcollapse">'.JText::_('Additional').'</a><ul class="k2flist lst qty'.($n - $collapseLimit).'">';
                                }
                                
                                $rendered[] = '<li class="alt'.(($j + 1) % $mod).' n'.($j + 1).'">'.$values[$i].'</li>';
                                $j++;
                        }
                        
                        $rendered = '<ul class="k2flist lst qty'.$collapseLimit.'">'.implode('', $rendered).'</ul>';
                } else {
                        $rendered = '<span>'.implode('', $values).'</span>';
                        
                }
                
                $schemaProp = self::value($field, 'schemaprop');
                if ($schemaProp) $schemaProp = ' itemprop="'.$schemaProp.'"';
                
                $ui = '<div class="'.$valid.'"'.$schemaProp.'>';
                
                $ui .= ($isPart ? '<div class="'.$id.'">' : '<div class="fvc '.$id.'">');
                
                if (empty($lbl)) $ui .= '<div class="nolbl">';
                
                if (!$isFormatted) $rendered = self::formatValue($rendered, $fieldRule, $field);
                
                $ui .= $lbl . '<div class="fv">'.$rendered.'</div>';
                
                if (empty($lbl)) $ui .= '</div>';
                
                $ui .= '<div class="clr">&nbsp;</div></div></div>';
                
                return $ui;
        }
        
        function _tipItemize($str) {
                if (strpos($str, 'nlbrli')) {
                        $str = explode('nlbrli', $str);
                        $str = '<ul class="nlbrli"><li>'.implode('</li><li>', $str).'</li></ul>';
                }
                $str = '<span class="nlbr">'.str_replace('nlbr', '<br />', $str).'</span>';
                return $str;
        }
        
        private function renderUIList($values, $fields, $item, $plgSettings = array(), $headerElement = 'span') {
                $uis = $this->renderUIFoldValuesInSections($values, $plgSettings, true, '<li>', '</li>');
                $noSectionTitle = isset($plgSettings['sectiontitle']) && in_array($plgSettings['sectiontitle'], array('false', '0')) || false;
                
                foreach ($uis as $section => $_uis) {
                        $id = self::generateUISectionId($section);
                        //$ui .= '<ul class="sectioncontainer uilist '.$id.'"><span class="sec lbl">'.JText::_($section).'</span>'.$_uis.'</ul>';
                        
                        $ui .= '<ul class="sectioncontainer uilist '.$id.'">'.
                                ($noSectionTitle ? '' : '<'.$headerElement.' class="sectionheader">'.JText::_($section).'</'.$headerElement.'>').
                                $_uis.'</ul>';
                }
                
                return $ui;
        }
        
        private function renderUIPlain($values, $fields, $item, $plgSettings = array(), $headerElement = 'span') {
                $uis = $this->renderUIFoldValuesInSections($values, $plgSettings, true);
                $ui = '';
                $noSectionTitle = self::isFalse($plgSettings, 'sectiontitle', false);
                
                foreach ($uis as $section => $_uis) {
                        $id = self::generateUISectionId($section);
                        $ui .= '<div class="sectioncontainer uiplain '.$id.'">'.
                                ($noSectionTitle ? '' : '<'.$headerElement.' class="sectionheader">'.JText::_($section).'</'.$headerElement.'>').
                                '<div class="sectioncontent">'.$_uis.'</div></div>';
                }
                
                return $ui;
        }       
        
        public static function getRenderUICols($fields) {
                $fieldsByCols = array();
                
                foreach ($fields as $field) {
                        if (self::isType($field, 'complex')) {
                                $subfields = self::value($field, 'subfields');
                                foreach ($subfields as $subfield) {
                                        $col = self::value($subfield, 'col');
                                        if (is_numeric($col)) break;
                                }
                        } else {
                                $col = self::value($field, 'col');
                        }
                        
                        if (is_numeric($col)) {
                                if (!isset($fieldsByCols[$col])) $fieldsByCols[$col] = array();
                                $fieldsByCols[$col][] = $field;
                        } else {
                                $fieldsByCols['__empty__'] = $field;
                        }
                }
                
                if (isset($fieldsByCols['__empty__'])) unset($fieldsByCols['__empty__']);
                
                ksort($fieldsByCols);
                
                return $fieldsByCols;
        }
        
        private function renderUIK2ftable($values, $fields, $item) {
                static $isFirst = true;
                
                $fieldsByCols = self::getRenderUICols($fields);
                $valuesByField = JprovenUtility::indexBy($values, 'field');
                $ui = $uiFolded = '';
                $col = $colFolded = 0;
                
                foreach ($fieldsByCols as $fieldsByCol) {
                        $_ui = $_uiFolded = '';
                        
                        foreach ($fieldsByCol as $field) {
                                $id = self::value($field, 'id');
                                $isFolded = self::isTrue($field, 'folded');
                                if (isset($valuesByField[$id])) {
                                        $vals = $valuesByField[$id];

                                        if ($vals) {
                                                foreach ($vals as $val) {
                                                        if ($isFolded) {
                                                                $_uiFolded .= $val['rendered'];
                                                        } else {
                                                                $_ui .= $val['rendered'];
                                                        }
                                                }
                                                unset($valuesByField[$id]);
                                        }
                                }
                        }
                        
                        if (!empty($_ui)) {
                                $ui .= '<div class="fieldsValuesCell col col'.($col+1).'">' . $_ui . '</div>';
                                $col++;
                        }
                        
                        if (!empty($_uiFolded)) {
                                $uiFolded .= '<div class="fieldsValuesCell col col'.($col+1).'">' . $_uiFolded . '</div>';
                                $colFolded++;
                        }
                }

                $_uiFolded = K2fieldsK2Item::renderFolded($item);

                if (!empty($_uiFolded)) {
                        $uiFolded .= '<div class="col col'.($colFolded+1).'">'.$_uiFolded.'</div>';
                        $colFolded++;
                }
                
                foreach ($valuesByField as $fieldId => $vals) {
                        $isFolded = self::isTrue($fields[$fieldId], 'folded');
                        $isK2item = self::isType($fields[$fieldId], 'k2item');
                        
                        foreach ($vals as $val) {
                                if (!$isFolded || $isK2item) {
                                        $ui .= '<div class="col col'.($col+1).'">'.$val['rendered'].'</div>';
                                        $col++;
                                } else {
                                        $uiFolded .= '<div class="col col'.($colFolded+1).'">'.$val['rendered'].'</div>';
                                        $colFolded++;
                                }
                        }
                }
                
                $_ui = '<div class="cols cols'.$col.($colFolded > 0 ? ' jpcollapse' : '').'">'.$ui.'<div class="clr">&nbsp;</div></div>';
                $tmpl = JRequest::getCmd('tmpl');
                
                if ($isFirst && $tmpl != 'component') {
                        $ui = '<div class="itemListHeading">'.str_replace(' jpcollapse', '', $_ui).'</div>'.$_ui;
                        $isFirst = false;
                } else $ui = $_ui;
                
                if ($colFolded > 0) $ui .= '<div class="colsFolded colsFolded'.$colFolded.'">'.$uiFolded.'</div>';
                
                return $ui;
        }        
        
        private function renderUIHeaders($values, $fields, $item, $plgSettings = array()) {
                return $this->renderUIPlain($values, $fields, $item, 'h3', $plgSettings = array());
        }
        
        private function renderUIJqueryAccordion($values, $fields, $item, $plgSettings = array(), $type = 'tabs', $options = array()) {
                static $count = 0;
                $count++;
                
                $uis = $this->renderUIFoldValuesInSections($values, $plgSettings, false);
                $accId = 'k2f-'.$type.'-'.$count;
                $ui = '<div id='.$accId.' class="k2f-pane k2f-jquery-ui-accordion">';
                
                foreach ($uis as $section => $_uis) {
                        $ui .= '<h3><a href="#">'.JText::_($section).'</a></h3><div class="k2f-panel">'.implode('', $_uis).'</div>';
                }
                
                $ui .= '</div>';

                $params = K2HelperUtilities::getParams('com_k2');
                
                $document = JFactory::getDocument();
                
                $backendJQueryHandling = $params->get('backendJQueryHandling', 'remote');
                
		if ($backendJQueryHandling == 'remote') {
			$document->addScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');
		} else {
			$document->addScript(JURI::root(true).'/media/k2/assets/js/jquery-ui-1.8.16.custom.min.js');
		}
                
                $document->addScriptDeclaration('jQuery(document).ready(function(){ jQuery("#'.$accId.'" ).accordion(); });');
                
                return $ui;
        }
        
        private function renderUIJqueryTab($values, $fields, $item, $plgSettings = array(), $type = 'tabs', $options = array()) {
                static $count = 0;
                $count++;
                
                $uis = $this->renderUIFoldValuesInSections($values, $plgSettings, false);
                $handlers = array();
                $panels = array();
                foreach ($uis as $section => $_uis) {
                        $id = self::generateUISectionId($section);
                        $handlers[] = '<li><a href="#'.$id.'">'.JText::_($section).'</a></li>';
                        $panels[] = '<div id="'.$id.'" class="k2f-panel">'.implode('', $_uis).'</div>';
                }
                
                $tabId = 'k2f-'.$type.'-'.$count;
                $ui = 
                        '<div id='.$tabId.' class="k2f-pane k2f-jquery-ui-tab">'.
                        '<ul class="simpleTabsNavigation">'.implode('', $handlers).'</ul>'.
                        implode('', $panels).
                        '</div>'
                        ;

                $params = K2HelperUtilities::getParams('com_k2');
                
                $document = JFactory::getDocument();
                
                $backendJQueryHandling = $params->get('backendJQueryHandling', 'remote');
                
		if ($backendJQueryHandling == 'remote') {
			$document->addScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');
		} else {
			$document->addScript(JURI::root(true).'/media/k2/assets/js/jquery-ui-1.8.16.custom.min.js');
		}
                
                $document->addScriptDeclaration('jQuery(document).ready(function(){ jQuery("#'.$tabId.'" ).tabs(); });');
                
                return $ui;
        }
        
        private function renderUITab($values, $fields, $item, $plgSettings = array(), $type = 'tabs', $options = array()) {
                static $count = 0;
                $count++;
                
                jimport('joomla.html.pane');
                
                if ($type == 'sliders') $options = array('allowAllClose' => true, 'show' => -1);
                else $options = array();
                
                $uis = $this->renderUIFoldValuesInSections($values, $plgSettings, false);
                $pane = JPane::getInstance($type, $options);
                $ui = $pane->startPane('k2f-'.$type.'-'.$count);
                
                foreach ($uis as $section => $_uis) {
                        $id = self::generateUISectionId($section);
                        
                        $ui .= 
                                $pane->startPanel(JText::_($section), $id) .
                                implode('', $_uis) .
                                $pane->endPanel()
                        ;
                }
                
                $ui .= $pane->endPane();
                
                return $ui;
        }
        
        private function renderUIAccordion($values, $fields, $item, $plgSettings = array()) {
                return $this->renderUITab($values, $fields, $item, $plgSettings, 'sliders');
        }
        
        private function renderUIJkefeltab($values, $fields, $item, $plgSettings = array(), $type = 'tabs') {
                jimport('joomla.plugin.helper');
                
                if (!JPluginHelper::importPlugin('content', 'jkefel')) 
                        JError::raiseWarning('ERROR_CODE', JText::_('PLG_K2FIELDS_PLUGIN_JKEFEL_INACTIVE'));
                
                $uis = $this->renderUIFoldValuesInSections($values, $plgSettings);
                $ui = '';
                $n = count($uis);
                $i = 0;
                
                foreach ($uis as $section => $_uis) {
                        $id = self::generateUISectionId($section);
                        $section = empty($section) ? '' : ' title=['.JText::_($section).']';
                        
                        $ui .= 
                                '{jkefel '.$section.' kefelui=['.$type.'] id=['.$id.']}' .
                                implode('', $_uis) .
                                ($i == $n - 1 ? '{/jkefelend}' : '{/jkefel}')
                                ;
                        
                        $i++;
                }
                
                plgContentjkefel::jkefel($ui);
                
                return $ui;
        }
        
        private function renderUIJkefelaccordion($values, $fields, $item, $plgSettings = array()) {
                return $this->renderUIJkefeltab($values, $fields, $item, $plgSettings = array(), 'sliders');
        }
        
        private function renderUIFoldValuesInSections(
                $values,
                $plgSettings,
                $glue = false, 
                $itemGluePre = '', 
                $itemGluePost = '',
                $gluePre = '', 
                $gluePost = ''
        ) {
                $uis = array();
                
                foreach ($values as $value) {
                        if (isset($plgSettings['merge']) && in_array($plgSettings['merge'], array('true', '1'))) {
                                $section = isset($plgSettings['mergesection']) ? $plgSettings['mergesection'] : self::setting('emptysectionname');
                        } else {
                                $section = $value['section'];
                        }
                        
                        if (!isset($uis[$section])) 
                                $uis[$section] = $glue ? '' : array();
                        
                        if ($glue) 
                                $uis[$section] .= $itemGluePre . $value['rendered'] . $itemGluePost;
                        else 
                                $uis[$section][] = $value['rendered'];
                }
                
                if (!empty($gluePre) || !empty($gluePost)) 
                        foreach ($uis as &$ui) $ui = $gluePre . $ui . $gluePost;
                
                return $uis;
        }
        
        private static function generateUISectionId($sectionName, $postFix = false) {
                static $count = 0;
                
                if (empty($sectionName)) {
                        $postFix = true;
                        $count++;
                }
                
                return 
                        'sec'.preg_replace('#[^\w]#i', '', strtolower($sectionName)) .
                        ($postFix ? 'page'.$count : '')
                ;
        }
        
        private function isAggregateType($field) {
                $aggr = array('media', 'map', 'k2item', 'complex');
                return in_array(self::value($field, 'valid'), $aggr) || 
                        self::isTrue($field, 'combine') || 
                        self::isTrue($field, 'aggregate') || 
                        self::isTrue($field, 'repeatcombine')
                ;
        }
        
        protected static function formatValue($value, $rule, $field = null) {
                $isValueRow = is_object($value);
                
                if (is_string($value)) {
                        $val = $txt = $value;
                        $img = '';
                        $value = new stdClass();
                        $value->val = $val;
                } else {
                        $val = self::value($value, 'value');
                        $txt = self::value($value, 'txt', $val);
                        $img = self::value($value, 'img');
                }
                
                $_txt = $txt;
                $rendered = '';
                
                $pre = self::value($field, 'pre', '');
                
                if (!empty($pre)) {
                        $_txt = $pre . $_txt;
                        $rendered .= '<span class="pre">'.$pre.'</span>';
                }
                
                $post = self::value($field, 'post', '');
                
                if (!empty($post)) {
                        $_txt .= $post;
                }
                
                if (!empty($img)) {
                        // corresponding adjustment is made in jputility.js::loadImage
                        $img = JURI::root().JprovenUtility::loc().'icons/'.$img;
                        $alt = JprovenUtility::html($_txt);
                        $img = JHTML::_('image', $img, $alt, array('title'=>$alt));
                }
                
                $format = self::value($field, 'format', (isset($rule['format']) ? $rule['format'] : ''));
                
                if (empty($format)) {
                        if (!empty($img)) {
                                if ($isValueRow) {
                                        $txt = $img;
                                        $img = '';
                                } else {
                                        $txt .= $img;
                                }
                        }
                        
                        $format = '%text%';
                }
                
                $rendered .=  str_ireplace(
                        array('%value%', '%img%', '%text%'), 
                        array($val, $img, $txt), 
                        $format
                );
                
                if (!empty($post)) {
                        $rendered .= '<span class="post">'.$post.'</span>';
                }
                
                return $rendered;                
        }
        
        public function renderGeneric($item, $values, $field, $helper, $rule = null, $isRendered = false) {
                if (!isset($rule)) $rule = array();
                
                if (!isset($rule['label'])) $rule['label'] = 'true';
                
                $rule['label'] = (boolean) $rule['label'];
                
                if (!isset($rule['palt'])) $rule['palt'] = 2;
                
                $rendered = '';
                $val = array();
                $excludeValues = (array) self::value($field, 'excludevalues', array());
                $collapsible = self::isTrue($field, 'collapsible');
                $collapseLimit = self::value($field, 'collapselimit', $collapsible ? 3 : 0);
                
                foreach ($values as $j => $value) {
                        $v = self::value($value, 'value');
                        
                        if (!empty($v)) {
                                if ($value->partindex == -1) {
                                        $rendered .= '<span class="condition">' . $v . '</span>';
                                        continue;
                                }
                                
                                if (!empty($excludeValues)) {
                                        $isExclude = false;
                                        foreach ($excludeValues as $excludeValue) {
                                                if (strpos($excludeValue, 'reg:') === 0) {
                                                        $excludeValue = str_replace('reg:', '', $excludeValue);
                                                        if (preg_match('#'.$excludeValue.'#i', $v)) {
                                                                $isExclude = true;
                                                                break;
                                                        }
                                                } else if ($excludeValue == $v) {
                                                        $isExclude = true;
                                                        break;
                                                }
                                        }
                                        if ($isExclude) continue;
                                }
                                
//                                if ($collapsible && $j == $collapseLimit) {
//                                        $val[] = '<a href="javascript:void(0)" class="jpcollapse">'.JText::_('Additional').'</a><ul class="k2flist lst qty'.(count($values) - $collapseLimit).'">';
//                                }
//                                
                                $val[] = self::formatValue($value, $rule, $field);
                        }
                }
                
                $schemaProp = self::value($field, 'schemaprop');
                if ($schemaProp) $schemaProp = ' itemprop="'.$schemaProp.'"';
                
                if (!empty($val)) {
                        if (count($val) == 1) {
                                $val = '<span>'.$val[0].'</span>';
                        } else {
                                $sep = self::value($field, 'separator', '');
                                $val = '<ul class="k2flist"><li>'.implode($sep.'</li><li>', $val).'</li></ul>';
                        }

                        $isSubfield = self::value($field, 'subfieldof');
                        $isSubfield = !empty($isSubfield);
                        
                        if ($isSubfield) {
                                $partIndex = self::value($field, 'partindex', -1);
                                $cls = $partIndex != -1 ? 'fp fp'.$partIndex : '';
                                $valid = self::value($field, 'valid');
                                $rendered .= 
                                        '<div class="'.$valid.'"'.$schemaProp.'><div class="'.$cls.'"><span class="lbl">'.self::value($field, 'name').'</span>'.
                                        '<div class="fv">'.$val.'</div></div></div>'
                                        ;
                        } else {                           
                                $rendered .= $val;
                        }
                }
                
                return $rendered;
        }
        
        public function getRenderer($field) {
                $valid = self::value($field, 'valid');
                $mtd = empty($valid) ? 'renderGeneric' : 'render'.ucfirst($valid); 
                $cls = $this;

                if (!method_exists($cls, $mtd)) {
                        $cls = $this->loadType($valid);
                        $mtd = 'render';

                        if (!$cls || !method_exists($cls, $mtd)) {
                                $cls = $this;
                                $mtd = 'renderGeneric';
                        }
                }
                
                return array($cls, $mtd);
        }
        
        public function renderComplex($item, $values, $field, $helper, $rule = null) {
                $subfields = self::value($field, 'subfields');
                $noFields = count($subfields);
                $renderedValues = array();
                $listValues = array();
                
                foreach ($values as $listValue)
                        $listValues[] = JprovenUtility::indexBy($listValue, 'partindex');
                
                foreach ($listValues as $listIndex => $partsValues) {
                        $renderedValues[$listIndex] = '';
                        
                        foreach ($partsValues as $partIndex => $partValues) {
                                if (!isset($subfields[$partIndex])) continue;
                                
                                $fld = $subfields[$partIndex];
                                
                                self::setValue($fld, 'partindex', $partIndex);
                                
                                if ($partIndex == -1) {
                                        // TODO: handle condition properly
                                        continue;
                                }
                                
                                $renderer = $this->getRenderer($fld);
                                
                                if ($this->isAggregateType($fld)) {
                                        $renderedValues[$listIndex] .= call_user_func($renderer, $item, $partValues, $fld, $this, $rule);
                                } else {
                                        if (self::isAutoField($fld)) {
                                                $renderedValues[$listIndex] .= call_user_func($renderer, $item, $partValues, $fld, $this, $rule);
                                                self::addAutoFieldRendered($item, $fld);
                                        } else {
                                                $renderedValues[$listIndex] .= call_user_func($renderer, $item, $partValues, $fld, $this, $rule);
                                        }
                                }   
                        }
                }
                
                return $this->renderFieldValues($renderedValues, $field, $rule);
        }
        
        // TODO: add click counter and url shortner service
        public function renderUrl($item, $values, $field, $helper, $rule = null) {
                $maxLength = self::value($field, 'maxlength', 30);
                
                if (count($values) == 2 && $values[0]->partindex == -1) {
                        $title = $values[0]->value;
                        $url = $values[1]->value;
                } else {
                        $url = $values[0]->value;
                        $title = self::value($field, 'title', $url);
                }
                
                if ($maxLength > 0 && strlen($title) > $maxLength) {
                        $title = substr($title, 0, $maxLength) . '...';
                }
                
                $attrs = '';
                $val = self::value($field, 'target', false);
                
                if ($val) $attrs = 'target="'.$val.'" ';
                
                $val = self::value($field, 'target', false);
                if ($val) $attrs = 'class="'.$val.'" ';
                
                $title = JprovenUtility::html($title);
                
                $renderedValues = '<a '.$attrs.'href="'.$url.'" title="'.$title.'">'.$title.'</a>';
                
                return $renderedValues;
        }
        
        public function renderFacebook($item, $values, $field, $helper, $rule = null) {
                $facebookAppId = self::value($field, 'facebooksend', false);
                                
                $ui = '
                        <div id="fb-root"></div>
                        <script>(function(d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) return;
                        js = d.createElement(s); js.id = id;
                        js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId='.$facebookAppId.'";
                        fjs.parentNode.insertBefore(js, fjs);
                        }(document, "script", "facebook-jssdk"));</script>
                        <div class="fb-like" 
                        ';
                
                $facebookSend = self::value($field, 'facebooksend', false) ? 'true' : 'false';
                $facebookLayout = self::value($field, 'facebooklayout', 'standard');
                $facebookShowfaces = self::value($field, 'facebookshow_faces', false) ? 'true' : 'false';
                $facebookAction = self::value($field, 'facebookaction', 'Like');
                $facebookWidth = (int) self::value($field, 'facebookwidth', 450);

                if ($facebookLayout == 'standard') {
                        if ($facebookWidth < 265 && $facebookAction == 'recommend') {
                                $facebookWidth = $facebookSend == 'true' ? 325 : 265;
                        } else if ($facebookWidth < 285 && $facebookSend == 'true') {
                                $facebookWidth = 285;
                        } else if ($facebookWidth < 225) {
                                $facebookWidth = 225;
                        }
                } else if ($facebookLayout == 'button_count') {
                        if ($facebookWidth < 120 && $facebookAction == 'recommend') {
                                $facebookWidth = $facebookSend == 'true' ? 180 : 120;
                        } else if ($facebookWidth < 150 && $facebookSend == 'true') {
                                $facebookWidth = 150;
                        } else if ($facebookWidth < 90) {
                                $facebookWidth = 90;
                        }
                } else if ($facebookLayout == 'box_count') {
                        if ($facebookWidth < 85 && $facebookAction == 'recommend') {
                                $facebookWidth = $facebookSend == 'true' ? 145 : 85;
                        } else if ($facebookWidth < 145 && $facebookSend == 'true') {
                                $facebookWidth = 145;
                        } else if ($facebookWidth < 55) {
                                $facebookWidth = 55;
                        }
                }

                $facebookFont = self::value($field, 'facebookfont', false);
                $facebookColorscheme = self::value($field, 'facebookcolorscheme', false);

                $ui .= ' data-href="'.self::socLink($item).
                        '" data-send="'.$facebookSend.
                        '" data-layout="'.$facebookLayout.
                        '" data-show-faces="'.$facebookShowfaces.
                        '" data-width="'.$facebookWidth.
                        '" data-action="'.$facebookAction.'" '.
                        '" data-font="'.$facebookFont.'" '.
                        '" data-color-scheme="'.$facebookColorscheme.'"></div>'
                ;
                
                return $ui;
        }
        
        private static function socLink($item) {
                $url = JRoute::_($item->link);
                $url = urlencode($url);
                return $url;
        }
        
        public function renderTwitter($item, $values, $field, $helper, $rule = null) {
                $tweetText = self::value($field, 'twittertext', '');
                if (!empty($tweetText)) $tweetText = ' data-text="'.$tweetText.'"';

                $tweetCount = self::value($field, 'twittercounter', false) ? '' : ' data-count="none"';

                $tweetVia = self::value($field, 'twittervia', '');
                if (!empty($tweetVia)) $tweetVia = ' data-via="'.$tweetVia.'"';

                $tweetRelated = self::value($field, 'twitterrelated', '');
                if (!empty($tweetRelated)) $tweetRelated = ' data-related="'.$tweetRelated.'"';

                $tweetHash = self::value($field, 'twitterhash', '');
                if (!empty($tweetHash)) $tweetHash = ' data-hashtags="'.$tweetHash.'"';

                $tweetButton = self::value($field, 'twitterbutton', '');
                if (!empty($tweetButton)) $tweetButton = ' data-size="'.$tweetButton.'"';

                $ui = '
<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.self::socLink($item).'"'.$tweetText.$tweetCount.$tweetVia.$tweetRelated.$tweetHash.$tweetButton.'>Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>                                
';
                
                return $ui;
        }
        
        public function renderLinkedin($item, $values, $field, $helper, $rule = null) {
                $linkedinCounter = self::value($field, 'linkedincounter', 'top');
                
                $ui = '
<script src="http://platform.linkedin.com/in.js" type="text/javascript"></script>
<script type="IN/Share" data-url="'.self::socLink($item).'" data-counter="'.$linkedinCounter.'"></script>
';                
                return $ui;
        }
        
        public function renderPinterest($item, $values, $field, $helper, $rule = null) {
                static $pinterestJSLoaded = false;
                
                if (!$pinterestJSLoaded) {
                        $ui .= '
<script type="text/javascript">
(function() {
window.PinIt = window.PinIt || { loaded:false };
if (window.PinIt.loaded) return;
window.PinIt.loaded = true;
function async_load(){
var s = document.createElement("script");
s.type = "text/javascript";
s.async = true;
if (window.location.protocol == "https:")
s.src = "https://assets.pinterest.com/js/pinit.js";
else
s.src = "http://assets.pinterest.com/js/pinit.js";
var x = document.getElementsByTagName("script")[0];
x.parentNode.insertBefore(s, x);
}
if (window.attachEvent)
window.attachEvent("onload", async_load);
else
window.addEventListener("load", async_load, false);
})();
</script>                                                
';
                        $pinterestJSLoaded = true;
                }
                
                $pinterestCounter = self::value($field, 'pinterestcounter', 'top');

                $pinterestDescription = self::value($field, 'pinterestdescription', '');
                if ($pinterestDescription == 'text') {
                        $pinterestDescription = self::value($field, 'pinterestdescriptiontext', '');
                } else if ($pinterestDescription == 'item') {
                        $pinterestDescription = $item->title;
                } else if ($pinterestDescription == 'image') {
                        // TODO: which image?
                }
                $pinterestDescription = urlencode($pinterestDescription);

                // TODO: which image should be pinned, ie. media parameter in url below
                $pinterestImage = self::value($field, 'pinterestcounter', 'top');

                $ui .= '
<a href="http://pinterest.com/pin/create/button/?url='.self::socLink($item).'&media=urlofimage.com&description=Optionalpindescription" class="pin-it-button" count-layout="'.$pinCounter.'">Pin It</a>                                        
';                
                return $ui;
        }
        
        public function renderGoogleplus($item, $values, $field, $helper, $rule = null) {
                static $googleplusJSLoaded = false;
                
                if (!$googleplusJSLoaded) {
                        $ui = '
<script type="text/javascript">
(function() {
var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
po.src = "https://apis.google.com/js/plusone.js";
var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);
})();
</script>                                                
';
                        $googleplusJSLoaded = true;
                }

                $googleplusAnnotation = self::value($field, 'googleplusannotation', 'inline');

                $googleplusSize = self::value($field, 'googleplusbuttonsize', '');
                if (!empty($googleplusSize)) $googleplusSize = ' size="'.$googleplusSize.'"';

                $ui .= '<g:plusone '.$googleplusSize.' annotation="'.$googleplusAnnotation.'"></g:plusone>';
                
                return $ui;
        }
        
        public function renderFlattr($item, $values, $field, $helper, $rule = null) {
                if (JFactory::getApplication()->input->get('view', '') != 'item') return '';
                                
                $flattrTitle = self::value($field, 'flattrtitle', $item->title);
                $flattrDescription = self::value($field, 'flattrdescription', $item->metadesc);
                $flattrUID = self::value($field, 'flattruid', '');
                if (!empty($flattrUID)) $flattrUID = ';uid:'.$flattrUID;
                $flattrCategory = self::value($field, 'flattrcategory', '');
                if (!empty($flattrCategory)) $flattrCategory = ';category:'.$flattrCategory;
                $flattrTags = self::value($field, 'flattrtags', '');
                if (!empty($flattrTags)) $flattrTags = ';tags:'.$flattrTags;
                $flattrHidden = self::value($field, 'flattrhidden', '');
                if (!empty($flattrHidden)) $flattrHidden = ';hidden:1';
                $flattrButton = self::value($field, 'flattrbutton', '');
                if (!empty($flattrButton)) $flattrButton = ';button:compact';

                $ui = '
<a title="'.$flattrTitle.'" rel="flattr'.$flattrUID.$flattrCategory.$flattrTags.$flattrHidden.$flattrButton.';" href="'.self::socLink($item).'" class="FlattrButton" style="display:none;">'.$flattrDescription.'</a>
<script type="text/javascript">
/* <![CDATA[ */
(function() {
    var s = document.createElement("script");
    var t = document.getElementsByTagName("script")[0];

    s.type = "text/javascript";
    s.async = true;
    s.src = "http://api.flattr.com/js/0.6/load.js?mode=auto";

    t.parentNode.insertBefore(s, t);
 })();
/* ]]> */
</script>
';       
                
                return $ui;
        }
        
        public function renderReadability($item, $values, $field, $helper, $rule = null) {
                if (JFactory::getApplication()->input->get('view', '') != 'item') return '';

                $readabilityRead = self::value($field, 'readabilityread', '0');
                $readabilityPrint = self::value($field, 'readabilityprint', '0');
                $readabilityEmail = self::value($field, 'readabilityemail', '0');
                $readabilityKindle = self::value($field, 'readabilitykindle', '0');

                if (!$readabilityEmail && !$readabilityRead && !$readabilityKindle && !$readabilityPrint) return '';

                $readabilityOrientation = self::value($field, 'readabilityorientation', '0');
                $readabilityColorText = self::value($field, 'readabilitycolortext', '#5c5c5c');
                $readabilityColorBg = self::value($field, 'readabilitycolorbg', '#f3f3f3');

                $ui = '<div class="rdbWrapper" data-show-read="'.$readabilityRead.'" data-show-send-to-kindle="'.$readabilityKindle.'" data-show-print="'.$readabilityPrint.'" data-show-email="'.$readabilityEmail.'" data-orientation="'.$readabilityOrientation.'" data-version="1" data-text-color="'.$readabilityColorText.'" data-bg-color="'.$readabilityColorBg.'"></div>';
                $ui .= '<script type="text/javascript">(function() {var s = document.getElementsByTagName("script")[0],rdb = document.createElement("script"); rdb.type = "text/javascript"; rdb.async = true; rdb.src = document.location.protocol + "//www.readability.com/embed.js"; s.parentNode.insertBefore(rdb, s); })();</script>';
                
                return $ui;
        }
        
        public static function getEmailRecord($data = '') {
                static $results = array();
                
                if (!empty($data)) {
                        $data = is_array($data) ? $data['k2rec'] : $data;
                } else {
                        $data = JRequest::getString('k2rec');
                }
                
                if (isset($results[$data])) return $results[$data];
                
                $rec = base64_decode($data);
                $rec = explode('%%', $rec);
                $rec = array('item' => $rec[0], 'field'=>$rec[1], 'itemid'=>$rec[2], 'title'=>urldecode($rec[3]), 'rec'=>$data);
                
                if (count($rec) > 4) $rec['email'] = $rec[4];
                
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $rec['fieldoptions'] = $model->getFieldsById($rec['field']);
                
                $db = JFactory::getDBO();
                $db->setQuery('SELECT i.*, c.name AS cattitle, c.alias as catalias FROM #__k2_items i LEFT JOIN #__k2_categories c ON i.catid = c.id  WHERE i.id = '.(int)$rec['item']);
                $rec['itemoptions'] = $db->loadObject();
                
                $title = self::value($rec['fieldoptions'], 'formtitle');
                $title = str_replace(array('%title%', '%category%'), array($rec['itemoptions']->title, $rec['itemoptions']->cattitle), $title);
                $footer = self::value($rec['fieldoptions'], 'formfooter');
                $footer = str_replace(array('%title%', '%category%'), array($rec['itemoptions']->title, $rec['itemoptions']->cattitle), $footer);
                
                $rec['formtitle'] = $title;
                $rec['formfooter'] = $footer;
                
                if (!isset($rec['email'])) {
                        $rec['email'] = $model->itemValues($rec['item'], $rec['field']);
                        $rec['email'] = self::value($rec['email'][$rec['field']][0], 'value');
                }
                
                $results[$data] = $rec;
                
                return $rec;
        }
        
        public function renderForm($item, $values, $field, $helper, $rule = null) {
                $rendered = '';
                
                $menu = self::value($field, 'menu');
                
                if (empty($menu)) return JText::_('K2FIELDS_EMAILFIELD_FORM_MENU_MISSING');

                $menus = JSite::getMenu();
                $menuItem = $menus->getItem($menu);

                if (!$menuItem) return JText::_('K2FIELDS_EMAILFIELD_FORM_MENU_INCORRECT');

                $Itemid = JRequest::getInt('Itemid');

                /**
                 * NOTE: we make life easier by assuming that form authors 
                 * will subscribe to the convention of collecting the related 
                 * K2 item data and setting designated fields as author needs.
                 * 
                 * As such the following request variables are available:
                 * - K2 item id => k2id
                 * - K2 item title => k2title
                 * - K2 item link => k2link
                 */
                $email = self::value($field, 'id');
                $label = self::value($field, 'label', $menuItem->title);
                $label = str_replace(array('%title%', '%category%'), array($item->title, $item->category->name), $label);
                $title = self::value($field, 'title', $label);
                
                $title = str_replace(array('%title%', '%category%'), array($item->title, $item->category->name), $title);
                $rec = $item->id.'%%'.$email.'%%'.$Itemid.'%%'.urlencode($item->title);
                if (self::isType($field, 'form')) $rec .= '%%'.self::value($field, 'email');
                $rec = base64_encode($rec);

                if ($menuItem->query['option'] == 'com_fabrik') $menuItem->link .= '&iframe=1';

                JHTML::_('behavior.modal');

                $winSize = 'x: '.self::value($field, 'width', 550).', y: '.self::value($field, 'height', 550);
                $link = JRoute::_($menuItem->link.'&Itemid='.$menu.'&k2rec='.$rec.'&tmpl=component');

                if (strpos($label, 'recommend')) $css = 'recommend';
                else if (strpos($label, 'report')) $css = 'report';
                else $css = 'contact';

                $css = preg_replace('/[^\S]/i', '', $label);
                $css = strtolower($css);
                $rendered .= '<a class="modal emails '.$css.'" rel="{handler: \'iframe\', size: {'.$winSize.'}}" href="'.$link.'" title="'.$title.'"><span>'.$label.'</span></a>';
                
                return $rendered;
        }
        
        public function renderEmail($item, $values, $field, $helper, $rule = null) {
                $formats = self::value($field, 'emailformat', 'image');
                $formats = explode(',', $formats);
                $rendered = '';
                
                foreach ($formats as $format) {
                        $email = $values[0]->value;
                        
                        switch ($format) {
                                case 'form':
                                        $rendered .= $this->renderForm($item, $values, $field, $helper, $rule);
                                        break;
                                case 'link':
                                        $rendered .= '<a href="mailto:'.$email.'">'.JText::_('Click to mail').'</a>';
                                        break;
                                case 'image':
                                case 'img':
                                        $loc = $this->emailImageLocation($email);

                                        if ($loc['file'] !== $email) {
                                                if ($loc['file'] !== true) {
                                                        $fontSize = 10;
                                                        $font = JPATH_SITE."/media/k2fields/fonts/Existence-Light.ttf";

                                                        $oFont = self::value($field, 'id');

                                                        if ($oFont = JFolder::files(JPATH_SITE."/media/k2fields/fonts/", $oFont."\.[ttf|otf]", false, true)) {
                                                                $font = current($oFont);
                                                        }

                                                        $size = imagettfbbox($fontSize, 0, $font, $email);
                                                        $w = abs($size[2]) + abs($size[0]);
                                                        $h = abs($size[7]) + abs($size[1]);
                                                        
                                                        $im = imagecreate($w, $h);
                                                        $white = imagecolorallocate($im, 255, 255, 255);
                                                        $black = imagecolorallocate($im, 0, 0, 0);
                                                        imagestring($im, 2, 1, 0, $email, $black);
                                                        imagepng($im, $loc['file']);
                                                        imagedestroy($im);
                                                }

                                                $rendered .= '<a href="mailto:'.$email.'"><img src="'.$loc['url'].'" /></a>';
                                        } else {
                                                $rendered .= $email;
                                        }

                                        break;
                                case 'raw': default:
                                        $rendered .= $email;
                                        break;
                        }
                }
                
                return $rendered;
        }

        private function emailImageLocation($email) {
                jimport('joomla.filesystem.folder');
                jimport('joomla.filesystem.file');
                jimport('joomla.utilities.utility');

                $tmpFile = JPATH_CACHE . '/k2fields';

                $exists = true;

                if (!JFolder::exists($tmpFile)) {
                        $exists = false;

                        if (!JFolder::create($tmpFile)) {
                                return $email;
                        }
                }

                $tmpFile .= '/emails';

                if (!JFolder::exists($tmpFile)) {
                        $exists = false;

                        if (!JFolder::create($tmpFile)) {
                                return $email;
                        }
                }

                $tmpFile .= '/' . JFile::makeSafe(JUtility::getHash($email)) . '.png';

                $exists = JFile::exists($tmpFile);

                return array(
                    'file' => $exists ? true : $tmpFile,
                    'url' => JURI::root().str_replace(JPATH_CACHE.'/', 'cache/', $tmpFile)
                );
        }

        public function renderDuration($item, $values, $field, $helper, $rule = null) {
                $format = self::value($field, 'durationformat', '%s hours %s minutes');
                $n = 0;
                
                foreach ($values as &$val) {
                        $v = $val->value;
                        if (!$v) continue;
                        $sep = strpos($v, ':') !== false ? ':' : '.';
                        $v = explode($sep, $v);
                        if (count($v) == 1) $v[1] = '00';
                        $val->value = sprintf($format, $v[0], $v[1]);
                        $n++;
                }
                
                if ($n == 0) return '';
                
                $values = $this->renderGeneric($item, $values, $field, $helper, $rule);
                
                return $values;
                
//                $format = self::value($field, 'format', false);
//
//                if ($format === false) {
//                        $format = self::setting('timeFormat');
//                }   
//                
//                jdbg::pe($values);
//                
//                $config = JFactory::getConfig();
//                $tzoffset = $config->getValue('config.offset');
//                $d = JFactory::getDate('now', $tzoffset);
//                
//                jdbg::p($d);
//                
//                $d = strtotime(gmdate(self::setting('dateFormat'), time()));
//                jdbg::p($d);
//                
//                $d = date_create(date(self::setting('dateFormat')).' '.$values[0]->value);
//                jdbg::pe($d);
//                $d = $d->diff(date_create('today'));
//                $v = $d->format('%h hours %i minutes');
//                
//                return $v;
        }

        public function renderDatetime($item, $values, $field, $helper, $rule = null) {
                return $this->_renderDatetime($item, $values, $field, $helper, $rule, 'datetime');
        }

        public function renderDays($item, $values, $field, $helper, $rule = null) {
                static $map = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'All days', 'Weekend');
                $values = JprovenUtility::getColumn($values, 'txt', true);
                $values = (array) $values;
                $rendered = $this->renderFieldValues($values, $field, $rule);
                return $rendered;
        }

        public function renderDate($item, $values, $field, $helper, $rule = null) {
                return $this->_renderDatetime($item, $values, $field, $helper, $rule, 'date');
        }

        public function renderTime($item, $values, $field, $helper, $rule = null) {
                return $this->_renderDatetime($item, $values, $field, $helper, $rule, 'time');
        }
        
        // mode = collapse || link
        public static function autoTitle($item, $title = '', $mode = 'link') {
                $view = JRequest::getCmd('view');
                
                if ($view == 'itemlist') {
                        if ($item->params->get('catItemTitleLinked')) {
                                $title = K2FieldsHelperRoute::createItemLink($item, $title, $mode);
                        } else {
                                $title = $item->title;
                        }
                        
                        if ($item->params->get('catItemFeaturedNotice') && $item->featured) {
                                $title .= '<span><sup>'.JText::_('Featured').'</sup></span>';
                        }
                        
                        $title = '<h3 class="catItemTitle" itemprop="name">'.$title.'</h3>';
                } else {
                        $title = $item->title;
                        if ($item->params->get('itemFeaturedNotice') && $item->featured) {
                                $title .= '<span><sup>'.JText::_('Featured').'</sup></span>';
                        }
                        $title = '<h2 class="itemTitle" itemprop="name">'.$title.'</h2>';
                }
                
                return $title;
        }
        
        public static function autoRating($item, $displayLabel = false) {
                $rating = '<div class="catItemRatingBlock" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
                
                if ($item->nonk2rating) {
                        $rating .= '<span class="rating_starc"><div class="rating_star_user"><div style="width:' . $item->votingPercentage . '%;">&nbsp;</div></div><span class="ratingDetails"><span itemprop="ratingValue">'.($item->votingPercentage/20).'</span><span itemprop="reviewCount">'.$item->numOfvotes.'</span></span></span>';
                } else {
                        $rating .= '<div class="itemRatingForm">
                                        <ul class="itemRatingList">
                                                <li class="itemCurrentRating" id="itemCurrentRating' . $item->id . '" style="width:' . $item->votingPercentage . '%;"></li>
                                                <li><a href="#" rel="' . $item->id . '" title="' . JText::_('1 star out of 5') . '" class="one-star">1</a></li>
                                                <li><a href="#" rel="' . $item->id . '" title="' . JText::_('2 stars out of 5') . '" class="two-stars">2</a></li>
                                                <li><a href="#" rel="' . $item->id . '" title="' . JText::_('3 stars out of 5') . '" class="three-stars">3</a></li>
                                                <li><a href="#" rel="' . $item->id . '" title="' . JText::_('4 stars out of 5') . '" class="four-stars">4</a></li>
                                                <li><a href="#" rel="' . $item->id . '" title="' . JText::_('5 stars out of 5') . '" class="five-stars">5</a></li>
                                        </ul>
                                        <div id="itemRatingLog' . $item->id . '" class="itemRatingLog">' . $item->numOfvotes . '</div>
                                        <div class="clr"></div>
                                </div>
                                <div class="clr"></div>
                                ';
                }
                
                if ($displayLabel)
                        $rating .= '<span>'.$item->nonk2rating ? JText::_('Current rate') : JText::_('Rate this item').'</span>';
                
                $rating .= '</div>';
                
                return $rating;
        }
        
        public function renderTitle($item, $values = null, $field = null, $helper = null, $rule = null) {
                $pv = $item->params->get(JRequest::getCmd('view') == 'item' ? 'itemTitle' : 'catItemTitle');
                
                return $pv ? self::autoTitle($item) : '';
        }
        
        public function renderRate($item, $values = null, $field = null, $helper = null, $rule = null) {
                $pv = $item->params->get(JRequest::getCmd('view') == 'item' ? 'itemRating' : 'catItemRating');
                return $pv ? self::autoRating($item) : '';
        }
        
        private function _combineValues($values) {
                $result = array();
                foreach ($values as $value) $result = array_merge($result, $value);
                return $result;
        }
        
        private function _renderDatetime($item, $values, $field, $helper, $rule, $inType) {
                $format = self::setting(strtolower($inType).'Format');
                
                $aggr = $this->isAggregateType($field);
                
                if ($aggr) $values = $this->_combineValues($values);
                
                $val = JprovenUtility::createDate($values[0]->value);
                $rendered = $val->format($format);                        
                
                $repeat = self::value($field, 'repeat');
                
                if ($repeat) {
                        $repeatList = self::value($field, 'repeatlist', 'list');
                        $repeatFormat  = self::value($field, 'repeatformat', $format);
                        
                        if ($repeatList == 'words' || $repeatList == 'combined') {
                                $freq = (int) JprovenUtility::getColumn($values, 'value', true, array('partindex' => 2));
                                $unit = JprovenUtility::getColumn($values, 'value', true, array('partindex' => 3));

                                if ($repeat == 'enddate') {
                                        $max = count($values) - 5;
                                } else {
                                        $max = JprovenUtility::getColumn($values, 'value', true, array('partindex' => 4));
                                }
                                
                                $intervals = array('d'=>'day', 'w'=>'week', 'm'=>'month', 'y'=>'year');
                                $interval = JprovenUtility::nize($intervals[$unit], $freq);
                                $d = self::createDateInterval($freq * $max, $unit);
                                $val->add($d);
                                $val = $val->format($repeatFormat);
                                $rendered = JText::sprintf('From %s repeated every %s %s until %s', $rendered, $freq, $interval, $val);
                        } else {
                                $rendered = '';
                        }
                        
                        if ($repeatList == 'list' || $repeatList == 'combined') {
                                $listmax = self::value($field, 'repeatlistmax', 1);
                                $expire = self::isTrue($field, 'repeatexpire');
                                $repeatCombine = self::isTrue($field, 'repeatcombine');
                                $vals = JprovenUtility::getColumn($values, 'value', false, array('partindex' => 0));
                                $arr = array();
                                $renderedCnt = 0;
                                $now = JprovenUtility::createDate();
                                
                                if ($repeatCombine) sort($vals);
                                
                                foreach ($vals as $val) {
                                        $val = JprovenUtility::createDate($val);
                                        
                                        if ($expire && $val < $now) continue;
                                        
                                        $val = $val->format($repeatFormat);
                                        
                                        if ($renderedCnt == $listmax) {
                                                $arr[] = '<a href="javascript:void(0)" class="jpcollapse">'.JText::_('Additional').'</a><ul class="datetimelist">';
                                        }
                                        
                                        $arr[] = '<li>'.$val.'</li>';
                                        
                                        $renderedCnt++;
                                }
                                
                                $rendered .= '<ul class="k2flist">'.implode('', $arr).'</ul>';
                        }
                }
                
                if ($aggr) $rendered = $this->renderFieldValues(array($rendered), $field, $rule);
                
                return $rendered;
        }        
        
        public function renderField($extraField, $itemID=NULL, $type = null) {
		if (!is_null($itemID) && $itemID){
                        static $items = array();
                        if (!isset($items[$itemID])) {
                                K2Model::addTablePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables/');
                                $item = JTable::getInstance('K2Item', 'Table');
                                $item->load($itemID);
                                $items[$itemID] = $item;
                        } else $item = $items[$itemID];
		}
                
                $defaultValues = $extraField->value;
                if (is_string($defaultValues)) $defaultValues=json_decode($defaultValues);
                
                $active = '';

		foreach ($defaultValues as $value){
			if ($extraField->type=='textfield' || $extraField->type=='csv' || $extraField->type=='labels')
				$active=$value->value;
			else if ($extraField->type=='textarea'){
				$active[0]=$value->value;
				$active[1]=$value->editor;
			}
			else if($extraField->type=='link'){
				$active[0]=$value->name;
				$active[1]=$value->value;
				$active[2]=$value->target;
			}
			else
				$active='';
		}
                
		if (isset($item)){
			//$currentValues=json_decode($item->extra_fields);
                        $currentValues=json_decode($item->extra_fields);
                        
			if (count($currentValues)){
				foreach ($currentValues as $value){
					if ($value->id==$extraField->id){
						if($extraField->type=='textarea'){
							$active[0]=$value->value;
						}
						else
							$active=$value->value;
					}

				}
			}
		} else {
                        K2Model::addIncludePath('searchterms', JPATH_SITE . '/components/com_k2fields/models');
                        $st = K2Model::getInstance('searchterms', 'K2FieldsModel');
                        K2FieldsModelSearchterms::parseSearchTerms();
//                        $active = null;
                        $active = K2FieldsModelSearchterms::getFieldValue($extraField);
                }
                
                // value assignment based on k2fields type
                
                switch ($extraField->valid) {
                        case 'k2item':
                                if ($active) {
                                        $values = self::explodeValues($active, $extraField);
                                        $vals = (array) JprovenUtility::getColumn($values, 1);
                                        $query = 'SELECT id, title FROM #__k2_items WHERE id IN ('.implode(',', $vals).')';
                                        $this->_db->setQuery($query);
                                        $vals = $this->_db->loadObjectList('id');
                                        foreach ($values as &$value) {
                                                $value[2] = $vals[$value[1]]->title;
                                        }
                                        $active = self::implodeValues($values, $extraField);
                                }
                                break;
                        default:
                                if (!isset($item)) {
                                        if (is_array($active)) array_unshift ($active, '');
                                        else $active = array('', $active);
                                        $active = self::implodeValues(array($active), $extraField);
                                }
                                break;
                }
                
                $pre = self::pre($type);

		switch ($extraField->type){
			case 'textfield':
                        $output='<textarea name="'.$pre.$extraField->id.'" id="'.$pre.$extraField->id.'" rows="10" cols="40">'.$active.'</textarea>';
			//$output='<input type="text" name="'.$pre.$extraField->id.'" value="'.$active.'"/>';
			break;

			case 'labels':
			$output='<input type="text" name="'.$pre.$extraField->id.'" value="'.$active.'"/> '.JText::_('Comma separated values');
			break;			
			
			case 'textarea':
			if($active[1]){
				$output='<textarea name="'.$pre.$extraField->id.'" id="'.$pre.$extraField->id.'" rows="10" cols="40" class="k2ExtraFieldEditor">'.$active[0].'</textarea>';
			}
			else{
				$output='<textarea name="K2ExtraField_'.$extraField->id.'" rows="10" cols="40">'.$active[0].'</textarea>';
			}

			break;

			case 'select':
			$output=JHTML::_('select.genericlist', $defaultValues, $pre.$extraField->id, '', 'value', 'name',$active);
			break;

			case 'multipleSelect':
			$output=JHTML::_('select.genericlist', $defaultValues, $pre.$extraField->id.'[]', 'multiple="multiple"', 'value', 'name',$active);
			break;

			case 'radio':
			$output=JHTML::_('select.radiolist', $defaultValues, $pre.$extraField->id, '', 'value', 'name',$active);
			break;

			case 'link':
			$output='<label>'.JText::_('Text').'</label>';
			$output.='<input type="text" name="'.$pre.$extraField->id.'[]" value="'.$active[0].'"/>';
			$output.='<label>'.JText::_('URL').'</label>';
			$output.='<input type="text" name="'.$pre.$extraField->id.'[]" value="'.$active[1].'"/>';
			$output.='<label for="'.$pre.$extraField->id.'">'.JText::_('Open in').'</label>';
			$targetOptions[]=JHTML::_('select.option', 'same', JText::_('Same window'));
			$targetOptions[]=JHTML::_('select.option', 'new', JText::_('New window'));
			$targetOptions[]=JHTML::_('select.option', 'popup', JText::_('Classic javascript popup'));
			$targetOptions[]=JHTML::_('select.option', 'lightbox', JText::_('Lightbox popup'));
			$output.=JHTML::_('select.genericlist', $targetOptions, $pre.$extraField->id.'[]', '', 'value', 'text', $active[2]);
			break;

			case 'csv':
				$output = '<input type="file" name="'.$pre.$extraField->id.'[]"/>';

				if(is_array($active) && count($active)){
					$output.= '<input type="hidden" name="K2CSV_'.$extraField->id.'" value="'.htmlspecialchars($json->encode($active)).'"/>';
					$output.='<table class="csvTable">';
					foreach($active as $key=>$row){
						$output.='<tr>';
						foreach($row as $cell){
							$output.=($key>0)?'<td>'.$cell.'</td>':'<th>'.$cell.'</th>';
						}
						$output.='</tr>';
					}
					$output.='</table>';
					$output.='<label>'.JText::_('Delete CSV data').'</label>';
					$output.='<input type="checkbox" name="K2ResetCSV_'.$extraField->id.'"/>';
				}
			break;

		}
                
		return $output;
	}
        
        /**
         * creates easily processable associative array from provided option string
         * with the format as defined below
         * 
         * @param string $optStr = k2f---options---fieldname
         * options = optionName=optionValue[:::optionName=optionValue]*
         * 
         * Additional notes:
         * If valid = complex => 
         *  sub-field options without the prefix k2f--- but followed by ---fieldname
         *  sub-field options needs to preceed any defintion of the main field
         * 
         * @param type $id
         * 
         * @return 
         * associative array where the key is the option name of each field
         * If valid = complex =>
         *  a value called subfields is entered where definition of 
         *  each constituent field is parsed in place
         * 
         * Additional notes regarding field value mappings:
         * 
         * in field definitions we are able to provide values for list valued k2field types
         * these values can be provide explicitly as follows
         *      value1==text1==img1::value2==text2==img2::...==valueN==textN==imgN
         * in addition we provide ability to dynamically fetch the same set of data from other sources
         * currently supported are sql and url
         * such sources are specified as follows replacing the explicit value list above:
         *      sourcetype:(source)
         *      sourcetype=[sql|url|php|file]
         *      source=
         *      IF sourcetype == sql THEN 
         *       sql query where result columns are labeled as value, text and img
         *      IF sourcetype == url THEN 
         *       url result should be in JSON format an array where each array member consists 
         *       of the properties value, text and img
         *      IF sourcetype == php THEN
         *       php code should evaluate properly and return an array variable
         *       containing members of either objects or arrays with properties value, text and img
         *       ex. PHPCODE; return $result;
         * 
         *      note that in the above description only value is the mandatory value property
         *      and text and img are optional
         */
        public function mapFieldOptions($opts, $fieldId = -1, $useFilter = false, $subfieldOf = -1, $position = -1) {
                $optStr = '';
                
                if (is_object($opts)) {
                        $optStr = $opts->definition;
                        $options = get_object_vars($opts);
                } else if (is_array($opts)) {
                        $optStr = $opts['definition'];
                        $options = $opts;
                } else {
                        $optStr = $opts;
                        $options = array();
                }
                
                $optStr = preg_replace('#[\n\r]#', '', $optStr);
                
                if (empty($optStr) || !$this->isK2Field($optStr, true)) return false;
                
                $optStr = substr($optStr, strlen('k2f'.self::FIELD_SEPARATOR));
                $name = substr($optStr, strrpos($optStr, self::FIELD_SEPARATOR) + strlen(self::FIELD_SEPARATOR));
                $optStr = substr($optStr, 0, strrpos($optStr, self::FIELD_SEPARATOR));
                
                if (empty($name) || empty($optStr)) return false;
                
                $options['name'] = $name;
                $options['id'] = $fieldId;
                
                $sub = false;
                if (preg_match('#subfields=([\d\%]+)#', $optStr, $m)) {
                        $ids = str_replace(self::VALUE_SEPARATOR, ',', $m[1]);
                        $query = "SELECT id, replace(definition, 'k2f---', CONCAT('subfieldid=', id, ':::')) as def FROM #__k2_extra_fields_definition WHERE id IN ({$ids})";
                        $this->_db->setQuery($query);
                        $defs = $this->_db->loadObjectList('id');
                        $ids = array_keys($defs);

                        foreach ($defs as $id => &$def) {
                                if (preg_match('#deps=(.+)(\:\:\:|\-\-\-)#U', $def->def, $mm)) {
                                        $deps = explode(self::VALUE_SEPARATOR, $mm[1]);
                                        $_deps = array();
                                        foreach ($deps as $dep) {
                                                list($val, $fld) = explode(self::VALUE_COMP_SEPARATOR, $dep);
                                                if (!isset($_deps[$val])) $_deps[$val] = array();
                                                if (($pos = array_search($fld, $ids)) !== false) {
                                                        $_deps[$val][] = $pos + 1;
                                                } else {
                                                        $_deps[$val][] = 'id:'.$fld;
                                                }
                                        }
                                        $deps = array();
                                        foreach ($_deps as $val => $_dep) {
                                                foreach ($_dep as $__dep) {
                                                        $deps[] = $val.self::VALUE_COMP_SEPARATOR.$__dep;
                                                }
                                        }
                                        $deps = implode(self::VALUE_SEPARATOR, $deps);
                                        $_deps = json_encode($_deps);
                                        $def->def = str_replace('deps='.$mm[1], 'deps='.$deps, $def->def);
                                }
                        }
                        $defs = (array) JprovenUtility::getColumn($defs, 'def');
                        $defs = implode(':::', $defs);
                        $optStr = $defs.':::'.str_replace(':::'.$m[0], '', $optStr);
                        $sub = true;
//                } else if (preg_match('#deps=([^\{].+[^\}])(\:\:\:|\-\-\-|)#', $optStr, $m)) {
                } else if (preg_match('#deps=(.+)(\:\:\:|\-\-\-|$)#U', $optStr, $m)) {
                        $deps = explode(self::VALUE_SEPARATOR, $m[1]);
                        $_deps = array();
                        foreach ($deps as $dep) {
                                $dep = explode(self::VALUE_COMP_SEPARATOR, $dep);
                                if (!isset($_deps[$dep[0]])) $_deps[$dep[0]] = array();
                                $_deps[$dep[0]][] = 'id:'.$dep[1].(count($dep) > 2 ?':1':'');
                        }
                        $_deps = json_encode($_deps);
                        $optStr = str_replace('deps='.$m[1], 'deps='.$_deps, $optStr);
                }
                $sopts = explode(self::FIELD_SEPARATOR, $optStr);
                
                foreach ($sopts as $k => &$opt) {
                        if ($k > 0) {
                                $n = substr($opt, 0, strpos($opt, self::FIELD_OPTIONS_SEPARATOR));
                                $opt = substr($opt, strlen($n.self::FIELD_OPTIONS_SEPARATOR));
                                $sopts[$k-1] = 'k2f'.self::FIELD_SEPARATOR.$sopts[$k-1].self::FIELD_OPTIONS_SEPARATOR.'subfieldOf='.$fieldId.self::FIELD_SEPARATOR.$n;
                                $sopts[$k-1] = $this->mapFieldOptions($sopts[$k-1], -1, $useFilter, $fieldId, $k);
                        }
                }
                
                $optStr = array_pop($sopts);
                $opts = explode(self::FIELD_OPTIONS_SEPARATOR, $optStr);
                
                for ($i = 0, $n = count($opts); $i < $n; $i++) {
                        $opt = $opts[$i];
                        $key = strtolower(substr($opt, 0, strpos($opt, '=')));
                        $val = substr($opt, strpos($opt, '=') + 1);
                        
                        if ($key == '' || $val == '') continue;
                        
                        if ($key == 'replace') {
                                if (preg_match_all('#replace\[([^\]]+)\]#i', $val, $m)) {
                                        $key = 'conditions';
                                        $options['listmax'] = count($m[1]);
                                        $val = implode(self::VALUE_SEPARATOR, $m[1]);
                                        $options['list'] = 'conditional';
                                }
                        } else if ($key == 'values') {
                                if (preg_match('#^(sql|php|url|function|file)\:(.+)#i', $val, $m)) {
                                        $type = $m[1];
                                        $src = $m[2];
                                        
                                        $values = null;
                                        
                                        if ($type == 'sql') {
                                                $db = JFactory::getDBO();
                                                $db->setQuery($src);
                                                $values = $db->loadObjectList();
                                        } else if ($type == 'function') {
                                                try {
                                                        $values = call_user_func($src);
                                                } catch (JException $ex) {
                                                        $values = null;
                                                }
                                        } else if ($type == 'php') {
                                                try {
                                                        $values = eval($src);
                                                } catch (JException $ex) {
                                                        $values = null;
                                                }
                                        } else if ($type == 'file') {
                                                $file = JPATH_SITE.'/'.$src;
                                                $values = JFile::read($file);
                                                $values = explode("\n", $values);
                                                foreach ($values as &$value) {
                                                        $value = trim($value);
                                                        $value = explode(self::VALUE_COMP_SEPARATOR, $value);
                                                }
                                        }
                                        
                                        if (isset($values) && is_array($values) && count($values) > 0) {
                                                $isObj = is_object($values[0]);
                                                $props = array_keys($isObj ? get_object_vars($values[0]) : $values[0]);
                                                $valueKey = in_array('value', $props) ? 'value' : $props[0];
                                                $textKey = in_array('text', $props) ? 'value' : count($props) > 1 ? $props[1] : false;
                                                $imgKey = in_array('img', $props) ? 'img' : count($props) > 2 ? $props[2] : false;
                                                $val = array();

                                                for ($j = 0, $m = count($values); $j < $m; $j++) {
                                                        $value = $values[$j];
                                                        
                                                        $val[$j] = array(
                                                                'value' => $isObj ? $value->$valueKey : $value[$valueKey],
                                                                'text' => $textKey ? ($isObj ? $value->$textKey : $value[$textKey]) : '',
                                                                'img' => $imgKey ? ($isObj ? $value->$imgKey : $value[$imgKey]) : ''
                                                        );
                                                }
                                        }
                                } else {
                                        $val = explode(self::VALUE_SEPARATOR, $val);

                                        for ($j = 0, $m = count($val); $j < $m; $j++) {
                                                $tmp = explode(self::VALUE_COMP_SEPARATOR, $val[$j]);

                                                $val[$j] = array(
                                                    'value' => $tmp[0],
                                                    'text' => count($tmp) > 1 ? $tmp[1] : '',
                                                    'img' => count($tmp) > 2 ? $tmp[2] : ''
                                                );
                                        }  
                                }
                        } else if ($key == 'levels') {
                                $val = explode(self::VALUE_SEPARATOR, $val);
                        } else if (in_array($key, array('deps', 'interval'))) {
                                $val = json_decode($val);
                        } else if ($key == 'access') {
//                                static $viewLevels = array();
//
//                                if (empty($viewLevels)) {
//                                        $db = $this->_db;
//
//                                        $query = $db->getQuery(true)->select('id, LOWER(title) AS title')->from('#__viewlevels');
//
//                                        $db->setQuery((string) $query);
//
//                                        $viewLevels = $db->loadObjectList('title');
//                                }

                                // J2.5 ACL view permissions
                                $val = explode(self::VALUE_SEPARATOR, $val);
                                $val = array('read'=>$val[0], 'edit'=>$val[1]);
//                                $v = array();
//                                for ($j = 0, $m = count($val); $j < $m; $j++) {
//                                        $tmp = explode(self::VALUE_COMP_SEPARATOR, $val[$j]);
//                                        if (!is_numeric($tmp[1])) {
//                                                $tmp[1] = strtolower($tmp[1]);
//                                                $tmp[1] = isset($viewLevels[$tmp[1]]) ? $viewLevels[$tmp[1]] : -1;
//                                                if ($tmp[1] === -1) {
//                                                        // Raise error about undefined view access level
//                                                }
//                                        }
//                                        $v[$tmp[0]] = $tmp[1];
//                                }
//                                $val = $v;
                        }
                        
                        $options[$key] = $val;
                }
                
                if (!isset($options['valid'])) $options['valid'] = 'alphanum';
                
                if (count($sopts)) {
                        if (isset($options['overridesubfieldsprops'])) {
                                $overs = explode(self::VALUE_SEPARATOR, $options['overridesubfieldsprops']);
                                
                                foreach ($sopts as &$sopt) {
                                        foreach ($overs as $over) {
                                                if (isset($options[$over])) 
                                                        $sopt[$over] = $options[$over];
                                        }
                                }
                        }
                        
                        $options['subfields'] = $sopts;
                }
                
                if (isset($options['value'])) {
                        $vals = json_decode($options['value']);
                        foreach ($vals as $i => $val)
                                if (empty($val->value)) unset($vals[$i]);
                        $options['value'] = json_encode($vals);
                }

                /**
                 * @@todo: as part of upcoming refactoring:
                 * 2. provide interface where the getFieldsParameters and
                 * the property fieldParameterFilters need to be implemented
                 */

                $cls = $this->loadType($options['valid']);
                
                if ($cls) {
                        $mtd = 'getParameters';
                        
                        if (method_exists($cls, $mtd)) {
                                $clsOptions = call_user_func(array($cls, $mtd), $options);
                                
                                if ($useFilter) {
                                        $cls = get_class_vars($cls);
                                        if (isset($cls['fieldParameterFilters'])) {
                                                $filters = $cls['fieldParameterFilters'];

                                                if (!empty($filters)) 
                                                        $clsOptions = JprovenUtility::filterArray($clsOptions, $filters, 1);
                                        }
                                }
                                
                                $options = array_merge($options, $clsOptions);
                        }
                }
                
                self::initializeOptions($options, $useFilter);
                
                if (self::isType($options, 'alias')) {
                        $v = self::value($options, 'alias');
                        $aliasedOptions = $this->getFieldsById($v);
                        $aliased = array('valid');
                        $noneAliasable = array('search');
                        
                        foreach ($options as $key => $option) {
                                if (in_array($key, $noneAliasable)) {
                                        $aliasedOptions[$key] = $option;
                                        continue;
                                }
                                if (!in_array($key, $aliased)) {
                                        if ($option !== '' && $option !== null)
                                                $aliasedOptions[$key] = $option;
                                } else {
                                        $aliasedOptions['alias_'.$key] = $option;
                                }
                        }
                        
                        foreach ($aliasedOptions as $key => $option) {
                                if (!isset($options[$key]) && in_array($key, $noneAliasable)) {
                                        unset($aliasedOptions[$key]);
                                }
                        }
                        
                        $aliasedOptions = array_filter($aliasedOptions);
                        
                        if (!isset($options['filters']) || empty($options['filters'])) 
                                unset($aliasedOptions['filters']);
                        
                        return $aliasedOptions;
                }
                
                return $options;
        }
        
        private static function initializeOptions(&$options, $useFilter) {
                if (isset($options['list']) && !isset($options['listmax'])) 
                        $options['listmax'] = self::setting('listmax');
                
                if (self::isDatetimeType($options)) {
                        if (!isset($options['theme'])) 
                                $options['theme'] = self::setting('datepickertheme', null, 'dashboard');
                }
                
                $options['names'] = JprovenUtility::nize($options['name'], 2);
                
                $valid = self::value($options, 'valid');
                
                if ($valid == 'k2item') {
                        if ($useFilter) {
                                $ui = self::value($options, 'ui', 'autocomplete');
                                if ($ui != 'select' || $ui == 'autocomplete') unset($options['items']);
                        }
                } else if ($valid == 'complex') {
                        $subfields = self::value($options, 'subfields');
                        $searchFound = $durationFound = $daysFound = false;
                        
                        foreach ($subfields as $subfield) {
                                $search = self::value($subfield, 'search');
                                $valid = self::value($subfield, 'valid');
                                
                                if ($valid == 'duration') $durationFound = true;
                                if ($valid == 'days') $daysFound = true;
                                
                                if ($search && ($durationFound || $daysFound)) $searchFound = true;
                                
                                if ($searchFound && $durationFound && $daysFound) {
                                        $options['daysduration'] = true;
                                        break;
                                }
                        }
                }                
        }
        
        /**
         * @param array $options
         * 
         * @param (array or string) $filters set of option names and optionally values that 
         *      need to be present among $options
         * 
         * $param int $toReturn option|bool|value
         * 
         * $param string $method any|all
         * 
         * @return 
         *      false indicating that filter options were not found among options
         *      or
         *      $options where filter fields are exploded to array
         */
        private function filterFieldOptions($options, $filters, $method = 'any', $return = 'option') {
                if (empty($filters) || empty($options)) return $options;
                
                $subfieldsFiltered = false;
                
                if ($options['valid'] == 'complex') {
                        if (isset($options['subfields'])) {
                                foreach ($options['subfields'] as &$sf) {
                                        $sf = self::filterFieldOptions($sf, $filters, $method, $return);

                                        if ($sf) {
                                                $subfieldsFiltered = true;
                                        }
                                }
                        }
                        
                        return $options['subfields'];
                }
                
                $filters = (array) $filters;
                $filterNames = array_keys($filters);
                
                if (is_numeric($filterNames[0])) {
                        $filterNames = $filters;
                        $filterValues = array_fill(0, count($filterNames), false);
                } else {
                        $filterValues = array_values($filters);
                }
                
                $filtered = false;
                $i = 0;
                $result = array();
                
                foreach ($filterNames as $i => $filterName) {
                        $filtered = false;
                        
                        if (isset($options[$filterName])) {
                                if (!$filterValues[$i]) {
                                        $result[$filterName] = $options[$filterName];
                                        
                                        if ($result[$filterName] == 'true') 
                                                $result[$filterName] = true;
                                        else if ($result[$filterName] == 'false') 
                                                $result[$filterName] = false;
                                        
                                        $filtered = true;
                                } else {
                                        $options[$filterName] = explode(self::VALUE_SEPARATOR, $options[$filterName]);
                                        
                                        if (in_array($filterValues[$i], $options[$filterName])) {
                                                if (!isset($result[$filterName][$filterValues[$i]]))
                                                        $result[$filterName][$filterValues[$i]] = array();
                                                
                                                $result[$filterName][$filterValues[$i]] = $options[$filterName];
                                                
                                                if ($result[$filterName][$filterValues[$i]] == 'true')
                                                        $result[$filterName][$filterValues[$i]] = true;
                                                else if ($result[$filterName][$filterValues[$i]] == 'false')
                                                        $result[$filterName][$filterValues[$i]] = false;

                                                $filtered = true;
                                        }
                                }
                        } else {
                                $filtered = false;
                        }
                        
                        if ($method == 'any' && $filtered || $method == 'all' && !$filtered) {
                                break;
                        }
                }
                
                if ($filtered) {
                        if ($return == 'option')  {
                               return $result;
                        } else if ($return == 'bool') {
                               return true;
                        } else if ($return == 'value') {
                               return $value;
                        }
                }
                
                return false;
        }
        
        private static function _v($arr, $ind, $def='') {
                $res = JprovenUtility::value($arr, $ind-1, $def);
                return trim($res);
        }
        
        public static function isDate($field) {
                $valid = self::value($field, 'valid');
                return in_array($valid, array('date', 'datetime'));
        }
        
        public static function isDateTime($field) {
                $valid = self::value($field, 'valid');
                return in_array($valid, array('date', 'time', 'datetime'));
        }
        
        public static function isNumeric($field) {
                $valid = self::value($field, 'valid');
                return in_array($valid, array('number', 'real', 'integer'));
        }
}