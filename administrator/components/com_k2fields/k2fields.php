<?php
//$Copyright$
 
// no direct access
defined('_JEXEC') or die('Restricted access');

$lang = JFactory::getLanguage();
$lang->load('plg_k2_k2fields');

require_once JPATH_SITE.'/components/com_k2fields/helpers/utility.php';

$option = JRequest::getWord('option');
$view = JRequest::getWord('view');
$ctrl = JRequest::getWord('controller');

if (empty($view) && empty($ctrl)) {
        $app = JFactory::getApplication();
                
        if ($app->isAdmin()) JprovenUtility::redirectToPlg('k2fields', 'k2');
}

JprovenUtility::loader('K2Fields', 'fields', true);