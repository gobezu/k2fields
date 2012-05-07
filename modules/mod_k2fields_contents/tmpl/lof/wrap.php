<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScriptDeclaration(
"window.addEvent('domready', function(){         
        new LofK2SlideShowsCreator(
                ".json_encode($partitionIds) .",
                { 
                        fxObject:{
                                transition:" . $params->get( 'effect', 'Fx.Transitions.Quad.easeIn' ) . ",
                                duration:" . (int)$params->get('duration', '700') . "
                        },
                        interval:" . (int)$params->get('interval', '3000') . ",
                        direction :'" . $params->get('layout_style','opacity') . "', 
                        navItemHeight:" . $params->get('navitem_height', 100) . ",
                        navItemWidth:" . $navItemWidth . ",
                        navItemsDisplay:" . $params->get('max_items_display', 3) . "
                },
                {
                        displayButton:" . $params->get('display_button', '') . ",
                        autoStart:" . $params->get('auto_start', 1) . "
                }
        );
});");
