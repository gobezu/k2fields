<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$k2Settings = array('synchronize'=>true, 'module_id' =>$module->id, 'module' => 'mod_'.$module->name);

foreach ($k2Settings as $setting => $k2Setting) {
        if (empty($k2Setting)) $k2Settings[$setting] = $params->get($setting);
}

if ($isPartitioned) {
        $k2Settings['partby'] = $partBy;
        $k2Settings['partid'] = $partId;
}

$settings['width'] = $moduleWidth;
$settings['height'] = $moduleHeight;

$items = JprovenUtility::getColumn($list, 'id');

echo K2fieldsModuleWidgetkitHelper::render($items, $module, $k2Settings, $settings);