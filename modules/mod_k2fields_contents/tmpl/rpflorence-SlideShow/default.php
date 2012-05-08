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
                $id = $partitionId.'_item_'.$no;
                ?>
		<div id="<?php echo $id; ?>"><?php require $itemLayout; ?></div>
                <?php 
                if ($settings->get('show_navigator')) {
                        $imageCaption = $settings->get('show_caption') ?
                                K2FieldsModuleHelper::imageCaption($item, $params, $itemLayout):
                                '';
                        $captions[] = '<li><a class="current" href="#'.$id.'" title="'.$imageCaption.'"></a></li>';
                }
        }
        ?>
	</div>
        <?php if (!empty($captions)) echo '<ul>'.implode('', $captions).'</ul>'; ?>
</div>
