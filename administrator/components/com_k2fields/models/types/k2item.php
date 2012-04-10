<?php

//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2fieldsK2Item {
        private static $folded = array();
        
        public static function getParameters($field = null, $options = null) {
                static $items = array();
                
                if (empty($options)) $options = $field;
                
//                if ($reverseField = K2FieldsModelFields::value($field, 'reverse')) {
//                        $model = JModel::getInstance('fields', 'K2FieldsModel');
//                        $reverseField = $model->getFieldsById($reverseField);
//                        $field = JprovenUtility::mergeK2FieldValues($reverseField, $field);
//                }
                
                $fieldId = K2FieldsModelFields::value($field, 'id');
                
                if (!isset($items[$fieldId])) {
                        $query = self::completeItems($field);

                        $db = JFactory::getDBO();
                        $db->setQuery($query);
                        $items[$fieldId] = $db->loadObjectList();
                }
                
                $options['items'] = $items[$fieldId];
                
                return $options;
        }
        
        function render($item, $values, $field, $helper, $rule) {
                $view = JRequest::getCmd('view');
                $as = K2FieldsModelFields::value($field, 'as', '');
                
                if ($view != 'item')
                        $as = K2FieldsModelFields::value($field, 'listas', $as);
                
                $items = array();
                
                if ($reverseFieldId = K2FieldsModelFields::value($field, 'reverse')) {
                        $reverseField = $helper->getFieldsById($reverseFieldId);
                        $field = JprovenUtility::mergeK2FieldValues($reverseField, $field);
                        
                        if ($as != 'jpajaxlist' && $as != 'jplist') {
                                $query = 'SELECT DISTINCT v.itemid FROM #__k2_extra_fields_values AS v WHERE v.fieldid = '.(int)$reverseFieldId.' AND v.value = '.(int)$item->id;
                                $db = JFactory::getDBO();
                                $db->setQuery($query);
                                $items = $db->loadResultArray();

                                if (!empty($items)) {
                                        $values = array();
                                        $fieldId = K2FieldsModelFields::value($field, 'id');

                                        foreach ($items as $_item) {
                                                $val = $helper->itemValues($_item, $fieldId);
                                                $val = JprovenUtility::first($val);
                                                $values = array_merge($values, $val);
                                        }

                                        $values = JprovenUtility::indexBy($values, 'fieldid');
                                }
                        } else {
                                $itemCat = K2FieldsModelFields::value($item, 'catid');
                                $fieldCat = K2FieldsModelFields::value($field, 'categories');
                                
                                if ($fieldCat == $itemCat) {
                                        $fieldCat = K2FieldsModelFields::value($field, 'cats');
                                        $fieldCat = explode(',', $fieldCat);
                                        JprovenUtility::setValue ($field, 'categories', $fieldCat[0]);
                                }                                
                        }
                } else {
                        foreach ($values as $value) {
                                $v = JprovenUtility::getColumn($value, 'value', false, array('partindex' => 0));
                                $items[] = $v;
                        }
                }
                
                if (empty($items) && $as != 'jpajaxlist' && $as != 'jplist') return '';
                
                $result = array();
                
                if ($as == 'view') {
                        if (isset($item->breakPluginLoop) && $item->breakPluginLoop == JRequest::getInt('id')) return;

                        require_once JPATH_SITE.'/components/com_k2fields/controllers/k2item.php';

                        $document = JFactory::getDocument();
                        $controller = new K2FieldsControllerK2item();
                        $model = $controller->getModel('k2item');
                        $viewType = $document->getType();
                        $view = $controller->getView('k2item', $viewType);
                        $view->setModel($model, true);

                        $saveId = JRequest::getInt('id');
                        $contents = array();

                        ob_start();
                        
                        foreach ($items as $id) {
                                JRequest::setVar('id', $id);

                                $cachable = JprovenUtility::isCachable($id);

                                if ($cachable && $viewType != 'feed') {
                                        $option = JRequest::getCmd('option');
                                        $cache = JFactory::getCache($option, 'view');
                                        $cache->get($view, 'display');
                                } else {
                                        $view->display();
                                }

                                $result[] = ob_get_clean();
                        }
                        
                        JRequest::setVar('id', $saveId);
                } else if ($as == 'jpajaxlist' || $as == 'jplist') {
                        $fieldId = K2FieldsModelFields::value($field, 'id');
                        $cats = K2FieldsModelFields::value($field, 'categories');
                        
                        $query = array(
                            's'.$fieldId.'_0' => $item->id
                        );
                        
                        $ui = K2HelperRoute::createCategoryLink(
                                $cats, 
                                $query,
                                '',
                                $as == 'jpajaxlist' ? 'ajax' : 'link'
                        );
                        
                        $result[] = $ui;
                } else if (strpos($as, 'embed') === 0) {
                        $includeFields = K2FieldsModelFields::value($field, 'includefields');
                        
                        if (!empty($includeFields)) {
                                $includeFields = explode(K2FieldsModelFields::VALUE_SEPARATOR, $includeFields);
                                JprovenUtility::toInt($includeFields);
                                $includeFields = ' fields='.implode(',', $includeFields);
                        } else $includeFields = '';
                        
                        $fold = K2FieldsModelFields::value($field, 'foldfields');
                        
                        if (!empty($fold)) {
                                $fold = explode(K2FieldsModelFields::VALUE_SEPARATOR, $fold);
                                JprovenUtility::toInt($fold);
                                $foldFields = ' foldfields='.implode(',', $fold);
                        } else $foldFields = '';
                        
                        $content = '{k2f k2item=true k2cat='.$item->catid.' item='.implode(',', $items).' title=true'.($as == 'embedraw' ? '' : ' titlecollapse=true').$includeFields.$foldFields.' fold=0}';
                        
                        $ui = JprovenUtility::replacePluginValues(
                                $content,
                                'k2f', 
                                true
                        );
                        
                        if (!empty($fold)) {
                                $foldFields = ' fields='.implode(',', $fold);
                                
                                $content = '{k2f k2item=true k2cat='.$item->catid.' item='.implode(',', $items).' title=true'.($as == 'embedraw' ? '' : ' titlecollapse=true').$foldFields.'}';
                                $uiFold = JprovenUtility::replacePluginValues(
                                        $content,
                                        'k2f', 
                                        true
                                );
                        } else $uiFold = '';
                        
                        $_item = K2FieldsModelFields::value($item, 'id');
                        
                        if (!isset(self::$folded[$_item])) self::$folded[$_item] = '';
                        
                        self::$folded[$_item] .= $uiFold;
                        $result[] = $ui;
                } else {
                        $query = 'SELECT i.id, i.title, i.alias, c.id as catid, c.alias as catalias FROM #__k2_items AS i LEFT JOIN #__k2_categories AS c ON i.catid = c.id WHERE i.id IN ('.implode(',', $items).')';
                        $db = JFactory::getDBO();
                        $db->setQuery($query);
                        $items = $db->loadObjectList('id');
                        $lis = array_keys($values);
                        $vals = JprovenUtility::flatten($values);
                        
                        foreach ($items as $itemId => $_item) {
                                foreach ($lis as $i => $li) {
                                        $cond = JprovenUtility::getRow(
                                                $vals, 
                                                array('itemid' => $item->id, 'listindex' => $i, 'partindex' => -1)
                                        );
                                        
                                        $itemRow = JprovenUtility::getRow(
                                                $vals, 
                                                array('itemid' => $item->id, 'value' => $itemId, 'listindex' => $i, 'partindex' => 0)
                                        );
                                        
                                        if (!$itemRow) continue;

                                        $cond = empty($cond) ? '' : $cond[0]->value;
                                        $cond = '<span class="condition">'.$cond.'</span>';

                                        if ($as == 'title') {
                                                $ui = $_item->title;
                                        } else {
                                                $as = str_replace('link', '', $as);
                                                $ui = K2HelperRoute::createItemLink($_item, '', $as);
                                        }

                                        $result[] = $cond.$ui;
                                }
                        }
                }
                
                $result = $helper->renderFieldValues($result, $field, $rule);
                
                return $result;
        }
        
        public static function renderFolded($item) {
                if (!is_int($item)) $item = K2FieldsModelFields::value($item, 'id');
                return isset(self::$folded[$item]) ? self::$folded[$item] : '';
        }
        
        public static function completeItems($field, $value = '', $pos = null) {
                if (K2FieldsModelFields::isType($field, 'complex') && !empty($pos)) {
                        $field = K2FieldsModelFields::value($field, 'subfields');
                        $field = $field[$pos];
                }
                
                $categories = K2FieldsModelFields::value($field, 'categories');
                
                if ($reverseField = K2FieldsModelFields::value($field, 'reverse')) {
                        $model = JModel::getInstance('fields', 'K2FieldsModel');
                        $reverseField = $model->getFieldsById($reverseField);
                        $categories = K2FieldsModelFields::value($reverseField, 'categories');
                }
                
                $categories = explode(',', $categories);
                $excludes = K2FieldsModelFields::value($field, 'exclude');
                
                if (!empty($excludes)) $excludes = explode(',', $excludes);
                
                $ccategories = JprovenUtility::getK2CategoryChildren($categories);
                if (!empty($ccategories)) $categories = array_merge($categories, $ccategories['cats']);
                $categories = JprovenUtility::removeValuesFromArray($categories, $excludes);
                
                $user = JFactory::getUser();
                $allowed = 'access IN ('. implode(',', $user->getAuthorisedViewLevels()).')';               

                $query = 
                        'SELECT DISTINCT i.id AS ovalue, i.title AS value, c.id as catid, c.name as cattitle ' .
                        'FROM #__k2_items AS i LEFT JOIN #__k2_categories AS c ON i.catid = c.id WHERE i.catid IN ('.implode(',', $categories).') AND i.published = 1 AND i.'.$allowed.' AND i.trash = 0 AND c.published = 1 AND c.'.$allowed.' AND c.trash = 0 ' .
                        (!empty($value) ? ' AND i.title LIKE '.$value : '')
                        ;
                
                $fieldsFilters = K2FieldsModelFields::value($field, 'k2itemfilters');
                
                if (!empty($fieldsFilters)) {
                        $fieldsFilters = explode(K2FieldsModelFields::VALUE_SEPARATOR, $fieldsFilters);
                        $list = new K2FieldsList();
                        $model = JModel::getInstance('fields', 'K2FieldsModel');
                        $db = JFactory::getDbo();
                        
                        for ($i = 0, $n = count($fieldsFilters); $i < $n; $i++) {
                                list($fieldId, $fieldFilters) = explode(K2FieldsModelFields::VALUE_COMP_SEPARATOR, $fieldsFilters[$i]);
                                $filterField = null;
                                $fieldFilters = explode(',', $fieldFilters);
                                
                                foreach ($fieldFilters as $i => &$fieldFilter) {
                                        if (JprovenUtility::endWith($fieldFilter, '*')) {
                                                $fieldFilter = (int) str_replace('*', '', $fieldFilter);
                                                
                                                if (empty($filterField)) {
                                                        $filterField = $model->getFieldsById($fieldId);
                                                        $source = K2FieldsModelFields::value($filterField, 'source');
                                                }
                                                
                                                $fieldFilter = $list->getTree($source, $fieldFilter, null, -1, true, true, array('id'), 'query');
                                        } else {
                                                $fieldFilter = $db->quote($fieldFilter);
                                        }
                                }
                                
                                $fieldFilters = implode(',', $fieldFilters);
                                
                                $v = 'v'.$i;
                                $query .= ' AND i.id IN (SELECT DISTINCT '.$v.'.itemid FROM #__k2_extra_fields_values AS '.$v.' WHERE '.$v.'.fieldid = '.$fieldId.' AND '.$v.'.value IN ('.$fieldFilters.'))';
                        }
                }
                
                $query .= 'ORDER BY c.ordering, i.ordering';
                
                return $query;
        }
        
        public static function reverseComplete($value) {
                $value = (array) $value;
                
                JprovenUtility::toInt($value);
                
                $query = 
                        'SELECT i.id AS ovalue, i.title AS value, c.id as catid, c.name as cattitle ' .
                        'FROM #__k2_items AS i LEFT JOIN #__k2_categories AS c ON i.catid = c.id WHERE i.id IN ('.implode(',', $value).')'
                        ;
                
                return $query;
        }
}
?>
