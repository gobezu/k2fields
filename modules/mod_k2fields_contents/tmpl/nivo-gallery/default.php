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
                        $image = isset($item->image) ? $item->image : JURI::base().$mediaFolder.'bg.png';
                        ?>
                        <li data-type="html" data-title="<?php echo JprovenUtility::html($item->title); ?>" data-caption="">
                                <?php require $itemLayout; ?> 
                        </li>
                <?php endforeach; ?> 
                </ul>
        </div>
</div>