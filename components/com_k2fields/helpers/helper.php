<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');

class K2FieldsHelper { 
        public static $availableTabs = array('content', 'image', 'gallery', 'video', 'extrafields', 'attachments', 'plugins');

        public static function getItemlistLimit($category = null) {
                if (JFactory::getApplication()->input->get('format', '', 'word') == 'feed') {
                        $params = JComponentHelper::getParams('com_k2');
                        return $params->get('feedLimit');
                }
                
                if (JFactory::getApplication()->input->get('limit', '', 'int')) {
                        return JFactory::getApplication()->input->get('limit', '', 'int');
                }
                
                if (empty($category) || $category <= 0) {
                        $jpmode = JprovenUtility::isAjaxCall() ? 'jpmode' : '';
                        
                        return K2FieldsModelFields::setting($jpmode.'itemlistlimit', null, 10);
                }
                
                $params = K2HelperUtilities::getParams('com_k2');
                
                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                
                if (!($category instanceof TableK2Category)) {
                        $category = JTable::getInstance('K2Category', 'Table');
                        $category->load($category);
                }
                                
                $cparams = new JRegistry($category->params);
                
                if ($cparams->get('inheritFrom')) {
                        $masterCategory = JTable::getInstance('K2Category', 'Table');
                        $masterCategory->load($cparams->get('inheritFrom'));
                        $cparams = new JRegistry($masterCategory->params);
                }
                
                $params->merge($cparams);
                
                JprovenUtility::normalizeK2Parameters($category, $params);
                
                $limit = 
                        $params->get('num_leading_items') + 
                        $params->get('num_primary_items') + 
                        $params->get('num_secondary_items') + 
                        $params->get('num_links')
                        ;
                
                return $limit;
        }
        
        public static function getTabs($catId) {
                $tabs = K2FieldsModelFields::categorySetting($catId, 'accesstabsineditform');
                
                if (!empty($tabs)) {
                        $tabs = $tabs[key($tabs)][0];
                        $excludes = array_shift($tabs);
                        $excludes = empty($excludes) ? array() : explode(',', $excludes);
                        $client = array_pop($tabs);
                        
                        if (!in_array($client, array('site', 'admin', 'all', ''))) {
                                $tabs[] = $client;
                                $client = 'all';
                        } else if (empty($client)) {
                                $client = 'all';
                        }
                        
                        $cclient = JFactory::getApplication()->isSite() ? 'site' : 'admin';
                        
                        if ($cclient == $client || $client == 'all') {
                                $tabs = array_shift($tabs);
                                $tabs = explode(',', $tabs);
                                $tabs = array('excludes'=>$excludes, 'tabs'=>$tabs);
                        } else {
                                $tabs = null;
                        }
                }
                
                return $tabs;
        }
        
        public static function getPostCategories() {
                //adapted version of function K2ModelCategories::categoriesTree
                $db = JFactory::getDBO();
                $query = "SELECT c.*, (SELECT COUNT(*) FROM #__k2_extra_fields f WHERE f.group = c.extraFieldsGroup) AS ef FROM #__k2_categories c WHERE c.id > 0 AND c.published=1  AND c.trash=0 ORDER BY c.parent, c.ordering";
                $db->setQuery($query);
                $mitems = $db->loadObjectList();
                $children = array ();
                if ($mitems) {
                        foreach ($mitems as $v) {
                                $pt = $v->parent;
                                $list = @$children[$pt]?$children[$pt]: array ();
                                array_push($list, $v);
                                $children[$pt] = $list;
                        }
                }
                $list = JHTML::_('menu.treerecurse', 0, '', array (), $children, 9999, 0, 0);
                $cats = $mitems;
                $cats = JprovenUtility::indexBy($cats, 'id');
                $mitems = array ();
                foreach ($list as $item) {
                        $mitems[] = JHTML::_('select.option', $item->id, '&nbsp;&nbsp;&nbsp;'.$item->treename);
                }
                require_once JPATH_SITE.'/components/com_k2/helpers/permissions.php';
                for ($i = 0, $n = sizeof($mitems); $i < $n; $i++) {
                        if (!self::isPostable($mitems[$i]->value, $cats) || !K2HelperPermissions::canAddItem($mitems[$i]->value)) {
                                $mitems[$i]->disable = true;
                        }
                }
                $categories_option[] = JHTML::_('select.option', 0, '- '.JText::_('Add item to').' -');
                $categories_options = @array_merge($categories_option, $mitems);
                $categories_options = JHTML::_('select.genericlist', $categories_options, 'editorLauncher', '', 'value', 'text');   
                return $categories_options;
        }
        
        public static function isPostable($catId, $cats) {
                $tabs = self::getTabs($catId);
                array_shift($tabs);
                $isOnlyExtraFieldsTab = true;
                
                foreach ($tabs as $i => &$tab) {
                        $tab = explode('=', $tab);
                        if ($tab[0] != 'extrafields') {
                                $isOnlyExtraFieldsTab = false;
                                break;
                        }
                }
                
                return !$isOnlyExtraFieldsTab || (int) $cats[$catId][0]->ef > 0;
        }
}

?>
