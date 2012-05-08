<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 
?>
<div class="camera_wrap <?php echo $themeName; ?>" id="<?php echo $partitionId; ?>">
        <?php foreach ($list as $no => $item) { 
        $imageCaption = K2FieldsModuleHelper::imageCaption($item, $params, $itemLayout);
        $link = $settings->get('linked') ? $item->link : '';
        $linkTarget = $link ? $settings->get('linktarget', '') : '';
        ?>
        <div data-thumb-text="<?php echo $item->title; ?>" data-thumb="<?php echo $item->imageThumb; ?>" data-src="<?php echo $item->image; ?>" data-link="<?php echo $link; ?>" data-target="<?php echo $linkTarget; ?>">
                <?php if ($settings->get('show_caption')) : ?>
                <div class="camera_caption <?php echo $settings->get('caption_effect'); ?>">
                        <?php echo $imageCaption; ?>
                </div>
                <?php endif; ?>                
        </div>            
        <?php } ?>        
</div>