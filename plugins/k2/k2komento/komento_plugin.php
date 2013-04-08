<?php
/**
 * @package		Komento
 * @copyright	Copyright (C) 2012 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Komento is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' .DIRECTORY_SEPARATOR . 'com_komento' . DIRECTORY_SEPARATOR . 'komento_plugins' . DIRECTORY_SEPARATOR . 'abstract.php' );

class KomentoComk2 extends KomentoExtension
{
	public $_item;
	public $_map = array(
		'id'			=> 'id',
		'title'			=> 'title',
		'hits'			=> 'hits',
		'created_by'	=> 'created_by',
		'catid'			=> 'catid'
		);

	private $_currentTrigger = '';

	public function __construct( $component )
	{
		$this->addFile( JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_k2' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR .'route.php' );

		if( !class_exists( 'K2HelperPermissions' ) )
		{
			$this->addFile( JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_k2' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR .'permissions.php' );
		}

		parent::__construct( $component );
	}

	public function load( $cid )
	{
		static $instances = array();

		if( !isset( $instances[$cid] ) )
		{
			$db		= Komento::getDBO();
			$query	= 'SELECT a.*, c.alias AS category_alias'
					. ' FROM ' . $db->nameQuote( '#__k2_items' ) . ' AS a'
					. ' LEFT JOIN ' . $db->nameQuote( '#__k2_categories')  . ' AS c ON c.id = a.catid'
					. ' WHERE a.id' . '=' . $db->quote($cid);
			$db->setQuery( $query );

			if( !$this->_item = $db->loadObject() )
			{
				return $this->onLoadArticleError( $cid );
			}

			$instances[$cid] = $this->_item;
		}

		$this->_item = $instances[$cid];

		return $this;
	}

	public function getContentIds( $categories = '' )
	{
		$db		= Komento::getDBO();
		$query = '';

		if( empty( $categories ) )
		{
			$query = 'SELECT `id` FROM ' . $db->nameQuote( '#__k2_items' ) . ' ORDER BY `id`';
		}
		else
		{
			if( is_array( $categories ) )
			{
				$categories = implode( ',', $categories );
			}

			$query = 'SELECT `id` FROM ' . $db->nameQuote( '#__k2_items' ) . ' WHERE `catid` IN (' . $categories . ') ORDER BY `id`';
		}

		$db->setQuery( $query );
		return $db->loadResultArray();
	}

	public function getCategories()
	{
		$db		= Komento::getDBO();
		$query	= 'SELECT a.id, a.name AS title, a.parent AS parent_id, a.name, a.parent'
				. ' FROM `#__k2_categories` AS a'
				. ' WHERE a.trash = 0'
				. ' ORDER BY a.ordering';
		$db->setQuery( $query );
		$categories	= $db->loadObjectList();

		$children = array();

		foreach ($categories as $row)
		{
			$pt		= $row->parent_id;
			$list	= @$children[$pt] ? $children[$pt] : array();
			$list[] = $row;
			$children[$pt] = $list;
		}

		$categories	= JHTML::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0);

		return $categories;
	}

	public function isListingView()
	{
		return ( $this->_currentTrigger == 'onK2CommentsCounter' ) ? true : false;
	}

	public function isEntryView()
	{
		return JRequest::getCmd('view') == 'item';
	}

	public function onExecute( &$article, $html, $view, $options = array() )
	{
		if( $options['trigger'] == 'onK2CommentsCounter' )
		{
			// Try to integrate with K2's comment counter
			$model	= Komento::getModel( 'comments' );
			$count	= $model->getCount( $this->component, $this->getContentId() );
			$article->numOfComments = $count;
		}

		if( $view == 'entry' && $options['trigger'] == 'onK2CommentsBlock' )
		{
			// Try to integrate with K2's comment counter
			$model	= Komento::getModel( 'comments' );
			$count	= $model->getCount( $this->component, $this->getContentId() );
			$article->numOfComments = $count;

			return $html;
		}
	}

	private function _getItemId()
	{
		$menus = JApplication::getMenu('site');
		$component = JComponentHelper::getComponent('com_k2');

		if(K2_JVERSION=='15'){
			$items = $menus->getItems('componentid', $component->id);
		} else {
			$items = $menus->getItems('component_id', $component->id);
		}

		if( count($items) == 1 )
		{
			return '&Itemid=' . $items[0]->id;
		}

		$match = null;

		foreach ($items as $item)
		{
			if ((@$item->query['task'] == 'category') && (@$item->query['id'] == $this->_item->catid))
			{
				$match = $item;
			}
			else
			{
				if(!isset($item->K2Categories)) {
					if(K2_JVERSION == '15') {
						$menuparams = explode("\n", $item->params);
						foreach($menuparams as $param) {
							if(strpos($param, 'categories=')===0) {
								$array = explode('categories=', $param);
								$item->K2Categories = explode('|', $array[1]);
							}
						}
					} else {
						$menuparams = json_decode($item->params);
						$item->K2Categories = isset($menuparams->categories)? $menuparams->categories: array();
					}
				}
				if(isset($item->K2Categories) && is_array($item->K2Categories)) {
					foreach ($item->K2Categories as $catid)	{
						if ((@$item->query['view'] == 'itemlist') && (@$item->query['task'] == '') && (@(int)$catid == $id)) {
							$match = $item;
							break;
						}
					}
				}
			}
		}

		if( $match )
		{
			return '&Itemid=' . $item->id;
		}
		else
		{
			return '';
		}
	}

	public function getEventTrigger()
	{
		$entryTrigger = 'onK2CommentsCounter';

		if( $this->isEntryView() )
		{
			$entryTrigger = 'onK2CommentsBlock';
		}
		return $entryTrigger;
		// return array( 'onK2CommentsBlock', 'onK2CommentsCounter' );
	}
        
        public function onAfterSaveComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterSaveComment', array(&$comment));
        }
        
        public function onAfterProcessComment(&$comment) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterProcessComment', array(&$comment));
        }
        
        public function onBeforePublishComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onBeforePublishComment', array(&$comment));
        }
        
        public function onAfterPublishComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterPublishComment', array(&$comment));
        }
        
        public function onAfterUnpublishComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterUnpublishComment', array(&$comment));
        }
        
        public function onBeforeUnpublishComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onBeforeUnpublishComment', array(&$comment));
        }
        
        public function onBeforeDeleteComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onBeforeDeleteComment', array(&$comment));
        }
        
        public function onAfterDeleteComment( &$comment ) {
                JPluginHelper::importPlugin('k2');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterDeleteComment', array(&$comment));
        }
        
	public function getAuthorName()
	{
		return $this->_item->created_by_alias ? $this->_item->created_by_alias : $this->_item->author->name;
	}

	public function getCommentAnchorId()
	{
		return 'itemCommentsAnchor';
	}

	public function onBeforeLoad( $eventTrigger, $context, &$article, &$params, &$page, &$options )
	{
		if( !$params instanceof JRegistry )
		{
			return false;
		}

		$this->_currentTrigger = $eventTrigger;

		return true;
	}

	public function onParameterDisabled( $eventTrigger, $context, &$article, &$params, &$page, &$options )
	{
		$params->set( 'comments', 0 );
		return false;
	}

	public function getContentPermalink()
	{
		$link = '';

		if( JFactory::getApplication()->isSite() )
		{
			$link = K2HelperRoute::getItemRoute($this->_item->id.':'.urlencode($this->_item->alias), $this->_item->catid.':'.urlencode($this->_item->category_alias));
			$link = urldecode(JRoute::_($link));
		} else {
			$link = 'index.php?option=com_k2&view=item&id=' . $this->_item->id . $this->_getItemId();
		}

		$link = $this->prepareLink( $link );

		return $link;
	}
}
