<?php

//$Copyright$
/**
 * The following is an adaptation of components/com_k2/helpers/route.php in order 
 * to accommodate pathway additions of search terms and also expanding the search of matching menu items to
 * include parent category (the whole category path)
 * 
 * All methods remain the same except getItemRoute which gets addition as per comment below
 */
/**
 * @version		$Id: route.php 478 2010-06-16 16:11:42Z joomlaworks $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.gr
 * @copyright	Copyright (c) 2006 - 2010 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');

class K2FieldsHelperRoute {

        function getItemRoute($id, $catid = 0) {
                $needles = array(
                    'item' => (int) $id,
                    'itemlist' => (int) $catid,
                );

                $link = 'index.php?option=com_k2&view=item&id=' . $id;

                if ($item = self::_findItem($needles)) {
                        $link .= '&Itemid=' . $item->id;
                } else if ($catid) {
                        $input = JFactory::getApplication()->input;
                        $Itemid = $input->get('Itemid', '', 'int');
                        if ($input->get('option', '', 'cmd') == 'com_k2fields' && 
                                $input->get('cid', '', 'int') == $catid && $Itemid) {
                                $link .= '&Itemid=' . $Itemid;
                        }
                }

                if (!JprovenUtility::isAjaxCall()) {
                        require_once JPATH_SITE . '/components/com_k2fields/models/searchterms.php';

                        $s = K2FieldsModelSearchterms::getSearchUrl();
                        
                        if ($s)
                                $link .= '&' . $s;
                }

                return $link;
        }

        public static function createCategoryLink($category, $searchQueries = array(), $title = '', $mode = '', $class = 'k2fcatlink') {
                if (empty($title)) {
                        if (!is_object($category)) {
                                $db = JFactory::getDBO();
                                $db->setQuery('SELECT id, name FROM #__k2_categories WHERE id = ' . (int) $category);
                                $category = $db->loadObject();
                        }

                        $title = $category->name;
                }

                $catid = is_object($category) ? $category->id : $category;

                $task = empty($searchQueries) ? 'category' : 'search';

                array_filter($searchQueries);

                if ($task == 'category') {
                        $link = 'index.php?option=com_k2&view=itemlist&layout=category&task=category&id=' . $catid;
                        $needles = array('itemlist' => (int) $catid);
                        if ($item = self::_findItem($needles))
                                $link .= '&Itemid=' . $item->id;
                } else {
                        $item = '';

                        $link = 'index.php?option=com_k2fields&view=itemlist&cid=' . $catid;

                        if (!empty($searchQueries)) {
                                $searchQueries['task'] = 'search';
                                $needles = array('view' => 'itemlist', 'cid' => (int) $catid);
                                $needles = array_merge($needles, $searchQueries);
                                $item = self::_findItem($needles, 'com_k2fields');
                        }

                        if (empty($item)) {
                                $needles = array('view' => 'itemlist', 'cid' => (int) $catid, 'task' => 'search');
                                $item = self::_findItem($needles, 'com_k2fields');
                        }

                        if (empty($item)) {
                                $needles = array('itemlist' => (int) $catid);
                                $item = self::_findItem($needles, 'com_k2');
                        }

                        if (!empty($item)) {
                                $link .= '&Itemid=' . $item->id;
                        }
                }

                foreach ($searchQueries as $key => $val)
                        $link .= '&' . $key . '=' . $val;

                return self::createLink($link, $title, $mode, $class);
        }

        public static function createItemLink($item, $title = '', $mode = '', $class = 'k2flink') {
                if (empty($title))
                        $title = $item->title;

                $link = self::getItemRoute(self::createItemSlug($item), self::createCategorySlug($item));

                if (isset($item->k2item) && $item->k2item)
                        $link .= '&k2item=1';
                if (isset($item->k2cat) && $item->k2cat)
                        $link .= '&k2cat=' . $item->k2cat;

                return self::createLink($link, $title, $mode, $class);
        }

        public static function createItemSlug($item) {
                return $item->alias ? ($item->id . ':' . urlencode($item->alias)) : $item->id;
        }

        public static function createCategorySlug($item) {
                return isset($item->categoryalias) && $item->categoryalias ? ($item->catid . ':' . urlencode($item->categoryalias)) : $item->catid;
        }

        protected static function createLink($link, $title = '', $mode = array(), $class = '') {
                $mode = (array) $mode;

                if (in_array('ajax', $mode) || in_array('modal', $mode)) {
                        $link .= '&tmpl=component';
                        $alink = $link . '&jpmode=ajax';
                        $alink = urldecode(JRoute::_($alink));
                }

                $link = urldecode(JRoute::_($link));

                if (!empty($mode)) {
                        foreach ($mode as &$m)
                                $m = 'jp' . $m;

                        if (in_array('jpmodal', $mode)) {
                                JHTML::_('behavior.modal');
                        }
                }

                $qTitle = htmlentities($title, ENT_QUOTES, 'UTF-8');

                if (in_array('jpcollapse', $mode) || in_array('jpajax', $mode)) {
                        $href = 'javascript:void(0);';
                } else if (in_array('jpmodal', $mode)) {
                        $href = $alink;
                } else {
                        $href = $link;
                }

                $ui = '<a class="' . $class . ' ' . implode(' ', $mode) . '" href="' . $href . '" title="' . $qTitle . '">' . $title . '</a>';

                if (in_array('jpcollapse', $mode)) {
                        $ui .= '<a class="' . $class . ' jpjump jpread" href="' . $link . '" title="' . $qTitle . '">' . JText::_('Read more') . '</a>';
                }

                if (in_array('jpajax', $mode)) {
                        $ui .= '<span class="jpajaxcontainer" href="' . $alink . '">&nbsp;</span>';
                }

                return $ui;
        }

        function getCategoryRoute($catid) {
                $needles = array(
                    'itemlist' => (int) $catid
                );

                $link = 'index.php?option=com_k2&view=itemlist&task=category&id=' . $catid;

                if ($item = self::_findItem($needles)) {
                        $link .= '&Itemid=' . $item->id;
                }
                return $link;
        }

        function getUserRoute($userID) {

                if (K2_CB) {
                        global $_CB_framework;
                        return $_CB_framework->userProfileUrl((int) $userID);
                }


                $needles = array(
                    'user' => (int) $userID
                );
                $user = &JFactory::getUser($userID);
                if (K2_JVERSION == '16' && JFactory::getConfig()->get('unicodeslugs') == 1) {
                        $alias = JApplication::stringURLSafe($user->name);
                } else if (JPluginHelper::isEnabled('system', 'unicodeslug') || JPluginHelper::isEnabled('system', 'jw_unicodeSlugsExtended')) {
                        $alias = JFilterOutput::stringURLSafe($user->name);
                } else {
                        mb_internal_encoding("UTF-8");
                        mb_regex_encoding("UTF-8");
                        $alias = trim(mb_strtolower($user->name));
                        $alias = str_replace('-', ' ', $alias);
                        $alias = mb_ereg_replace('[[:space:]]+', ' ', $alias);
                        $alias = trim(str_replace(' ', '', $alias));
                        $alias = str_replace('.', '', $alias);

                        $stripthese = ',|~|!|@|%|^|(|)|<|>|:|;|{|}|[|]|&|`|â€ž|â€¹|â€™|â€˜|â€œ|â€�|â€¢|â€º|Â«|Â´|Â»|Â°|«|»|…';
                        $strips = explode('|', $stripthese);
                        foreach ($strips as $strip) {
                                $alias = str_replace($strip, '', $alias);
                        }
                        $params = &K2HelperUtilities::getParams('com_k2');
                        $SEFReplacements = array();
                        $items = explode(',', $params->get('SEFReplacements', NULL));
                        foreach ($items as $item) {
                                if (!empty($item)) {
                                        @list($src, $dst) = explode('|', trim($item));
                                        $SEFReplacements[trim($src)] = trim($dst);
                                }
                        }
                        foreach ($SEFReplacements as $key => $value) {
                                $alias = str_replace($key, $value, $alias);
                        }
                        $alias = trim($alias, '-.');
                        if (trim(str_replace('-', '', $alias)) == '') {
                                $datenow = &JFactory::getDate();
                                $alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
                        }
                }
                $link = 'index.php?option=com_k2&view=itemlist&task=user&id=' . $userID . ':' . $alias;

		if ($item = self::_findItem($needles)) {
			$link .= '&Itemid='.$item->id;
		}
		else if(K2_USERS_ITEMID) {
			$link .= '&Itemid='.K2_USERS_ITEMID;
		}                

                return $link;
        }

        function getTagRoute($tag) {

                $needles = array(
                    'tag' => $tag
                );

                $link = 'index.php?option=com_k2&view=itemlist&task=tag&tag=' . urlencode($tag);

                if ($item = self::_findItem($needles)) {
                        $link .= '&Itemid=' . $item->id;
                }
                ;

                return $link;
        }

        protected static function _findK2fieldsItem($needles) {
                $match = null;

                if (!isset($needles['itemlist']))
                        return $match;

                $needles['cid'] = $needles['itemlist'];
                unset($needles['itemlist']);
                unset($needles['item']);

                $component = JComponentHelper::getComponent('com_k2fields');
                $menus = JApplication::getMenu('site', array());
                $items = $menus->getItems('component_id', $component->id);

                if (empty($items))
                        return $match;

                foreach ($items as $item) {
                        if (JprovenUtility::contains($item->query, $needles)) {
                                $match = $item;
                                break;
                        }
                }

                return $match;
        }

        function _findItem($needles, $option = 'com_k2') {
                $match = null;

                if ($option == 'com_k2fields') {
                        $match = self::_findK2fieldsItem($needles);
                        return $match;
                }

                $component = & JComponentHelper::getComponent('com_k2');
                $menus = & JApplication::getMenu('site', array());
                $items = $menus->getItems('component_id', $component->id);

                $match = null;
                foreach ($needles as $needle => $id) {
                        if (count($items)) {
                                foreach ($items as $item) {
                                        if ($needle == 'user') {
                                                if ((@$item->query['task'] == $needle) && (@$item->query['id'] == $id)) {
                                                        $match = $item;
                                                        break;
                                                }
                                        } else if ($needle == 'tag') {
                                                if ((@$item->query['task'] == $needle) && (@$item->query['tag'] == $id)) {
                                                        $match = $item;
                                                        break;
                                                }
                                        } else {
                                                if ((@$item->query['view'] == $needle) && (@$item->query['id'] == $id)) {
                                                        $match = $item;
                                                        break;
                                                }
                                        }
                                        if (!is_null($match)) {
                                                break;
                                        }
                                }
                                // Second pass only for multiple categories links. Triggered only if we do not have find any match (link to direct category) START
                                if (is_null($match)) {
                                        foreach ($items as $item) {
                                                if ($needle == 'itemlist') {
                                                        if (!isset($item->K2Categories)) {
                                                                $menuparams = json_decode($item->params);
								$item->K2Categories = isset($menuparams->categories)? $menuparams->categories: array();
                                                        }
                                                        if (isset($item->K2Categories) && is_array($item->K2Categories)) {
                                                                foreach ($item->K2Categories as $catid) {
                                                                        if ((@$item->query['view'] == $needle) && (@(int) $catid == $id)) {
                                                                                $match = $item;
                                                                                break;
                                                                        }
                                                                }
                                                        }
                                                }
                                                if (!is_null($match)) {
                                                        break;
                                                }
                                        }
                                }
                                // Second pass END
                        }
                        if (!is_null($match)) {
                                break;
                        }
                }
                if (is_null($match) && $option == 'com_k2')
                        $match = self::_findItem($needles, 'com_k2fields');
                return $match;
        }
}