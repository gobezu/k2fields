<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

require_once dirname(__FILE__).'/widgetkithelper.php';

$wkSettings = $params->get('widgetkit_settings');
$wkSettings = explode('%%', $wkSettings);

$settings = array(
        'type'=>$params->get('widgetkit_type'), 
        'style'=>$params->get('widgetkit_theme'),
        'width'=>$moduleWidth,
        'height'=>$moduleHeight,
        'k2' => array(
                'partby'=>$isPartitioned?$partBy:'',
                'partid'=>$isPartitioned?$partId:'',
                'synchronize'=>1, 
                'module_id'=>$module->id, 
                'module'=>'mod_'.$module->name
        )
);

foreach ($wkSettings as $wkSetting) {
        $wkSetting = explode('==', $wkSetting);
        $settings[$wkSetting[0]] = $wkSetting[1];
}

if ($isPartitioned) {
        $partTitles = array('category'=>'categoryname', 'author'=>'author');
        
        $partitionId = "mod_k2fields_contents_".$module->id;
        $document->addScriptDeclaration('new WKK2fields("'.$partitionId.'");');
} else {
        $document->addScriptDeclaration('new WKK2fields();');
}
