<?php
// $Copyright$
// Based on K2ViewItem where the only change made is as noted below that the
// model name passed to the cache call is K2fieldsModelItem instead of the fixed
// one K2ModelItem and I wish it was dynamic as follows:
// $modelName = get_class($model);
// 
/**
 * @version		$Id: view.html.php 823 2011-06-20 06:10:44Z joomlaworks $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.gr
 * @copyright	Copyright (c) 2006 - 2011 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class K2fieldsViewK2item extends JView {

	function display($tpl = null) {
		$mainframe = &JFactory::getApplication();
		$user = &JFactory::getUser();
		$document = &JFactory::getDocument();
		$params = &K2HelperUtilities::getParams('com_k2');
		$limitstart = JRequest::getInt('limitstart', 0);
		$view = JRequest::getWord('view');
		$task = JRequest::getWord('task');

		$db = &JFactory::getDBO();
		$jnow = &JFactory::getDate();
		$now = $jnow->toMySQL();
		$nullDate = $db->getNullDate();

                // changes by jproven 2011-08-24
                $view = JRequest::getCmd('view');
                if ($view != 'item') {
                        $this->setLayout('category_view_item');
                } else {
                        $this->setLayout('item_view');
                }

		//Add link
		if (K2HelperPermissions::canAddItem())
		$addLink = JRoute::_('index.php?option=com_k2&view=item&task=add&tmpl=component');
		$this->assignRef('addLink', $addLink);

		//Get item
                // changes by jproven 2011-08-23
		$model = &$this->getModel();
                $mName = $model->getName();
                $model->_name = 'item';
                $this->setModel($model);
                $model->_name = $mName;
                $itemlistModel = JModel::getInstance('itemlist', 'K2Model');
                $this->setModel($itemlistModel);
                $model = &$this->getModel();
		$item = $model->getData();
                $modelName = get_class($model);

		//Prepare item
		if ($user->guest){
			$cache = &JFactory::getCache('com_k2_extended');
			$hits = $item->hits;
			$item->hits = 0;
                        // change by jproven 2011-08-23
			$item = $cache->call(array($modelName, 'prepareItem'), $item, $view, $task);
			$item->hits = $hits;
		} else {
			$item = $model->prepareItem($item, $view, $task);
		}

		//Plugins
		$item = $model->execPlugins($item, $view, $task);

		//User K2 plugins
		$item->event->K2UserDisplay = '';
		if (is_object($item->author->profile) && isset($item->author->profile->id)) {
			$dispatcher = &JDispatcher::getInstance();
			JPluginHelper::importPlugin('k2');
			$results = $dispatcher->trigger('onK2UserDisplay', array(&$item->author->profile, &$params, $limitstart));
			$item->event->K2UserDisplay = trim(implode("\n", $results));
		}

		//Access check
		if ($this->getLayout() == 'form') {
			JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
		}
		if(K2_JVERSION=='16'){
			if (!in_array($item->access, $user->authorisedLevels()) || !in_array($item->category->access, $user->authorisedLevels())) {
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
			}
		}
		else{
			if ($item->access > $user->get('aid', 0) || $item->category->access > $user->get('aid', 0)) {
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
			}
		}
		//Published check
		if (!$item->published || $item->trash) {
			JError::raiseError(404, JText::_('K2_ITEM_NOT_FOUND'));
		}

		if ($item->publish_up != $nullDate && $item->publish_up > $now) {
			JError::raiseError(404, JText::_('K2_ITEM_NOT_FOUND'));
		}

		if ($item->publish_down != $nullDate && $item->publish_down < $now) {
			JError::raiseError(404, JText::_('K2_ITEM_NOT_FOUND'));
		}

		if (!$item->category->published || $item->category->trash) {
			JError::raiseError(404, JText::_('K2_ITEM_NOT_FOUND'));
		}

		//Increase hits counter
		$model->hit($item->id);

		//Set default image
		K2HelperUtilities::setDefaultImage($item, $view);

		//Comments
		$item->event->K2CommentsCounter = '';
		$item->event->K2CommentsBlock = '';
		if ($item->params->get('itemComments')) {
			//Trigger comments events
			$dispatcher = &JDispatcher::getInstance();
			JPluginHelper::importPlugin ('k2');
			$results = $dispatcher->trigger('onK2CommentsCounter', array ( & $item, &$params, $limitstart));
			$item->event->K2CommentsCounter = trim(implode("\n", $results));
			$results = $dispatcher->trigger('onK2CommentsBlock', array ( & $item, &$params, $limitstart));
			$item->event->K2CommentsBlock = trim(implode("\n", $results));

			//Load K2 native comments system only if there are no plugins overriding it
			if(empty($item->event->K2CommentsCounter) && empty($item->event->K2CommentsBlock)){

				//Load reCAPTCHA script
				if (!JRequest::getInt('print') && ($item->params->get('comments') == '1' || ($item->params->get('comments') == '2' && K2HelperPermissions::canAddComment($item->catid)))) {
					if ($item->params->get('recaptcha') && $user->guest) {
						$document->addScript('http://api.recaptcha.net/js/recaptcha_ajax.js');
						$js = '
	                 	function showRecaptcha(){
									    Recaptcha.create("'.$item->params->get('recaptcha_public_key').'", "recaptcha", {
									        theme: "'.$item->params->get('recaptcha_theme', 'clean').'"
									    });
										}
										$K2(window).load(function() {
											showRecaptcha();
										});
										';
						$document->addScriptDeclaration($js);
					}
				}
				
				//Check for inline comment moderation
        		if(!$user->guest && $user->id==$item->created_by && $params->get('inlineCommentsModeration')){
        		    $inlineCommentsModeration = true;
        		    $commentsPublished = false;
        		}
        		else {
        		    $inlineCommentsModeration = false;
        		    $commentsPublished = true;
        		}
        		$this->assignRef('inlineCommentsModeration', $inlineCommentsModeration);
				

				$limit = $params->get('commentsLimit');
				$comments = $model->getItemComments($item->id, $limitstart, $limit, $commentsPublished);
				$pattern = "@\b(https?://)?(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,6})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";

				for ($i = 0; $i < sizeof($comments); $i++) {
					$comments[$i]->commentText = nl2br($comments[$i]->commentText);
					$comments[$i]->commentText = preg_replace($pattern, '<a target="_blank" rel="nofollow" href="\0">\0</a>', $comments[$i]->commentText);
					$comments[$i]->userImage = K2HelperUtilities::getAvatar($comments[$i]->userID, $comments[$i]->commentEmail, $params->get('commenterImgWidth'));
					if ($comments[$i]->userID>0)
					$comments[$i]->userLink = K2HelperRoute::getUserRoute($comments[$i]->userID);
					else
					$comments[$i]->userLink = $comments[$i]->commentURL;
				}

				$item->comments = $comments;

				jimport('joomla.html.pagination');
				$total = $item->numOfComments;
				$pagination = new JPagination($total, $limitstart, $limit);

			}

		}

		//Author's latest items
		if ($params->get('itemAuthorLatest') && $item->created_by_alias == '') {
			$model = &$this->getModel('itemlist');
			$authorLatestItems = $model->getAuthorLatest($item->id, $params->get('itemAuthorLatestLimit'), $item->created_by);
			if (count($authorLatestItems)) {
				for ($i = 0; $i < sizeof($authorLatestItems); $i++) {
					$authorLatestItems[$i]->link = urldecode(JRoute::_(K2HelperRoute::getItemRoute($authorLatestItems[$i]->id.':'.urlencode($authorLatestItems[$i]->alias), $authorLatestItems[$i]->catid.':'.urlencode($authorLatestItems[$i]->categoryalias))));
				}
				$this->assignRef('authorLatestItems', $authorLatestItems);
			}
		}

		//Related items
		if ($params->get('itemRelated') && isset($item->tags) && count($item->tags)) {
			$model = &$this->getModel('itemlist');
			$relatedItems = $model->getRelatedItems($item->id, $item->tags, $params->get('itemRelatedLimit'));
			if (count($relatedItems)) {
				for ($i = 0; $i < sizeof($relatedItems); $i++) {
					$relatedItems[$i]->link = urldecode(JRoute::_(K2HelperRoute::getItemRoute($relatedItems[$i]->id.':'.urlencode($relatedItems[$i]->alias), $relatedItems[$i]->catid.':'.urlencode($relatedItems[$i]->categoryalias))));
				}
				$this->assignRef('relatedItems', $relatedItems);
			}

		}
                
		//Navigation (previous and next item)
		if ($params->get('itemNavigation')) {
			$model = &$this->getModel('item');

			$nextItem = $model->getNextItem($item->id, $item->catid, $item->ordering);
                        
			if (!is_null($nextItem)) {
				$item->nextLink = urldecode(JRoute::_(K2HelperRoute::getItemRoute($nextItem->id.':'.urlencode($nextItem->alias), $nextItem->catid.':'.urlencode($item->category->alias))));
				$item->nextTitle = $nextItem->title;
			}
                        
			$previousItem = $model->getPreviousItem($item->id, $item->catid, $item->ordering);
			if (!is_null($previousItem)) {
				$item->previousLink = urldecode(JRoute::_(K2HelperRoute::getItemRoute($previousItem->id.':'.urlencode($previousItem->alias), $previousItem->catid.':'.urlencode($item->category->alias))));
				$item->previousTitle = $previousItem->title;
			}
		}

		//Absolute URL
		$uri = &JURI::getInstance();
		$item->absoluteURL = $uri->toString();

		//Email link
		$item->emailLink = JRoute::_('index.php?option=com_mailto&tmpl=component&link='.base64_encode($item->absoluteURL));

		//Twitter link
		if ($params->get('itemTwitterLink') && $params->get('twitterUsername')) {

			switch($params->get('urlShortener')){
				case 1:
					$itemURLForTwitter = @file_get_contents('http://tinyurl.com/api-create.php?url='.$item->absoluteURL);
					break;
				case 2:
					$itemURLForTwitter = @file_get_contents('http://is.gd/create.php?format=simple&url='.$item->absoluteURL);
					break;
				case 3:
					$itemURLForTwitter = @file_get_contents('http://v.gd/create.php?format=simple&url='.$item->absoluteURL);
					break;
				default:
					$itemURLForTwitter = $item->absoluteURL;
			}

			$item->twitterURL = 'http://twitter.com/intent/tweet?text='.urlencode($item->title.' '.$itemURLForTwitter.' via @'.$params->get('twitterUsername'));
		}

		//Social link
		$item->socialLink = urlencode($item->absoluteURL);

                // Commented out by jproven.com 2011-08-24
		//Set page title
//		$menus = &JSite::getMenu();
//		$menu = $menus->getActive();
//		if (is_object($menu) && isset($menu->query['view']) && $menu->query['view'] == 'item' && isset($menu->query['id']) && $menu->query['id'] == $item->id) {
//			$menu_params = new JParameter($menu->params);
//			if (!$menu_params->get('page_title')) {
//				$params->set('page_title', $item->cleanTitle);
//			}
//		} else {
//			$params->set('page_title', $item->cleanTitle);
//		}
//		$document->setTitle($params->get('page_title'));

		//Set pathway
//		$menus = &JSite::getMenu();
//		$menu = $menus->getActive();
//		$pathway = &$mainframe->getPathWay();
//		if($menu) {
//			if($menu->query['view']!='item' || $menu->query['id']!= $item->id){
//				if(!isset($menu->query['task']) || $menu->query['task']!='category' || $menu->query['id']!= $item->catid)
//				$pathway->addItem($item->category->name, $item->category->link);
//				$pathway->addItem($item->cleanTitle, '');
//			}
//		}

		//Set metadata
		if ($item->metadesc) {
			$document->setDescription($item->metadesc);
		}
		else {
			$metaDescItem = preg_replace("#{(.*?)}(.*?){/(.*?)}#s", '', $item->introtext.' '.$item->fulltext);
			$metaDescItem = K2HelperUtilities::characterLimit($metaDescItem, $params->get('metaDescLimit', 150));
			$metaDescItem = htmlentities($metaDescItem, ENT_QUOTES, 'utf-8');
			$document->setDescription($metaDescItem);
		}
		if ($item->metakey) {
			$document->setMetadata('keywords', $item->metakey);
		}
		else {
			if(isset($item->tags) && count($item->tags)){
				$tmp = array();
				foreach($item->tags as $tag){
					$tmp[]=$tag->name;
				}
				$document->setMetadata('keywords', implode(',', $tmp));
			}
		}

		if ($mainframe->getCfg('MetaTitle') == '1') {
			$document->setMetadata('title', $item->title);
		}
		if ($mainframe->getCfg('MetaAuthor') == '1' && isset($item->author->name)) {
			$document->setMetadata('author', $item->author->name);
		}
		$mdata = new JParameter($item->metadata);
		$mdata = $mdata->toArray();
		foreach ($mdata as $k=>$v) {
			if ($k == 'robots' || $k == 'author') {
				if ($v)
				$document->setMetadata($k, $v);
			}
		}

		//Look for template files in component folders
		$this->_addPath('template', JPATH_COMPONENT.DS.'templates');
		$this->_addPath('template', JPATH_COMPONENT.DS.'templates'.DS.'default');

		//Look for overrides in template folder (K2 template structure)
		$this->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2'.DS.'templates');
		$this->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2'.DS.'templates'.DS.'default');

		//Look for overrides in template folder (Joomla! template structure)
		$this->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2'.DS.'default');
		$this->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2');

		//Look for specific K2 theme files
		if ($item->params->get('theme')) {
			$this->_addPath('template', JPATH_COMPONENT.DS.'templates'.DS.$item->params->get('theme'));
			$this->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2'.DS.'templates'.DS.$item->params->get('theme'));
			$this->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2'.DS.$item->params->get('theme'));
		}
		
                // Added by jproven, 2011-08-24
		//Look for template files in component (com_k2fields) folders
		$this->_addPath('template', JPATH_SITE.'/components/com_k2fields/templates');
		$this->_addPath('template', JPATH_SITE.'/components/com_k2fields/templates'.DS.'default');

		//Look for overrides in template folder (K2 fields template structure)
		$this->_addPath('template', JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_k2fields/templates');
		$this->_addPath('template', JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_k2fields/templates/default');

		//Look for overrides in template folder (Joomla! template structure)
		$this->_addPath('template', JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_k2fields/default');
		$this->_addPath('template', JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_k2fields');

		//Look for specific K2 theme files
		if ($item->params->get('theme')) {
			$this->_addPath('template', JPATH_SITE.'/components/com_k2fields/templates'.DS.$item->params->get('theme'));
                        $this->_addPath('template', JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_k2fields/templates/'.$item->params->get('theme'));
                        $this->_addPath('template', JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_k2fields/'.$item->params->get('theme'));
		}
		
		//Assign data
		$this->assignRef('item', $item);
		$this->assignRef('user', $user);
		$this->assignRef('params', $item->params);
		$this->assignRef('pagination', $pagination);
		
		parent::display($tpl);
	}
}

