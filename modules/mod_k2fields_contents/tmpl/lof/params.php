<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

// UI settings
$tmp = $params->get( 'module_height', 'auto' );
$moduleHeight = ( $tmp=='auto' ) ? 'auto' : (int)$tmp.'px';
$tmp = $params->get( 'module_width', 'auto' );
$moduleWidth = ( $tmp=='auto') ? 'auto': (int)$tmp.'px';
$themeClass = $params->get( 'loftheme' , '');
$class = $params->get( 'navigator_pos', 'right' ) == "0" ? '':'lof-sn'.$params->get( 'navigator_pos', 'right' );
$css3 = $params->get('enable_css3','1')? " lof-css3":"";
$isIntrotext = $params->get('slider_information', 'description') == 'description'?0:1;

$navEnableThumbnail = $params->get( 'enable_thumbnail', 1 );
$navEnableTitle = $params->get( 'enable_navtitle', 1 );
$navEnableDate = $params->get( 'enable_navdate', 1 );
$navEnableCate = $params->get( 'enable_navcate', 1 );
$customSliderClass = $params->get('custom_slider_class','');
$customSliderClass = is_array($customSliderClass)?$customSliderClass:array($customSliderClass);
$mainWidth = $params->get( 'main_width', 650 );
$navItemWidth = (int) $params->get('navitem_width', 310);

$itemLayout = modK2fieldsContentsHelper::layout($module, $params, 'item');

$lofIds = array();