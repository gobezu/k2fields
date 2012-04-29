<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="slider-wrapper theme-<?php echo $themeName; ?>">
        <div id="<?php echo $partitionId; ?>" class="nivoSlider"<?php echo $styles; ?>>
                <?php foreach ($list as $no => $item) { 
                        $image = isset($item->image) ? $item->image : JURI::base().$mediaFolder.'bg.png';
                        $link = $settings->get('linked') ? $item->link : '';
                        $linkTarget = $link ? $settings->get('linktarget', '') : '';
                        $captionId = $partitionId.'-'.$no;
                        if ($link) { ?>
                                <a href="<?php echo $linkTarget; ?>" target="<?php echo $linkTarget; ?>"><img src="<?php echo $image; ?>" title="#<?php echo $captionId; ?>" /></a>
                        <?php 
                        } else { 
                        ?>
                                <img src="<?php echo $image; ?>" title="#<?php echo $captionId; ?>" />
                        <?php
                        }
                        if ($settings->get('show_caption')) {
                        ?>
                                <div id="<?php echo $captionId; ?>" class="nivo-html-caption">
                                        <?php require $itemLayout; ?>
                                </div>
                        <?php 
                        } 
                }
                ?> 
        </div>
</div>