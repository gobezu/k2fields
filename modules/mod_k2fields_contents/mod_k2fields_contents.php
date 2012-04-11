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

// get items
require_once JPATH_SITE.'/components/com_k2fields/helpers/modulehelper.php';

$componentParams = JComponentHelper::getParams('com_k2');
$_avatarWidth = $componentParams->get('userImageWidth');
$partBy = $params->get('part_by', 'category');

if ($partBy == 'none') $partBy = '';

$document = JFactory::getDocument();

$templateName = $params->get('template', 'awkwardshowcase');
$params->set('module_id', $module->id);

$itemList = K2FieldsModuleHelper::getList($params, $componentParams, $document->getType(), $partBy);

if (count($itemList)) {
        $isPartitioned = !empty($partBy);

        if (count($itemList) == 1) {
                if ($isPartitioned) {
                        $itemList = array_shift($itemList);
                        $isPartitioned = false;
                }
        }

        if (!$isPartitioned) shuffle($itemList);

        require_once dirname( __FILE__ ).'/helper.php';

        $settings = modK2fieldsContentsHelper::settings($params);
        $themeName = $params->get($templateName.'_theme');
        $themeLayout = modK2fieldsContentsHelper::theme($module, $params);
        $itemLayout = modK2fieldsContentsHelper::layout($module, $params, 'item');
        $thumbNailLayout = modK2fieldsContentsHelper::layout($module, $params, 'thumbnail');
        $captionLayout = modK2fieldsContentsHelper::layout($module, $params, 'caption');
        $template = JModuleHelper::getLayoutPath($module->module, $templateName.'/default');
        
        $document = JFactory::getDocument();
        
        $mediaFolder = 'media/mod_'.$module->name.'/'.$templateName.'/';
        
        require $template;
        
        if (JFile::exists(JPATH_SITE.'/'.$mediaFolder.'script.js')) 
                $document->addScript(JURI::base().$mediaFolder.'script.js');
        
        if (JFile::exists(JPATH_SITE.'/'.$mediaFolder.'style.css')) 
                $document->addStyleSheet(JURI::base().$mediaFolder.'style.css');
}