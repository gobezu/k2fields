<?php
//$Copyright$
 
// no direct access
defined('_JEXEC') or die('Restricted access');

JLoader::register('K2HelperRoute', JPATH_SITE.'/components/com_k2/helpers/route.php');

class modK2fieldsCategoryMenuHelper {
        static function _isChild($tree, $parent, $child) {
                if (!isset($tree['children'][$parent])) return false;
                
                if (in_array($child, $tree['children'][$parent])) return true;
                
                foreach ($tree['children'][$parent] as $ch) {
                        self::_isChild($tree, $ch, $child);
                }
                
                return false;
        }
        static function _rec($tree, $currentRoot, $level, $option, $view, $catid) {
                $nodes = $tree['children'][$currentRoot];
                $ui = '<ul class="'.($level == 1 ? 'menu' : 'level'.$level).'">';
                
                foreach ($nodes as $node) {
                        $cat = $tree['cats'][$node];
                        
                        if (isset($cat->itemsCount)) {
                                $cat->name .= '<span class="itemCounter"> ('.$cat->itemsCount.')</span>';
                        }
                        
                        $hasChildren = isset($tree['children'][$node]);
                        $css = ' class="';
                        $isActive = false;
                        
                        if ($option == 'com_k2') {
                                if ($view == 'item') {
                                        $item = JFactory::getApplication()->input->getInt('id');
                                        $query = 'SELECT catid FROM #__k2_items WHERE id = '.$item;
                                        $db = JFactory::getDbo();
                                        $db->setQuery($query);
                                        $catid = $db->loadResult();
                                }
                                
                                if ($catid == $cat->id) {
                                        $css .= 'active current ';
                                        $isActive = true;
                                } else if (self::_isChild($tree, $cat->id, $catid)) {
                                        $css .= 'active ';
                                        $isActive = true;
                                }
                        }
                        
                        $css .= 'level' . $level . ($hasChildren ? ' parent': '') . '"';
                        
                        $ui .= '<li' . $css . '>';
                        
                        if ($hasChildren) {
                                $ui .= '<span class="separator parent level' . $level .($isActive ? ' active' : '').'"><span>' . $cat->name . '</span></span>';
                                $ui .= self::_rec($tree, $node, $level + 1, $option, $view, $catid);
                        } else {
                                $ui .= '<a href="' . urldecode(JRoute::_(K2HelperRoute::getCategoryRoute($cat->id . ':' . urlencode($cat->alias)))) . '"><span>' . $cat->name . '</span></a>';
                        }
                        
                        $ui .= '</li>';
                }
                
                $ui .= '</ul>';
                
                return $ui;
        }
	static function treerecurse(&$params, $id = 0, $level = 0, $begin = false) {
		$id = (int) $id;
		$root_id = (int) $params->get('root_id');
                $depth = $params->get('depth', -1);
                $orderby = $params->get('categoriesListOrdering', 'id ASC');
                $tree = JprovenUtility::getK2CategoryChildren($root_id, $depth, true, false, $orderby, (bool) $params->get('categoriesListItemsCounter'));
		$catid = JFactory::getApplication()->input->getInt('id');
		$option = JFactory::getApplication()->input->getCmd('option');
		$view = JFactory::getApplication()->input->getCmd('view');
                $ui = self::_rec($tree, $root_id, 1, $option, $view, $catid);
                return $ui;
	}
}
