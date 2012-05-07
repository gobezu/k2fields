<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

$partition_tmp = $params->get( 'partition_module_height', 'auto' );
$partition_moduleHeight = ( $partition_tmp=='auto' ) ? 'auto' : (int)$partition_tmp.'px';
$partition_tmp = $params->get( 'partition_module_width', 'auto' );
$partition_moduleWidth = ( $partition_tmp=='auto') ? 'auto': (int)$partition_tmp.'px';
$partition_themeClass = $params->get( 'partition_loftheme' , '');
$partition_openTarget = $params->get( 'partition_open_target', 'parent' );
$partition_class = $params->get( 'partition_navigator_pos', 'right' ) == "0" ? '':'lof-sn'.$params->get( 'partition_navigator_pos', 'right' );

$partition_css3 = $params->get('partition_enable_css3','1')? " lof-css3":"";
$partition_isIntrotext = $params->get('partition_slider_information', 'description') == 'description'?0:1;

$partition_navEnableThumbnail = $params->get( 'partition_enable_thumbnail', 1 );
$partition_navEnableTitle = $params->get( 'partition_enable_navtitle', 1 );
$partition_navEnableDate = $params->get( 'partition_enable_navdate', 1 );
$partition_navEnableCate = $params->get( 'partition_enable_navcate', 1 );
$partition_enableImageLink = $params->get( 'partition_enable_image_link', 1 );
$partition_mainWidth = (int) $params->get('partition_main_width', 650);
?>
<div id="lofass-<?php echo $module->id; ?>" class="lof-ass<?php echo $params->get('partition_moduleclass_sfx', ''); ?> moduleItemView" style="height:<?php echo $partition_moduleHeight; ?>; width:<?php echo $partition_moduleWidth; ?>">
        <div class="lofass-container <?php echo $partition_css3; ?> <?php echo $partition_themeClass; ?> <?php echo $partition_class; ?>">
                <div class="preload"><div></div></div>
                <!-- MAIN CONTENT --> 
                <div class="lof-main-wapper" style="height:<?php echo (int) $params->get('partition_main_height', 300); ?>px;width:<?php echo $partition_mainWidth; ?>px;">
                        <?php 
                                $partitionI = 0;
                                foreach ($itemList as $catId => $list) {
                                        $partitionId = $partitionIds[$partitionI];
                                        $partitionI++; 
                                ?>
                                <div class="lof-main-item">
                                        <div class="<?php echo 'cat' . $catId . ' modcat' . $catId; ?>">
                                                <div class="lof-description lof-contains-lof-description">
                                                        <?php require $template; ?>
                                                </div>
                                        </div>
                                </div> 
                        <?php } ?>

                </div>
                <!-- END MAIN CONTENT --> 
                <!-- NAVIGATOR -->
                <?php if ($params->get('partition_display_button', 1)) : ?>
                        <div class="lof-buttons-control">
                                <a href="" onclick="return false;" class="lof-previous"><?php echo JText::_('Previous'); ?></a>
                                <a href="" class="lof-next"  onclick="return false;"><?php echo JText::_('Next'); ?></a>
                        </div>
                <?php endif; ?>
                <?php if ($partition_class): ?>    
                        <div class="lof-navigator-outer">
                                <ul class="lof-navigator">
                                        <?php
                                        $i = 0;
                                        $n = count($itemList);
                                        foreach ($itemList as $catId => $list) : ?>
                                                <li class="lof-navigator-item-<?php echo $i . ($i == 0 ? ' lof-navigator-item-first' : '') . ($i == $n - 1 ? ' lof-navigator-item-last' : '') ?>">
                                                        <div>
                                                                <h4><?php echo $list[0]->categoryname; ?></h4>
                                                        </div>
                                                </li>        
                                        <?php 
                                        $i++;
                                        endforeach; ?> 
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
                                transition:" . $params->get( 'partition_effect', 'Fx.Transitions.Quad.easeIn' ) . ",
                                duration:" . (int)$params->get('partition_duration', '700') . "
                        },
                        interval:" . (int)$params->get('partition_interval', '3000') . ",
                        direction :'" . $params->get('partition_layout_style','opacity') . "', 
                        navItemHeight:" . $params->get('partition_navitem_height', 100) . ",
                        navItemWidth:" . $params->get('partition_navitem_width', 310) . ",
                        navItemsDisplay:" . $params->get('partition_max_items_display', 3) . "
                },
                {
                        displayButton:" . $params->get('partition_display_button', 'true') . ",
                        autoStart:" . $params->get('partition_auto_start', 1) . "
                }
        );
});");
