<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!JprovenUtility::checkPluginActive('k2fields', 'system', '', true)) {
        JError::raiseError('500', 'Unable to activate/locate k2fields system plugin which is required for proper functioning of k2fields. Please correct that and try again.');
        return;
}

JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2plugin.php');

class plgk2k2fields extends K2Plugin {
        var $pluginName = 'k2fields';
        var $pluginNameHumanReadable = 'Extending K2';
        
        const AUTO_METATAG_SEPARATOR = 'K2FIELDSAUTOGEN:';
        
        function plgk2k2fields(&$subject, $params) {
                parent::__construct($subject, $params);
//                JPlugin::loadLanguage('plg_k2_k2fields', JPATH_ADMINISTRATOR);
                $this->loadLanguage('', JPATH_ADMINISTRATOR);
        }

        /*** K2 plugin events ***/
        function onK2BeforeDisplay(&$item, &$params, $limitstart) {
//                $model = K2Model::getInstance('fields', 'K2FieldsModel');
//                $model->adjustFieldValues($item);
                
                $this->normalizeMetatag($item, 'metadesc');
                $this->normalizeMetatag($item, 'metakey');
                
                if (JprovenUtility::plgParam('k2fields', 'k2', 'override_itemmodel') != '1')
                        JprovenUtility::normalizeK2Parameters($item);

                if (!isset($item->category) || !is_object($item->category)) {
                        $query = 'SELECT * FROM #__k2_categories WHERE id = '.(int) $item->catid;
                        $db = JFactory::getDbo();
                        $db->setQuery($query);
                        $item->category = $db->loadObject();                        
                }
                
                $link = K2FieldsHelperRoute::getItemRoute($item->id.':'.urlencode($item->alias), $item->catid.':'.urlencode($item->category->alias));
		$item->link = urldecode(JRoute::_($link));
                
                if (is_string($item->params)) {
                        $item->params = new JRegistry($item->params);
                }
                
                if ($item->params->get('itemComments') && $item->params->get('itemRating'))
                        $item->nonk2rating = JPluginHelper::importPlugin('k2', 'jcomments') && JPluginHelper::importPlugin('jcomments', 'rate');
                
                self::setLayout($item);
                
                // $this->processSearchPlugins($item);
                return self::processExtrafields('BeforeDisplay', $item, $params, $limitstart);
        }
        
        function onK2AfterDisplay(&$item, &$params, $limitstart) {
                return self::processExtrafields('AfterDisplay', $item, $params, $limitstart);
        }
        
        function onK2AfterDisplayTitle(&$item, &$params, $limitstart) {
                return self::processExtrafields('AfterDisplayTitle', $item, $params, $limitstart);
        }
        
        function onK2BeforeDisplayContent(&$item, &$params, $limitstart) {
                return self::processExtrafields('BeforeDisplayContent', $item, $params, $limitstart);
        }
        
        function onK2AfterDisplayContent(&$item, &$params, $limitstart) {
                return self::processExtrafields('AfterDisplayContent', $item, $params, $limitstart);
        }
        
        function onK2PrepareContent(& $item, & $params, $limitstart) {
        }

        function onK2CategoryDisplay(&$category, &$params, $limitstart) {
                JprovenUtility::normalizeK2Parameters($category, $params);
                
                if (self::param('paginationmode', 'k2') == 'ajax') {
                        if (!$params->get('num_leading_items')) {
                                $num = self::param('itemlistlimit', 20);
                                $params->set('num_leading_items', $num);
                        }
                        
                        $params->set('num_primary_items', 0);
                        $params->set('num_secondary_items', 0);
                        $params->set('num_links', 0);
                }
                // In view of itemlist layout is set after category plugin called
                // therefore we can't do it here and will rely on having this set in onK2BeforeDisplay
                // but if no items are present then this will not work
//                self::setLayout(null, $category->params);
        }
        
        function onBeforeK2Save(&$item, $isNew) {
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                if ($isNew) $model->setDefaultValues();            
                return $model->preSave($item);
        }
        
        function onAfterK2Save(&$item, $isNew) {
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $model->save($item, $isNew);
                
                $row = JTable::getInstance('K2Item', 'Table');
                $row->load($item->id);
                
                $app = JFactory::getApplication();
                $isSave = false;
                
                $glue = self::param('appendtitleglue', ' / ');
                $meta = $model->generateTitle($item, $glue);
                
                if ($meta) {
                        $t = explode($glue, $row->title);
                        $meta = $t[0] . $glue . $meta;
                        $row->alias = '';
                        $isSave = true;
                        $row->title = $item->title = $meta;
                }
                
                $meta = $model->generateKeywords($item);
                
                if ($meta) {
                        $t = explode(self::AUTO_METATAG_SEPARATOR, $row->metakey);
                        $meta = $t[0] . self::AUTO_METATAG_SEPARATOR . $meta;
                        $isSave = true;
                        $row->metakey = $item->metakey = $meta;
                }
                
                $meta = $model->generateDescription($item);
                
                if ($meta) {
                        $t = explode(self::AUTO_METATAG_SEPARATOR, $row->metakey);
                        $meta = $t[0] . self::AUTO_METATAG_SEPARATOR . $meta;
                        $isSave = true;
                        $row->metakey = $item->metakey = $meta;
                }
                
                if ($isSave) {
                        if (!$row->check()) {
                                $app->redirect('index.php?option=com_k2&view=item&cid='.$row->id, $row->getError(), 'error');
                        }

                        if (!$row->store()) {
                                $app->redirect('index.php?option=com_k2&view=items', $row->getError(), 'error');
                        }                        
                }
                
                if ($app->isAdmin()) return;
                
                $action = self::param('actionaftersave', 'closeandload');
                $js = false;
                
                switch ($action) {
                        case 'closeandreload':
                                $js = "window.parent.document.location.reload();";
                                break;
                        case 'close':
                                $js = "window.parent.SqueezeBox.close();";
                                break;
                        case 'closeandload':
                        default:
                                $category = JTable::getInstance('K2Category', 'Table');
                                $category->load($item->catid);
                                $link = K2FieldsHelperRoute::getItemRoute($item->id.':'.urlencode($item->alias), $category->id.':'.urlencode($category->alias));
                                $js = "window.parent.document.location.href='".JRoute::_($link)."';";
                                break;
                }
                
                if ($js) {
                        $msg = $isNew ? JText::_('K2_ITEM_SAVED') : JText::_('K2_CHANGES_TO_ITEM_SAVED');
                        $app = JFactory::getApplication();
                        $app->enqueueMessage($msg, 'info');
                        $app->close("<script type='text/javascript'>".$js."</script>\n");
                }
        }        

        function onRenderAdminForm(&$item, $type, $tab = '') {
                if (JFactory::getApplication()->isAdmin() && ($type == 'item' || $type == 'category'))
                        JprovenUtility::setLayout();
                
                if ($type == 'item') {
//                        $input = JFactory::getApplication()->input;
//                        $option = $input->get('option');
//                        $view = $input->get('view');
//                         && $view == 'item'

                        if (JFactory::getApplication()->isSite()) JprovenUtility::setLayout(); 
                        if ($tab == 'extra-fields') self::loadResources($tab, $item);
                } else if ($type == 'user') {
                        // return self::adjustUserFormLayout($item);
                }
        }
        
        /*** Utility functions ***/
        function normalizeMetatag(&$item, $prop, $replacement = ',') {
                $rep = strpos($item->$prop, self::AUTO_METATAG_SEPARATOR);
                if ($rep === false) {
                        return;
                } else if ($rep === 0) {
                        $rep = '';
                } else {
                        $rep = $replacement;
                }
                $item->$prop = str_replace(self::AUTO_METATAG_SEPARATOR, $rep, $item->$prop);
        }
        
        function _v($arr, $ind, $def='') {
                $res = JprovenUtility::value($arr, $ind-1, $def);
                return trim($res);
        }
        
        private static function processExtrafields($caller, &$item, &$params, $limitstart) {
                if (K2FieldsModelFields::value($item, 'k2item')) return;
                
                $view = JFactory::getApplication()->input->get('view');
                $_view = $view;
                
                if ($_view != 'itemlist') $_view = '';
                
                $pos = K2FieldsModelFields::categorySetting($item->catid, $_view.'catextrafieldsposition');
                
                if (!$pos) {
                        $pos = self::param($_view.'extrafieldsposition', 'AfterDisplay');
                } else {
                        $pos = current(current(current($pos)));
                }
                
                if ($caller != $pos) return;
                
                /*
                 * NOTE: due to autofields that doesn't carry values checking presence of fields
                 * can't be validation to reject further parsing. Instead make available field processing
                 * efficient
                 *
                 */
                
                $inText = false;
                $tmp = $item->text;
                
                if (preg_match('#(\{k2f[^\}]*})#i', $tmp, $plg)) {
                        $plg = $plg[0];
                        $inText = true;
                } else {
                        jimport('joomla.filesystem.file');
                        
                        $plg = '';
                        $view = JFactory::getApplication()->input->get('view');
                        $ids = array('i'.$item->id, 'c'.$item->catid);
                        
                        if ($params->get('parsedInModule')) array_unshift($ids, 'm'.$params->get('module_id'));
                        
                        $file = JprovenUtility::createTemplateFileName(
                                $params->get('theme'), 
                                'fields', 
                                $ids,
                                $params->get('parsedInModule') ? array('module', 'itemlist') : ''
                        );

                        if ($file) {
                                $plg = JFile::read($file);
                                $plg = trim($plg);
                                
                                if (strpos($plg, '{k2fintrotext}') !== false) {
                                        $plg = str_replace('{k2fintrotext}', $item->introtext, $plg);
                                }

                                if (strpos($plg, '{k2ffulltext}') !== false) {
                                        $plg = str_replace('{k2ffulltext}', $item->fulltext, $plg);
                                }
                                
                                if (strpos($plg, '{k2ftitle}') !== false) {
                                        $title = $item->title;
                                        
                                        if (($params->get('parsedInModule') || $view == 'itemlist') && $params->get('catItemTitleLinked')) {
                                                $title = K2FieldsHelperRoute::createItemLink($item);
                                        }
                                        
                                        $plg = str_replace('{k2ftitle}', $title, $plg);
                                }
                        }
                        
                        if (empty($plg)) $plg = '{k2f}';
                }
                
                $item->text = $plg;
                $item = JprovenUtility::replacePluginValues($item, 'k2f', false, array('parsedInModule'=>$params->get('parsedInModule')));
                
                // TODO: TOO obtrusive
                $item->extra_fields = array();
                $result = $item->text;
                
                if ($result) self::loadResources('item', $item);
                
                if (!empty($item->k2f)) {
                        return '';
                } else {
                        $item->text = $inText ? str_replace($plg, $result, $tmp) : $tmp;
                        return $inText ? '' : $result;
                }
        }
        
        private static $catState;
        
        public static function catState($name = '') {
                if (isset(self::$catState) && !empty($name)) return K2FieldsModelFields::value(self::$catState, $name);
                
                return null;
        }
        
        // TODO: what happens when we have items from various categories, as in search results
        private static function setLayout(&$item = null, $cparams = null) {
                $view = JFactory::getApplication()->input->get('view');
                
                if ($item) {
                        if ($item->params->get('parsedInModule')) return;
                        
                        $item->itemlistCSS = '';
                        
                        $tabular = K2FieldsModelFields::categorySetting($item->catid, 'tabularlayout');
                        $item->isItemlistTabular = $view == 'itemlist' && !empty($tabular);
                        $item->itemlistCSS = $item->isItemlistTabular ? ' itemListTabular' : '';
                        
                        if ($item->isItemlistTabular) $item->itemlistCSS = ' itemListTabular';
                        
                        $map = K2FieldsModelFields::categorySetting($item->catid, 'maplayout');
                        
                        $item->isItemlistMap = $view == 'itemlist' && !empty($map);
                        
                        if ($item->isItemlistMap) $item->itemlistCSS .= ' itemListMap';
                        
                        if (empty(self::$catState)) self::$catState = clone $item;
                }
                
                static $isLayoutSet = false;
                
                if ($isLayoutSet) return;
                
                $layout = self::param('specificLayout', 'yes');
                
                if ($layout == 'yes') {
                        $params = JprovenUtility::getK2Params();
                        
                        if (!empty($item) && empty($cparams)) {
                                if (isset($item->categoryparams)) {
                                        $cparams = $item->categoryparams;
                                } else if (isset($item->category) && is_object ($item->category)) {
                                        $cparams = $item->category->params;
                                } else {
                                        $query = 'SELECT params FROM #__k2_categories WHERE id = '.$item->catid;
                                        $db = JFactory::getDBO();
                                        $db->setQuery($query);
                                        $cparams = $db->loadResult();
                                }
                                
                                if (!empty($cparams)) {
                                        if (is_string($cparams))
                                                $cparams = new JRegistry($cparams);

                                        if ($cparams->get('inheritFrom')) {
                                                $masterCategory = &JTable::getInstance('K2Category', 'Table');
                                                $masterCategory->load($cparams->get('inheritFrom'));
                                                $cparams = new JRegistry($masterCategory->params);
                                        }

                                        $params = $cparams;
                                }
                        } else if (!empty($cparams)) {
                                if (is_string($cparams))
                                        $cparams = new JRegistry($cparams);
                                
                                $params = $cparams;
                        }
                        
                        $theme = $params->get('theme');
                        $addId = $view == 'item' ? $item->catid : -1;
                        $layout = JprovenUtility::setLayout($theme, null, null, null, $addId);
                }
                
                $isLayoutSet = true;
        }       
        
        private static function adjustUserFormLayout($item) {
                // TODO: http://mootools.net/forge/p/form_passwordstrength
                // Generate user profile fields based on definition in plugin setting
                $data = self::param('userprofilefields');
                $data = explode("\n", $data);
                $colName = 1;
                $colType = 2;
                $colLabel = 3;
                $colOptions = 4;
                $colDefault = 5;
                $colRequired = 6;
                $colClass = 7;

                $xml = '';

                foreach ($data as $d) {
                        $d = trim($d);

                        if (empty($d)) continue;

                        $d = explode(K2FieldsModelFields::VALUE_SEPARATOR, $d);

                        $name = self::_v($d, $colName);
                        $type = self::_v($d, $colType, 'text');
                        $label = self::_v($d, $colLabel);
                        $options = self::_v($d, $colOptions);
                        $default = self::_v($d, $colDefault, '');
                        $required = self::_v($d, $colRequired, '');
                        $class = self::_v($d, $colClass, '');

                        $class .= (!empty($required) ? ' required' : '');
                        if (!empty($class))
                                $class = ' class="'.trim($class).'"';

                        $xml .= '<param name="'.$name.'" type="'.$type.'" label="'.$label.'" default="'.$default.'"'.$class;

                        switch ($type) {
                                case 'sql':
                                        $xml .= ' query="'.$options.'">';
                                        break;
                                case 'list':
                                case 'radio':
                                case 'yesno':
                                case 'binary':
                                        $xml .= '>';
                                        if ($type == 'yesno' || $type == 'binary') $options = '0=No|1=Yes';
                                        $options = explode('|', $options);
                                        foreach ($options as $option) {
                                                $option = explode('=', $option);
                                                if (count($option) == 1) $option[] = $option[0];
                                                $xml .= '<option value="'.$option[0].'">'.$option[1].'</option>';
                                        }
                                        break;
                                case 'textarea':
                                        if (empty($options)) {
                                                $options = array(40, 10);
                                        } else {
                                                $options = explode('|', $options);
                                        }
                                        $xml .= ' col="'.$options[0].'" rows="'.$options[1].'">';
                                        break;
                                case 'text':
                                case 'hidden':
                                default:
                                        if (empty($options)) $options = 40;
                                        $xml .= ' size="'.$options.'">';
                                        break;
                        }
                        $xml .= '</param>';
                }

                $allow = self::param('userprofileallowgroup');
                $user = JFactory::getUser();

                if (isset($item->id) && $item->id == $user->id) {
                        // Editing
                        $allow = $allow == 'all';
                } else {
                        // Registering
                        $allow = $allow != 'never';
                }

                if ($allow) {
                        $where = '';

                        if ($user->guest || $user->gid < 23) {
                                 $where = " WHERE permissions LIKE '%editAll=0%'";
                        }

                        $k2Params = JprovenUtility::getK2Params();
                        $groupDefault = $k2Params->get('K2UserGroup', 1);
                        $xml = '<param name="userprovidedgroup" type="sql" default="'.$groupDefault.'" query="SELECT id AS value, name AS k2fieldsuserprovidedgroup FROM #__k2_user_groups'.$where.'" label="Profile"></param>'.$xml;
                }

                if (empty($xml)) return null;

                $xml = 
'<?xml version="1.0" encoding="utf-8"?>
<k2fields>
<params group="k2fields" addpath="/administrator/components/com_k2fields/elements">
        '.$xml.'
</params>
</k2fields>';

                $xmlParser = JFactory::getXMLParser('Simple');
                // TODO: temporary fix with name isf $this->pluginName
                $form = new K2Parameter($item->plugins, '', 'k2fields');

                if ($xmlParser->loadString($xml)) {
                        if ($params = $xmlParser->document->params) {
                                foreach ($params as $param) {
                                        $form->setXML($param);
                                }
                        }
                }

                $fields = $form->render('plugins', 'k2fields');
                $plugin = new JObject;
                // TODO: temporary fix with human readable name ($this->pluginNameHumanReadable)
                $plugin->set('name', 'Extending K2');
                $plugin->set('fields', $fields);

                return $plugin;                
        }
        
//        private function addTemplatePathsForItem() {
//                $option = JRequest::getCmd('option');
//                $view = JRequest::getWord('view');
//                
//                if ($option != 'com_k2' || $view != 'item') return;
//                
//                // rating template
//                $controller = JprovenUtility::getK2Controller();
//                
//                if (empty($controller)) return;
//                
//                $app = JFactory::getApplication();
//
//                $dirs = array(
//                    JPATH_SITE.'/components/com_k2fields/templates',
//                    JPATH_SITE.'/components/com_k2fields/templates/default',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/templates',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/templates/default',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/item',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/item/tmpl'
//                );
//
//                $document = JFactory::getDocument();
//                $viewType = $document->getType();
//                $view = $controller->getView($view, $viewType);
//                
//                foreach ($dirs as $dir) 
//                        $view->_addPath('template', $dir);
//        }
        
        /*** utilities ***/
        public static function param($name, $value = '', $dir = 'get') {
                if ($dir == 'get') return JprovenUtility::plgParam('k2fields', 'k2', $name, $value, $dir);
                
                JprovenUtility::plgParam('k2fields', 'k2', $name, $value, $dir);
        }        
        
        static function getMode($tab) {
                return $tab == 'extra-fields' ? 'edit' : $tab;
        }
        
        static function getFieldPrefix($tab = null) {
                if (empty($tab)) {
                        $type = JFactory::getApplication()->input->get('type', '');
                        
                        if ($type == 'searchfields') {
                                $tab = 'search';
                        }
                } 
                
                if ($tab == 'search' || $tab == 'menu') return 's';
                else if ($tab == 'editfields') return 'ef';
                else return 'K2ExtraField_';
//                return $tab == 'search' || $tab == 'menu' ? 's' : 'K2ExtraField_';
        }
        
        public static function loadResources($tab = null, $item = null, $addParams = null) {
                static $jsDone = false, $jsK2fDone = false, $includeDone = false, $itemDone = false, $compressedLoaded = false;
                
                $document = JFactory::getDocument();
                
                if ($tab != 'search') K2FieldsMap::loadResources($item, null, true);
                
                if (!$includeDone) {
                        JprovenUtility::load('k2fields.css', 'css');
                        $includeDone = true;
                }
                
                if (!$jsDone) {
                        JprovenUtility::loc(true, true, 'lib/datepicker.js', true);
                        //$theme = JprovenUtility::plgParam('k2fields', 'k2', 'datepickertheme', 'datepicker_dashboard');
                        $theme = 'datepicker_dashboard';
                        JprovenUtility::loc(true, true, 'lib/datepicker/'.$theme.'/'.$theme.'.css', true, 'css');
                        
                        // Loading order here is important as there is dependency
                        if ($tab == 'editfields' || $tab == 'extra-fields') {
                                JprovenUtility::loc(true, true, 'lib/Formular/formular.js', true);
                                JprovenUtility::loc(true, true, 'lib/Formular/formular.css', true, 'css');
                        }
                        
                        JprovenUtility::loc(true, true, 'lib/autocompleter.js', true);
                        
                        if ($tab == 'menu') JprovenUtility::load('jpmenuitemhandler.js', 'js');
                        
                        if (JFile::exists(JPATH_SITE.'/media/k2fields/js/k2fields.all.js')) {
                                $ver = "$Ver$";
                                JprovenUtility::load('k2fields.all.js?v='.$ver, 'js');
                                $compressedLoaded = true;
                        } else {
                                JprovenUtility::load('jpform.js', 'js');
                                JprovenUtility::load('jputility.js', 'js');
                                JprovenUtility::load('jpsearch.js', 'js');
                                JprovenUtility::load('jpvalidator.js', 'js');
                                JprovenUtility::load('k2fields_options.js', 'js');
                                JprovenUtility::load('k2fields.js', 'js');
                                JprovenUtility::load('jpprocessor.js', 'js');
                        }
                        
                        $modalize = JprovenUtility::plgParam('k2fields', 'k2', 'modalizelinks');
                        
                        if (!empty($modalize)) {
                                $modalizes = explode("\n", $modalize);
                                $uri = JURI::getInstance();
                                $path = $uri->getPath();
                                if (strpos($path, 'index.php') === false) $path = $path . 'index.php';
                                
                                foreach ($modalizes as &$modalize) {
                                        $modalize = explode(K2FieldsModelFields::VALUE_SEPARATOR, $modalize);
                                        if ($modalize[0]) $modalize[0] = JURI::root(true).'/'.$modalize[0];
                                        if ($modalize[1]) $modalize[1] = $path.'?'.$modalize[1];
                                }
                        } else {
                                $modalizes = array();
                        }
                        
                        $returnvalue = JFactory::getURI();
			$returnvalue = $returnvalue->toString(array('path', 'query', 'fragment'));
                        $returnvalue = base64_encode($returnvalue);
                        
                        $returnvalue = array(array(JURI::root(true).'/logout', JURI::root(true).'/index.php?option=com_user&task=logout', $returnvalue));
                        
                        $document->addScriptDeclaration("\n".'window.addEvent("domready", function(){ new JPProcessor({"jmodal":'.json_encode($modalizes).', "returnvalue":'.json_encode($returnvalue).'}).process(); });');
                        
                        $jsDone = true;
                }
                
                if (in_array($tab, array('extra-fields', 'search', 'menu', 'editfields')) && !$jsK2fDone) {
                        $document = JFactory::getDocument();
                        
                        if (!$compressedLoaded && JprovenUtility::plgParam('k2fields', 'k2', 'preloadjsmodules', true) && $tab != 'k2fields-editor') {
                                static $modules = array('basic', 'complex', 'list', 'map', 'media', 'k2item');
                                
                                foreach ($modules as $module) {
                                        JprovenUtility::load('k2fields'.$module.'.js', 'js');
                                }
                        }
                        
                        K2Model::getInstance('searchterms', 'k2fieldsmodel');
                        
                        $params = array(
                                'listItemSeparator' => K2FieldsModelFields::LIST_ITEM_SEPARATOR,
                                'listConditionSeparator' => K2FieldsModelFields::LIST_CONDITION_SEPARATOR,
                                'valueSeparator' => K2FieldsModelFields::VALUE_SEPARATOR,
                                'multiValueSeparator' => K2FieldsModelFields::MULTI_VALUE_SEPARATOR,
                                'userAddedValuePrefix' => K2FieldsModelFields::USERADDED,
                                'base' => JURI::root(),
                                'k2fbase' => JprovenUtility::loc(),
                                'mode' => self::getMode($tab),
                                'pre' => self::getFieldPrefix($tab),
                                'extendables' => self::getExtendables(),
                                'selfName' => K2FieldsModelFields::JS_VAR_NAME,
                                'maxListItem' => K2FieldsModelFields::setting('listmax'),
                                'autoFields' => K2FieldsModelFields::$autoFieldTypes,
                                'maxFieldLength' => K2FieldsModelFields::setting('alphafieldmaxlength'),
                                'view'=>  JFactory::getApplication()->input->get('view')
                        );
                        
                        if (isset($addParams)) $params = array_merge($params, $addParams);
                        
                        $params = json_encode($params);
                        
                        $document->addScriptDeclaration('var '.K2FieldsModelFields::JS_VAR_NAME.' = new k2fields('.$params.');');
                        
                        if ($tab == 'editfields' || $tab == 'extra-fields') {
                                JprovenUtility::loc(true, true, 'lib/Form.AutoGrow.js', true);
                                JprovenUtility::loc(true, true, 'lib/Form.Placeholder.js', true);
                        }
                        
                        $jsK2fDone = true;
                        
                        if ($tab == 'search') JprovenUtility::load('jpsearch.js', 'js');
                        
//                        if ($tab == 'extra-fields') {
//                                JprovenUtility::load('lib/markitup/images/style.css', 'css');
//                                JprovenUtility::load('lib/markitup/markitup/jquery.markitup.js', 'js');
//                                JprovenUtility::load('lib/markitup/markitup/sets/default/set.js', 'js');
//                                JprovenUtility::load('lib/markitup/markitup/skins/simple/style.css', 'css');
//                                JprovenUtility::load('lib/markitup/markitup/sets/default/style.css', 'css');
//                        }
                }        
        }
        
        static function getExtendables() {
                jimport('joomla.filesystem.folder');
                
                $loc = JprovenUtility::loc(false, true) . 'js';
                $files = JFolder::files($loc, 'k2fields[a-z0-9]+\.js', false, false);
                
                foreach ($files as &$file) $file = str_replace(array('k2fields', '.js'), '', $file);
                
                return $files;
        }
        
        static function getK2Fields($value = null, $mode = 'group', $modeFilter = null) {
                if (is_object($value)) {
                        $value = $value->catid;
                        $mode = 'group';
                }

                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $fields = $model->getFields($value, $mode, $modeFilter);
                
                return $fields;
        }
        
        function processSearchPlugins(&$item) {
                $searchRecord = K2FieldsModelFields::categorySetting($item->catid, 'autorelatedlistgenerate');
                $o = new stdClass();
                $plg = '';
                $o->text = $item->text;
                $o->id = $item->id;
                $plg = '';
                
                if (!empty($searchRecord)) {
                        $foundCat = JprovenUtility::firstKey($searchRecord);
                        $searchRecord = JprovenUtility::first($searchRecord);
                        $searchRecord = $searchRecord[0];
                        
                        // 0. search within categoryid
                        // 1. generate as(url,list)
                        // 2. based on (tag|keyword|density based keyword(TBI)|fieldids,fieldposition)
                        // 3. fixed values(if field)
                        // 4. search in (tag|keyword|density based keyword(TBI)|fieldids,fieldposition)
                        // 5. exclude specific sub-categories|all
                        $colAs = 1;
                        $colBase = 2;
                        $colFixedValue = 3;
                        $colSearchCat = 4;
                        $colSearchIn = 5;
                        $colPos = 6;
                        $colExcludeSubCat = 7;
                        $colExcludeSearchSubCat = 8;
                        
                        $excluded = false;
                        $excluded = self::_v($searchRecord, $colExcludeSubCat, false);
                        
                        if (!empty($excluded)) {
                                $excluded = explode(',', $excluded);

                                $excluded = in_array($item->catid, $excluded) || 
                                        (in_array('all', $excluded) && $foundCat != $item->catid);
                        }
                        
                        if (!$excluded) {
                                $plg = '{k2fsearch ';
                                
                                $catid = self::_v($searchRecord, $colSearchCat);
                                
                                if (!empty($catid))
                                        $plg .= ' cid='.$catid;
                                
                                $as = self::_v($searchRecord, $colAs, self::param('defaultrelatedas'));
                                
                                if (!empty($as))
                                        $plg .= ' as='.$as;
                                
                                $searchValues = self::_v($searchRecord, $colFixedValue);
                                $basedOn = self::_v($searchRecord, $colBase);
                                
                                if (empty($basedOn) && empty($searchValues)) {
                                        $basedOn = 'keyword';
                                }
                                
                                if ($basedOn == 'tag') {
                                        // TBI
                                        $query = 'SELECT DISTINCT t.name FROM #__k2_tags AS t, #__k2_tags_xref r WHERE t.id = r.tagID AND r.itemID = '.$item->id;
                                        $db = JFactory::getDBO();
                                        $db->setQuery($query);
                                        $searchValues = $db->loadResultArray();
                                } else if ($basedOn == 'keyword') {
                                        // TBI
                                        $searchValues = $item->metakey;
                                        
                                        if (!empty($searchValues)) {
                                                if (strpos($searchValues, '||')) {
                                                        $searchValues = explode('||', $searchValues);
                                                } else {
                                                        $searchValues = explode(',', $searchValues);
                                                }
                                        }
                                } else if ($basedOn == 'density') {
                                        // TBI
                                } else if (!empty($basedOn)) {
                                        $flds = explode('|', $basedOn);
                                        $basedOn = 'fields';
                                        K2Model::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/models/');
                                        $model = K2Model::getInstance('fields', 'K2FieldsModel');
                                        $ids = array();
                                        
                                        foreach ($flds as $fld) {
                                                if (!is_numeric($fld)) 
                                                        list($fld, $pos) = explode(',', $fld);
                                                
                                                $ids[] = $fld;        
                                        }
                                        
                                        $_searchValues = $model->itemValues($item->id, $ids);
                                        $searchValues = array();
                                        
                                        foreach ($flds as $fld) {
                                                $pos = -1;
                                                
                                                if (!is_numeric($fld)) 
                                                        list($fld, $pos) = explode(',', $fld);
                                                
                                                $ids[] = $fld;
                                                
                                                if (isset($_searchValues[$fld])) {
                                                        $val = $_searchValues[$fld];
                                                        
                                                        foreach ($val as $v) {
                                                                if ($pos != -1 && $v->partindex == $pos || $v->partindex != -1) {
                                                                        $searchValues[] = $v->value;
                                                                        break;
                                                                }
                                                        }
                                                }
                                        }
                                }
                                
                                if (!empty($searchValues) && ($basedOn == 'keyword' || $basedOn == 'tag')) {
                                        $val = $searchValues;
                                        $searchValues = array();
                                        
                                        foreach ($val as $v)
                                                $searchValues[] = explode('|', $v);
                                        
                                        if ($basedOn == 'keyword') {
                                                $act = (bool) self::param('removekeywordrelated');
                                                
                                                if ($act) {
                                                        $item->metakey = '';
                                                } else {
                                                        $item->metakey = str_replace(array('|', '||'), ',', $item->metakey);
                                                }
                                        }
                                }
                                
                                if (empty($basedOn) || empty($searchValues)) {
                                        $searchValues = self::_v($searchRecord, $colFixedValue);
                                        $basedOn = 'fixed';
                                }
                                
                                if ($basedOn == 'fixed' && !empty($searchValues)) {
                                        $searchValues = explode('||', $searchValues);
                                        
                                        foreach ($searchValues as &$searchValue) 
                                                $searchValue = explode('|', $searchValue);
                                }
                                
                                $searchIn = self::_v($searchRecord, $colSearchIn, 'text');
                                
                                if ($searchIn == 'text') {
                                        $plg .= ' ft='.$searchValues;
                                } else if (!empty($searchIn)) {
                                        $sFlds = explode('||', $searchIn);
                                        $searchIn = 'fields';
                                        
                                        foreach ($sFlds as $i => $sFld) {
                                                foreach ($searchValues[$i] as $val)
                                                        $plg .= ' '.$sFld.'='.$val;
                                        }
                                }
                                
                                if (empty($searchIn)) 
                                        $plg = '';
                        }
                        
                        if (!empty($plg)) {
                                $plg .= '}';
                                
                                $pos = self::_v($searchRecord, $colPos, self::param('defaultrelatedposition'));
                                
                                switch ($pos) {
                                        case 'start':
                                        case 'first':
                                                $o->text = $plg . $o->text;
                                                
                                                break;
                                        case 'afterintro':
                                        case 'beforefull':
                                                list($intro, $full) = explode('{K2Splitter}', $o->text);
                                                
                                                if ($pos == 'afterintro') {
                                                        $intro .= $plg;
                                                } else if ($pos == 'beforefull') {
                                                        $full = $plg . $full;
                                                }
                                                
                                                $o->text = $intro . '{K2Splitter}' . $full;
                                                
                                                break;
                                        case 'last':
                                        case 'end':
                                        default:
                                                $o->text .= $plg;
                                                
                                                break;
                                }
                        }
                }
                
                $o = JprovenUtility::replacePluginValues($o, 'k2fsearch');
                $item->text = $o->text;
        }
}