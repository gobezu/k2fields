<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$settings->style = $settings->theme;
$settings = get_object_vars($settings);

require_once dirname(__FILE__).'/widgetkithelper.php';

if ($isPartitioned) {
        $partTitles = array('category'=>'categoryname', 'author'=>'author');
        
        $partitionId = "mod_k2fields_contents_".$module->id;
        $document->addScriptDeclaration('new WKK2fields("'.$partitionId.'");');
} else {
        $document->addScriptDeclaration('new WKK2fields();');
}
