<?php
//$Copyright$
 
defined('_JEXEC') or die('Restricted access');

$view = JRequest::getWord('view', 'itemlist');
$task = JRequest::getCmd('task', 'search');

if ($view == 'fields' || $view == 'field') {
        require_once JPATH_ADMINISTRATOR.'/components/com_k2fields/k2fields.php';
} else if ($view == 'item' || $view == 'itemlist' && $task == 'search' || $view == 'rate' && $task == 'values') {
        JLoader::register('K2HelperRoute', JPATH_SITE.'/components/com_k2/helpers/route.php');
        JLoader::register('K2HelperPermissions', JPATH_SITE.'/components/com_k2/helpers/permissions.php');
        JLoader::register('K2HelperUtilities', JPATH_SITE.'/components/com_k2/helpers/utilities.php');

        K2HelperPermissions::setPermissions();
        K2HelperPermissions::checkPermissions();
        
        JprovenUtility::loader('K2Fields', $view);
} else {
        JError::raiseError(404, JText::_('View not found'));
}
?>