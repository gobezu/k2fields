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
<div class="galleria-wrapper">
        <ul id="<?php echo $partitionId; ?>" style="<?php echo $_moduleSize; ?>">
        <?php foreach ($list as $no => $item) : 
                $layer = '';
                
                if ($settings->get('layerData') == 'item') {
                        ob_start();
                        require $itemLayout;
                        $layer = ob_get_contents();
                        ob_end_clean();
                        $layer = JprovenUtility::html($layer);
                } else if ($settings->get('layerData') == 'title') {
                        $layer = $caption;
                }
                ?>
                <li>
                        <a href="<?php echo $item->image; ?>">
                                <img 
                                        data-link="<?php echo $settings->get('linked') ? $item->link : ''; ?>"
                                        data-title="<?php echo JprovenUtility::html($item->title); ?>" 
                                        data-layer="<?php echo $layer; ?>"
                                        src="<?php echo $item->imageThumb; ?>" 
                                />
                        </a>
                </li>
        <?php endforeach; ?> 
        </ul>
</div>