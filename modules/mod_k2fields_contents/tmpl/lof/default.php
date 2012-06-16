<?php
//$Copyright$
// no direct access
defined('_JEXEC') or die('Restricted access');
if (!$isPartitioned) {
        $catId = 0;
        $mainWidth = $moduleWidth - $navItemWidth;
} else {
        $mainWidth = $partition_mainWidth - $navItemWidth;
}
$mainWidth -= 19; // Taking height for the button width
$params->set('max_items_display', floor((int)$moduleHeight/$params->get('navitem_height', 100)));
?>
<div id="<?php echo $partitionId; ?>" class="lof-ass<?php echo $params->get('moduleclass_sfx', ''); ?> moduleItemView" style="height:<?php echo $moduleHeight; ?>';width:'<?php echo $moduleWidth; ?>;">
        <div class="lofass-container <?php echo $css3; ?> <?php echo $themeClass; ?> <?php echo $class; ?>">
                <div class="preload"><div></div></div>
                <!-- MAIN CONTENT --> 
                <div class="lof-main-wapper" style="height:<?php echo $moduleHeight; ?>;width:<?php echo (int) $mainWidth; ?>px;">
                        <?php foreach ($list as $no => $item): ?>
                                <div class="lof-main-item<?php echo(isset($customSliderClass[$no]) ? " " . $customSliderClass[$no] : "" ); ?>">
                                        <div class="<?php echo 'item' . $item->id . ' moditem' . $item->id . ' cat' . $item->catid; ?>">
                                                <div class="lof-description">
                                                        <?php require $itemLayout; ?>
                                                </div>
                                        </div>
                                </div> 
                        <?php endforeach; ?>

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
                                        for ($i = 0, $n = count($list); $i < $n; $i++):
                                                $item = $list[$i];
                                                ?>
                                                <li class="lof-navigator-item-<?php echo $i . ($i == 0 ? ' lof-navigator-item-first' : '') . ($i == $n - 1 ? ' lof-navigator-item-last' : '') ?>">
                                                        <div>
                                                                <?php if ($navEnableThumbnail && isset($item->imageThumb) && $item->imageThumb): ?>
                                                                        <?php echo K2FieldsModuleHelper::imageThumb($item, $params); ?> 
                                                                <?php endif; ?> 
                                                                <?php if ($navEnableTitle): ?>
                                                                        <h4><?php echo $item->title; ?></h4>
                                                                <?php endif; ?> 
                                                                <?php if ($navEnableDate): ?> 
                                                                        <span><?php echo $item->date; ?></span>
                                                                <?php endif; ?> 
                                                                <?php if ($navEnableCate): ?> 
                                                                        <br><span><b><?php echo JText::_("Publish In:"); ?></b></span>
                                                                        <a href="<?php $item->catlink; ?>" title="<?php echo $item->category_title; ?>"><b><?php echo $item->category_title; ?></b></a>

                                                                <?php endif; ?> 
                                                        </div>    
                                                </li>
                                        <?php endfor; ?> 		
                                </ul>
                        </div>
                <?php endif; ?>       
        </div>
</div>