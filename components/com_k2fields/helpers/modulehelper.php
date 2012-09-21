<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2FieldsModuleHelper {
        public static function renderCategoryLabel($items, &$params, &$access, $imgOnly = false) {
                $item = $items[0];
                
                if (isset($item->tabName)) {
                        $name = $item->tabName;
                } else {
                        if (isset(self::$categoriesData) && isset(self::$categoriesData[$item->catid])) {
                                $name = self::$categoriesData[$item->catid][0][1];
                        }
                        
                        if (!isset($name)) $name = $item->categoryname;
                }
                
                $img = !empty($items->tabImage) ? $item->tabImage : $item->categoryimage;
                
                return 
                        $name .
                        (
                                $params->get('catImage') && !empty($img) && !$imgOnly ? 
                                '<img src="'.JURI::root().'media/k2/categories/'.$img.'" alt="'.$name.'" />' :
                                ''
                        )
                        ;
        }
        
	public static function renderCategory(&$items, &$params, &$access, $preTexts, $tabNo) {
                $componentParams = JComponentHelper::getParams('com_k2');
                $preText = self::renderPretext($preTexts, $tabNo);
		require JModuleHelper::getLayoutPath('mod_k2fields_contents', '_category');
	}
        
        private static $categories = array(), $categoryTree = array(), $categoryParents = array(), $categoriesData, $_items = array();
        
        private static function getCategoryChildren($ids, $excludeIds, $depth = -1, $clear = false) {
                static $currDepth = 1;
                
                if ($clear) {
                        self::$categories = array();
                        self::$categoryTree = array();
                        self::$categoryParents = array();
                        $currDepth = 1;
                }
                
                if (($depth != -1 && $currDepth > $depth) || empty($ids))
                        return self::$categories;
                
                $user = JFactory::getUser();
                $access = ".access IN (".implode(',', $user->authorisedLevels()).")";
                
                $db = JFactory::getDBO();
                $ids = (array) $ids;
                $ids = array_unique($ids);
                
                $query = "SELECT *, (SELECT COUNT(*) FROM #__k2_categories cc WHERE cc.parent = c.id AND cc.published=1 AND cc.trash=0 AND cc".$access.") AS cnt FROM #__k2_categories c WHERE c.parent IN (".(implode(", ", $ids)).") AND c.published=1 AND c.trash=0 AND c".$access;
                
                if (!empty($excludeIds)) {
                        $excludeIds = (array) $excludeIds;
                        $query .= " AND c.id NOT IN (".implode(',', $excludeIds).")";
                }
                
                $query .= " ORDER BY c.ordering";
                
                $db->setQuery($query);
                $rows = $db->loadObjectList();
                $catsWithChildren = array();

                foreach ($rows as $row) {
                        self::$categories[$row->id] = $row;
                        
                        if (!isset(self::$categoryTree[$row->parent])) 
                                self::$categoryTree[$row->parent] = array();
                        
                        self::$categoryTree[$row->parent][] = $row->id;
                        self::$categoryParents[$row->id] = $row->parent;
                        
                        if ($row->cnt > 0) 
                                $catsWithChildren[] = $row->id;
                }
                
                $currDepth++;
                
                if (!empty($catsWithChildren)) 
                        self::getCategoryChildren($catsWithChildren, $excludeIds, $depth, false);
                
                return self::$categories;                
        }
        
        public static function getItemLayout($item, $ext, $extType, $layoutDir, $extLayoutDir) {
                $extDir = '/' . $extType . 's/' . $ext;
                $tmpl = JFactory::getApplication()->getTemplate();
                
		$dirs = array(JPATH_SITE.'/templates/'.$tmpl.'/html/'.$layoutDir.'/', JPATH_SITE . $extDir . '/' . $extLayoutDir . '/');
                $tmpl = '';
                
                // In priority order
                $files = array('i'.$item->id.'.php', 'c'.$item->catid.'.php', 'item.php');
                
                foreach ($dirs as $dir) {
                        foreach ($files as $file) {
                                if (JFile::exists($dir.$file)) {
                                        $tmpl = $dir.$file;
                                        break;
                                }
                        }
                        if (!empty($tmpl)) break;
                }
                
                return $tmpl;
        }
        
        public static function getList($params, $componentParams, $format = 'html', $partBy = 'category', $caller = 'mod_k2fields_contents') {
                $cache = JFactory::getCache($caller);
                $result = $cache->call('K2FieldsModuleHelper::_getList', $params, $componentParams, $format, $partBy);
                return $result;
        }

        // Credit: modified version of corresponding fuction in mod_k2_content
        /**
         * Priorities:
         * 1. explicit items
         * 2. sticky criterias
         * 3. categories
         */
        static $searchBy = array('fields'=>array(), 'categories'=>array());
	static function _getList($params, $componentParams, $format = 'html', $partBy = 'category') {
                $moduleId = $params->get('module_id');
                
                if ($moduleId && isset(self::$_items[$moduleId])) return self::$_items[$moduleId];
                
                $items = $params->get('items');
                $itemsProvided = !empty($items);
                $stickTo = $params->get('stickto');
                $categories = $params->get('cats', array());
                $childrenMode = (int) $params->get('getChildren', 0);
                $input = JFactory::getApplication()->input;
                $option = $input->get('option');
                $view = $input->get('view');
                $itemId = $input->get('id', '', 'int');
                $db = JFactory::getDbo();
                $now = $db->quote(JFactory::getDate()->toMySQL());
                $nullDate = $db->quote($db->getNullDate());
                $user = JFactory::getUser();
                
                if ($itemsProvided) {
                        if (!is_array($items)) $items = explode('|', $items);
                        
                        if (!is_numeric($items[0])) {
                                $items = self::prepareList($items, $params);
                                if ($moduleId) self::$_items[$moduleId] = $items;
                                return $items;
                        }
                } else if ($stickTo == 'item') {
                        $items = $option == 'com_k2' && $view == 'item' && $itemId ? array($itemId) : null;
                        
                        if (empty($items)) return array();
                        
                        $itemsProvided = true;
                }
                
                $limit = $params->get('itemCountTotal', 0);
                $limitPerCat = $params->get('itemCount', 0);
                
                if (!$itemsProvided) {
                        if ($stickTo != 'none' && $stickTo != 'menu' && $option != 'com_k2' && $option != 'com_k2fields') return array();
                        
                        if ($stickTo != 'none' && $stickTo != 'menu' && $stickTo != 'cat' && $view != 'item') return array();
                        
                        $stickToCategory = $params->get('sticktocategory');
                        $excludeCategories = $params->get('excludecats', array());

                        if (!empty($excludeCategories)) {
                                $excludeChildrenMode = (int) $params->get('getChildrenExclude', 0);
                                
                                if ($excludeChildrenMode != 0) {
                                        $depth = $excludeChildrenMode == 2 ? 1 : -1;
                                        $childrenCategories = self::getCategoryChildren($excludeCategories, array(), $depth, true);
                                        $childrenCategories = array_keys($childrenCategories);
                                        $excludeCategories = array_merge($excludeCategories, $childrenCategories);
                                }
                                
                                $excludeCategories = JprovenUtility::toIntArray($excludeCategories);
                        }
                        
                        if ($stickTo != 'menu' && $stickTo != 'cat') {
                                if ($childrenMode != 0) {
                                        $depth = $childrenMode == 2 ? 1 : -1;
                                        $childrenCategories = self::getCategoryChildren($categories, $excludeCategories, $depth, true);
                                        $childrenCategories = array_keys($childrenCategories);
                                        $categories = array_merge($categories, $childrenCategories);
                                }
                                
                                $categories = JprovenUtility::toIntArray($categories);
                        }
                        
                        $query = '';
                        
                        if ($stickTo == 'menu') {
                                self::$categoriesData = JprovenUtility::getK2CategoriesSelector(
                                        2, 0, $excludeCategories, false, '', false, '', true, false
                                );
                                
                                $categories = (array) JprovenUtility::getColumn(self::$categoriesData, 0);
                                self::$categoriesData = JprovenUtility::indexBy(self::$categoriesData, array(0));
                        } else if ($stickTo == 'cat') {
                                if ($view == 'item') {
                                        $query = 'SELECT catid FROM #__k2_items WHERE id = '.$itemId;
                                        $db->setQuery($query);
                                        $categories = $db->loadResult();
                                } else {
                                        $categories = $input->get($option == 'com_k2fields' ? 'cid' : 'id', null, 'int');
                                        $itemId = null;
                                }
                                
                                if (empty($categories) || in_array($categories, $excludeCategories)) return array();
                                
                                $categories = (array) $categories;
                                
                                if ($childrenMode != 0) {
                                        $depth = $childrenMode == 2 ? 1 : -1;
                                        $childrenCategories = self::getCategoryChildren($categories, $excludeCategories, $depth, true);
                                        $childrenCategories = array_keys($childrenCategories);
                                        $categories = array_merge($categories, $childrenCategories);
                                }
                                
                                $categories = JprovenUtility::toIntArray($categories);
                        } else if ($stickTo == 'tag') {
                                $query = "SELECT DISTINCT itemID FROM #__k2_tags_xref WHERE tagID IN (SELECT tagID FROM #__k2_tags_xref WHERE itemId = ".$itemId.") AND itemID <> ".$itemId;
                        } else if ($stickTo == 'key') {
                                $query = 'SELECT metakey FROM #__k2_items WHERE id = '.$itemId;
                                $db->setQuery($query);
                                
                                $keys = $db->loadResult();

                                if (empty($keys)) return array();

                                $keys = explode(',', $keys);
                                $keys = ' CONCAT(",", REPLACE(i.metakey, ", ", ","), ",") LIKE "%'.
                                        implode('%" OR CONCAT(",", REPLACE(i.metakey, ", ", ","), ",") LIKE "%', $keys).'%"';

                                $query = 'SELECT DISTINCT id FROM #__k2_items i';
                                $query .= ' WHERE i.published = 1';
                                $query .= ' AND (i.publish_up = '.$nullDate.' OR i.publish_up <= '.$now.')';
                                $query .= ' AND (i.publish_down = '.$nullDate.' OR i.publish_down >= '.$now.')';
                                $query .= ' AND i.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')';
                                $query .= ' AND i.trash = 0';
                                $query .= ' AND '.$keys;
                        } else if ($stickTo == 'field') {
                                $fields = $params->get('sticktofields', '');
                                
                                if (!is_array($fields)) $fields = explode(',', $fields);

                                $complexFields = array();
                                $aliasFields = array();
                                $subFields = array();

                                foreach ($fields as $i => $field) {
                                        if (strpos($field, ',')) {
                                                // complex
                                                list($sf,$f) = explode(',', $field);
                                                if (!in_array($f, $complexFields)) $complexFields[] = $f;
                                                if (!isset($subFields[$f])) $subFields[$f] = array();
                                                $subFields[$f][] = $sf;
                                                unset($fields[$i]);
                                        } else if (strpos($field, '.')) {
                                                // aliased
                                                $field = explode('.', $field);
                                                $fields[$i] = array($field[0], 0);
                                                $aliasFields[$field[0]] = $field[1];
                                        } else {
                                                $fields[$i] = array($field, 0);
                                        }
                                }
                                
                                if (!empty($complexFields)) {
                                        $fieldsModel = JModel::getInstance('fields', 'K2FieldsModel');
                                        $complexFields = $fieldsModel->getFieldsById($complexFields);
                                        
                                        foreach ($complexFields as $f) {
                                                $sfs = K2FieldsModelFields::value($f, 'subfields');
                                                $id = K2FieldsModelFields::value($f, 'id');
                                                $_sfs = $subFields[$id];
                                                foreach ($sfs as $pos => $sf) {
                                                        $sfid = K2FieldsModelFields::value($sf, 'subfieldid');
                                                        if (!in_array($sfid, $_sfs)) continue;
                                                        $fields[] = array($id, $pos);
                                                }
                                        }
                                }
                                
                                $query = 'SELECT fieldid, partindex, listindex, GROUP_CONCAT(value SEPARATOR "%%") AS value
                                        FROM #__k2_extra_fields_values
                                        WHERE itemid = '.$itemId.' AND partindex <> -1'
                                        ;
                                
                                foreach ($fields as $field) {
                                        $query .= ' AND fieldid = ' .(int)$field[0].' AND partindex = '.(int)$field[1];
                                }
                                
                                $query .= ' GROUP BY fieldid, partindex, listindex ORDER BY `index`';
                                
                                $db->setQuery($query);
                                $values = $db->loadObjectList();
                                
                                $valuesByField = JprovenUtility::indexBy($values, array('fieldid', 'partindex'));
                                $fields = array_keys($valuesByField);
                                
                                $tbl = 'SELECT itemid, fieldid, listindex, partindex, GROUP_CONCAT(value SEPARATOR "%%") AS value FROM #__k2_extra_fields_values WHERE itemid <> '.$itemId.' AND (';
                                self::$searchBy['fields'] = $valuesByField;
                                
                                $i = 0;
                                foreach ($valuesByField as $fieldId => $valuesByParts) {
                                        if (isset($aliasFields[$fieldId])) {
                                                $fieldId = $aliasFields[$fieldId];
                                        }
                                        
                                        foreach ($valuesByParts as $partIndex => $valuesByList) {
                                                if ($i > 0) $tbl .= ' OR ';
                                                $tbl .= '(fieldid = '.$fieldId.' AND partindex = '.$partIndex.')';
                                                $i++;
                                        }
                                }
                                
                                $tbl .= ') GROUP BY itemid, fieldid, partindex, listindex';
                                
                                $query = 'SELECT DISTINCT itemid AS id FROM ('.$tbl.') AS i WHERE ';

                                $j = 0;
                                foreach ($valuesByField as $fieldId => $valuesByParts) {
                                        if (isset($aliasFields[$fieldId])) {
                                                $fieldId = $aliasFields[$fieldId];
                                        }
                                        
                                        foreach ($valuesByParts as $partIndex => $valuesByList) {
                                                if ($j > 0) $query .= ' AND ';
                                                $query .= 'fieldid = ' . $fieldId . ' AND partindex = ' . $partIndex . ' AND value IN (';
                                                $i = 0;
                                                foreach ($valuesByList as $value) {
                                                        if ($i > 0) $query .= ', ';
                                                        $query .= $db->quote($value->value);
                                                        $i++;
                                                }
                                                $query .= ')';
                                                $j++;
                                        }
                                }
                        }
                        
                        if ($query) {
                                if ($stickToCategory == 'same') {
                                        $query .= ' AND i.itemid in (SELECT id FROM #__k2_items WHERE catid IN (SELECT catid FROM #__k2_items WHERE id = '.$itemId.'))';
                                } else if ($stickToCategory == 'cats' && !empty($categories)) {
                                        $query .= ' AND i.itemid in (SELECT id FROM #__k2_items WHERE catid IN ('.implode(',', $categories).'))';
                                }
                                
                                self::$searchBy['categories'] = $categories;
                                $query .= ' ORDER BY RAND()';
                                
                                $lim = $stickToCategory != 'none' && isset($limitPerCat) ? $limitPerCat : $limit;
                                
                                if (!empty($lim)) $query .= ' LIMIT 0, '.$lim;
                                
                                $db->setQuery($query);
                                $items = $db->loadResultArray();
                                
                                if (empty($items)) return array();
                                
                                $categories = null;
                        }
                        
                        if (!empty($categories)) {
                                $categories = JprovenUtility::toIntArray($categories);
                                $categories = array_unique($categories);
                        }
                }
                
                $ordering = $params->get('itemsOrdering','');
                $access = '.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')';
                
                $query = "SELECT DISTINCT i.*, c.name AS categoryname, c.id AS categoryid, c.image AS categoryimage, c.alias AS categoryalias, c.alias AS catalias, c.params AS categoryparams";

                // Check if it's not the averaged amount that's already stored in items
                if ($ordering == 'best') $query .= ", (r.rating_sum/r.rating_count) AS rating";

                if ($ordering == 'comments') $query .= ", COUNT(comments.id) AS numOfComments";

                $queryFrom = " FROM #__k2_items as i LEFT JOIN #__k2_categories c ON c.id = i.catid";

                if ($ordering == 'best') $queryFrom .= " LEFT JOIN #__k2_rating r ON r.itemID = i.id";

                if ($ordering == 'comments') $queryFrom .= " LEFT JOIN #__k2_comments comments ON comments.itemID = i.id";

                $queryWhere = " WHERE i.published = 1 AND i{$access} AND i.trash = 0 AND c.published = 1 AND c{$access} AND c.trash = 0";
                $queryWhere .= " AND ( i.publish_up = ".$nullDate." OR i.publish_up <= ".$now." )";
                $queryWhere .= " AND ( i.publish_down = ".$nullDate." OR i.publish_down >= ".$now." )";

                if ($params->get('FeaturedItems') == '0')
                        $queryWhere .= " AND i.featured != 1";

                if ($params->get('FeaturedItems') == '2')
                        $queryWhere .= " AND i.featured = 1";

                if ($params->get('imagesOnly') && ($imageFields = $params->get('imagefield'))) {
                        $imageFields = JprovenUtility::toIntArray((array) $imageFields, true);
                        
                        $queryFrom .= ' INNER JOIN #__k2_extra_fields_values AS efvimgs ON i.id = efvimgs.itemid AND efvimgs.fieldid IN ('.$imageFields.') AND efvimgs.partindex = '.(K2FieldsMedia::SRCPOS-1).' AND efvimgs.value <> "" AND efvimgs.value IS NOT NULL';
                }
                
                if ($ordering == 'comments')
                        $queryWhere .= " AND comments.published = 1";

                // TODO: Not supported yet, are order by rating
                switch ($ordering) {
                        case 'date':
                                $orderby = 'i.created ASC';
                                break;

                        case 'rdate':
                                $orderby = 'i.created DESC';
                                break;

                        case 'alpha':
                                $orderby = 'i.title';
                                break;

                        case 'ralpha':
                                $orderby = 'i.title DESC';
                                break;

                        case 'order':
                                if ($params->get('FeaturedItems') == '2')
                                        $orderby = 'i.featured_ordering';
                                else
                                        $orderby = 'i.ordering';
                                break;

                        case 'rorder':
                                if ($params->get('FeaturedItems') == '2')
                                        $orderby = 'i.featured_ordering DESC';
                                else
                                        $orderby = 'i.ordering DESC';
                                break;

                        case 'hits':
                                if ($params->get('popularityRange')){
                                        $datenow = &JFactory::getDate();
                                        $date = $datenow->toMySQL();
                                        $queryWhere .= " AND i.created > DATE_SUB('{$date}',INTERVAL ".$params->get('popularityRange')." DAY) ";
                                }
                                $orderby = 'i.hits DESC';
                                break;

                        case 'rand':
                                $orderby = 'RAND()';
                                break;

                        case 'best':
                                $orderby = 'i.rating DESC';
                                break;

                        case 'comments':
                                if ($params->get('popularityRange')){
                                        $datenow = &JFactory::getDate();
                                        $date = $datenow->toMySQL();
                                        $queryWhere .= " AND i.created > DATE_SUB('{$date}',INTERVAL ".$params->get('popularityRange')." DAY) ";
                                }
                                $queryWhere .= " GROUP BY i.id ";
                                $orderby = 'numOfComments DESC';
                                break;

                        case 'modified':
                                $orderby = 'i.modified DESC';
                                break;

                        default:
                                $orderby = 'i.id DESC';
                                break;
                }
                
                $timeRange = $params->get('timerange');
                $timeRangeField = $params->get('timerangefield');
                
                if (!empty($timeRange) && !empty($timeRangeField)) {
                        require_once JPATH_SITE.'/components/com_k2fields/models/searchterms.php';
                        $smModel = JModel::getInstance('searchterms', 'K2FieldsModel');
                        
                        require_once JPATH_ADMINISTRATOR.'/components/com_k2fields/models/fields.php';

                        $fieldsModel = JModel::getInstance('fields', 'K2FieldsModel');
                        $field = $fieldsModel->getFieldsById($timeRangeField);
                        $time = K2FieldsModelSearchterms::convertDates($timeRange, $field);
                        
                        if (!empty($time['start']) || !empty($time['end']))
                                $queryFrom .= ' INNER JOIN #__k2_extra_fields_values AS efvtrf ON i.id = efvtrf.itemid AND efvtrf.fieldid = '.K2FieldsModelFields::value($field, 'id');

                        if (!empty($time['start']))
                                $queryWhere .= ' AND efvtrf.datum >= '.$db->Quote($time['start']);

                        if (!empty($time['end']))
                                $queryWhere .= ' AND efvtrf.datum <= '.$db->Quote($time['end']);
                }
                
                $query .= $queryFrom . $queryWhere;
                
                if (empty($items)) {
                        if (!empty($limitPerCat) || empty($limit)) {
                                if (empty($limitPerCat)) $limitPerCat = 5;

                                $cnts = array_fill(0, count($categories), $limitPerCat);
                        } else {
                                $categories = array($categories);
                                $cnts = array($limit);
                        }
                        
                        $queries = array();

                        $efCriterias = $params->get('itemExtraFieldsCriterias');
                        $efc = array();
                        
                        if ($efCriterias) {
                                require_once JPATH_SITE.'/components/com_k2fields/models/searchterms.php';
                                $st = JModel::getInstance('searchterms', 'K2fieldsModel');
                                
                                $efCriterias = explode("\n", $efCriterias);
                                $efc = array();
                                
                                foreach ($efCriterias as &$efCriteria) {
                                        $efCriteria = explode('%%', $efCriteria);
                                        parse_str($efCriteria[1], $efCriteria[1]);
                                        $efCriteria[1] = $st->parseSearchTerms($efCriteria[1], true, 'terms');
                                        $q = '';
                                        $st->sqlJoin($q, $efCriteria[1], 'i');
                                        $efc[$efCriteria[0]] = $q;
                                }
                        }
                        
                        foreach ($categories as $c => $cat) {
                                if (!is_array($cat)) $cat = array($cat);
                                
                                $q = $query;
                                
                                foreach ($cat as $_cat) {
                                        if (isset($efc[$_cat])) {
                                                $q = str_replace(' WHERE ', $efc[$_cat].' WHERE ', $q);
                                        }
                                }

                                $sql = @implode(',', $cat);

                                $queries[] = "(" . $q . " AND i.catid IN ({$sql}) ORDER BY " . $orderby . " LIMIT 0, ".$cnts[$c] . ")";
                        }
                        
                        // TODO: group by instead of union
                        $queries = implode(' UNION  ALL ', $queries);
                } else {
                        $queries = 
                                $query .
                                ' AND i.id IN ('.implode(',', $items).') ORDER BY '.$orderby.
                                (!empty($limit) ? ' LIMIT 0, '.$limit : '');
                }
                
                $db->setQuery($queries);
                $items = $db->loadObjectList();
                
                if ($params->get('itemSimilarLink', false)) {
                        $parts = array();
                        if (!empty(self::$searchBy['fields'])) {
                                foreach (self::$searchBy['fields'] as $fieldId => $valsByParts) {
                                        foreach ($valsByParts as $part => $vals) {
                                                foreach ($vals as $val) {
                                                        $parts[] = 's'.$fieldId.'_'.$part.'='.$val->value;
                                                }
                                        }
                                }
                        }
                        if (!empty(self::$searchBy['categories'])) {
                                $parts[] = 'scid='.implode(',', self::$searchBy['categories']);
                        }
                        if (!empty($parts)) {
                                self::$searchBy = implode('&', $parts);
                        }
                }
                
                if ($partBy == 'category') $indexBy = 'catid';
                else if ($partBy == 'author') $indexBy = 'created_by';
                else $indexBy = 'catid';
                
                $items = JprovenUtility::indexBy($items, $indexBy);
                
                if (!$itemsProvided && $childrenMode == 2 && isset($categories)) {
                        $rootChildren = self::$categoryTree[$rootCategory];
                        $_items = array();
                        
                        foreach ($items as $c => $itemsPerCat) {
                                $pc = $c;
                                
                                while (!in_array($pc, $rootChildren)) {
                                        $pc = self::$categoryParents[$pc];
                                }
                                
                                if (!isset($_items[$pc])) 
                                        $_items[$pc] = array();
                                
                                foreach ($itemsPerCat as &$item) {
                                        $item->tabName = self::$categories[$pc]->name;
                                        $item->tabImage = self::$categories[$pc]->image;
                                }
                                
                                $_items[$pc] = array_merge($_items[$pc], $itemsPerCat);
                        }
                        
                        foreach ($_items as &$item) shuffle($item);
                        
                        $items = $_items;
                }
                
                $items = self::prepareList($items, $params, $componentParams, $format);
                
                if (empty($partBy)) $items = JprovenUtility::flatten($items);
                
                if ($moduleId) self::$_items[$moduleId] = $items;
                
                return $items;
	}
        
        public static function renderPretext($preTexts, $tabNo) {
                if (!isset($preTexts[$tabNo])) $tabNo = 'all';
                if (!isset($preTexts[$tabNo])) return '';
                return $preTexts[$tabNo];
        }
        
        public static function getPretexts($params) {
                $preTexts = array();
                
                $texts = $params->get('itemPreText');
                
                if ($texts) {
                        $tag = $params->get('itemPreTextTag', '');
                        $open = $close = '';
                        
                        if ($tag) {
                                $open = '<'.$tag.'>';
                                $close = '</'.$tag.'>';
                        }
                        
                        $texts = explode("\n", $texts);
                        
                        foreach ($texts as $text) {
                                $text = explode('%%', $text);
                                if ($text[0] != 'all') $text[0] = (int) $text[0] - 1;
                                $preTexts[$text[0]] = array($open.$text[1].$close);
                                if (count($text) > 2) $preTexts[$text[0]][] = $text[2];
                                if (count($text) > 3) $preTexts[$text[0]][] = $text[3];
                        }
                }
                
                return $preTexts;
        }
        
        public static function image($item, $params, $itemLayout = '', $attribs = array()) {
                if (!$item->image) return '';
                return JHTML::_('image', $item->image, $item->imageCaption, $attribs);
        }
        
        public static function imageThumb($item, $params, $itemLayout = '') {
                if (!$item->imageThumb) return '';
                return JHTML::_('image', $item->imageThumb, $item->imageCaption);
        }
        
        public static function imageCaption($item, $params, $itemLayout = '', $source = '', $preventCleaning = false) {
                if (!$source) $source = $params->get('imagecaptionsource');
                
                if ($source == 'none') return '';
                
                $caption = '';
                
                switch ($source) {
                        case 'intro':
                                $caption = $item->intro;
                                break;
                        case 'title':
                                $caption = $item->title;
                                break;
                        case 'image':
                                $caption = $item->imageCaption;
                                break;
                        case 'layout':
                                ob_start();
                                require $itemLayout;
                                $caption = ob_get_contents();
                                ob_end_clean();
                                break;
                        default:
                                return '';
                                break;
                }
                
                if ($params->get('wordlimitcaption') && !$preventCleaning) {
                        $caption = K2HelperUtilities::wordLimit($caption, $params->get('wordlimitcaption'));
                }               
                
                return $caption;
        }
        
        public static function prepareList($items, $params, $componentParams, $format) {
		if (count($items)) {
                        $model = JModel::getInstance('item', 'K2Model');
                        
                        require_once JPATH_SITE.'/components/com_k2/helpers/permissions.php';
                        
                        $limitstart = $params->get('limitstart');
                        $catId = key($items);
                        $cats = null;
                        
                        if (!isset($items[$catId][0]->category) || !is_object($items[$catId][0]->category)) {
                                $cats = array_keys($items);
                                $cats = JprovenUtility::toIntArray($cats, true);
                                $query = 'SELECT * FROM #__k2_categories WHERE id IN ('.$cats.')';
                                $db = JFactory::getDbo();
                                $db->setQuery($query);
                                $cats = $db->loadObjectList('id');
                        }
                        
			foreach ($items as $catId => &$itemsPerCat) {
                                foreach ($itemsPerCat as &$item) {
                                        $notEmpty = !empty($item->introtext);
                                        if ($cats) $item->category = $cats[$catId];
                                        
                                        JprovenUtility::loadK2SpecificResources($catId, $item->id);
                                        
                                        //Clean title
                                        $item->title = JFilterOutput::ampReplace($item->title);

                                        //Images
                                        $item->image = $item->imageThumb = $item->imageCaption = '';
                                        
                                        if ($params->get('itemImage') == 'k2fields') {
                                                $imageFields = $params->get('imagefield');
                                                        
                                                if ($imageFields) {
                                                        $imageFields = array_unique($imageFields);
                                                        $imageFields = (array) $imageFields;
                                                        $fieldsModel = JModel::getInstance('fields', 'K2FieldsModel');
                                                        $imageFields = $fieldsModel->getFieldsById($imageFields, array('view' => 'module'));
                                                        K2FieldsModelFields::filterBasedOnView($imageFields, 'module');
                                                        $theImageField = null;
                                                        
                                                        foreach ($imageFields as $i => $imageField) {
                                                                $_cats = K2FieldsModelFields::value($imageField, 'cats');
                                                                $_cats = explode(',', $_cats);
                                                                
                                                                if (in_array($item->catid, $_cats)) {
                                                                        if (K2FieldsModelFields::value($imageField, 'mainmedia')) {
                                                                                $theImageField = $imageField;
                                                                                break;
                                                                        }
                                                                }
                                                        }
                                                        
                                                        if ($theImageField) {
                                                                $picplg = K2FieldsModelFields::value($theImageField, 'picplg');
                                                                $itemlistpicplg = K2FieldsModelFields::value($theImageField, 'itemlistpicplg');
                                                                K2FieldsModelFields::setValue($theImageField, 'picplg', 'source');
                                                                K2FieldsModelFields::setValue($theImageField, 'itemlistpicplg', 'source');
                                                                $medias = $fieldsModel->itemValues($item->id, K2FieldsModelFields::value($theImageField, 'id'));
                                                                $medias = K2FieldsMedia::render($item, $medias, $theImageField, $fieldsModel);
                                                                
                                                                if (is_array($medias)) {
                                                                        $medias = $medias[1][0][0];
                                                                        $item->image = JURI::base().$medias['src'];
                                                                        $item->imageThumb = JURI::base().$medias['thumb'];
                                                                        $item->imageCaption = $medias['caption'];
                                                                        $item->noMediaFields = true;
                                                                        
                                                                        if ($params->get('imagefieldshow', 'thumb') == 'thumb') {
                                                                                $item->image = $item->imageThumb;
                                                                        }
                                                                }
                                                                
                                                                K2FieldsModelFields::setValue($theImageField, 'picplg', $picplg);
                                                                K2FieldsModelFields::setValue($theImageField, 'itemlistpicplg', $itemlistpicplg);
                                                        }
                                                }
                                        }
                                        
                                        if ($params->get('itemImage') == 'k2') {        
                                                $date = JprovenUtility::createDate($item->modified);
                                                $timestamp = '?t='.$date->format('U');

                                                if (JFile::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.md5("Image".$item->id).'_XS.jpg')){
                                                        $item->imageXSmall = JURI::base(true).'/media/k2/items/cache/'.md5("Image".$item->id).'_XS.jpg';
                                                        if($componentParams->get('imageTimestamp')){
                                                                $item->imageXSmall.=$timestamp;
                                                        }
                                                }

                                                if (JFile::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.md5("Image".$item->id).'_S.jpg')){
                                                        $item->imageSmall = JURI::base(true).'/media/k2/items/cache/'.md5("Image".$item->id).'_S.jpg';
                                                        if($componentParams->get('imageTimestamp')){
                                                                $item->imageSmall.=$timestamp;
                                                        }
                                                }

                                                if (JFile::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.md5("Image".$item->id).'_M.jpg')){
                                                        $item->imageMedium = JURI::base(true).'/media/k2/items/cache/'.md5("Image".$item->id).'_M.jpg';
                                                        if($componentParams->get('imageTimestamp')){
                                                                $item->imageMedium.=$timestamp;
                                                        }					    
                                                }

                                                if (JFile::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.md5("Image".$item->id).'_L.jpg')){
                                                        $item->imageLarge = JURI::base(true).'/media/k2/items/cache/'.md5("Image".$item->id).'_L.jpg';
                                                        if($componentParams->get('imageTimestamp')){
                                                                $item->imageLarge.=$timestamp;
                                                        }	
                                                }

                                                if (JFile::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.md5("Image".$item->id).'_XL.jpg')){
                                                        $item->imageXLarge = JURI::base(true).'/media/k2/items/cache/'.md5("Image".$item->id).'_XL.jpg';
                                                        if($componentParams->get('imageTimestamp')){
                                                                $item->imageXLarge.=$timestamp;
                                                        }
                                                }

                                                if (JFile::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.md5("Image".$item->id).'_Generic.jpg')){
                                                        $item->imageGeneric = JURI::base(true).'/media/k2/items/cache/'.md5("Image".$item->id).'_Generic.jpg';
                                                        if($componentParams->get('imageTimestamp')){
                                                                $item->imageGeneric.=$timestamp;
                                                        }	
                                                }

                                                $image = 'image'.$params->get('itemImgSize', 'Small');
                                                if (isset($item->$image))
                                                        $item->image = $item->$image;
                                        }
                                        
                                        if ($params->get('itemImage') && empty($item->image)) {
                                                if ($params->get('defaultimage')) {
                                                        if ($params->get('defaultimage') != -1) {
                                                                $image = JURI::base().'media/mod_k2fields_contents/defaultimage/'.$params->get('defaultimage');

                                                                if (!$params->get('defaultimagethumb')) {
                                                                        $imageThumb = preg_replace('#\d+$#', '', $image);
                                                                        $imageThumb = str_replace('/defaultimage/', '/defaultimagethumb/', $imageThumb);
                                                                } else {
                                                                        $imageThumb = JURI::base().'media/mod_k2fields_contents/defaultimagethumb/'.$params->get('defaultimagethumb');
                                                                }

                                                                $item->image = $image.'.png';
                                                                $item->imageThumb = $imageThumb.'.png';
                                                        } else {
                                                                $item->image = JURI::base().'media/mod_k2fields_contents/noimage.png';
                                                                $item->imageThumb = $item->image;
                                                        }
                                                } else {
                                                        $item->image = $item->imageThumb = '';
                                                }
                                        }
                                        
                                        //Read more link
                                        $item->link = urldecode(JRoute::_(K2FieldsHelperRoute::getItemRoute($item->id.':'.urlencode($item->alias), $item->catid.':'.urlencode($item->categoryalias))));

                                        //Tags
                                        if ($params->get('itemTags')) {
                                                $tags = $model->getItemTags($item->id);
                                                for ($i = 0; $i < sizeof($tags); $i++) {
                                                        $tags[$i]->link = JRoute::_(K2FieldsHelperRoute::getTagRoute($tags[$i]->name));
                                                }
                                                $item->tags = $tags;
                                        }

                                        //Category link
                                        if ($params->get('itemCategory'))
                                        $item->categoryLink = urldecode(JRoute::_(K2FieldsHelperRoute::getCategoryRoute($item->catid.':'.urlencode($item->categoryalias))));

                                        //Extra fields
                                        if ($params->get('itemExtraFields')) {
                                                $item->extra_fields = $model->getItemExtraFields($item->extra_fields);
                                        }

                                        //Comments counter
                                        if ($params->get('itemCommentsCounter'))
                                        $item->numOfComments = $model->countItemComments($item->id);

                                        //Attachments
                                        if ($params->get('itemAttachments'))
                                        $item->attachments = $model->getItemAttachments($item->id);

                                        //Import plugins
                                        if ($format != 'feed') {
                                                $dispatcher = &JDispatcher::getInstance();
                                                JPluginHelper::importPlugin('content');
                                        }

                                        //Video
                                        if ($params->get('itemVideo') && $format != 'feed') {
                                                $params->set('vfolder', 'media/k2/videos');
                                                $params->set('afolder', 'media/k2/audio');
                                                $item->text = $item->video;
                                                $dispatcher->trigger('onPrepareContent', array(&$item, &$params, $limitstart));
                                                $item->video = $item->text;
                                        }
                                        
                                        $item->date = JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC2'));

                                        // Introtext
                                        $item->text = '';
                                        if ($params->get('itemIntroText')) {
                                                // Word limit
                                                if ($params->get('itemIntroTextWordLimit')) {
                                                        $item->text .= K2HelperUtilities::wordLimit($item->introtext, $params->get('itemIntroTextWordLimit'));
                                                } else {
                                                        $item->text .= $item->introtext;
                                                }
                                        }
                                        
                                        if ($format != 'feed') {
                                                $params->set('parsedInModule', 1); // for plugins to know when they are parsed inside this module
                                                $item->params = new JParameter($item->params);
                                                $item->params->merge($params);
                                                
                                                $cparams = new JParameter($item->category->params);

                                                if ($cparams->get('inheritFrom')) {
                                                        $masterCategory = JTable::getInstance('K2Category', 'Table');
                                                        $masterCategory->load($cparams->get('inheritFrom'));
                                                        $cparams = new JParameter($masterCategory->params);
                                                }
                                                
                                                $params->merge($cparams);                                                

                                                if($params->get('JPlugins', 1)){
                                                        //Plugins
                                                        $results = $dispatcher->trigger('onBeforeDisplay', array(&$item, &$params, $limitstart));
                                                        $item->event->BeforeDisplay = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onAfterDisplay', array(&$item, &$params, $limitstart));
                                                        $item->event->AfterDisplay = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onAfterDisplayTitle', array(&$item, &$params, $limitstart));
                                                        $item->event->AfterDisplayTitle = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onBeforeDisplayContent', array(&$item, &$params, $limitstart));
                                                        $item->event->BeforeDisplayContent = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onAfterDisplayContent', array(&$item, &$params, $limitstart));
                                                        $item->event->AfterDisplayContent = trim(implode("\n", $results));

                                                        $dispatcher->trigger('onPrepareContent', array(&$item, &$params, $limitstart));
                                                        
                                                        if ($params->get('itemIntroText')) {
                                                                $item->introtext = $item->text;
                                                        }
                                                } else {
                                                        $item->event->BeforeDisplay = '';
                                                        $item->event->AfterDisplay = '';
                                                        $item->event->AfterDisplayTitle = '';
                                                        $item->event->BeforeDisplayContent = '';
                                                        $item->event->AfterDisplayContent = '';
                                                }
                                                
                                                //Init K2 plugin events
                                                $item->event->K2BeforeDisplay = '';
                                                $item->event->K2AfterDisplay = '';
                                                $item->event->K2AfterDisplayTitle = '';
                                                $item->event->K2BeforeDisplayContent = '';
                                                $item->event->K2AfterDisplayContent = '';
                                                $item->event->K2CommentsCounter = '';

                                                if ($params->get('K2Plugins', 1)) {
                                                        //K2 plugins
                                                        JPluginHelper::importPlugin('k2');
                                                        $results = $dispatcher->trigger('onK2BeforeDisplay', array(&$item, &$params, $limitstart));
                                                        $item->event->K2BeforeDisplay = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onK2AfterDisplay', array(&$item, &$params, $limitstart));
                                                        $item->event->K2AfterDisplay = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onK2AfterDisplayTitle', array(&$item, &$params, $limitstart));
                                                        $item->event->K2AfterDisplayTitle = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onK2BeforeDisplayContent', array(&$item, &$params, $limitstart));
                                                        $item->event->K2BeforeDisplayContent = trim(implode("\n", $results));

                                                        $results = $dispatcher->trigger('onK2AfterDisplayContent', array(&$item, &$params, $limitstart));
                                                        $item->event->K2AfterDisplayContent = trim(implode("\n", $results));

                                                        $dispatcher->trigger('onK2PrepareContent', array(&$item, &$params, $limitstart));
                                                        
                                                        if ($params->get('itemIntroText')) {
                                                                $item->introtext = $item->text;
                                                        }
                                                        
                                                        if ($params->get('itemCommentsCounter')) {
                                                                $results = $dispatcher->trigger('onK2CommentsCounter', array ( & $item, &$params, $limitstart));
                                                                $item->event->K2CommentsCounter = trim(implode("\n", $results));
                                                        }
                                                } else {
                                                        $item->event->K2BeforeDisplay = '';
                                                        $item->event->K2AfterDisplay = '';
                                                        $item->event->K2AfterDisplayTitle = '';
                                                        $item->event->K2BeforeDisplayContent = '';
                                                        $item->event->K2AfterDisplayContent = '';
                                                }
                                        }

                                        //Clean the plugin tags
                                        $item->introtext = preg_replace("#{(.*?)}(.*?){/(.*?)}#s", '', $item->introtext);
                                        
                                        //Author
                                        if ($params->get('itemAuthor')) {
                                                if (! empty($item->created_by_alias)) {
                                                        $item->author = $item->created_by_alias;
                                                        $item->authorGender = NULL;
                                                        if ($params->get('itemAuthorAvatar'))
                                                        $item->authorAvatar = K2HelperUtilities::getAvatar('alias');
                                                } else {
                                                        $author = &JFactory::getUser($item->created_by);
                                                        $item->author = $author->name;
                                                        $query = "SELECT `gender` FROM #__k2_users WHERE userID=".(int)$author->id;
                                                        $db = JFactory::getDBO();
                                                        $db->setQuery($query, 0, 1);
                                                        $item->authorGender = $db->loadResult();
                                                        if ($params->get('itemAuthorAvatar')) {
                                                                $item->authorAvatar = K2HelperUtilities::getAvatar($author->id, $author->email, $componentParams->get('userImageWidth'));
                                                        }
                                                        //Author Link
                                                        $item->authorLink = JRoute::_(K2FieldsHelperRoute::getUserRoute($item->created_by));
                                                }
                                        }
                                        
                                        if (is_array($item->extra_fields)) {
                                                foreach($item->extra_fields as $key => $extraField) {
                                                        if($extraField->type == 'textarea' || $extraField->type == 'textfield') {
                                                                $tmp = new JObject();
                                                                $tmp->text = $extraField->value;
                                                                if($params->get('JPlugins',1)){
                                                                        $dispatcher->trigger('onContentPrepare', array ('mod_k2_content', &$tmp, &$params, $limitstart));
                                                                }
                                                                if($params->get('K2Plugins',1)){
                                                                        $dispatcher->trigger('onK2PrepareContent', array ( & $tmp, &$params, $limitstart));
                                                                }
                                                                $extraField->value = $tmp->text;
                                                        }
                                                }
                                        }                                        
                                }
			}
		}
                
		return $items;
        }
}
