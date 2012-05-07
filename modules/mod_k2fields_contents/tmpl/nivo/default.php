<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
$captions = array();
?>
<div class="nivo-wrapper theme-<?php echo $themeName; ?>" style="<?php echo $moduleSize; ?>">
        <div id="<?php echo $partitionId; ?>" class="nivoSlider" style="<?php echo $moduleSize; ?>">
                <?php foreach ($list as $no => $item) : 
                        $image = isset($item->image) ? $item->image : JURI::base().$mediaFolder.'bg.png';
                        $link = $settings->get('linked') ? $item->link : '';
                        $linkTarget = $link ? $settings->get('linktarget', '') : '';
                        $caption = '';
                        if ($settings->get('show_caption') == 'title') {
                                $caption = JprovenUtility::html($item->title);
                        }
                        if ($caption) $caption = ' title="'.$caption.'"';
                        ?>
                <div>
                        <?php if ($link) : ?><a href="<?php echo $linkTarget; ?>" target="<?php echo $linkTarget; ?>"><?php endif; ?>
                        <img src="<?php echo $image; ?>" <?php echo $caption; ?> />
                        <?php if ($link) : ?></a><?php endif; ?>
                </div>
                <?php endforeach; ?> 
        </div>
</div>