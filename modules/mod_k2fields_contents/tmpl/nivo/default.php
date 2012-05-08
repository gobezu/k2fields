<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
$captions = array();
?>
<div class="nivo-wrapper theme-<?php echo $themeName; ?>" style="<?php echo $moduleSize; ?>">
        <div id="<?php echo $partitionId; ?>" class="nivoSlider" style="<?php echo $moduleSize; ?>">
                <?php 
                $no = 0;
                foreach ($list as $item) : 
                        $link = $settings->get('linked') ? $item->link : '';
                        $linkTarget = $link ? $settings->get('linktarget', '') : '';
                        $captions[] = K2FieldsModuleHelper::imageCaption($item, $params, $itemLayout, $settings->get('show_caption'), true);
                        ?>
                <div>
                        <?php if ($link) : ?><a href="<?php echo $linkTarget; ?>" target="<?php echo $linkTarget; ?>"><?php endif; ?>
                        <?php echo K2FieldsModuleHelper::image($item, $params, $itemLayout, array('title'=>'#'.$partitionId.'_'.$no)); ?>
                        <?php if ($link) : ?></a><?php endif; ?>
                </div>
                <?php 
                        $no++;
                endforeach; 
                ?> 
        </div>
        <?php foreach ($captions as $no => $caption) : ?>
        <div id="<?php echo $partitionId . '_' . $no; ?>" class="nivo-html-caption">
                <?php echo $caption; ?>
        </div>
        <?php endforeach; ?>
</div>