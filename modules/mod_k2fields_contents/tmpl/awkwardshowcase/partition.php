<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

if ($moduleWidth !== false) :
?>
<div style="width:<?php echo $moduleWidth; ?>px;<?php echo $padding; ?>" class="<?php echo $css; ?>">
<?php 
endif; 
?>
        <div id="<?php echo $showcaseId; ?>" class="showcase">
                <?php foreach ($list as $no => $item) { ?>
                <div class="showcase-slide">
                        <div class="showcase-content">
                                <?php require $itemLayout; ?>
                        </div>
                        <?php if ($settings->get('thumbnails')) : ?>
                        <div class="showcase-thumbnail">
                                <?php require $thumbNailLayout; ?>
                                <!-- <div class="showcase-thumbnail-caption">CAPTION</div> -->
                                <div class="showcase-thumbnail-cover"></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($settings->get('show_caption')) : ?>
                        <div class="showcase-caption">
                                <h2><?php require $captionLayout; ?></h2>
                        </div>
                        <?php endif; ?>                        
                </div>
                <?php } ?>
        </div>
<?php 
if ($moduleWidth !== false) : 
?>
</div>
<?php 
endif; 
?> 