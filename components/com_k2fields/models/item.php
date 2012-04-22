<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

if (!class_exists('K2ModelItem') && JFactory::getApplication()->input->get('option') == 'com_k2fields') {
        if (JprovenUtility::plgParam('k2fields', 'k2', 'override_itemmodel') == '1') {
                require_once JPATH_SITE.'/components/com_k2fields/models/k2/item.php';
        } else {
                require_once JPATH_SITE.'/components/com_k2/models/item.php';
        }
}

class K2FieldsModelItem extends K2ModelItem {
}

?>
