<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2FieldsList {
        var 
                $_name = '#__k2_extra_fields_list_values', 
                $_primary = 'id', 
                $_left = 'lft', 
                $_right = 'rgt', 
                $_value = 'val',
                $_image = 'img',
                $_db = null,
                $_levelIndicator = '> '; 
        
	public function __construct($db = null) {
                if (empty($db)) $db = JFactory::getDBO();
                
                $this->_db = $db;
                $this->_name = $db->replacePrefix($this->_name);
	}
        
        public function render($item, $values, $field, $helper, $rule) {
                $view = JRequest::getCmd('view') == 'itemlist' ? 'list' : '';
                $show = $helper->value($field, $view.'listformat');
                
                if (empty($show) && $view == 'list') 
                        $show = $helper->value($field, 'listformat');
                
                if (!empty($show)) {
                        $leaf = count($values);
                        $levels = $helper->value($field, 'levels');
                        $search = array_merge(array('root', 'leaf', 'parent'), $levels);
                        $replace = array_merge(array(1, $leaf, $leaf-1), range(1, count($levels)));
                        $show = str_replace($search, $replace, $show);
                        $show = explode(',', $show);
                        $_values = array();
                        
                        foreach ($values as $i => $value) {
                                if (in_array($i+1, $show)) $_values[] = $value;
                        }
                        
                        $values = $_values;
                }
                
                return $helper->renderGeneric($item, $values, $field, $helper, $rule);
        }
        
        public function createField($field, $value) {
                // if autocompleter provide autocompleter
                // render normal
        }
        
        function processRequest($task, $id) {
                $format = 'json';
                $result = '';
                
                if ($task == 'field') {
                        if (empty($id)) {
                                $result = array('error' => 'Missing field ID');
                        } else {
                                $model = JModel::getInstance('fields', 'K2FieldsModel');
                                $fields = $model->getFieldsById($id);
                                $result = $this->getList($fields, $format);
                        }
                }
                
                if ($format == 'json') $result = json_encode($result);
                
                return $result;
        }
        
        public function getValue($node) {
                $primary = $this->_quoteIdentifier($this->_primary);
                $name = $this->_quoteIdentifier($this->_name);
                $value = $this->_quoteIdentifier($this->_value);
                
                $query = '
                        SELECT node.'.$value.' AS value, node.lat, node.lng, node.img
                        FROM '.$name.' AS node
                        WHERE node.'.$primary.'='.(int) $node;
                
                $this->_db->setQuery($query);
                
                return $this->_db->loadObject();
        }
        
        public static function getParameters($field = null, $options = null) {
                if (empty($options)) $options = $field;
                
                static $fields = array();
                
                $fieldId = K2FieldsModelFields::value($options, 'id');
                
                if ($fieldId == -1) {
                        $fieldId = K2FieldsModelFields::value($options, 'subfieldof').'_'.K2FieldsModelFields::value($options, 'position');
                }
                
                if (isset($fields[$fieldId])) return $fields[$fieldId];
                
                $options['node'] = K2FieldsList::getSetting('node', $options, true, null);
                
                if (empty($options['node'])) {
                        $source = K2FieldsModelFields::setting('source', $options);
                        $list = new K2FieldsList();
                        $name = $list->_quoteIdentifier($list->_name);
                        $primary = $list->_quoteIdentifier($list->_primary);
                        $left = $list->_quoteIdentifier($list->_left);
                        $right = $list->_quoteIdentifier($list->_right);
                        
                        $query = 
                                'SELECT '.$primary.
                                ' FROM '.$name.
                                ' WHERE list = '.$source.
                                ' AND lft = (SELECT MIN('.$left.') FROM '.$name.' WHERE list = '.$source.')'.
                                ' AND rgt = (SELECT MAX('.$right.') FROM '.$name.' WHERE list = '.$source.')'.
                                ' LIMIT 1'
                                ;
                        
                        $db = JFactory::getDBO();
                        $db->setQuery($query);
                        $options['node'] = $db->loadResult();
                }
                
                $options['label'] = K2FieldsList::getSetting('label', $options, false);
                if (!$options['label'])
                        $options['label'] = K2FieldsList::getSetting('name', $options);
                $options['value'] = K2FieldsList::getSetting('value', $options, true, null);
                $options['level'] = K2FieldsList::getSetting('level', $options, true, null);
                $options['depth'] = K2FieldsList::getSetting('depth', $options, true, 1);
                $options['showvalue'] = K2FieldsList::getSetting('showvalue', $options, false, 'fullpath');
                $options['task'] = K2FieldsList::getSetting('task', $options, true, 'field');
                
                if (!K2FieldsModelFields::isFalse($options, 'loadtree', false)) {
                        $source = K2FieldsModelFields::setting('source', $options);
                        $db = JFactory::getDBO();
                        $list = new K2FieldsList();
                        $query = $list->treeQuery($source, true, true, ' WHERE (node.depth = parent.depth + 1 OR node.depth = 0)', 'limited');
                        $db->setQuery($query);
                        $options['tree'] = $db->loadObjectList();
                }
                
                $fields[$fieldId] = $options;
                
                return $options;
        }        
        
        protected static function getSetting($name, $options = null, $getFromRequest = true, $default = null) {
                if (empty($options)) return plgk2k2fields::param($name);
                
                $val = '';
                
                if (isset($options[$name])) $val = $options[$name];
                
                if ($val == '' && $getFromRequest) $val = JRequest::getString($name);
                
                if ($val == '') $val = plgk2k2fields::param($name);
                
                if ($val == '') $val = $default;
                
                return $val;
        }
        
        public function getList($fieldOptions, $format = 'json', $cols = null) {
                if ($fieldOptions['task'] == 'retrievepath') {
                        $fieldOptions['depth'] = -1;
                }
                
                $options = $this->getTree($fieldOptions['source'], $fieldOptions['node'], $fieldOptions['level'], $fieldOptions['depth'], true, $cols);
                $result = null;
                
                if ($format == 'html') {
                        if (JPluginHelper::importPlugin('k2', 'k2fields')) {
                                $pre = plgk2k2fields::getFieldPrefix();
                        } else {
                                $pre = 'K2ExtraField_';
                        }             

                        $result = JHTML::_(
                                'select.genericlist', 
                                $options, 
                                $pre.$fieldOptions['id'],
                                null, 
                                $this->_primary, 
                                $fieldOptions['label'],
                                $fieldOptions['value']
                        );
                } else if ($format == 'array' || $format == 'json') {
                        $result = $options;
                }
                
                return $result;
        }
        
        /*
         * root => parent = 0
         * 
         * in addition to calling insert calculates depth and 
         */
        public function add($list, $value, $image, $parent, $published = 1, $dontRecalc = false) {
                $op = $this->getOp();
                
                $status = $op->insert(
                        array(
                                'list'       => $list,
                                $this->_value   => $value,
                                $this->_image   => $image,
                                'published'     => $published
                        ),
                        $parent
                );
                
                if ($status && !$dontRecalc) {
                        return $this->precalc($list);
                }
                
                return false;
        }
        
        private function getOp() {
                static $op = null;
                if (empty($op)) {
                        require_once dirname(__FILE__).'/listop.php';
                        $op = new K2FieldsListOp($this->_name, $this->_primary, $this->_left, $this->_right);
                }
                return $op;
        }
        
        public function remove($node, $dontRecalc = false) {
                $name = $this->_quoteIdentifier($this->_name);
                $this->_db->setQuery('SELECT list FROM '.$name.' WHERE id = '.(int)$node);
                $list = $this->_db->loadResult();
                
                if (empty($list)) return;
                
                $op = $this->getOp();
                $status = $op->deleteNode($node, true);
                
                if ($status && !$dontRecalc) return $this->precalc($list);
                
                return $status;
        }
        
        public function findNode($list, $nodeValue) {
                $name = $this->_quoteIdentifier($this->_name);
                $value = $this->_quoteIdentifier($this->_value);
                $primary = $this->_quoteIdentifier($this->_primary);
                $this->_db->setQuery('SELECT '.$primary.' FROM '.$name.' WHERE list = '.(int)$list.' AND '.$value.' = '.$this->_db->Quote($nodeValue));
                $node = $this->_db->loadResult();
                return $node;
        }
        
        public function findLists($nodes) {
                $nodes = array_unique($nodes);
                $nodes = implode(',', $nodes);
                $name = $this->_quoteIdentifier($this->_name);
                $this->_db->setQuery('SELECT DISTINCT list FROM '.$name.' WHERE id IN ('.$nodes.')');
                $lists = $this->_db->loadResultArray();
                return $lists;
        }
        
        function getTree($list, $node = null, $level = null, $depth = 1, $includeParent = false, $immediateChildren = true, $cols = null, $result = 'result') {
                if (!isset ($node) || isset($level)) $includeParent = true;
                if (!is_numeric($level)) $level = null;
                if (!is_numeric($node)) $node = null;
                if (!is_numeric($depth)) $depth = 1;
                
                $where = '';
                $isPath = false;
                
                if (isset($level)) $where .= ' AND node.depth = ' . (int) $level;
                
                if (!isset($node)) {
                        if (!isset($level)) $where .= ' AND parent.depth = 0 AND node.depth = 0';
                } else {
                        if ($depth == -1) {
                                $parentNode = $this->path($node, 'list', 'query');
                                $isPath = true;
                        } else {
                                $parentNode = (int) $node;
                        }
                        
                        $where .= ' AND parent.id IN (' . $parentNode . ')';
                }
                
                if (!isset($level) && !$isPath && $depth > 0 && $immediateChildren) {
                        $depthIncr = $includeParent ? '' : '+ 1';
                        $where .= ' AND node.depth BETWEEN parent.depth ' . $depthIncr . ' AND parent.depth + ' . (int) $depth;
                }
                
                if ($isPath) {
                        $left = $this->_quoteIdentifier($this->_left);
                        $where .= ' AND ((parent.depth = node.depth - 1 AND parent.id <> node.id) OR node.depth = 0) ORDER BY node.depth ASC, node.'.$left.' ASC';
                } else {
                        $primary = $this->_quoteIdentifier($this->_primary);
                        $where .= ' GROUP BY node.'.$primary;
                }
                
                $query = $this->treeQuery($list, false, $includeParent, $where, $cols);
                
                if ($result == 'query') return $query;
                
                $this->_db->setQuery($query);
                $result = $this->_db->loadObjectList();
                
                if (empty($result)) return array();
                
                return $result;
        }
        
        /**
         *
         * @param type $list
         * @param type $finalize
         * @param type $includeParent
         * @param type $where
         * @param type $cols = array of columns, empty (all) or limited
         * @return string 
         */
        public function treeQuery($list, $finalize = false, $includeParent = false, $where = '', $cols = null) {
                if (is_array($cols)) {
                        if (!in_array($this->_primary, $cols)) {
                                array_unshift ($cols, $this->_primary);
                        }
                        
                        foreach ($cols as &$col) {
                                $col = 'node.'.$this->_quoteIdentifier($col);
                        }
                        
                        $cols = implode(', ', $cols);
                } else {
                        $primary = $this->_quoteIdentifier($this->_primary);
                        $value = $this->_quoteIdentifier($this->_value);
                        $image = $this->_quoteIdentifier($this->_image);
                        
                        $isLimited = !empty($cols);
                        
                        $cols = '
                                node.'.$primary.' AS value, 
                                node.'.$value.' AS text, 
                                node.'.$image.' AS img, 
                                node.depth, 
                                parent.'.$primary.' as parent_id
                                ';
                        
                        if (!$isLimited) $cols .= ',
                                node.path,
                                node.fullpath,
                                parent.'.$value.' as parent_value, 
                                parent.depth as parent_depth
                                ';
                        
                }
                
                $name = $this->_quoteIdentifier($this->_name);
                $left = $this->_quoteIdentifier($this->_left);
                $right = $this->_quoteIdentifier($this->_right);
                
                $query = 
                'SELECT '.$cols.
                ' FROM '.$name.' AS parent JOIN '.$name.' AS node ON parent.published = 1 AND node.published = 1 AND node.`list` = parent.`list` AND parent.`list` = '.$list.' AND node.'.$left.' BETWEEN parent.'.$left.($includeParent ? '' : ' + 1').' AND parent.'.$right.' '.$where;
                
                if ($finalize) {
                        $primary = $this->_quoteIdentifier($this->_primary);
                        $query .= ' GROUP BY node.'.$primary;
                }
                
                return $query;
        }
        
        /**
         *
         * @param type $key search word assumed to be correctly quoted and post/prefixed with wildcard character
         * @param type $field
         * @param type $isSearch
         * @param type $result
         * @return string 
         */
        public function completePath($key, $field, $isSearch = false, $result = 'query') {
                $primary = $this->_quoteIdentifier($this->_primary);
                $left = $this->_quoteIdentifier($this->_left);
                $right = $this->_quoteIdentifier($this->_right);
                $name = $this->_quoteIdentifier($this->_name);
                $value = $this->_quoteIdentifier($this->_value);
                
                $query = '
                        SELECT node.fullpath AS value, GROUP_CONCAT(parent.'.$primary.' SEPARATOR "'. K2FieldsModelFields::VALUE_SEPARATOR.'") AS ovalue
                        FROM '.$name.' AS node, '.$name.' AS parent
                        WHERE node.'.$left.' BETWEEN parent.'.$left.' AND parent.'.$right.' AND node.list = '.(int) $field .' AND node.'.$value.' LIKE '.$key.'
                        ORDER BY node.'.$primary.', parent.'.$left.'
                        '
                        ;  
                
                if ($result == 'query') {
                        return $query;
                }
                
                $this->_db->setQuery($query);
                return $this->_db->loadObjectList();
        }
        
        public function path($node, $part = 'list', $result = 'query') {
                $primary = $this->_quoteIdentifier($this->_primary);
                $left = $this->_quoteIdentifier($this->_left);
                $right = $this->_quoteIdentifier($this->_right);
                $name = $this->_quoteIdentifier($this->_name);
                $indicator = $this->_levelIndicator;
                $value = $this->_quoteIdentifier($this->_value);
                
                if ($part = 'list') {
                        $select = 'parent.'.$primary;
                } else {
                        $select = 'GROUP_CONCAT(parent.'.$value.' SEPARATOR "'.$indicator.'")';
                }
                
                $query = '
                        SELECT '.$select.'
                        FROM '.$name.' AS node, '.$name.' AS parent
                        WHERE node.'.$left.' BETWEEN parent.'.$left.' AND parent.'.$right.' AND node.'.$primary.' = '.(int) $node .'
                        ORDER BY parent.'.$left.'
                        ';
                
                if ($result == 'query') {
                        return $query;
                }
                
                $this->_db->setQuery($query);
                
                return $part = 'list' ? $this->_db->loadObjectList() : $this->_db->loadResult();
        }
        
        protected function _quoteIdentifier($identifier) {
                return $this->_db->nameQuote($identifier);
        }
        
        protected function _quote($value) {
                return $this->_db->Quote($value);
        }
        
        public function import($file, $sep = '%%') {
                jimport('joomla.filesystem.path');
                jimport('joomla.filesystem.file');
                
                $file = JPath::clean(JPATH_SITE.'/'.$file);
                
                if (!JFile::exists($file)) return;
                
                $imports = JFile::read($file);
                
                if (empty($imports)) return;
                
                $imports = explode("\n", $imports);
                $fields = array();
                $nodes = array();
                
                $ordered = array();
                
                foreach ($imports as &$import) {
                        $import = explode($sep, $import);
                        $list = $import[0];
                        
                        if ($list == '-') {
                                // format 1: $record[1] == node id
                                // format 2: $record[1] == field id, $record[2] == value                                
                                $node = $imports[1];
                                
                                if (count($import) == 3) {
                                        $fields[] = $import[1];
                                        $node = $this->findNode($import[1], $import[2]);
                                } else {
                                        $nodes[] = $node;
                                }
                                
                                if (!empty($node)) {
                                        $this->remove($node, true);
                                }                                
                        } else {
                                $value = trim($import[2]);
                                $parent = trim($import[1]);
                                $image = count($import) == 5 ? trim($import[3]) : '';
                                
                                if (!isset($ordered[$list])) $ordered[$list] = array();
                                
                                $ordered[$list][$value] = array($parent, $image, 0);
                        }
                }
                
                foreach ($ordered as $list => &$values) {
                        foreach ($values as $value => &$record) {
                                $this->_import($values, $list, $value);
                        }
                }
                
                if (!empty($nodes)) {
                        $_fields = $this->findLists($nodes);
                        $fields = array_merge($fields, $_fields);
                }
                
                if (!empty($fields)) {
                        $fields = array_unique($fields);
                        
                        foreach ($fields as $field) {
                                $this->precalc($field);
                        }
                }
        }    
        
        protected function _import(&$listValues, $list, $value) {
                list($parent, $image, $inserted) = $listValues[$value];
                
                if ($inserted === 0) {
                        if (strpos($parent, 'infile:') === 0) {
                                $parentInfile = str_replace('infile:', '', $parent);
                                
                                if ($parentInfile == '') return;
                                
                                // Check if it is already inserted and get the node
                                $parent = $this->findNode($list, $parentInfile);
                                
                                if (!$parent) {
                                        // If not already inserted do insert
                                        $this->_import($listValues, $list, $parentInfile);
                                        $parent = $listValues[$parentInfile][2];
                                }
                        } else if (strpos($parent, 'value:') === 0) {
                                $parent = str_replace('value:', '', $parent);
                                $parent = $this->findNode($list, $parent);
                                
                                if (!$parent) return;
                        } else if (!empty($parent)) {
                                $parent = (int) $parent;
                        }
                        
                        $this->add($list, $value, $image, $parent, 1, false);
                        $listValues[$value][2] = $this->findNode($list, $value);
                }
        }
        
        public function precalc($list) {
                $primary = $this->_quoteIdentifier($this->_primary);
                $left = $this->_quoteIdentifier($this->_left);
                $right = $this->_quoteIdentifier($this->_right);
                $name = $this->_quoteIdentifier($this->_name);
                $value = $this->_quoteIdentifier($this->_value);
                $pathIndicator = $this->_quote($this->_levelIndicator);
                $fullPathIndicator = $this->_quote(' '.$this->_levelIndicator);
                
                $fullPathQuery = '
                        (
                        SELECT GROUP_CONCAT(parent.'.$value.' SEPARATOR '.$fullPathIndicator.')
                        FROM '.$name.' AS node, '.$name.' AS parent
                        WHERE node.'.$left.' BETWEEN parent.'.$left.' AND parent.'.$right.' AND node.'.$primary.' = d.'.$primary.'
                        ORDER BY parent.'.$left.'
                        )
                        ';
                
                $query = 
                        '
                                UPDATE 
                                '.$name.' AS v, 
                                (
                                        SELECT d.'.$primary.', d.depth, CONCAT(REPEAT('.$pathIndicator.', d.depth), d.'.$value.') AS path, '.$fullPathQuery.' AS fullpath
                                        FROM 
                                        (
                                                SELECT node.'.$primary.', node.'.$value.', (COUNT(parent.'.$value.') - 1) AS depth
                                                FROM '.$name.' AS node, '.$name.' AS parent
                                                WHERE node.'.$left.' BETWEEN parent.'.$left.' AND parent.'.$right.' AND node.list = '.(int) $list.'
                                                GROUP BY node.'.$value.'
                                        ) AS d
                                ) AS r
                                SET v.depth = r.depth, v.path = r.path, v.fullpath = r.fullpath
                                WHERE v.'.$primary.' = r.'.$primary.' AND v.list = '.(int) $list.'
                        ';
                
                $this->_db->setQuery($query);
                
                return $this->_db->query() !== false;
        }
}

?>
