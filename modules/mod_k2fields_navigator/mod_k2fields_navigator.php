<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!class_exists('JprovenUtility')) {
        if (!JFile::exists(JPATH_SITE.'/components/com_k2fields/helpers/utility.php')) {
                return;
        } else {
                JLoader::register('JprovenUtility', JPATH_SITE.'/components/com_k2fields/helpers/utility.php');
        }
}

if (!JprovenUtility::checkPluginActive('k2fields', 'k2', '')) return;

require dirname(__FILE__).'/helper.php';

$language = JFactory::getLanguage();
$language->load('mod_k2.j16', JPATH_ADMINISTRATOR, null, true);

$fieldId = $params->get('field', 0);

if (!$fieldId) return;

$excludeValues = $params->get('excludevalues', array());
if ($excludeValues) {
        $excludeValues = trim($excludeValues);
        $excludeValues = explode("\n", $excludeValues);
}

$orderBy = $params->get('ordervaluesby', 'definition');

$itemId = JFactory::getApplication()->input->get('Itemid');
$useItemid = $params->get('useitemid', 'current') == 'current' ? $itemId : $params->get('menuitemid', $itemId);

$useCatids = $params->get('usecatids', 0);
if ($useCatids) $useCatids = (array) $useCatids;

$showCount = (bool) $params->get('showcount', false);
$showImage = (bool) $params->get('showimage', true);
$showAs = $params->get('showas', 'link');
$showFormat = $params->get('showformat', '');
$linkTitle = $params->get('linktitle', 'Navigate to [text]');
$imageTitle = $params->get('imagetitle', '[text]');
$dontShowEmpty = (bool) $params->get('dontshowempty', false);

$showFormat = trim($showFormat);

if ($showFormat) {
        $showCount = strpos($showFormat, '[count]');
        $showImage = strpos($showFormat, '[image]');
        $showAs = '';
} else if ($showAs == 'link') {
        $showFormat = '<a href="[link]" title="Navigate to [text]">[image][text]'.($showCount ? ' <span class="count">([count])</span>' : '').'</a>';
} else if ($showAs == 'text') {
        $showFormat = ($showImage ? '[image]' : '').'[text]'.($showCount ? ' ([count])' : '');
}

$link = 'index.php?option=com_k2fields&view=itemlist&task=search&s'.$fieldId.'_0=[value]';

if ($useItemid) {
        $link .= '&Itemid='.$useItemid;
}

if ($useCatids) {
        $link .= '&cid='.implode(',', $useCatids);
}

$showFormat = str_replace('[link]', $link, $showFormat);

$imageFormat = $showImage ? '<img src="[image]" alt="'.$imageTitle.'"/>' : '';

$values = modK2FieldsNavigatorHelper::getFieldValues(
        $fieldId,
        $useCatids,
        $excludeValues,
        $dontShowEmpty,
        $showCount
);

if (count($values)) {
        require JModuleHelper::getLayoutPath('mod_k2fields_navigator', $params->get('layout', 'default'));
}