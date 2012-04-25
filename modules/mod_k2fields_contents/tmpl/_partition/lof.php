<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

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


?>
<div id="lofass-<?php echo $module->id; ?>" class="lof-ass<?php echo $params->get('moduleclass_sfx', ''); ?> moduleItemView" style="height:<?php echo $moduleHeight; ?>; width:<?php echo $moduleWidth; ?>">
        <div class="lofass-container <?php echo $css3; ?> <?php echo $themeClass; ?> <?php echo $class; ?>">
                <div class="preload"><div></div></div>
                <!-- MAIN CONTENT --> 
                <div class="lof-main-wapper" style="height:<?php echo (int) $params->get('main_height', 300); ?>px;width:<?php echo (int) $params->get('main_width', 650); ?>px;">
                        <?php 
                                foreach ($itemList as $catId => $list) {
                                ?>
                                <div class="lof-main-item<?php echo(isset($customSliderClass[$no]) ? " " . $customSliderClass[$no] : "" ); ?>">
                                        <div class="<?php echo 'cat' . $catId . ' modcat' . $catId; ?>">
                                                <div class="lof-description">
                                                        <?php require $template; ?>
                                                </div>
                                        </div>
                                </div> 
                        <?php } ?>

                </div>
                <!-- END MAIN CONTENT --> 
                <!-- NAVIGATOR -->
                <?php if ($params->get('display_button', 1)) : ?>
                        <div class="lof-buttons-control">
                                <a href="" onclick="return false;" class="lof-previous"><?php echo JText::_('Previous'); ?></a>
                                <a href="" class="lof-next"  onclick="return false;"><?php echo JText::_('Next'); ?></a>
                        </div>
                <?php endif; ?>
                <?php if ($class): ?>    
                        <div class="lof-navigator-outer">
                                <ul class="lof-navigator">
                                        <?php
                                        foreach ($itemList as $catId => $list) : ?>
                                                <li class="lof-navigator-item-<?php echo $i . ($i == 0 ? ' lof-navigator-item-first' : '') . ($i == $n - 1 ? ' lof-navigator-item-last' : '') ?>">
                                                        <div>
                                                                <h4><?php echo $list[0]->categoryname; ?></h4>
                                                        </div>
                                                </li>        
                                        <?php endforeach; ?> 
                                </ul>
                        </div>
                <?php endif; ?>       
        </div>
</div>

<?php
$document->addScriptDeclaration(
"window.addEvent('domready', function(){         
        new LofK2SlideShowsCreator(
                ".json_encode(array("lofass-".$module->id)) .",
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
