<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 
?>
<div class="camera_wrap <?php echo $themeName; ?>" id="<?php echo $partitionId; ?>">
        <?php foreach ($list as $no => $item) { 
        $image = isset($item->image) ? $item->image : JURI::base().$mediaFolder.'bg.png';
        $imageThumb = isset($item->image_thumb) ? $item->image_thumb : JURI::base().$mediaFolder.'bgThumb.png';
        $link = $settings->get('linked') ? $item->link : '';
        $linkTarget = $link ? $settings->get('linktarget', '') : '';
        ?>
        <div data-thumb-text="<?php echo $item->title; ?>" data-thumb="<?php echo $imageThumb; ?>" data-src="<?php echo $image; ?>" data-link="<?php echo $link; ?>" data-target="<?php echo $linkTarget; ?>">
                <?php if ($settings->get('show_caption')) : ?>
                <div class="camera_caption <?php echo $settings->get('caption_effect'); ?>">
                        <?php require $itemLayout; ?>
                </div>
                <?php endif; ?>                
        </div>            
        <?php } ?>        
</div>