<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$k2Settings = array('source'=>'specific', 'module_id' =>$module->id, 'module' => 'mod_'.$module->name, 'itemIntroText' =>'', 'itemTitle'=>'', 'itemExtraFields'=>'');

foreach ($k2Settings as $setting => $k2Setting) {
        if (empty($k2Setting)) $k2Settings[$setting] = $params->get($setting);
}

if ($isPartitioned) {
        $k2Settings['partby'] = $partBy;
        $k2Settings['partid'] = $partId;
}

$items = JprovenUtility::getColumn($list, 'id');

echo K2fieldsModuleWidgetkitHelper::render($items, $module, $k2Settings, $settings);