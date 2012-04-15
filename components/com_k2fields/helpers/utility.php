<?php
// $Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');

if (class_exists('JprovenUtility')) return;

class JprovenUtility {
        /**
         * assists in overwriting values in items table by taking values from 
         * extra_fields_values for a certain field and category by aggregating it back
         */
        static public function reverseFromValues($fieldId, $catId) {
                $query = "
select vvv.itemid, group_concat(vvv.val separator '=:=:=-:-:-') as ef, ii.extra_fields as oef
from (
	select vv.itemid, vv.listindex, group_concat(vv.val separator '%%') as val
	from (
		select v.itemid, v.listindex, v.partindex, group_concat(v.value separator '-%-%-') as val
		from jos_k2_items i inner join jos_k2_extra_fields_values v on i.id = v.itemid and i.catid = ".$catId." and v.fieldid = ".$fieldId."
		group by v.itemid, v.listindex, v.partindex
		order by v.itemid, v.listindex, v.partindex, v.`index` asc
	) as vv
	group by vv.itemid, vv.listindex
) as vvv inner join jos_k2_items ii on vvv.itemid = ii.id
group by vvv.itemid
";
                $db = JFactory::getDBO();
                $db->setQuery($query);
                $items = $db->loadObjectList('itemid');
                
                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                $row = JTable::getInstance('K2Item', 'Table');
                
                foreach ($items as $itemId => $item) {
                        $vals = json_decode($item->oef);
                        
                        foreach ($vals as $val) {
                                if ($val->id == $fieldId) {
                                        $val->value = $item->ef;
                                        $vals = json_encode($vals);
                                        $row->load($itemId);
                                        $row->extra_fields = $vals;
                                        $row->store();
                                        break;
                                }
                        }
                }
        }
        /**
         * loads appropriate controller for the provided extension
         * controller to load is determined in the following order
         *      request view variable (per convention implicitly the controller)
         *      request controller variable
         *      given default view
         */
        static public function loader($extension, $defaultView, $forceAdmin = false) {
                jimport('joomla.filesystem.file');
                jimport('joomla.filesystem.path');

                $controller = JRequest::getWord('view', JRequest::getWord('controller', $defaultView));
                $app = JFactory::getApplication();
                $loc = $app->isSite() && !$forceAdmin ? JPATH_SITE : JPATH_ADMINISTRATOR;
                $loc .= '/components/com_' . strtolower($extension) . '/controllers/' . $controller . '.php';
                $loc = JPath::clean($loc);

                if (JFile::exists($loc)) {
                        require_once $loc;
                        $controller = ucfirst($controller);
                        $extension = ucfirst($extension);
                        $classname = $extension . 'Controller' . $controller;
                        $controller = new $classname();
                        $task = JRequest::getWord('task');
                        $controller->execute($task);
                        $controller->redirect();
                }
        }
        
        public static function buildUrl($query, $excludes = array()) {
                $url = array();
                foreach ($query as $k => $v) 
                        if (!in_array($k, $excludes))
                                $url[] = $k.'='.$v;
                return implode('&', $url);
        }
        
        public static function getK2PostCategoriesSelector($selectorID, $firstElement) {
                $module = JprovenUtility::getModule('mod_k2fields', false);
                $params = $module->params;
                $defaultCategory = $params->get('defaultcategory', 0);
                $categoryselector = $params->get('categoryselector', 1);
                $includedefaultmenuitem = $params->get('includedefaultmenuitem', 1);
                $excludes = $params->get('excludecategories', array());
                $categoryselector = $params->get('categoryselector', 1);

                if (!empty($excludes)) {
                        $excludes = (array) $excludes;
                        foreach ($excludes as &$exclude) $exclude = (int) $exclude;
                }

                $categories_options = JprovenUtility::getK2CategoriesSelector(
                        $categoryselector, 
                        $defaultCategory, 
                        $excludes, 
                        $includedefaultmenuitem,
                        $selectorID,
                        true,
                        $firstElement,
                        false,
                        true
                ); 
                
                return $categories_options;
        }
        
        public static function getPage($order = 1, $isClass = true) {
                $menus = JSite::getMenu();
                $active = $menus->getActive();
                
                if (isset($active) && $active->menutype == 'mainmenu') {
                        $items = $menus->getItems('menutype', 'mainmenu');
                        
                        if ($order == 'default') {
                                $default = $menus->getDefault();
                        } else if ($order == 'first' || $order == 1) {
                                $default = $items[0];
                        } else if ($order == 'last' || $order == -1) {
                                $default = $items[count($items) - 1];
                        } else if ($order <= count($items)) {
                                $default = $items[$order - 1];
                        }
                        
                        if (isset($default) && $active->id == $default->id) {
                                return $isClass ? 'target-page' : true;
                        } else {
                                return $isClass ? 'no-target-page' : false;
                        }
                } else {
                        return $isClass ? 'no-target-page' : false;
                }
        }
        
        public static function getK2CurrentCategory($defaultCategory, $isBasedOnMenu = true, $includeDefaultMenuItem = false) {
                $option = JRequest::getCmd('option');
                
                if ($option == 'com_k2') {
                        $view = JRequest::getCmd('view');
                        $task = JRequest::getCmd('task');
                        
                        if ($view == 'item') {
                                $isK2item = JRequest::getBool('k2item', false);
                                
                                if ($isK2item) {
                                        $catid = JRequest::getInt('k2cat');
                                } else if ($task == 'add' || $task == 'edit') {
                                        JModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/models');
                                        $id = JRequest::getInt('cid');
                                        
                                        if ($id) {
                                                $item = JModel::getInstance('item', 'K2Model');
                                                $item = $item->getData();
                                                $catid = $item->catid;
                                        } else {
                                                $catid = JRequest::getInt('catid');
                                        }
                                } else {
                                        JModel::addIncludePath(JPATH_SITE.'/components/com_k2/models');
                                        $item = JModel::getInstance('item', 'K2Model');
                                        $item = $item->getData();
                                        $catid = $item->catid;
                                }
                        } else if ($task == 'category') {
                                $catid = JRequest::getInt('id');
                        }
                        
                        if (isset($catid) && $isBasedOnMenu) {
                                $menu = JSite::getMenu();
                                $default = $menu->getDefault();
                                $menuItems = $menu->getItems('menutype', $default->menutype);
                                $found = false;
                                $c = '';
                                
                                foreach ($menuItems as $menuItem) {
                                        if (!$includeDefaultMenuItem && $menuItem->id == $default->id) continue;
                                        
                                        if ($menuItem->component == 'com_k2' && $menuItem->query['view'] == 'itemlist') {
                                                $c = $menuItem->query['id'];
                                        } else if ($menuItem->component == 'com_k2fields' && $menuItem->query['view'] == 'itemlist') {
                                                $c = $menuItem->query['cid'];
                                        }
                                        if ($found = ($c == $catid)) break;
                                }
                                
                                if (!$found) $catid = null;
                        }
                }
                
                if (!isset($catid)) {
                        $catid = 
                                $option == 'com_k2fields' ? 
                                JRequest::getInt('cid', $defaultCategory) : 
                                $defaultCategory;
                }
                
                return $catid;
        }
        
        protected static function getK2CategoriesSelectorMenuItem($menuItem, $currentCatid, $prefix, $includeNonK2, $checkPermission) {
                $component = $menuItem->component;
                
                if ($component == 'com_k2' && $menuItem->query['view'] == 'itemlist') {
                        $catId = $menuItem->query['id'];
                        $state = '';
                } else if ($component == 'com_k2fields' && $menuItem->query['view'] == 'itemlist') {
                        $catId = $menuItem->query['cid'];
                        $state = self::buildUrl($menuItem->query, array('option', 'view', 'task', 'layout', 'cid'));
                        //$state = str_replace('index.php?option=com_k2fields&view=itemlist&task=search&', '', $menuItem->link);
                } else if ($includeNonK2) {
                        $catId = '';
                        $state = $menuItem->link;
                } else {
                        return '';
                }
                
                $nameAttr = K2_JVERSION == '16' ? 'title' : 'name';
                $name = explode(' || ', $menuItem->$nameAttr);
                
                if ($checkPermission && $catId != '' && !K2HelperPermissions::canAddItem($catId)) {
                        return '';
                }
                
                return 
                        array(
                            $catId ? array($catId, $name[0]) : '', 
                            '<option value="'.$catId.'" init-state="'.$state.'" init-itemid="'.$menuItem->id.'" '.
                                (!empty($currentCatid) && $currentCatid == $catId ? ' selected="selected" ' : '').
                                ($menuItem->type == 'separator' ? ' disabled="disabled" ' : '').
                                '>' .
                                str_repeat($prefix, count($menuItem->tree) - 1) . $name[0] .
                                '</option>'
                        );
        }
        
        public static function getK2CategoriesSelector(
                $categoryselector, 
                $defaultCategory, 
                $excludes, 
                $includeDefaultMenuItem, 
                $categoriesId = 'cid',
                $checkPermission = false,
                $firstElement = '',
                $onlyValues = false,
                $overrideSelector = true
        ) {
                if ($categoriesId == 'cid' || $categoriesId == 'catid') 
                        $currentCatid = self::getK2CurrentCategory($defaultCategory, $categoryselector == 2, $includeDefaultMenuItem);
                else 
                        $currentCatid = '';
                
                $cats = array();
                
                $prefix = '&nbsp;&nbsp;- ';
                $maintainMenuHierarchy = true;
                $includeNonK2 = false;
                $user = JFactory::getUser();
                
                // if not public and if backend access
                
                if ($overrideSelector && $user->authorise('core.admin', 'com_k2')) $categoryselector = 1;
                
                if ($categoryselector == 2) {
                        $menu = JApplication::getMenu('site');
                        $default = $menu->getDefault();
                        $menuItems = $menu->getItems('menutype', $default->menutype);
                        $menuItems = JprovenUtility::indexBy($menuItems, 'id');
                        $menuItems = JprovenUtility::getColumn($menuItems, 0, false, array(), true);
                        $menuItemsOrdered = array();
                        
                        foreach ($menuItems as $itemId => $menuItem) {
                                if ($menuItem->parent_id == 1) {
                                        $menuItemsOrdered[$itemId] = $menuItem;
                                        unset($menuItems[$itemId]);
                                }
                                
                                foreach ($menuItems as $_itemId => $_menuItem) {
                                        if ($_menuItem->parent_id == $itemId) {
                                                $menuItemsOrdered[$_itemId] = $_menuItem;
                                                unset($menuItems[$_itemId]);
                                        }
                                }
                        }
                        
                        $menuItems = $menuItemsOrdered;
                        $categories = array();
                        
                        foreach ($menuItems as $itemId => $menuItem) {
                                if (!$includeDefaultMenuItem && $menuItem->id == $default->id) continue;
                                
                                $option = self::getK2CategoriesSelectorMenuItem($menuItem, $currentCatid, $prefix, $includeNonK2, $checkPermission);
                                
                                if (empty($option)) continue;
                                
                                $categories[$itemId] = $option[1];
                                $cats[] = $option[0];
                        }
                        
//                        $parentRendered = array();
//                        $rendered = array();
//                        
//                        if (false && $maintainMenuHierarchy) {
//                                foreach ($categories as $itemId => $category) {
//                                        $menuItem = $menuItems[$itemId];
//                                        $tmp = array_pop($menuItem->tree);
//                                        $tree = $menuItem->tree;
//                                        $parents = '';
//                                        
//                                        if (count($tree) > 0) {
//                                                foreach ($tree as $parent) {
//                                                        if (in_array($parent, $parentRendered)) continue;
//                                                        
//                                                        $parentRendered[] = $parent;
//                                                        
//                                                        if (isset($categories[$parent])) {
//                                                                $_parent = $categories[$parent];
//                                                                unset($categories[$parent]);
//                                                        } else {
//                                                                $_parent = $menuItems[$parent];
//                                                                $_parent = self::getK2CategoriesSelectorMenuItem($_parent, $menuItems, '', $prefix, true, $checkPermission);
//                                                        }
//                                                        
//                                                        $parents .= $_parent[1];
//                                                        $cats[] = $_parent[0];
//                                                }
//                                                
//                                                $categories[$itemId] = $parents . $categories[$itemId];
//                                        }
//                                        
//                                        if ($tmp) array_push($menuItem->tree, $tmp);
//                                }
//                        }
                        
                        if ($firstElement) array_unshift($categories, '<option value="" selected="selected">'.JText::_($firstElement).'</option>');
                        
                        if ($onlyValues) return array_filter($cats);
                        
                        $categories = '<select id="'.$categoriesId.'" name="'.$categoriesId.'">'.implode('', $categories).'</select>';
                        
                        return $categories;
                } else {
                        jimport('joomla.application.component.model');
                        JModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/models/');
                        $categoriesModel = JModel::getInstance('categories', 'K2Model');
                        $categories = $categoriesModel->categoriesTree(null, true);
                }
                
                if ($firstElement) {
                        $firstElement = JHTML::_('select.option', 0, JText::_($firstElement));
                        array_unshift($categories, $firstElement);
                }
                
                $foundExcluded = false;
                $parentDepth = -1;
                $remove = '&nbsp;&nbsp;&nbsp;';
                $cats = array();
                $excludes = (array) $excludes;
                
                foreach ($categories as $i => $category) {
                        $category->text = preg_replace('#^'.$remove.'#', '', $category->text);
                        $parts = explode($prefix, $category->text);
                        $depth = count($parts);

                        if (in_array($category->value, $excludes)) {
                                $foundExcluded = true;
                                $parentDepth = $depth;
                        } else if (!$foundExcluded || $depth <= $parentDepth) {
                                $cats[] = $category;
                                $foundExcluded = false;
                                $parentDepth = -1;
                        }
                }
                
                if ($onlyValues) return $cats;
                
                $categories = JHTML::_('select.genericlist', $cats, $categoriesId, '', 'value', 'text', $currentCatid);
                
                return $categories;
        }        

        public static function renderK2fieldsForm($fields, $type, $addJSToDoc = false, $cat = null, $item = null) {
                $output = '';
                
                if (count($fields) > 0) {
                        $counter = 0;
                        $model = JModel::getInstance('fields', 'K2FieldsModel');

                        if ($type == 'searchfields') {
                                $output .= '<ul class="admintable" id="extraFields">';
                                
                                if (count($fields)){
                                        foreach ($fields as $field){
                                                if (!K2FieldsModelFields::isFormField($field)) continue;
                                                
                                                $output .= '<li><label class="key">'.$field->name.'</label>';
                                                $output .= '<span>'.$model->renderField($field, null, 'search').'</span></li>';
                                                
                                                $counter++;
                                        }
                                }
                                $output .= '</ul>';
                        } else {
                                if (count($fields)){
                                        $output = array();
                                        
                                        foreach ($fields as $field){
                                                if (!K2FieldsModelFields::isFormField($field)) continue;
                                                
                                                $section = K2FieldsModelFields::value($field, 'section');
                                                
                                                if (empty($section))
                                                        $section = K2FieldsModelFields::setting('emptysectionname');
                                                
                                                if (!isset($output[$section])) 
                                                        $output[$section] = '';
                                                
                                                $output[$section] .=
                                                         '<tr><td align="right" class="key">'.$field->name.'</td>'
                                                        .'<td>'.$model->renderField($field, $item, $type).'</td></tr>';
                                                
                                                $counter++;
                                        }
                                        
                                        foreach ($output as $section => &$out) {
                                                $sectionId = preg_replace('#[^a-z0-9]#i', '', $section);
                                                $sectionId = strtolower($sectionId);
                                                $section = JText::_($section);
                                                $out = '<table class="admintable extraFields" id="section_'.$sectionId.'" section="'.  htmlentities($section).'">'.$out.'</table>';
                                        }
                                        
                                        if (!$cat && $item) {
                                                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                                                $row = JTable::getInstance('K2Item', 'Table');
                                                $row->load($item);
                                                $cat = $row->catid;
                                        }
                                        
                                        if ($cat) {
                                                $sectionsOrder = K2FieldsModelFields::categorySetting($cat, 'sectionsorder');
                                                
                                                if ($sectionsOrder) {
                                                        $key = self::firstKey($sectionsOrder);
                                                        $sectionsOrder = self::first($sectionsOrder);
                                                        $sectionsOrder = $sectionsOrder[0];
                                                        $ordered = array();
                        
                                                        foreach ($sectionsOrder as $ord) {
                                                                if (isset($output[$ord])) {
                                                                        $ordered[$ord] = $output[$ord];
                                                                        unset($output[$ord]);
                                                                }
                                                        }
                                                        
                                                        $output = array_merge($ordered, $output);
                                                }
                                        }
                                        
                                        $output = implode('', $output);
                                } else {
                                        $output .= '<table class="admintable extraFields"></table>';
                                }
                        }
                } else {
                        if ($type == 'searchfields') {
                                if (empty($cat) && empty($item)) {
                                        $output .= JText::_("Please select category you want to search");
                                } else {
                                        $output .= JText::_("This category doesn't have advanced search fields assigned");
                                }
                        } else {
                                $output = '<dl id="system-message"><dt class="notice">Notice</dt><dd class="notice message fade"><ul><li>'.JText::_(K2_PLEASE_SELECT_A_CATEGORY_FIRST_TO_RETRIEVE_ITS_RELATED_EXTRA_FIELDS).'</li></ul></dd></dl>';
                        }
                }
                
//                jdbg::p($fields);
                
                self::removeProperties($fields, K2FieldsModelFields::$dontSend);
                
//                jdbg::pe($fields);
                
//                $outputJS = 
//                        '
//                        <script type="text/javascript">'.
//                        K2FieldsModelFields::JS_VAR_NAME.'.options.fieldsOptions = '.json_encode($fields).';
//                        </script>
//                        ';                
                
                
                $outputJS = '
                        window.addEvent("domready", function() {'.
                                K2FieldsModelFields::JS_VAR_NAME.'.options.fieldsOptions = '.json_encode($fields).';
                        });
                        ';
                
                if ($addJSToDoc) {
                        $doc = JFactory::getDocument();
                        $doc->addScriptDeclaration($outputJS);
                        $outputJS = '';
                } else {
                        $outputJS = 
                                '
                                <script type="text/javascript">'.$outputJS.'</script>
                                ';
                }
                
                return $outputJS.$output;
        }
        
        public static function getK2Controller($useTrace = false, $view = '') {
                static $controller = false;

                if ($controller === false) {
                        if (empty($view)) $view = strtolower(JFactory::getApplication()->input->get('view'));
                        
                        if (empty($view)) return;
                        
                        if ($useTrace) {
                                $trcs = debug_backtrace();
                                $classes = JprovenUtility::getColumn($trcs, 'class');
                                $class = 'K2fieldsController'.ucfirst($view);
                                
                                if (($ind = array_search($class, $classes)) !== false) {
                                        $controller = $trcs[$ind]['object'];
                                } else {
                                        $class = 'K2Controller'.ucfirst($view);

                                        if (($ind = array_search($class, $classes)) !== false) {
                                                $controller = $trcs[$ind]['object'];
                                        }                        
                                }                                
                        }
                        
                        if (!$controller) {
                                $ctrls = array(
                                        'item' => array('K2fieldsControllerItem', 'K2ControllerItem'),
                                        'itemlist' => array('K2fieldsControllerItemlist', 'K2ControllerItemlist'),
                                        'category' => array('K2ControllerCategory')
                                );
                                
                                foreach ($ctrls[$view] as $ctrl) {
                                        if (class_exists($ctrl)) {
                                                $base = strtolower(str_ireplace('controller'.$view, '', $ctrl));
                                                $base = JPATH_BASE.'/components/com_'.$base.'/controllers';
                                                $controller = new $ctrl(array('base_path' => $base));
                                                break;
                                        }
                                }
                                
                                if (!$controller) {
                                        $base = JPATH_BASE . '/components/com_k2fields';
                                        $path = $base . '/controllers/' . $view . '.php';
                                        $ctrl = 'K2fieldsController';

                                        jimport('joomla.filesystem.file');

                                        if (!JFile::exists($path)) {
                                                $base = JPATH_BASE . '/components/com_k2';
                                                $path = $base . '/controllers/'.$view.'.php';
                                                $ctrl = 'K2Controller';
                                        }

                                        if (!JFile::exists($path)) return;

                                        require_once $path;

                                        $ctrl .= ucfirst($view);
                                        $controller = new $ctrl(array('base_path' => $base));                                        
                                }
                        }
                }
                
                               
//                if ($controller === false) {
//                        if ($useTrace) {
//                                $trcs = debug_backtrace();
//                                $controller = null;
//                                
//                                for ($i = 0, $n = count($trcs); $i < $n; $i++) {
//                                        if (isset($trcs[$i]['class']) && preg_match('#K2(fields|)ControllerItem(list)?#', $trcs[$i]['class'])) {
//                                                $controller = $trcs[$i]['object'];
//                                                break;
//                                        }
//                                }
//                        }
//                        
//                        if (!$controller) {
//                                if (empty($view)) {
//                                        // Adapted from /components/com_k2/k2.php
//                                        $input = JFactory::getApplication()->input;
//                                        $view = $input->get('view', '', 'cmd');
//                                        $task = $input->get('task', '', 'cmd');
//
//                                        if($view == 'media') {
//                                                $view = 'item';
//                                                if($task != 'connector') {
//                                                        $task = 'media';
//                                                }
//                                        }
//
//                                        if($view == 'users') {
//                                                $view = 'item';
//                                                $task = 'users';
//                                        }
//                                }
//                                
//                                $base = JPATH_SITE . '/components/com_k2fields';
//                                $path = $base . '/controllers/' . $view . '.php';
//                                $prefix = 'K2fieldsController';
//                                
//                                jimport('joomla.filesystem.file');
//                                
//                                if (!JFile::exists($path)) {
//                                        $base = JPATH_SITE . '/components/com_k2';
//                                        $path = $base . '/controllers/'.$view.'.php';
//                                        $prefix = 'K2Controller';
//                                }
//                                
//                                if (!JFile::exists($path)) return;
//                                
//                                require_once $path;
//                                
//                                $config = array('base_path' => $base);
//                                $classname = $prefix . $view;
//                                $controller = new $classname($config);
//                        }
//                }
                
                return $controller;
        }
        
        public static function nize($word, $n = 0) {
                require_once JPATH_SITE.'/media/k2fields/lib/inflector/inflector.php';
                                
                return $n > 1 ? KInflector::pluralize($word) : KInflector::singularize($word);
        }
        
        public static function load($file, $type) {
                self::loc(true, true, $file, true, $type);
        }
        
        public static function loc($isUrl = true, $isAbsolute = false, $file = '', $addToDocument = false, $type = 'js', $root = '') {
                static $loc = 'media/k2fields/';
                
                $result = empty($root) ? $loc : $root;
                
                if ($isAbsolute) $result = ($isUrl ? JURI::root() : JPATH_SITE . '/') . $loc;
                
                if (empty($type) && !empty($file)) {
                        jimport('joomla.filesystem.file');
                        $type = JFile::getExt($file);
                        $type = strtolower($type);
                }
                
                if (!empty($file)) {
                        $parts = explode('/', $file);
                        $result .= (count($parts) == 1 ? $type . '/' : '') . $file;
                }
                
                if ($addToDocument && !empty($file)) {
                        $file = $result;

                        if ($isAbsolute) {
                                if (!$isUrl) $file = str_replace(JPATH_SITE . '/', JURI::root(), $file);
                        } else {
                                $file = JURI::root() . $file;
                        }

                        $document = JFactory::getDocument();

                        if ($type == 'js') {
                                $document->addScript($file);
                        } else if ($type == 'css') {
                                $document->addStylesheet($file);
                        }
                }
                
                return $result;
        }        
        
        public static function isAjaxCall($url = null) {
                if (empty($url)) $url = JRequest::getString('jpmode', '');
                
                return preg_match('#[\?\&]jpmode=.+#i', $url);
        }
        
        public static function createDate($date = 'now') {
                jimport('joomla.utilities.date');
                
                $tzoffset = JFactory::getConfig()->getValue('config.offset');
                
                return new JDate($date, $tzoffset);
        }
        
        public static function normalizeK2Parameters(&$item = null, &$params = null) {
                if ((!$item || !isset($item->params) || !is_object($item->params)) && $params && is_object($params)) {
                        $result = clone $params;
                } else if (is_object($item->params)) {
                        $result = clone $item->params;
                } else {
                        $result = new JParameter($item->params);
                }
                
                if (JprovenUtility::isAjaxCall()) {
                        $disableParams = array(
                            'itemEmailButton', 'itemFontResizer', 'itemPrintButton', 
                            'itemDateCreated', 'itemTitle', 'itemAuthor',
                            'itemSocialButton', 'itemVideoAnchor',
                            'itemImageGalleryAnchor', 'itemCommentsAnchor',
                            'itemRating', 'itemDateModified', 'itemRelated', 
                            'itemComments'
                        );
                        
                        foreach ($disableParams as $p) $result->set($p, 0);
                }
                
                static $inheritFrom, $excludeInheritance;
                
                if (!isset($inheritFrom)) {
                        $cat = self::setting('inheritfromcategory', 'k2fields', 'k2');
                        
                        if (!empty($cat)) {
                                $db = JFactory::getDBO();
                                $db->setQuery('SELECT params FROM #__k2_categories WHERE id = '.(int)$cat);
                                $inheritFrom = $db->loadResult(); 
                        }
                        
                        if (!empty($inheritFrom)) {
                                $inheritFrom = new JParameter($inheritFrom);
                                $excludeInheritance = self::setting('dontinheritcategories', 'k2fields', 'k2', null, array());
                        }
                        
                        $excludeInheritParams = self::setting('dontinheritparams', 'k2fields', 'k2', null, array());
                        
                        $view = JRequest::getCmd('view', 'itemlist');
                        
                        foreach ($excludeInheritParams as $param) {
                                $param = preg_replace('#^'.$view.'#', '', $param);
                                $inheritFrom->set($param, '');
                        }
                }
                
                if (empty($item)) {
                        $id = JRequest::getInt('cid', JRequest::getInt('id'));
                } else {
                        $id = $item instanceof TableK2Category ? $item->id : $item->catid;
                }
                
                if ($inheritFrom instanceof JParameter && !in_array($id, $excludeInheritance)) {
                        $inherit = clone $inheritFrom;
                        
                        $result->merge($inherit);
                }
                
                if (isset($item->params) && is_object($item->params)) $item->params = $result;
                else $item->params = $result->toString ('INI');
                
                if ($params) $params = $result;
        }
        
        public static function setLayout($theme = '', $file = null, $view = null, $type = null, $addId = -1) {
                if (empty($file)) $file = self::createTemplateFileName($theme, $type, $addId);
                
                if ($file) {
                        $controller = self::getK2Controller();
                        
                        if (empty($view)) $view = JRequest::getWord('view');
                
                        $dir = dirname($file);
                        $layout = str_replace('.php', '', basename($file));
                        $document = JFactory::getDocument();
                        $viewType = $document->getType();
                        
                        if (!defined('JPATH_COMPONENT')) {
                                $option = JRequest::getCmd('option');
                                $controller->_addPath('view', JPATH_BASE.'/components/'.$option.'/views');
                        }
                        
                        $view = $controller->getView($view, $viewType);
                        $view->addTemplatePath($dir);
                        $view->setLayout($layout);
                        
                        return $layout;
                }               
        }
        
        public static function isCachable($id) {
                // Adapted from K2ControllerItem::display
                $user = JFactory::getUser();
		if ($user->guest){
                        $cache = true;
		} else {
                        $cache = true;
                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                        $row = JTable::getInstance('K2Item', 'Table');
                        $row->load($id);
                        if (K2HelperPermissions::canEditItem($row->created_by,$row->catid)) $cache = false;
                        $params = K2HelperUtilities::getParams('com_k2');
                        if ($row->created_by==$user->id && $params->get('inlineCommentsModeration')) $cache = false;
                        if ($row->access > 0) $cache = false;
                        $category = JTable::getInstance('K2Category', 'Table');
                        $category->load($row->catid);
                        if ($category->access > 0) $cache = false;
		}  
                return $cache;
        }
        
        public static function getReferer() {
                return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        }
        
        public static function isK2EditMode() {
                $option = JRequest::getCmd('option');
                
                if ($option != 'com_k2') return false;
                
                $app = JFactory::getApplication();
                $view = JRequest::getWord('view');
                $task = JRequest::getCmd('task');
                
                return $app->isAdmin() && ($view == 'item' || $view == 'category') || $task == 'edit' || $task == 'add';
        }
        
        private static function _createTemplateFileName($prefix, $dir, $ids = array(), $postfix = '', $ext = 'php') {
                jimport('joomla.filesystem.path');
                jimport('joomla.filesystem.file');
                
                $file = false;
                $ids = (array) $ids;
                
                foreach ($ids as $id) {
                        $file = JPath::clean(
                                $dir . 
                                $prefix . 
                                ($id != -1 ? '_' . $id : '') . 
                                (!empty($postfix) ? '_' . $postfix : '') . 
                                '.'.$ext, 
                                '/'
                        );
                        
                        if (JFile::exists($file)) break;

                        if (!empty($postfix)) {
                                $file = JPath::clean(
                                        $dir . 
                                        $prefix . 
                                        ($id != -1 ? '_' . $id : '') . 
                                        '.'.$ext, 
                                        '/'
                                );
                                
                                if (JFile::exists($file)) break;
                        }
                        
                        $file = false;
                }

                if ($file == false) {
                        $file = JPath::clean(
                                $dir . 
                                $prefix . 
                                '.'.$ext, 
                                '/'
                        );

                        if (!JFile::exists($file)) $file = false;
                }
                
                return $file;
        }
                
        public static function createTemplateFileName($theme = 'default', $type = '', $addId = -1) {
                if (empty($theme)) $theme = JRequest::getWord('theme', 'default');
                
                $template = JFactory::getApplication()->getTemplate();
                
                $dirs = array(
                    JPATH_SITE.'/templates/'.$template.'/html/com_k2fields/'.$theme.'/',
                    JPATH_BASE . '/components/com_k2fields/templates/' . $theme . '/'
                );
                
//                $dir = JPATH_BASE . '/components/com_k2fields/templates/' . $theme . '/';
                $listLayout = JRequest::getWord('listlayout', self::setting('listLayout', 'k2fields', 'k2', null, ''));
                $id = JRequest::getInt('id', -1);
                
                $view = JRequest::getWord('view');
                $task = JRequest::getCmd('task');
                $isForm = self::isK2EditMode();
                $post = ($isForm ? '_form25' : '_view').(empty($type) ? '' : '_'. $type);
                if ($isForm && $view == 'item') $dirs[1] = JPATH_SITE . '/components/com_k2fields/templates/default/';
                $file = false;
                $ext = $type == 'field' ? 'fld' : 'php';
                
                foreach ($dirs as $dir) {
                        if ($file !== false) break;
                        
                        if ($view == 'item') {
                                if ($id != -1) {
                                        $ids = array('i'.$id);
                                        if ($addId != -1) $ids[] = 'c'.$addId;
                                } else if ($addId != -1) {
                                        $ids = array('c'.$addId);
                                }
                                
                                $file = JprovenUtility::_createTemplateFileName('item'.$post, $dir, $ids, '', $ext);
                        } else if ($view == 'itemlist' && $task == 'category') {
                                $ids = array();
                                
                                if ($addId != -1) $ids[] = 'i'.$addId;
                                
                                $option = JRequest::getCmd('option');

                                if ($option == 'com_k2fields' && $id == -1) 
                                        $id = JRequest::getInt('cid', -1);
                                
                                if ($id != -1) $ids[] = 'c'.$id;
                                
                                $file = JprovenUtility::_createTemplateFileName('category'.$post, $dir, $ids, $listLayout, $ext);
                        } else {
                                $file = JprovenUtility::_createTemplateFileName($view.$post, $dir, -1, '', $ext);
                        }

                        if ($file === false && $theme != 'default')
                                $file = self::createTemplateFileName('default', $type);
//
//                        if ($file === false) {
//                                $dir = str_replace($theme, '', $dir);
//
//                                if ($view == 'item') 
//                                        $file = JprovenUtility::_createTemplateFileName('generic_item'.$post, $dir, $id, '', $ext);
//                                else if ($view == 'itemlist' || $view == 'category') 
//                                        $file = JprovenUtility::_createTemplateFileName('generic_category', $dir, $id, $listLayout, $ext);
//
//                                if ($file === false) 
//                                        $file = JprovenUtility::_createTemplateFileName('generic'.$post, $dir, -1, $listLayout, $ext);
//                        }
                }
                
                return $file;
        }
        
        public static function findSubTemplate($templateFile, $item) {
                $dir = dirname($templateFile).'/';
                $fileName = basename($templateFile);
                $tmplFile = preg_replace('#\.php$#i', '', $fileName);
                $layout = $tmplFile;
                $bases = array($tmplFile);
                $tmplFile = preg_replace('#\_c\d+$#', '', $tmplFile);
                if ($tmplFile != $layout) $bases[] = $tmplFile;
                $posts = array('item_i'.$item->id.'.php', 'item_c'.$item->catid, 'item');
                $file = '';
                jimport('joomla.filesystem.file');
                foreach ($bases as $base) {
                        foreach ($posts as $post) {
                                if (JFile::exists($dir.'/'.$base.'_'.$post.'.php')) {
                                        return array($base != $layout ? $base : '', $post, $layout);
                                }
                        }
                }
                return '';
        }
        
        public static function loadK2SpecificResources($cid = null, $id = null) {
                static $processed = array('cid'=>array(), 'id'=>array());
                
                if (!empty($id) && in_array($id, $processed['id']) || !empty($cid) && in_array($cid, $processed['cid'])) return;
                
                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                
                if (empty($cid) && !empty($id)) {
                        $tbl = JTable::getInstance('K2Item', 'Table');
                        $tbl->load($id);
                        $cid = $tbl->catid;
                } else if (empty($cid)) {
                        $option = JRequest::getCmd('option');
                        $view = JRequest::getWord('view');
                        $cid = $id = null;

                        if ($option != 'com_k2' && $option != 'com_k2fields') return;
                        
                        if ($option == 'com_k2fields') {
                                $cid = JRequest::getInt('cid');
                        } else if ($view == 'item') {
                                $id = JRequest::getInt('id');
                                $tbl = JTable::getInstance('K2Item', 'Table');
                                $tbl->load($id);
                                $cid = $tbl->catid;
                        } else if ($view == 'itemlist') {
                                $cid = JRequest::getInt('id');
                        }
                }
                
                if (!empty($id) && in_array($id, $processed['id']) || !empty($cid) && in_array($cid, $processed['cid'])) return;
                
                if (empty($id) && empty($cid)) return;
                
                $params = JComponentHelper::getParams('com_k2');
                $category = JTable::getInstance('K2Category', 'Table');
                $category->load($cid);
                $cparams = new JParameter($category->params);
                
                if ($cparams->get('inheritFrom')) {
                        $masterCategory = JTable::getInstance('K2Category', 'Table');
                        $masterCategory->load($cparams->get('inheritFrom'));
                        $cparams = new JParameter($masterCategory->params);
                }
                
                $params->merge($cparams);
                
                JprovenUtility::normalizeK2Parameters($category, $params);
                
                $theme = $params->get('theme', 'default');
                
                jimport('joomla.filesystem.file');

                $app = JFactory::getApplication();
                $tmpl = $app->getTemplate();

                $dirs = array(                    
                        JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/'.$theme,
                        JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/templates/'.$theme,
                        JPATH_SITE.'/components/com_k2fields/templates/'.$theme,
                    
                        JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2/'.$theme,
                        JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2/templates/'.$theme,
                        JPATH_SITE.'/components/com_k2/templates/'.$theme
                );
                
                // check first item specific file if in item view
                $file = null;
                
                if (!empty($id)) $file = self::_css($dirs, 'i'.$id);
                
                if (!$file && !empty($cid))
                        $file = self::_css($dirs, $cid);
                
                if (!$file)
                        $file = self::_css($dirs);
                
                if ($file) {
                        $document = JFactory::getDocument();
                        $file = str_replace(JPATH_SITE.DS, '', $file);
                        $file = JPath::clean($file, '/');
                        $file = JURI::root().$file;
                        $document->addStylesheet($file);
                }
                
                if (!empty($id)) $processed['id'][] = $id;
                if (!empty($cid)) $processed['cid'][] = $cid;
        }
        
        protected static function _css($dirs, $parts = '') {
                jimport('joomla.filesystem.path');
                
                $parts = (array) $parts;
                
                foreach ($parts as $part) {
                        if ($file = JPath::find($dirs, 'k2'.$part.'.css')) {
                                return $file;
                        }
                }
                
                return false;
        }        
                
        private static $json;
        
        public static function jsonArrize($value) {
                $result = array();
                foreach ($value as $ind => $val)
                        $result[(int)$ind] = $val;
                return $result;
        }

        public static function jsonEncode($value, $forceJSArray = false) {
                if ($forceJSArray) {
                        $result = "['".implode("','",$value)."']";
                } else {
                        if (function_exists('json_encode')) return json_encode($value);

                        if (empty(self::$json)) self::jsonDefine();
                        
                        $result = self::$json->encode($value);
                }
                
                return $result;
        }

        public static function jsonDecode($value) {
                if (function_exists('json_decode')) return json_decode($value);

                if (empty(self::$json)) self::jsonDefine();

                return self::$json->decode($value);
        }

        private static function jsonDefine() {
                require_once JPATH_SITE . '/media/k2fields/lib/JSON.php';
                self::$json = new Services_JSON;                        
                return true;
        }
        
        public static function removeValuesFromArray(&$arr, $valuesToRemove, $basedOnKey = false) {
                if (empty($arr) || empty($valuesToRemove)) return $arr;
                
                $valuesToRemove = (array) $valuesToRemove;
                
                foreach ($valuesToRemove as $valKey => $removeVal) {
                        foreach ($arr as $key => $val) {
                                if ($basedOnKey && $key == $valKey || !$basedOnKey && $val == $removeVal) {
                                        unset($arr[$key]);
                                }
                        }
                }
                
                return $arr;
        }

        public static function removeProperties(&$arr, $toRemove) {
                if (empty($arr) || empty($toRemove)) return $arr;
                
                $toRemove = (array) $toRemove;
                $keys = array_keys($arr);
                $isArray = is_array($arr[$keys[0]]);
                
                foreach ($arr as &$_arr) {
                        foreach ($toRemove as $attr) {
                                if ($isArray) {
                                        if (isset($_arr[$attr]))
                                                unset($_arr[$attr]);
                                } else {
                                        if (isset($_arr->$attr)) {
                                                unset($_arr->$attr);
                                        }
                                }
                        }
                }
        }

        public static function filterArray($arr, $filters, $maxDepth = -1, $depth = 0) {
                $filters = (array) $filters;
                $result = array();

                foreach ($arr as $key => &$val) {
                        if (is_object($val)) $val = (array) $val;
                        if (is_array($val) && !in_array($key, $filters)) {
                                if (!empty($val) && $maxDepth == -1 || $depth < $maxDepth) {
                                        $val = self::filterArray($val, $filters, $maxDepth, $depth + 1);
                                        if (!empty($val)) $result[$key] = $val; 
                                }
                        } else if (in_array($key, $filters)) {
                                $result[$key] = $val; 
                        }
                }
                
                return $result;
        }

        public static function toObject(&$array, $class = 'stdClass', $maxDepth = 1, $depth = 1) {
                $obj = null;
                if (is_array($array)) {
                        $obj = new $class();
                        foreach ($array as $k => $v) {
                                if ($depth < $maxDepth && is_array($v)) {
                                        $obj->$k = JprovenUtility::toObject($v, $class, $maxDepth, $depth + 1);
                                } else {
                                        $obj->$k = $v;
                                }
                        }
                } else $obj = $array;
                return $obj;
        }  

        public static function isPrefixed($value, $prefix) {
                return strpos(strtolower($value), strtolower($prefix)) === 0;
        }

        public static function isJ16() {
                return version_compare(JVERSION, '1.6.0', 'ge');
        }    
        
        public static function checkPluginActive($plugin, $folder, $warn = '', $enable = false) {
                jimport('joomla.plugin.helper');
                
                if (JPluginHelper::importPlugin($folder, $plugin)) return true;
                
                if (!empty($warn)) {
                        $plugin = self::getPlugin($plugin, $folder, 'id');
                        
                        if (self::isJ16()) {
                                $a = "index.php?option=com_plugins&task=plugin.edit&extension_id=".$plugin;
                        } else {
                                $a = "index.php?option=com_plugins&view=plugin&client=site&task=edit&cid[]=".$plugin;
                        }
                        
                        $warn = JText::sprintf($warn, $a);
                        
                        self::throwError(JLog::WARNING, $warn);
                }
                
                if ($enable) {
                        $db = JFactory::getDbo();
                        $db->setQuery('UPDATE #__extensions SET enabled = 1 WHERE type = '.$db->quote('plugin').' AND element = '.$db->quote($plugin).' AND folder = '.$db->quote($folder));
                        $db->query();
                        return JPluginHelper::importPlugin($folder, $plugin);
                }
                
                return false;
        }
        
        /**
         * For +2.5
         * @param type $code = JLog::consts
         * @param type $msg
         * @param type $type = Any of the PHP predefined exceptions, custom exception class or simply Exception
         * @throws type 
         * 
         * For 1.5
         * @param type $code = Error code of application as per J1.5 convnention
         * @param type $msg
         * @param type $type = Error, Notice, Warning
         * @throws type 
         */
        public static function throwError($code, $msg, $type = '') {
                if (false && self::isJ16()) {
                        JLog::add($msg, $code);
                        if (!empty($type)) {
                                $e = new $type($msg);
                                throw $e;
                        }
                } else {
                        if (empty($type)) $type = 'warning';
                        $function = 'raise'.ucfirst($type);
                        call_user_func_array(array('JError', $function), array($code, $msg));
                }
        }
        
        public static function getPlugin($plugin, $folder, $attr = '') {
                $db = JFactory::getDBO();
                
                if ($attr == '') { 
                        $_attr = '*';
                } else {
                        if ($attr == 'id' && self::isJ16()) {
                                $attr = 'extension_id';
                        }
                        
                        $_attr = $db->nameQuote($attr);
                }
                
                if (self::isJ16()) {
                        $query = 'SELECT '.$_attr.' FROM `#__extensions` WHERE type = '.$db->quote('plugin').' AND folder = '.$db->quote($folder).' AND element = '.$db->quote($plugin);
                } else {
                        $query = 'SELECT '.$_attr.' FROM `#__plugins` WHERE folder = '.$db->quote($folder).' AND element = '.$db->quote($plugin);
                }
                
                $db->setQuery($query);
                $plugin = $db->loadObject();
                
                return !empty($attr) && isset($plugin->$attr) ? $plugin->$attr : $plugin;
        }
        
        public static function getModule($module, $render = false, $client = null) {
                // Slightly customized version of joomla.application.module.helper._load to load a specific module
                $app = JFactory::getApplication();
                if (!isset($client)) {
                        $client = $app->getClientId();
                } else {
                        if ($client == 'site') {
                                $client = 0;
                        } else if ($client == 'admin') {
                                $client = 1;
                        } else {
                                $client = (int) $client;
                        }
                }
		$user = JFactory::getUser();
		$db = JFactory::getDBO();
                $access = JprovenUtility::isJ16() ? 
                        'IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')' :
                        '<= '. (int) $user->get('aid', 0);
                
                $mod = (int) $module;
                
                // Note: If only module name is provided then we select the first one
                if ($mod) {
                        $mod = ' m.id = ' . $mod;
                } else {
                        $mod = strpos($module, 'mod_') !== 0 ? 'mod_'.$module : $module;
                        $mod = ' m.module = "'.$mod.'"';
                }
                
                $query = 'SELECT id, title, module, position, content, showtitle, params'
                        . ' FROM #__modules AS m'
                        . ' WHERE m.published = 1'
                        . ' AND m.access ' . $access
                        . ' AND m.client_id = '. $client
                        . ' AND ' . $mod
                        . ' ORDER BY position, ordering LIMIT 1';

		$db->setQuery( $query );
                
		if (null === ($module = $db->loadObject())) {
                        JError::raiseWarning( 'SOME_ERROR_CODE', JText::_( 'Error loading given module' ).' (<em>'.$module .'</em>)');
                        return false;
		}

                //determine if this is a custom module
                $file					= $module->module;
                $custom 				= substr( $file, 0, 4 ) == 'mod_' ?  0 : 1;
                $module->user  	= $custom;
                // CHECK: custom module name is given by the title field, otherwise it's just 'om' ??
                $module->name		= $custom ? $module->title : substr( $file, 4 );
                $module->style		= null;
                $module->position	= strtolower($module->position);
                
                jimport('joomla.html.parameter');
                $module->params         = new JParameter($module->params);
                
                if ($render) $module->rendered = JModuleHelper::renderModule($module);
                
                return $module;
        }        
        
        public static function sendMail($subject, $msg, $recipients = array(), $addAdmins = false) {
                if (is_numeric($recipients)) {
                        $query = 'SELECT email, name FROM #__users WHERE id = '.$recipients;
                        $db = JFactory::getDBO();
                        $db->setQuery($query);

                        if (!$db->query()) {
                                JError::raiseError(500, $db->stderr(true));
                                return;
                        }
                        
                        $recipients = $db->loadObject();
                }
                
                if (is_object($recipients)) {
                        $recipients = array($recipients);
                }

                if (empty($recipients) || $addAdmins) {
                        $query = 'SELECT email, name FROM #__users WHERE gid = 25 AND sendEmail = 1';
                        $db = JFactory::getDBO();
                        $db->setQuery($query);

                        if (!$db->query()) {
                                JError::raiseError(500, $db->stderr(true));
                                return;
                        }
                        
                        $recipients = array_merge($recipients, $db->loadObjectList());
                }

                $uri = JURI::getInstance(JURI::base());
                $uri = $uri->toString(array('host', 'port'));
                
                jimport('joomla.utilities.date');
                $time = new JDate();
                $time = $time->toFormat();
                
                $subject = str_replace(array('%DATE%', '%DOMAIN%'), array($time, $uri), $subject);
                $msg = str_replace(array('%DATE%', '%DOMAIN%'), array($time, $uri), $msg);

                $config = JFactory::getConfig();
                $from = $config->getValue('mailfrom');
                $fromName = $config->getValue('fromname');
                
                foreach ($recipients as $recipient)
                        JUtility::sendMail($from, $fromName, $recipient->email, $subject, $msg);
        }
        
        public static function _setting($val, $assertedKeys, $sep, $allKey = 'all', $name = '') {
                if (!empty($assertedKeys) && !is_array($assertedKeys)) 
                        $assertedKeys = array($assertedKeys);
                
                $isAllKeyValue = false;
                
                if (!empty($assertedKeys) && is_string($val)) {
                        $result = array();
                        $vals = explode("\n", $val);
                        $keys = $assertedKeys;
                        
                        if (!empty($allKey)) $keys[] = $allKey;
                        
                        foreach ($vals as $_val) {
                                $_val = explode($sep, $_val);
                                $_val[0] = trim($_val[0]);
                                
                                if (in_array($_val[0], $keys)) {
                                        $__val = $_val[0];
                                        array_shift($_val);
                                        
                                        if (!isset($result[$__val])) {
                                                $result[$__val] = array();
                                        }
                                        
                                        $result[$__val][] = $_val;
                                }
                        }
                }
                
                if (!empty($assertedKeys)) {
                        if (empty($result)) $result = null;
                        return $result;
                }
                
                return $val;
        }
        
        public static function setting(
                $name, 
                $plgName,
                $folder,
                $options = null, 
                $default = null,
                $assertedKeys = null, 
                $sep = '::',
                $allKey = 'all') {
                
                $val = self::value($options, $name);
                
                if (!empty($val)) return $val;
                
                $val = self::plgParam($plgName, $folder, $name);
                $val = self::_setting($val, $assertedKeys, $sep, $allKey, $name);                
                
                if (empty($val)) $val = $default;
                
                return $val;
        }        
        
        public static function setValue(&$options, $name, $value) {
                if (is_array($options)) {
                        $options[$name] = $value;
                } else if (is_object($options)) {
                        $options->$name = $value;
                }
        }  
        
        public static function value($options, $name, $default = '') {
                if (is_array($options) && isset($options[$name])) {
                        $result = $options[$name];
                } else if (is_object($options) && property_exists($options, $name)) {
                        $result = $options->$name;
                }
                
                return !isset($result) ? $default : $result;
        }  
        
        public static function mergeValues($from, $to, $ignoreEmpty = true, $except = array(), $prefix = '', $postfix = '') {
                if (is_object($from)) 
                        $from = get_object_vars($from);
                
                $except = (array) $except;
                
                if (is_object($to)) {
                        foreach ($from as $key => $value) {
                                if ((!$ignoreEmpty || $ignoreEmpty && !empty($value)) && !in_array($key, $except)) {
                                        $to->$key = $value;
                                }
                        }
                } else {
                        foreach ($from as $key => $value) {
                                if ((!$ignoreEmpty || $ignoreEmpty && !empty($value)) && !in_array($key, $except)) {
                                        $to[$key] = $value;
                                }
                        }
                }
                
                if (!empty($prefix) || !empty($postfix)) {
                        if (is_object($to)) $vals = get_object_vars ($to);
                        else $vals = $to;
                        
                        foreach ($vals as $key => $value) {
                                $fkey = $prefix.$key.$postfix;
                                
                                if (!empty($from[$fkey])) {
                                        if (is_object($to)) $to->$key = $from[$fkey];
                                        else $to[$key] = $from[$fkey];
                                }
                        }
                }
                
                return $to;
        }
        
        public static function mergeK2FieldValues($from, $to) {
                return self::mergeValues($from, $to, true, array('name', 'reverse'), 'reverse_');
        }
        
        public static function redirectToPlg($plgName, $folder, $msg = '') {
                $app = JFactory::getApplication();
                
                if (!$app->isAdmin()) return;
                
                $db = JFactory::getDBO();
                $folder = $db->quote($folder);
                $plgName = $db->quote($plgName);
                
                $query = 'SELECT extension_id AS id FROM #__extensions WHERE type = "plugin" AND folder = '.$folder.' AND element = '.$plgName.' LIMIT 1';
                
                $db->setQuery($query);
                $plgId = $db->loadResult();
                
                $url = "index.php?option=com_plugins&task=plugin.edit&extension_id=".$plgId;
                
                $app->redirect($url, !empty($msg) ? $msg : JText::_('No component setting, redirected to corresponding plugin setting page'), 'info');
        }
        
        public static function plgParam($plgName, $folder, $name, $value = '', $dir = 'get') {
                static $plgs = array(), $j16;
                
                $plgsKey = $folder.'-'.$plgName;

                if (!isset($plgs[$plgsKey])) {
                        $plgs[$plgsKey] = array();
                        $j16 = JprovenUtility::isJ16();

                        if ($j16) {
                                $query = "SELECT extension_id AS id, params FROM #__extensions WHERE type = 'plugin' AND folder = '$folder' AND element = '$plgName' LIMIT 1";
                        } else {
                                $query = "SELECT id, params FROM #__plugins WHERE folder = '$folder' AND element = '$plgName' LIMIT 1";
                        }

                        $db = JFactory::getDBO();
                        $db->setQuery($query);
                        $plgs[$plgsKey]['plg'] = $db->loadObject();

                        jimport('joomla.html.parameter');
                        $plgs[$plgsKey]['params'] = new JParameter($plgs[$plgsKey]['plg']->params);
                        $plgs[$plgsKey]['values'] = array();
                }
                
                $values =& $plgs[$plgsKey]['values'];
                
                if ($dir == 'set') {
                        if ($value instanceof JRegistry) {
                                $plgs[$plgsKey]['params'] = $value;
                        } else {
                                $plgs[$plgsKey]['params']->set($name, $value);
                        }

                        $plgT = JTable::getInstance(!$j16 ? 'plugin' : 'extension');
                        $key = $j16 ? "extension_id" : 'id';
                        $data = array($key => $plgs[$plgsKey]['plg']->id, "params" => $plgs[$plgsKey]['params']->toString($j16 ? 'JSON' : 'INI'));

                        $plgT->bind($data);

                        if (!$plgT->store())
                                return JError::raiseWarning(500, $plgT->_db->getError());

                        if (!empty($name) && isset($values[$name])) unset($values[$name]);
                } else {
                        if (!isset($values[$name])) {
                                $values[$name] = $plgs[$plgsKey]['params']->get($name);

                                if (is_string($values[$name])) {
                                        $values[$name] = trim($values[$name]);
                                }
                                
                                if (!isset($values[$name]) || is_string($values[$name]) && empty($values[$name])) {
                                        $values[$name] = $value;
                                }
                        }

                        return $values[$name];
                }
        }         

        public static function cleanPlugins($text) {
                return preg_replace("#{(.*?)}(.*?){/(.*?)}#s", '', $text);
        }
        
        /**
         * Plugin syntax:
         * <$plgStart><$plgName>[ name=value]*<$plgEnd>
         * 
         * Syntax rules:
         * 1. characters not allowed within a plugin definition are the $plgStart and $plgEnd
         * 2. in addition characters not allowed in names are = and <space>
         * 3. in addition characters not allowed in values are =
         * 4. no characters (including <space>) are allowed between $plgStart and $plgName
         * 5. no delimiter is required between name and value
         */
        public static function parsePluginValues(
                $content, 
                $plgName, 
                $groupBy = false, 
                $groupByAllKeySubstitute = array(), 
                $plgStart = '{', 
                $plgEnd = '}'
        ) {
                $values = array();

                $definitionPos = 0;
                $explodeBy = $plgStart.$plgName;
                $plgParts = explode($explodeBy, $content);
                array_shift($plgParts);
                $pn = count($plgParts);

                for ($p = 0; $p < $pn; $p++) {
                        $values[$p] = array();
                        $def = $plgParts[$p];
                        $def = substr($def, 0, strpos($def, $plgEnd));
                        $values[$p]['_plg_'] = $explodeBy.$def.$plgEnd;
                        $parts = explode('=', $def);
                        $name = trim(array_shift($parts));
                        $i = 0; 
                        $n = count($parts);

                        while ($i < $n) {
                                $pos = strrpos($parts[$i], ' ');
                                $value = trim(substr($parts[$i], 0, $pos));
                                if (isset($values[$p][$name])) {
                                        if (!is_array($values[$p][$name]))
                                                $values[$p][$name] = array($values[$p][$name]);
                                        
                                        $values[$p][$name][] = $value;
                                } else {
                                        $values[$p][$name] = $value;
                                }
                                $pname = $name;
                                $name = trim(substr($parts[$i], $pos+1));
                                $i++;
                        }
                        
                        if ($i - 1 >= 0) {
                                $value = substr($parts[$i-1], 0);
                                if (is_array($values[$p][$pname])) {
                                        $values[$p][$pname][count($values[$p][$pname]) - 1] = trim($value);
                                } else {
                                        $values[$p][$pname] = trim($value);
                                }
                        }
                }
                
                if (!$groupBy) return $values;

                return self::indexBy($values, $groupBy, 'all', $groupByAllKeySubstitute);
        }

        /**
         *
         * @@todo: currently k2 specific
         */
        public static function replacePluginValues(&$content, $plgName, $provided = false) {
                $itemId = null;
                $text = $content;
                $textAttr = '';

                if (is_object($text)) { 
                        // $content instanceof TableK2Item
                        $textAttr = 'text';
                        $text = $content->text;

                        if (!$provided) 
                                $itemId = $content->id;
                } else {
                        $text = $content;
                }
                
                if (!$provided)
                        if (!empty($itemId)) 
                                $item = $itemId;
                        else if (JRequest::getCmd('option') == 'com_k2' && JRequest::getCmd('view') == 'item') 
                                $item = JRequest::getInt('id');
                
                $item = empty($item) ? null : $item;
                $rules = self::parsePluginValues($text, $plgName, array('item', 'field'), array($item));
                
                if ($provided && empty($item)) {
                        $item = key($rules);
                        $rules = self::parsePluginValues($text, $plgName, array('item', 'field'), array($item));
                }
                
                // rules with no applicable itemid are removed
                if (isset($rules['all'])) {
                        $alls = $rules['all'];
                        unset($rules['all']);
                        
                        foreach ($alls as $item => $itemRules)
                                foreach ($itemRules as $fieldsRule)
                                        $text = str_replace($fieldsRule['_plg_'], '', $text);
                }
                
                jimport('joomla.application.component.model');
                JModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/models/');
                $model = JModel::getInstance('fields', 'K2FieldsModel');
                
                foreach ($rules as $item => $itemRules) {
                        $renderedItemRules = call_user_func_array(
                                array($model, 'render'.ucfirst($plgName)), 
                                array(is_object($content) ? $content : $item, $itemRules, $text, $content)
                        );
                        
                        foreach ($renderedItemRules as $renderedFieldsRule)
                                foreach ($renderedFieldsRule as $renderedFieldRule) {
                                        $text = str_replace(
                                                $renderedFieldRule['_plg_'], 
                                                $renderedFieldRule['rendered'], 
                                                $text
                                        );
                                }
                }
                
                if (is_object($content) && !empty($textAttr)) {
                        $content->$textAttr = $text;
                } else {
                        $content = $text;
                }
                
                return $content;
        }
        
        public static function contains($inArray, $needles, $ignore = array()) {
                if (empty($inArray) || empty($needles)) return false;
                
                foreach ($needles as $needle => $value) {
                        if (!in_array($needle, $ignore) && (!isset($inArray[$needle]) || $inArray[$needle] != $value)) 
                                return false;
                }
                
                return true;
        }
        
        // Note: that earlier $array indexes are all replaced with new consecutive 
        // numeric keys
        public static function flatten($array, $caller = '') {
                if (!is_array($array)) return false;
                
                $result = array();
                
                foreach ($array as $el) {
                        if (!$el) continue;
                        
                        if (is_array($el)) {
                                $el = self::flatten($el);
                                if ($el) $result = array_merge($result, $el); 
                        } else {
                                $result[] = $el;
                        }
                }
                
                return $result;
        }
        
        public static function getRow($array, $criterias = array()) {
                if (empty($criterias)) return $array;
                
                $result = array();
                
                if (is_array($array)) {
                        foreach ($array as $item) {
                                $passed = true;
                                if (is_array($item)) {
                                        if (!empty($criterias)) {
                                                foreach ($criterias as $criteriaCol => $criteria) {
                                                        if ($item[$criteriaCol] != $criteria) {
                                                                $passed = false;
                                                        }
                                                }
                                        }
                                        if ($passed) $result[] = $item;
                                } elseif (is_object($item)) {
                                        if (!empty($criterias)) {
                                                foreach ($criterias as $criteriaCol => $criteria) {
                                                        if ($item->$criteriaCol != $criteria) {
                                                                $passed = false;
                                                        }
                                                }
                                        }
                                        if ($passed) $result[] = $item;
                                }
                        }
                }
                
                return $result;                
        }
        
        public static function makeOptions($array, $sepCol, $sepLblCol = '', $lblName = 'label', $append = '') {
                $isArray = is_array($array[0]);
                $options = array();
                $curr = $prev = '';
                
                if (empty($sepLblCol)) $sepLblCol = $sepCol;
                
                $array = self::indexBy($array, $sepCol);
                
                foreach ($array as $opts) {
                        $lbl = $append . ($isArray ? $opts[0][$sepLblCol] : $opts[0]->$sepLblCol);
                        $opt = $isArray ? array($lblName => $lbl) : new stdClass();
                        
                        if (!$isArray) $opt->$lblName = $lbl;
                        
                        array_unshift($opts, $opt);
                        
                        $options = array_merge($options, $opts);
                }
                
                return $options;
        }

        /**
         * customized version of JArrayHelper with ability to get column 
         * of array with arbitrary key sets
         */
        public static function getColumn($array, $index, $unique = false, $criterias = array(), $maintainIndex = false) {
                $result = array();
                
                $array = self::getRow($array, $criterias);
                
                if (is_array($array)) {
                        foreach ($array as $arrInd => $item) {
                                $passed = true;
                                if (is_array($item) && isset($item[$index])) {
                                        $result[$maintainIndex ? $arrInd : count($result)] = $item[$index];
                                } elseif (is_object($item) && isset($item->$index)) {
                                        $result[$maintainIndex ? $arrInd : count($result)] = $item->$index;
                                }
                        }
                }
                
                if ($unique) {
                        $result = array_unique($result);
                }

                return count($result) == 1 ? $result[0] : $result;
        }
        
        public static function toInt(&$value, $filterEmpty = true, $emptyDefault = null) {
                if (!is_array($value)) {
                        if (!is_object($value)) {
                                $v = (int) $value;
                                if (is_numeric($value) || $v === 0 && substr($value, 0, 1) == '0') $value = $v;
                                else if (empty($v)) $value = $filterEmpty ? null : $emptyDefault;
                                else $value = $v;
                        }
                        return;
                }
                for ($i = 0, $n = count($value); $i < $n; $i++) self::toInt($value[$i], $filterEmpty, $emptyDefault);
                if ($filterEmpty && $emptyDefault === null) array_filter($value);
        }
        
        public static function endWith($str, $end) {
                return $end != '' && substr($str, -strlen($end)) == $end;
        }

        public static function beginWith($str, $begin) {
                return $begin != '' && strpos($str, $begin) === 0;
        }        

        public static function indexBy($values, $keys, $genericKey = 'all', $keySubstitute = null, $unique = true, $flat = false) {
                $result = array();

                if (empty($keys)) return $values;

                $keys = (array) $keys;
                
                foreach ($values as $item) {
                        $res =& $result;

                        for ($k = 0, $kn = count($keys); $k < $kn; $k++) {
                                $key = $keys[$k];

                                if (is_array($item) && isset($item[$key])) {
                                        $key = $item[$key];
                                } else if (is_object($item) && isset($item->$key)) {
                                        $key = $item->$key;
                                } else if (isset($keySubstitute[$k])) {
                                        $val = $keySubstitute[$k];
                                        
                                        if (is_array($item)) {
                                                $item[$key] = $val;
                                        } else if (is_object($item)) {
                                                $item->$key = $val;
                                        }
                                        
                                        $key = $val;
                                } else {
                                        $key = $genericKey;
                                }

                                if (!$flat) {
                                        if (!isset($res[$key])) {
                                                $res[$key] = array();
                                                $res =& $res[$key];
                                        } else {
                                                $res =& $res[$key];
                                        }
                                        
                                        if (($k == $kn - 1) && ($unique && !in_array($item, $res) || !$unique)) 
                                                $res[] = $item;
                                } else {
                                        $res[$key] = $item;
                                }
                        }
                }

                return $result;
        }        
        
        public static function isEmpty($val) {
                $isEmpty = true;
                
                if (is_object($val)) $val = get_object_vars ($val);
                
                if (is_array($val)) {
                        $isEmpty = true;
                        
                        foreach ($val as $v) {
                                if (!empty($v)) {
                                        $isEmpty = false;
                                }
                        }
                } else {
                        $isEmpty = empty($val);
                }
                
                return $isEmpty;
        }
        
        public static function arraySort(&$array, $sortBy, $dir = SORT_ASC) {
                $col = self::getColumn($array, $sortBy);
                array_multisort($col, $dir, $array);
        }

        public static function chunkArray($array, $by, $emptyKey = '') {
                if (empty($array)) return array();
                
                if (empty($by)) return $array;
                        
                $n = count($array);
                $cnt = 0;
                $result = array();
                $isArray = is_array($array[0]);
                $keys = self::getColumn($array, $by, true);
                if (!is_array($keys)) $keys = array($keys);
                $result = array_fill_keys($keys, array());
                
                foreach ($array as $key => $el) {
                        $key = $isArray ? $el[$by] : $el->$by;
                        if ($key == '' && $emptyKey != '') $key = $emptyKey;
                        $result[$key][] = $el;
                }
                
                return $result;                        
        }

        public static function toIntArray($value, $implode = false) {
                if (is_numeric($value)) $value = array($value);
                else if (is_string($value)) $value = explode(',', $value);
                if (is_array($value)) for ($i = 0, $n = count($value); $i < $n; $i++) $value[$i] = (int) $value[$i];
                else return $implode ? '' : array();
                return $implode ? implode(',', $value) : $value;
        }

        public static function strposBalanced($findStart, $findEnd, $str, $startAt) {
                $pos = $startAt;
                $max = strlen($str);
                $startFound = 0;
                $seen = -1;
                $ch = '';
                $sLen = strlen($findStart);
                $eLen = strlen($findEnd);

                while ($pos < $max) {
                        if ($seen > -1 && $startFound == 0) break;

                        $ch = substr($str, $pos, $sLen);

                        if ($ch == $findStart) {
                                $startFound++;
                                if ($seen == -1) $seen = $pos;
                                $pos += $sLen;
                                continue;
                        }

                        $ch = substr($str, $pos, $eLen);

                        if ($ch == $findEnd) {
                                $startFound--;
                                $pos += $sLen;
                                continue;
                        }

                        $pos++;
                }

                if ($ch == $findEnd && $seen > -1) {
                        return array($seen, $pos - $seen);
                }

                return false;
        }


        /**
         * adaptation of David Cramer's <dcramer@gmail.com> httplib code
         *
         * @param string $host hostname (e.g. domain.com)
         * @param int $port port
         * @param string $method request method (GET/POST/PUT)
         * @param string $path request path
         * @param array $params associative array of parameters to send
         * @param array $headers associative array of headers to send
         */
        public static function makeHTTPRequest($request, $type = '', $method = 'GET', $timeout = 60, $ua = '') {
                static $CURL;

                if (!isset($CURL)) {
                        $CURL = in_array('curl', get_loaded_extensions(), true);
                }

                list($protocol, $request) = explode('://', $request);

                $request = JPath::clean(html_entity_decode($request), '/');

                if ($protocol) $request = $protocol . '://' . $request;

                if (empty($ua)) {
                        $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.10) Gecko/20100914 Firefox/3.6.10 ( .NET CLR 3.5.30729)';
                }

                if ($CURL) {
                        $ch = curl_init($request);

                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_USERAGENT, $ua);

                        $response_content = curl_exec($ch);

                        curl_close($ch);

                        return $response_content;
                }

                $method = strtoupper($method);

                $uri = JUri::getInstance();
                $uri = clone $uri;
                $uri->_parseURL($request);
                $host = $uri->getHost();

                $port = $uri->getPort();
                if (empty($port))
                        $port = 80;

                $path = $uri->getPath();
                $query_string = $uri->getQuery();

                if ($method == 'GET') {
                        $path .= '?' . $query_string;
                }

                $socket = @fsockopen($host, $port, $errorNumber, $errorString, (float) $timeout);

                if (!$socket) {
                        return self::error('Failed connecting to ' . $host . ':' . $port . ': ' . socket_strerror($errorNumber) . ' (' . $errorNumber . '); ' . $errorString);
                }

                stream_set_timeout($socket, (float) $timeout);

                // set default headers
                $headers['User-Agent'] = $ua;

                if (!$type) {
                        $ext = JFile::getExt($request);
                        $type = self::$extMime[$ext];
                } else if (array_key_exists($type, self::$extMime)) {
                        $type = self::$extMime[$type];
                }

                $headers['Content-Type'] = $type;

                if ($method == 'POST') {
                        $headers['Content-Length'] = strlen($query_string);
                }

                $headers['Host'] = $host;

                // build the header string
                $request_header = $method . " " . $path . " HTTP/1.1\r\n";

                foreach ($headers as $key => &$value) {
                        $request_header .= $key . ": " . $value . "\r\n";
                }

                $request_header .= "Connection: close\r\n\r\n";

                if ($method == "POST") {
                        $request_header .= $query_string;
                }

                fwrite($socket, $request_header);

                $response_header = '';

                do {
                        $response_header .= fread($socket, 1);
                } while (!preg_match('/\\r\\n\\r\\n$/', $response_header));

                $_headers = explode("\r\n", $response_header);
                $headers = array();

                foreach ($_headers as &$line) {
                        if (strpos($line, 'HTTP/') === 0) {
                                $data = explode(' ', $line);
                                $status = $data[1];
                                $message = implode(' ', array_slice($data, 2));
                        } elseif (strpos($line, ':')) {
                                $data = explode(':', $line);
                                $value = trim(implode(':', array_slice($data, 1)));
                                $headers[$data[0]] = $value;
                        }
                }

                $response_content = '';
                if (isset($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] == 'chunked') {
                        while ($chunk_length = hexdec(fgets($socket))) {
                                $response_content_chunk = '';
                                $read_length = 0;

                                while ($read_length < $chunk_length) {
                                        $response_content_chunk .= fread($socket, $chunk_length - $read_length);
                                        $read_length = strlen($response_content_chunk);
                                }

                                $response_content .= $response_content_chunk;
                                fgets($socket);
                        }
                } else {
                        while (!feof($socket)) {
                                $response_content .= fgets($socket, 128);
                        }
                }

                return chop($response_content);

                fclose($socket);
        }  
        
        public static function getPath($needle, $valueCol, $parentCol, $tbl, $criterias = array(), $logicalOp = 'AND', $includeSelf = true) {
                $db = JFactory::getDBO();
                $query = 'SELECT '.$db->nameQuote($valueCol).', '.$db->nameQuote($parentCol).' FROM '.$tbl;
                $cols = array();
                
                foreach ($criterias as $col => $criteria) {
                        $op = is_array($criteria) ? $criteria[0] : '=';
                        $val = is_array($criteria) ? $criteria[1] : $criteria;
                        $cols[] = ' ' . $db->nameQuote($col) . ' ' . $op . ' ' . $val;
                }
                
                $where = implode(' ' . $logicalOp, $cols);
                
                if (!empty($where))
                        $query .= ' WHERE ' . $where;
                
                $db->setQuery($query);
                $values = $db->loadObjectList($valueCol);
                $path = array();
                
                if ($includeSelf) 
                        $path[] = $needle;
                
                if (isset($values[$needle])) {
                        while (!empty($values[$needle]->$parentCol)) {
                                $path[] = $values[$needle]->$parentCol;
                                $needle = $values[$needle]->$parentCol;
                        }
                }
                
                return $path;
        } 
        
        public static function getK2CategoryPath($needle, $checkAccess = true) {
                $criterias = array('published' => 1, 'trash' => 0);
                
                if ($checkAccess) {
                        $user = JFactory::getUser();
                        
                        if (K2_JVERSION == '16') {
                                $aid = $user->authorisedLevels();
                                $aid = '('.implode(',', $aid).')';
                                $criterias['access'] = array('IN', $aid);
                        } else {
                                $aid = (int) $user->get('aid');
                                $criterias['access'] = array('<=', $aid);
                        }                        
                }
                
                return self::getPath($needle, 'id', 'parent', '#__k2_categories', $criterias);
        }
        
        public static function getK2CategoryChildren($ids, $depth = -1, $clear = false, $onlyIds = true) {
                static $currDepth = 0, $categories = array(), $categoryTree = array(), $categoryParents = array();
                
                if ($clear) {
                        $categories = array();
                        $categoryTree = array();
                        $categoryParents = array();
                        $currDepth = 0;
                }
                
                if (($depth != -1 && $currDepth > $depth) || empty($ids)) return $categories;
                
                $ids = (array) $ids;
                $user = JFactory::getUser();
                $aid = (int) $user->get('aid');
                $db = JFactory::getDBO();
                $ids = array_unique($ids);
                $cols = $onlyIds ? 'id, parent' : '*';
                
                $query = "SELECT {$cols}, (SELECT COUNT(*) FROM #__k2_categories cc WHERE cc.parent = c.id AND cc.published=1 AND cc.trash=0 AND cc.access<={$aid}) AS cnt FROM #__k2_categories c WHERE c.parent IN (".(implode(", ", $ids)).") AND c.published=1 AND c.trash=0 AND c.access<={$aid} ORDER BY c.ordering";
                
                $db->setQuery($query);
                
                $rows = $db->loadObjectList();
                $catsWithChildren = array();
                
                foreach ($rows as $row) {
                        if ($onlyIds) {
                                $categories[] = $row->id;
                        } else {
                                $categories[$row->id] = $row;
                        }
                        
                        if (!isset($categoryTree[$row->parent])) 
                                $categoryTree[$row->parent] = array();
                        
                        $categoryTree[$row->parent][] = $row->id;
                        $categoryParents[$row->id] = $row->parent;
                        
                        if ($row->cnt > 0) 
                                $catsWithChildren[] = $row->id;
                }
                
                $currDepth++;
                
                if (!empty($catsWithChildren)) 
                        self::getK2CategoryChildren($catsWithChildren, $depth, false, $onlyIds);
                
                return array('cats'=>$categories, 'children'=>$categoryTree, 'parents'=>$categoryParents);
        }       
        
        public static function first($array) {
                if (empty($array)) return;
                $key = self::firstKey($array);
                return $array[$key];
        }
        
        public static function firstKey($array) {
                $keys = array_keys($array);
                $index = 0;
                if ($keys[$index] == 'all' && count($keys) > 1 && !empty($array[$keys[1]])) $index = 1;
                return $keys[$index];
        }
        
        public static function replaceInDocument($search, $replace, $exceptTasks = array(), $inApp = '') {
                $app = JFactory::getApplication();
                
                if ($inApp == 'site' && !$app->isSite() || $inApp == 'admin' && !$app->isAdmin()) return;
                
                $task = JRequest::getCmd('task');
                $exceptTasks = (array) $exceptTasks;

                if (in_array($task, $exceptTasks)) return;
                
                $html = JResponse::getBody();
                
                $parts = explode($search, $html);
                
                if (count($parts) > 1) {
                        $html = implode($replace, $parts);
                        JResponse::setBody($html);
                }
        }
        
        public static function replaceResourcesInDocument(
                $resourceType,
                $removes, 
                $adds = '', 
                $mtd = 'document', 
                $exceptTasks = array(), 
                $inApp = '',
                $onlyReplace = true
        ) {
                $app = JFactory::getApplication();

                if ($inApp == 'site' && !$app->isSite() || $inApp == 'admin' && !$app->isAdmin()) return;
                
                $task = JRequest::getCmd('task');
                $exceptTasks = (array) $exceptTasks;

                if (in_array($task, $exceptTasks)) return;
                
                $removes = (array) $removes;
                $adds = (array) $adds;

                if ($mtd == 'document') {
                        $document = JFactory::getDocument();
                        $present = array();
                        $arr = null;
                        $isJs = $resourceType == 'js';
                        
                        if ($isJs) $arr = &$document->_scripts;
                        else $arr = &$document->_styleSheets;
                        
                        foreach ($removes as $i => $remove) {
                                if (isset($arr[$remove])) {
                                        $present[] = $i;
                                        unset($arr[$remove]);
                                }
                        }
                        
                        $addArr = array();
                        
                        $jsProp = self::isJ16() ? array('mime'=>'text/javascript', 'defer'=>false, 'async'=>false) : 'text/javascript';
                        
                        foreach ($adds as $i => $add) {
                                if ((!$onlyReplace || in_array($i, $present)) && !empty($add)) {
                                        $addArr[$add] = $isJs ? $jsProp : array('mime'=>'text/css', 'media'=>null, 'attribs'=>array());
                                }
                        }
                        
                        if ($isJs) $document->_scripts = array_merge($addArr, $document->_scripts);
                        else $document->_styleSheets = array_merge($addArr, $document->_styleSheets);
                } else if ($mtd == 'response' || $mtd == 'body') {
                        // TODO: removing stylesheets from response to be implemented
                        
                        $html = JResponse::getBody();
                        
                        if ($resourceType == 'js') {
                                if ($onlyReplace) {
                                        foreach ($removes as $i => $remove) {
                                                if (preg_match('#(<script.+src=)([\"\'])([^\"\']*)('.$remove.')(\2[^>]*>)#Ui', $html, $m)) {
                                                        $add = isset($adds[$i]) ? '<script type="text/javascript" src="'.$adds[$i].'"></script>' : '';
                                                        $html = str_replace($m[0], $add, $html);
                                                }
                                        }
                                } else {
                                        foreach ($removes as $remove)
                                                $html = preg_replace('#(<script.+src=)([\"\'])([^\"\']*)('.$remove.')(\2[^>]*>)#Ui', '', $html);

                                        $js = '';

                                        foreach ($adds as $add)
                                                $js .= '<script type="text/javascript" src="'.$add.'"></script>';

                                        if (!empty($js))
                                                $html = str_ireplace('</head>', $js.'</head>', $html);
                                }
                        } else {
                                if ($onlyReplace) {
                                        foreach ($removes as $i => $remove) {
                                                if (preg_match('#(<link(.+)href=)([\"\'])([^\"\']*)('.$remove.')(\3.*\/>)#Ui', $html, $m)) {
                                                        $add = isset($adds[$i]) ? '<link rel="stylesheet" type="text/css" href="'.$adds[$i].'" />' : '';
                                                        $html = str_replace($m[0], $add, $html);
                                                }
                                        }
                                } else {
                                        foreach ($removes as $remove) {
                                                if (preg_match('#(<link(.+)href=)([\"\'])([^\"\']*)('.$remove.')(\3.*\/>)#Ui', $html, $m)) {
                                                        $html = str_replace($m[0], '', $html);
                                                }
                                        }

                                        $css = '';

                                        foreach ($adds as $add)
                                                $css .= '<link rel="stylesheet" type="text/css" href="'.$add.'" />';

                                        if (!empty($css))
                                                $html = str_ireplace('</head>', $css.'</head>', $html);
                                }
                        }
                        
                        JResponse::setBody($html);  
                }
        }
}
?>
