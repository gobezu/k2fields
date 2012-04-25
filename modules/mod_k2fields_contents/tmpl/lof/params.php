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
$openTarget = $params->get( 'open_target', 'parent' );
$class = $params->get( 'navigator_pos', 'right' ) == "0" ? '':'lof-sn'.$params->get( 'navigator_pos', 'right' );

$css3 = $params->get('enable_css3','1')? " lof-css3":"";
$isIntrotext = $params->get('slider_information', 'description') == 'description'?0:1;

$navEnableThumbnail = $params->get( 'enable_thumbnail', 1 );
$navEnableTitle = $params->get( 'enable_navtitle', 1 );
$navEnableDate = $params->get( 'enable_navdate', 1 );
$navEnableCate = $params->get( 'enable_navcate', 1 );
$enableImageLink = $params->get( 'enable_image_link', 1 );
$customSliderClass = $params->get('custom_slider_class','');
$customSliderClass = is_array($customSliderClass)?$customSliderClass:array($customSliderClass);

$itemLayout = modK2fieldsContentsHelper::layout($module, $params, 'item');

$lofIds = array();

if ($isPartitioned) {
        foreach ($itemList as $catId => $v) $lofIds[] = 'lofass-'.$module->id.'-'.$catId;
} else {
        $catId = 0;
        $lofIds[] = 'lofass-'.$module->id.'-'.$catId;        
}

$document->addScriptDeclaration(
"window.addEvent('domready', function(){         
        new LofK2SlideShowsCreator(
                ".json_encode($lofIds) .",
                { 
                        fxObject:{
                                transition:" . $params->get( 'effect', 'Fx.Transitions.Quad.easeIn' ) . ",
                                duration:" . (int)$params->get('duration', '700') . "
                        },
                        interval:" . (int)$params->get('interval', '3000') . ",
                        direction :'" . $params->get('layout_style','opacity') . "', 
                        navItemHeight:" . $params->get('navitem_height', 100) . ",
                        navItemWidth:" . $params->get('navitem_width', 310) . ",
                        navItemsDisplay:" . $params->get('max_items_display', 3) . "
                },
                {
                        displayButton:" . $params->get('display_button', '') . ",
                        autoStart:" . $params->get('auto_start', 1) . "
                }
        );
});");
