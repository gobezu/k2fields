<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

require_once JPATH_SITE.'/components/com_k2/views/itemlist/view.html.php';

/** Original copyright with only difference introduced is the com_k2fields pathes at the bottom of the display method
 * @version		$Id: view.html.php 1607 2012-06-15 09:14:44Z lefteris.kavadas $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2012 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class K2FieldsViewItemlist extends K2View {

	function display($tpl = null) {

		$mainframe = &JFactory::getApplication();
		$params = &K2HelperUtilities::getParams('com_k2');
		$model = &$this->getModel('itemlist');
		$limitstart = JRequest::getInt('limitstart');
		$view = JRequest::getWord('view');
		$task = JRequest::getWord('task');
		$db = &JFactory::getDBO();

		// Add link
		if (K2HelperPermissions::canAddItem())
			$addLink = JRoute::_('index.php?option=com_k2&view=item&task=add&tmpl=component');
		$this->assignRef('addLink', $addLink);

		// Get data depending on task
		switch ($task) {

			case 'category':
				// Get category
				$id = JRequest::getInt('id');
				JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');
				$category = &JTable::getInstance('K2Category', 'Table');
				$category->load($id);

				// State check
				if (!$category->published || $category->trash) {
					JError::raiseError(404, JText::_('K2_CATEGORY_NOT_FOUND'));
				}

				// Access check
				$user = &JFactory::getUser();
				if(K2_JVERSION=='16'){
					if (!in_array($category->access, $user->authorisedLevels())) {
						JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
					}
					$languageFilter = $mainframe->getLanguageFilter();
					$languageTag = JFactory::getLanguage()->getTag();
					if($languageFilter && $category->language!=$languageTag && $category->language!='*') {
						return;
					}
				}
				else {
					if ($category->access > $user->get('aid', 0)) {
						JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
					}
				}

				// Hide the add new item link if user cannot post in the specific category
				if (!K2HelperPermissions::canAddItem($id)) {
					unset($this->addLink);
				}

				// Merge params
				$cparams = new JRegistry($category->params);
				
				// Get the meta information before merging params since we do not want them to be inherited
				$category->metaDescription = $cparams->get('catMetaDesc');
				$category->metaKeywords = $cparams->get('catMetaKey');
				$category->metaRobots = $cparams->get('catMetaRobots');
				$category->metaAuthor = $cparams->get('catMetaAuthor');
				
				if ($cparams->get('inheritFrom')) {
						$masterCategory = &JTable::getInstance('K2Category', 'Table');
						$masterCategory->load($cparams->get('inheritFrom'));
						$cparams = new JRegistry($masterCategory->params);
				}
				$params->merge($cparams);

				// Category link
				$category->link = urldecode(JRoute::_(K2HelperRoute::getCategoryRoute($category->id.':'.urlencode($category->alias))));

				// Category image
				$category->image = K2HelperUtilities::getCategoryImage($category->image, $params);

				// Category plugins
				$dispatcher = &JDispatcher::getInstance();
				JPluginHelper::importPlugin('content');
				$category->text = $category->description;
                if(K2_JVERSION=='16')
                {
                    $dispatcher->trigger('onContentPrepare', array ('com_k2.category', &$category, &$params, $limitstart));
                }
                else {
                    $dispatcher->trigger('onPrepareContent', array ( & $category, &$params, $limitstart));
                }
				
				$category->description = $category->text;

				// Category K2 plugins
				$category->event->K2CategoryDisplay = '';
				JPluginHelper::importPlugin('k2');
				$results = $dispatcher->trigger('onK2CategoryDisplay', array(&$category, &$params, $limitstart));
				$category->event->K2CategoryDisplay = trim(implode("\n", $results));
				$category->text = $category->description;
				$dispatcher->trigger('onK2PrepareContent', array ( & $category, &$params, $limitstart));
				$category->description = $category->text;

				$this->assignRef('category', $category);
				$this->assignRef('user', $user);

				// Category children
				$ordering = $params->get('subCatOrdering');
				$children = $model->getCategoryFirstChildren($id, $ordering);
				if (count($children)) {
					foreach ($children as $child) {
						if ($params->get('subCatTitleItemCounter')) {
							$child->numOfItems = $model->countCategoryItems($child->id);
						}
						$child->image = K2HelperUtilities::getCategoryImage($child->image, $params);
						$child->name = htmlspecialchars($child->name, ENT_QUOTES);
						$child->link = urldecode(JRoute::_(K2HelperRoute::getCategoryRoute($child->id.':'.urlencode($child->alias))));
						$subCategories[] = $child;
					}
					$this->assignRef('subCategories', $subCategories);
				}

				// Set limit
				$limit = $params->get('num_leading_items') + $params->get('num_primary_items') + $params->get('num_secondary_items') + $params->get('num_links');

				// Set featured flag
				JRequest::setVar('featured', $params->get('catFeaturedItems'));

				// Set layout
				$this->setLayout('category');

				// Set title
				$title = $category->name;
				$category->name = htmlspecialchars($category->name, ENT_QUOTES);

				// Set ordering
				if($params->get('singleCatOrdering')) {
					$ordering = $params->get('singleCatOrdering');
				}
				else {
					$ordering = $params->get('catOrdering');
				}

				$addHeadFeedLink = $params->get('catFeedLink');

				break;

			case 'user':
				// Get user
				$id = JRequest::getInt('id');
				$userObject = &JFactory::getUser($id);

				// Check user status
				if ($userObject->block) {
					JError::raiseError(404, JText::_('K2_USER_NOT_FOUND'));
				}

				// Get K2 user profile
				$userObject->profile = $model->getUserProfile();

				// User image
				$userObject->avatar = K2HelperUtilities::getAvatar($userObject->id, $userObject->email, $params->get('userImageWidth'));

				// User K2 plugins
				$userObject->event->K2UserDisplay = '';
				if (is_object($userObject->profile) && $userObject->profile->id > 0) {
					$dispatcher = &JDispatcher::getInstance();
					JPluginHelper::importPlugin('k2');
					$results = $dispatcher->trigger('onK2UserDisplay', array(&$userObject->profile, &$params, $limitstart));
					$userObject->event->K2UserDisplay = trim(implode("\n", $results));
				}
				$this->assignRef('user', $userObject);

				$date = &JFactory::getDate();
				$now = $date->toMySQL();
				$this->assignRef('now', $now);

				// Set layout
				$this->setLayout('user');

				// Set limit
				$limit = $params->get('userItemCount');

				// Set title
				$title = $userObject->name;
				$userObject->name = htmlspecialchars($userObject->name, ENT_QUOTES);

				// Set ordering
				$ordering = $params->get('userOrdering');

				$addHeadFeedLink = $params->get('userFeedLink',1);

				break;

			case 'tag':
				// Set layout
				$this->setLayout('tag');

				// Set limit
				$limit = $params->get('tagItemCount');

				// Set title
				$title = JText::_('K2_DISPLAYING_ITEMS_BY_TAG').' '.JRequest::getVar('tag');

				// Set ordering
				$ordering = $params->get('tagOrdering');

				$addHeadFeedLink = $params->get('tagFeedLink',1);

				break;

			case 'search':
				// Set layout
				$this->setLayout('generic');

				// Set limit
				$limit = $params->get('genericItemCount');

				// Set title
				$title = JText::_('K2_SEARCH_RESULTS_FOR').' '.JRequest::getVar('searchword');

				$addHeadFeedLink = $params->get('genericFeedLink',1);

				break;

			case 'date':
				// Set layout
				$this->setLayout('generic');

				// Set limit
				$limit = $params->get('genericItemCount');

				// Set title
				if (JRequest::getInt('day')) {
					$date = strtotime(JRequest::getInt('year').'-'.JRequest::getInt('month').'-'.JRequest::getInt('day'));
					$dateFormat = (K2_JVERSION == '15')? '%A, %d %B %Y': 'l, d F Y';
					$title = JText::_('K2_ITEMS_FILTERED_BY_DATE').' '.JHTML::_('date', $date, $dateFormat);
				} else {
					$date = strtotime(JRequest::getInt('year').'-'.JRequest::getInt('month'));
					$dateFormat = (K2_JVERSION == '15')? '%B %Y': 'F Y';
					$title = JText::_('K2_ITEMS_FILTERED_BY_DATE').' '.JHTML::_('date', $date, $dateFormat);
				}
				// Set ordering
				$ordering = 'rdate';
				
				$addHeadFeedLink = $params->get('genericFeedLink',1);

				break;

			default:
				// Set layout
				$this->setLayout('category');
				$user = &JFactory::getUser();
				$this->assignRef('user', $user);

				// Set limit
				$limit = $params->get('num_leading_items') + $params->get('num_primary_items') + $params->get('num_secondary_items') + $params->get('num_links');
				// Set featured flag
				JRequest::setVar('featured', $params->get('catFeaturedItems'));

				// Set title
				$title = $params->get('page_title');

				// Set ordering
				$ordering = $params->get('catOrdering');

				$addHeadFeedLink = $params->get('catFeedLink',1);

				break;

		}

		// Set limit for model
		if (!$limit) $limit = 10;
		JRequest::setVar('limit', $limit);

		// Get items
		if(!isset($ordering)) {
			$items = $model->getData();
		}
		else {
			$items = $model->getData($ordering);
		}
		

		// Pagination
		jimport('joomla.html.pagination');
		$total = $model->getTotal();
		$pagination = new JPagination($total, $limitstart, $limit);

		//Prepare items
		$user = &JFactory::getUser();
		$cache = &JFactory::getCache('com_k2_extended');
		$model = &$this->getModel('item');

		for ($i = 0; $i < sizeof($items); $i++) {

			//Item group
			if ($task == "category" || $task == "") {
				if ($i < ($params->get('num_links') + $params->get('num_leading_items') + $params->get('num_primary_items') + $params->get('num_secondary_items')))
					$items[$i]->itemGroup = 'links';
				if ($i < ($params->get('num_secondary_items') + $params->get('num_leading_items') + $params->get('num_primary_items')))
					$items[$i]->itemGroup = 'secondary';
				if ($i < ($params->get('num_primary_items') + $params->get('num_leading_items')))
					$items[$i]->itemGroup = 'primary';
				if ($i < $params->get('num_leading_items'))
					$items[$i]->itemGroup = 'leading';
			}

			// Check if the model should use the cache for preparing the item even if the user is logged in
			if ($user->guest || $task=='tag' || $task=='search' || $task=='date'){
				$cacheFlag = true;
			} else {
				$cacheFlag = true;
				if (K2HelperPermissions::canEditItem($items[$i]->created_by, $items[$i]->catid)){
					$cacheFlag = false;
				}
			}

			// Prepare item
			if ($cacheFlag){
				$hits = $items[$i]->hits;
				$items[$i]->hits = 0;
				JTable::getInstance('K2Category', 'Table');
				$items[$i] = $cache->call(array('K2ModelItem', 'prepareItem'), $items[$i], $view, $task);
				$items[$i]->hits = $hits;
			} else {
				$items[$i] = $model->prepareItem($items[$i], $view, $task);
			}

			// Plugins
			$items[$i] = $model->execPlugins($items[$i], $view, $task);

			// Trigger comments counter event
			$dispatcher = &JDispatcher::getInstance();
			JPluginHelper::importPlugin ('k2');
			$results = $dispatcher->trigger('onK2CommentsCounter', array ( & $items[$i], &$params, $limitstart));
			$items[$i]->event->K2CommentsCounter = trim(implode("\n", $results));
		}

		// Set title
		$document = &JFactory::getDocument();
		$menus = &JSite::getMenu();
		$menu = $menus->getActive();
		if (is_object($menu)) {
				$menu_params = new JRegistry($menu->params);
				if (!$menu_params->get('page_title'))
						$params->set('page_title', $title);
		} else {
				$params->set('page_title', $title);
		}

		if(K2_JVERSION == '16') {
			if ($mainframe->getCfg('sitename_pagetitles', 0) == 1) {
				$tmpTitle = JText::sprintf('JPAGETITLE', $mainframe->getCfg('sitename'), $params->get('page_title'));
				$params->set('page_title', $tmpTitle);
			}
			elseif ($mainframe->getCfg('sitename_pagetitles', 0) == 2) {
				$tmpTitle = JText::sprintf('JPAGETITLE', $params->get('page_title'), $mainframe->getCfg('sitename'));
				$params->set('page_title', $tmpTitle);
			}
		}
		$document->setTitle($params->get('page_title'));

		// Set metadata for category
		if($task == 'category') {
			if ($category->metaDescription) {
				$document->setDescription($category->metaDescription);
			}
			else {
				$metaDescItem = preg_replace("#{(.*?)}(.*?){/(.*?)}#s", '', $this->category->description);
				$metaDescItem = K2HelperUtilities::characterLimit($metaDescItem, $params->get('metaDescLimit', 150));
				$metaDescItem = htmlspecialchars($metaDescItem, ENT_QUOTES, 'UTF-8');
				$document->setDescription($metaDescItem);
			}
			if ($category->metaKeywords) {
				$document->setMetadata('keywords', $category->metaKeywords);
			}
			if ($category->metaRobots) {
				$document->setMetadata('robots', $category->metaRobots);
			}
			if ($category->metaAuthor) {
				$document->setMetadata('author', $category->metaAuthor);
			}
		}

		if(K2_JVERSION == '16') {

			// Menu metadata options
			if ($params->get('menu-meta_description')) {
				$document->setDescription($params->get('menu-meta_description'));
			}

			if ($params->get('menu-meta_keywords')) {
				$document->setMetadata('keywords', $params->get('menu-meta_keywords'));
			}

			if ($params->get('robots')) {
				$document->setMetadata('robots', $params->get('robots'));
			}

			// Menu page display options
			if($params->get('page_heading')) {
				$params->set('page_title', $params->get('page_heading'));
			}
			$params->set('show_page_title', $params->get('show_page_heading'));

		}

		// Pathway
		$pathway = &$mainframe->getPathWay();
		if (!isset($menu->query['task'])) $menu->query['task']='';
		if ($menu) {
			switch ($task) {
				case 'category':
					if ($menu->query['task']!='category' || $menu->query['id']!= JRequest::getInt('id'))
						$pathway->addItem($title, '');
					break;
				case 'user':
					if ($menu->query['task']!='user' || $menu->query['id']!= JRequest::getInt('id'))
						$pathway->addItem($title, '');
					break;

				case 'tag':
					if ($menu->query['task']!='tag' || $menu->query['tag']!= JRequest::getVar('tag'))
						$pathway->addItem($title, '');
					break;

				case 'search':
				case 'date':
					$pathway->addItem($title, '');
					break;
			}
		}

		// Feed link
		$config =& JFactory::getConfig();
		$menu = &JSite::getMenu();
		$default = $menu->getDefault();
		$active = $menu->getActive();
		if ($task=='tag'){
			$link = K2HelperRoute::getTagRoute(JRequest::getVar('tag'));
		} else {
			$link='';
		}
		if (!is_null($active) && $active->id==$default->id && $config->getValue('config.sef')){
			$link.= '&Itemid='.$active->id.'&format=feed&limitstart=';
		} else {
			$link.= '&format=feed&limitstart=';
		}

		$feed = JRoute::_($link);
		$this->assignRef('feed', $feed);

		// Add head feed link
		if ($addHeadFeedLink){
			$attribs = array('type'=>'application/rss+xml', 'title'=>'RSS 2.0');
			$document->addHeadLink(JRoute::_($link.'&type=rss'), 'alternate', 'rel', $attribs);
			$attribs = array('type'=>'application/atom+xml', 'title'=>'Atom 1.0');
			$document->addHeadLink(JRoute::_($link.'&type=atom'), 'alternate', 'rel', $attribs);
		}
		
		// Load Facebook meta tag for category image (don't use the placeholder)
		if($task == 'category' && $this->category->image && strpos($this->category->image,'placeholder/category.png')===false)
		{
			//$document->setMetaData('image', JString::str_ireplace(JURI::root(true).'/', JURI::root(false), $this->category->image));
			$document->setMetaData('image',substr(JURI::root(),0,-1).str_replace(JURI::root(true),'',$this->category->image));
		}
		
		// Assign data
		if ($task == "category" || $task == "") {
				$leading = @array_slice($items, 0, $params->get('num_leading_items'));
				$primary = @array_slice($items, $params->get('num_leading_items'), $params->get('num_primary_items'));
				$secondary = @array_slice($items, $params->get('num_leading_items') + $params->get('num_primary_items'), $params->get('num_secondary_items'));
				$links = @array_slice($items, $params->get('num_leading_items') + $params->get('num_primary_items') + $params->get('num_secondary_items'), $params->get('num_links'));
				$this->assignRef('leading', $leading);
				$this->assignRef('primary', $primary);
				$this->assignRef('secondary', $secondary);
				$this->assignRef('links', $links);
		} else {
				$this->assignRef('items', $items);
		}

		// Set default values to avoid division by zero
		if ($params->get('num_leading_columns')==0)
			$params->set('num_leading_columns',1);
		if ($params->get('num_primary_columns')==0)
			$params->set('num_primary_columns',1);
		if ($params->get('num_secondary_columns')==0)
			$params->set('num_secondary_columns',1);
		if ($params->get('num_links_columns')==0)
			$params->set('num_links_columns',1);

		$this->assignRef('params', $params);
		$this->assignRef('pagination', $pagination);

		// Look for template files in component folders
		$this->_addPath('template', JPATH_SITE.'/components/com_k2fields/templates');
		$this->_addPath('template', JPATH_SITE.'/components/com_k2fields/templates/default');
                
                $tmpl = $mainframe->getTemplate();

		// Look for overrides in template folder (K2 template structure)
		$this->_addPath('template', JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/templates');
		$this->_addPath('template', JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/templates/default');

		// Look for overrides in template folder (Joomla! template structure)
		$this->_addPath('template', JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields');
		$this->_addPath('template', JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/default');

		// Look for specific K2 theme files
		if ($params->get('theme')) {
                        $this->_addPath('template', JPATH_SITE.'/components/com_k2fields/templates/'.$params->get('theme'));
                        $this->_addPath('template', JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/'.$params->get('theme'));
                        $this->_addPath('template', JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/templates/'.$params->get('theme'));
		}
                
		$nullDate = $db->getNullDate();
		$this->assignRef('nullDate', $nullDate);
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin ('k2');
		$dispatcher->trigger('onK2BeforeViewDisplay');
		parent::display($tpl);

	}
}
