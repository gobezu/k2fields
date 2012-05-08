<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
$_moduleHeight = (int)$moduleHeight;
if ($_moduleHeight) {
        $_moduleHeight -= 28;
        $_moduleHeight .= 'px';
        $_moduleSize = 'height:'.$_moduleHeight.';width:'.$moduleWidth;
} else {
        $_moduleSize = $moduleSize;
}
?>
<div class="nivo-gallery-wrapper">
        <div id="<?php echo $partitionId; ?>" class="nivoGallery" style="<?php echo $_moduleSize; ?>">
                <ul>
                <?php foreach ($list as $no => $item) : 
                        if ($settings->get('show') == 'html') : ?>
                        <li data-type="html" data-title="<?php echo JprovenUtility::html($item->title); ?>">
                                <?php require $itemLayout; ?> 
                        </li>
                        <?php elseif ($settings->get('show') == 'image') : 
                                $imageCaption = K2FieldsModuleHelper::imageCaption($item, $params, $itemLayout, 'image');
                                $dataCaption = K2FieldsModuleHelper::imageCaption($item, $params, $itemLayout);
                                if (empty($imageCaption)) $imageCaption = K2FieldsModuleHelper::imageCaption($item, $params, $itemLayout, 'title');
                                if ($imageCaption == $dataCaption) $dataCaption = '';
                                ?>
                        <li data-title="<?php echo $imageCaption; ?>" data-caption="<?php echo $dataCaption; ?>">
                                <?php echo K2FieldsModuleHelper::image($item, $params, $itemLayout); ?>
                        </li>
                        <?php endif; ?>
                <?php endforeach; ?> 
                </ul>
        </div>
</div>