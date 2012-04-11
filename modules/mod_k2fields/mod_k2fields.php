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

$defaultCategory = $params->get('defaultcategory', 0);
$useItemid = $params->get('useitemid', 'current') == 'current' ? 
        JRequest::getInt('Itemid') : 
        $params->get('menuitemid', JRequest::getInt('Itemid'));

$exclfldft = $params->get('exclfldft', 0);
$showfreetextsearch = $params->get('showftsearch', true);
$dontshowfreetextsearchin = $params->get('dontshowftsearchin', array());
$categoryselector = $params->get('categoryselector', 1);
$includedefaultmenuitem = $params->get('includedefaultmenuitem', 1);
$showsearchfields = $params->get('showsearchfields', true);
$showorderby = $params->get('showorderby', true);
$ftautocomplete = $params->get('ftautocomplete', true);
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

$ft = JRequest::getString('ft', '');

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
        $categoryselectortext
);

jimport('joomla.plugin.plugin');

$placeholder = JText::_("search")."...";

if ($ft == $placeholder) $ft = '';

if ($showfreetextsearch || $categoryselector || $showsearchfields) {
        $renderedFields = modK2FieldsHelper::getFields($defaultCategory, $categoryselector == 2, $includedefaultmenuitem);
        $catid = JprovenUtility::getK2CurrentCategory($defaultCategory);
        $defaultmode = $params->get('defaultmode', 'active');

        if (empty($catid)) $catid = $defaultCategory;
        
        $app = JFactory::getApplication();
        $option = JRequest::getCmd('option');

        $path = JModuleHelper::getLayoutPath('mod_k2fields', 'default');
        
//        jimport('joomla.filesystem.file');
//        if (!$path || !JFile::exists($path)) $path = JPATH_SITE.'/modules/mod_k2fields/tmpl/default.php';
        
        require $path;
        
        if (JPluginHelper::importPlugin('k2', 'k2fields')) plgk2k2fields::loadResources('search');
        
        $document = JFactory::getDocument();
        
        $whentogglerempty = $params->get('whentogglerempty', 'inactive');
        
        if ($showfreetextsearch && $ftautocomplete) {
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
                    'width'=>$ftautocompletecustomwidth
                );
                $document->addScriptDeclaration("window.addEvent('domready', function(){
                        new JPSearch(document.id('searchboxContainer').getElement('input'),".json_encode($arr).",['cid'],'cid');
                });");
        }
}
?>