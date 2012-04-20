<?php
//$Copyright$

/** Original copyright : nothing changed from original except class names and there calls
 * @version		$Id: permissions.php 1492 2012-02-22 17:40:09Z joomlaworks@gmail.com $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2012 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.parameter');

class K2FieldsHelperPermissions {

	function setPermissions() {
		$params = &K2HelperUtilities::getParams('com_k2');
		$user = &JFactory::getUser();
		if ($user->guest){
			return;
		}
		$K2User = K2FieldsHelperPermissions::getK2User($user->id);
		if (!is_object($K2User)){
			return;
		}
		$K2UserGroup = K2FieldsHelperPermissions::getK2UserGroup($K2User->group);
		if (is_null($K2UserGroup)){
			return;
		}
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		$permissions = new JParameter($K2UserGroup->permissions);
		$K2FieldsPermissions->permissions = $permissions;
		if ($permissions->get('categories') == 'none') {
			return;
		}
		else if ($permissions->get('categories') == 'all') {
			if ($permissions->get('add') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
				$K2FieldsPermissions->actions[] = 'add.category.all';
				$K2FieldsPermissions->actions[] = 'tag';
				$K2FieldsPermissions->actions[] = 'extraFields';
			}
			if ($permissions->get('editOwn') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
				$K2FieldsPermissions->actions[] = 'editOwn.item.'.$user->id;
				$K2FieldsPermissions->actions[] = 'tag';
				$K2FieldsPermissions->actions[] = 'extraFields';
			}
			if ($permissions->get('editAll') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
				$K2FieldsPermissions->actions[] = 'editAll.category.all';
				$K2FieldsPermissions->actions[] = 'tag';
				$K2FieldsPermissions->actions[] = 'extraFields';
			}
			if ($permissions->get('publish') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
				$K2FieldsPermissions->actions[] = 'publish.category.all';
			}
			if ($permissions->get('comment')) {
				$K2FieldsPermissions->actions[] = 'comment.category.all';
			}
		}
		else {
			$selectedCategories = $permissions->get('categories', NULL);
			if (is_string($selectedCategories)){
				$searchIDs[] = $selectedCategories;
			}
			else {
				$searchIDs = $selectedCategories;
			}
			if ($permissions->get('inheritance')) {
				JLoader::register('K2ModelItemlist', JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'models'.DS.'itemlist.php');
				$categories = K2ModelItemlist::getCategoryTree($searchIDs);
			}
			else {
				$categories = $searchIDs;
			}
			if (is_array($categories) && count($categories)) {
				foreach ($categories as $category) {
					if ($permissions->get('add') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
						$K2FieldsPermissions->actions[] = 'add.category.'.$category;
						$K2FieldsPermissions->actions[] = 'tag';
						$K2FieldsPermissions->actions[] = 'extraFields';
					}
					if ($permissions->get('editOwn') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
						$K2FieldsPermissions->actions[] = 'editOwn.item.'.$user->id.'.'.$category;
						$K2FieldsPermissions->actions[] = 'tag';
						$K2FieldsPermissions->actions[] = 'extraFields';
					}
					if ($permissions->get('editAll') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
						$K2FieldsPermissions->actions[] = 'editAll.category.'.$category;
						$K2FieldsPermissions->actions[] = 'tag';
						$K2FieldsPermissions->actions[] = 'extraFields';
					}
					if ($permissions->get('publish') && $permissions->get('frontEdit') && $params->get('frontendEditing')) {
						$K2FieldsPermissions->actions[] = 'publish.category.'.$category;
					}
					if ($permissions->get('comment')) {
						$K2FieldsPermissions->actions[] = 'comment.category.'.$category;
					}
				}
			}
		}
		return;
	}

	function checkPermissions() {
		$view = JRequest::getCmd('view');
		if ($view != 'item'){
			return;
		}
		$task = JRequest::getCmd('task');

		switch ($task) {

			case 'add':
				if (!K2FieldsHelperPermissions::canAddItem())
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
				break;

			case 'edit':
			case 'deleteAttachment':
			case 'checkin':
				$cid = JRequest::getInt('cid');
				if (!$cid)
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));

				JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');
				$item = &JTable::getInstance('K2Item', 'Table');
				$item->load($cid);

				if (!K2FieldsHelperPermissions::canEditItem($item->created_by, $item->catid))
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
				break;

			case 'save':
				$cid = JRequest::getInt('id');
				if ($cid) {

					JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');
					$item = &JTable::getInstance('K2Item', 'Table');
					$item->load($cid);

					if (!K2FieldsHelperPermissions::canEditItem($item->created_by, $item->catid))
					JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
				}
				else {
					if (!K2FieldsHelperPermissions::canAddItem())
					JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
				}

				break;

			case 'tag':
				if (!K2FieldsHelperPermissions::canAddTag())
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
				break;

			case 'extraFields':
				if (!K2FieldsHelperPermissions::canRenderExtraFields())
				JError::raiseError(403, JText::_('K2_ALERTNOTAUTH'));
				break;

		}
	}

	function getK2User($userID) {

		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__k2_users WHERE userID = ".(int)$userID;
		$db->setQuery($query);
		$row = $db->loadObject();
		return $row;
	}

	function getK2UserGroup($id) {

		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__k2_user_groups WHERE id = ".(int)$id;
		$db->setQuery($query);
		$row = $db->loadObject();
		return $row;
	}

	function canAddItem($category = false) {

		$user = &JFactory::getUser();
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		if(in_array('add.category.all', $K2FieldsPermissions->actions)){
			return true;
		}
		if($category){
			return in_array('add.category.'.$category, $K2FieldsPermissions->actions);
		}
		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__k2_categories WHERE published=1 AND trash=0";
		if(K2_JVERSION == '16'){
			$query .= " AND access IN(".implode(',', $user->authorisedLevels()).")";
		}
		else {
			$aid = (int) $user->get('aid');
			$query .= " AND access<={$aid}";
		}
		$db->setQuery($query);
		$categories = $db->loadObjectList();
		foreach ($categories as $category) {
			if(in_array('add.category.'.$category->id, $K2FieldsPermissions->actions)){
				return true;
			}
		}

		return false;
	}

	function canAddToAll(){
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		return in_array('add.category.all', $K2FieldsPermissions->actions);
	}

	function canEditItem($itemOwner, $itemCategory) {
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		if(
		in_array('editAll.category.all', $K2FieldsPermissions->actions) ||
		in_array('editOwn.item.'.$itemOwner, $K2FieldsPermissions->actions) ||
		in_array('editOwn.item.'.$itemOwner.'.'.$itemCategory, $K2FieldsPermissions->actions) ||
		in_array('editAll.category.'.$itemCategory, $K2FieldsPermissions->actions)
		)
		{
			return true;
		}
		else {
			return false;
		}
	}
	
	function canPublishItem($itemCategory) {
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		if(in_array('publish.category.all', $K2FieldsPermissions->actions) || in_array('publish.category.'.$itemCategory, $K2FieldsPermissions->actions)){
			return true;
		}
		else {
			return false;
		}
	}

	function canAddTag() {
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		return in_array('tag', $K2FieldsPermissions->actions);
	}

	function canRenderExtraFields() {
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		return in_array('extraFields', $K2FieldsPermissions->actions);
	}

	function canAddComment($itemCategory) {
		$K2FieldsPermissions = &K2FieldsPermissions::getInstance();
		return in_array('comment.category.all', $K2FieldsPermissions->actions) || in_array('comment.category.'.$itemCategory, $K2FieldsPermissions->actions);
	}


}

class K2FieldsPermissions {
	var $actions = array();
	var $permissions = null;
	function & getInstance() {
		static $instance;
		if(!is_object($instance)){
			$instance = new K2FieldsPermissions();
		}
		return $instance;
	}
}