<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$items = JprovenUtility::getColumn($list, 'id');

echo K2fieldsModuleWidgetkitHelper::render($items, $module, $settings);