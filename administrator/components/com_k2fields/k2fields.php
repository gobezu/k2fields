<?php
//$Copyright$
 
// no direct access
defined('_JEXEC') or die('Restricted access');

$lang = JFactory::getLanguage();
$lang->load('plg_k2_k2fields');

require_once JPATH_SITE.'/components/com_k2fields/helpers/utility.php';

$option = JFactory::getApplication()->input->get('option');
$view = JFactory::getApplication()->input->get('view');
$ctrl = JFactory::getApplication()->input->get('controller', '', 'word');

if (empty($view) && empty($ctrl)) {
        $app = JFactory::getApplication();
                
        if ($app->isAdmin()) JprovenUtility::redirectToPlg('k2fields', 'k2');
}

JprovenUtility::loader('K2Fields', 'fields', true);