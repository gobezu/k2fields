<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 
$captions = array();
$_moduleHeight = (int)$moduleHeight;
if ($_moduleHeight) {
        $_moduleHeight -= 60;
        $_moduleHeight .= 'px';
        $_moduleSize = 'height:'.$_moduleHeight.';width:'.$moduleWidth;
} else {
        $_moduleSize = $moduleSize;
}
?>

<div class="rpflorence-SlideShow" style="<?php echo $_moduleSize; ?>"> 
	<div id="<?php echo $partitionId; ?>">
        <?php foreach ($list as $no => $item) {
                $image = isset($item->image) ? $item->image : JURI::base().$mediaFolder.'bg.png';
                $imageThumb = isset($item->image_thumb) ? $item->image_thumb : JURI::base().$mediaFolder.'bgThumb.png';
                $link = $settings->get('linked') ? $item->link : '';
                $linkTarget = $link ? $settings->get('linktarget', '') : '';
                $id = $partitionId.'_item_'.$no;
                ?>
		<div id="<?php echo $id; ?>">
                        <?php require $itemLayout; ?>
                </div>
                <?php 
                if ($settings->get('show_caption')) {
                        $captions[] = '<li><a class="current" href="#'.$id.'" title="'.JprovenUtility::html($item->title).'"></a></li>';
                }
        }
        ?>
	</div>
        <?php if (!empty($captions)) echo '<ul>'.implode('', $captions).'</ul>'; ?>
</div>
