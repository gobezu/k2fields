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

JFactory::getLanguage()->load('mod_k2_tools');

$defaultCategory = $params->get('defaultcategory', 0);
$itemId = JFactory::getApplication()->input->get('Itemid');
$useItemid = $params->get('useitemid', 'current') == 'current' ? $itemId : $params->get('menuitemid', $itemId);

$exclfldft = $params->get('exclfldft', 0);
$showfreetextsearch = $params->get('showftsearch', true);
$dontshowfreetextsearchin = $params->get('dontshowftsearchin', array());
$categoryselector = $params->get('categoryselector', 1);
$includedefaultmenuitem = $params->get('includedefaultmenuitem', 1);
$showsearchfields = $params->get('showsearchfields', true);
$showorderby = $params->get('showorderby', true);
$ftautocomplete = $params->get('ftautocomplete', true);
$ftautocompletecustomwidth = 'inherit';
if ($ftautocomplete) {
        $ftautocompletewidth = $params->get('ftautocompletewidth', 'autofit');
        if ($ftautocompletewidth != 'autofit') {
                $ftautocompletecustomwidth = $params->get('ftautocompletecustomwidth', 300);
        } else {
                $ftautocompletecustomwidth = 'inherit';
        }
}
$acmaxchars = $params->get('acmaxchars', 60);
$acminchars = $params->get('acminchars', 3);
$acmaxitems = $params->get('acmaxitems', 10);
$showsearchcount = $params->get('showsearchcount', 1);
$categoryselectortext = $params->get('categoryselectortext', 'K2_SELECT_CATEGORY');
$keepDefaultCategoryInHome = (bool) $params->get('keepdefaultcategoryinhome', 0);

$ft = JFactory::getApplication()->input->get('ft', '', 'string');

$excludes = $params->get('excludecategories', array());

if (!empty($excludes)) {
        $excludes = (array) $excludes;
        foreach ($excludes as &$exclude) $exclude = (int) $exclude;
}

require dirname(__FILE__).'/helper.php';

$categories = modK2FieldsHelper::getCategoriesSelector(
        $categoryselector,
        $defaultCategory,
        $excludes,
        $includedefaultmenuitem,
        'cid',
        $categoryselectortext,
        $keepDefaultCategoryInHome
);

$singleCategory = strpos($categories, '<input ') !== false;

jimport('joomla.plugin.plugin');

$placeholder = JText::_("search")."...";

if ($ft == $placeholder) $ft = '';

if ($showfreetextsearch || $categoryselector || $showsearchfields) {
        $renderedFields = modK2FieldsHelper::getFields($defaultCategory, $categoryselector == 2, $includedefaultmenuitem, $excludes, $keepDefaultCategoryInHome);
        $catid = JprovenUtility::getK2CurrentCategory($defaultCategory);
        $defaultmode = $params->get('defaultmode', 'active');
        $showsearchcount  = (bool) $params->get('showsearchcount', 0);
        $showsearchmax  = (int) $params->get('showsearchmax', 0);

        if (empty($catid)) $catid = $defaultCategory;

        $app = JFactory::getApplication();
        $option = JFactory::getApplication()->input->get('option');

        $path = JModuleHelper::getLayoutPath('mod_k2fields', 'default');
        $path = str_replace(JPATH_BASE, JPATH_SITE, $path);

        require $path;

        if (!isset($tab)) $tab = 'search';

        if (JPluginHelper::importPlugin('k2', 'k2fields')) {
                plgk2k2fields::loadResources(
                        $tab,
                        null,
                        array(
                                'module'=>$module->id,
                                'liveupdate'=>(bool) $params->get('showsearchcountliveupdate', 0),
                                'liveupdateresult'=>(bool) $params->get('showsearchcountliveupdateresult', 0)
                        )
                );
        }

        if ($showfreetextsearch) {
                $whentogglerempty = $params->get('whentogglerempty', 'inactive');
                $document = JFactory::getDocument();

                $arr = array(
                    'postUrl'=>'index.php?option=com_k2fields&task=search&view=itemlist&format=json&tmpl=component&exclfldft='.$exclfldft.'&acmc='.$acmaxchars.'&limit='.$acmaxitems.'&module='.$module->id.'&Itemid='. $useItemid,
                    'moreResultsUrl'=>'index.php?option=com_k2fields&task=search&view=itemlist&Itemid='.$useItemid.'&module='.$module->id.'&exclfldft='.$exclfldft,
                    'postVar'=>'ft',
                    'headerMsg'=>JText::_('Search results'),
                    'moreResultsMsg'=>JText::_('More results on: '),
                    'minLength'=>$acminchars,
                    'placeHolderClass'=>'placeholder',
                    'placeHolder'=>$placeholder,
                    'togglerElement'=>'cid',
                    'advancedSearchContainer'=>'ascontainer',
                    'searchCountUpdate'=>($showsearchcount ? 'true' : 'false'),
                    'whenTogglerEmpty'=>$whentogglerempty,
                    'defaultMode'=>$defaultmode,
                    'dontShowIn'=>$dontshowfreetextsearchin,
                    'width'=>$ftautocompletecustomwidth,
                    'searchmax'=>$showsearchmax
                );
                $document->addScriptDeclaration("window.addEvent('domready', function(){
                        new JPSearch(document.id('searchboxContainer').getElement('input'),".json_encode($arr).",['cid'],'cid');
                });");
        }
}