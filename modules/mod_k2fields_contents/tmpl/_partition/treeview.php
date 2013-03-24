<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if ($params->get('tree_persistency', 'none') == 'cookie') $document->addScript(JURI::base().$partitionMediaFolder.'lib/jquery.cookie.js');

$document->addScript(JURI::base().$partitionMediaFolder.'jquery.treeview.js');
$document->addStyleSheet(JURI::base().$partitionMediaFolder.'jquery.treeview.css');                        

$treeId = $partitionIds[0];
$params->set('dir', JURI::base().$partitionMediaFolder);
$tree = K2FieldsModuleHelper::getTree($treeId, $itemList, $params);

echo $tree;

$opts = array();

if ($params->get('tree_initial', 'close') == 'close') {
        $opts['collapsed'] = true;
}

if ($params->get('tree_persistency', 'none') != 'none') {
        $opts['persist'] = $params->get('tree_persistency', 'none');
        //jdbg::pe($opts);
        if ($opts['persist'] == 'cookie')  {
                $opts['cookieId'] = $treeId;
        }
}

if ($params->get('tree_show_buttons', 'none') != 'none') {
        $opts['control'] = '#'.$treeId.'_control';
}

$opts = json_encode($opts);

$document->addScriptDeclaration("jQuery(document).ready(function(){ jQuery('#".$treeId."').treeview(".$opts."); });");
