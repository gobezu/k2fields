<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

$_moduleWidth = (int)$moduleWidth;
if ($_moduleWidth) {
        // .slide_container padding
        $_moduleWidth -= 20*2;
        $__moduleWidth = $_moduleWidth;
        
        // .slide padding
        $__moduleWidth -= 20*2;
        
        if ($settings->get('showNextPrev')) {
                $_moduleWidth -= 24*2;
                $__moduleWidth -= 24*2;
        }
        $_moduleWidth .= 'px';
        $__moduleWidth .= 'px';
}

$_moduleHeight = (int)$moduleHeight;
if ($_moduleHeight) {
        if ($settings->get('generatePagination')) $_moduleHeight -= 45;
        
        // .slide_container padding
        $_moduleHeight -= 20*2;
        $__moduleHeight = $_moduleHeight;
        
        // .slide padding
        $__moduleHeight -= 20*2;
        
        $_moduleHeight .= 'px';
        $__moduleHeight .= 'px';
}

if ($_moduleHeight && $_moduleWidth) {
        $_moduleSize = 'height:'.$_moduleHeight.';width:'.$_moduleWidth;
        $__moduleSize = 'height:'.$__moduleHeight.';width:'.$__moduleWidth;
} else {
        $_moduleSize = $__moduleSize = $moduleSize;
}
?>
<div class="slidejs-wrapper">
        <div id="<?php echo $partitionId; ?>">
                <div class="slides_container" style="<?php echo $_moduleSize; ?>">
                <?php foreach ($list as $no => $item) : ?>
                        <div class="slide" style="<?php echo $__moduleSize; ?>;">
                                <?php require $itemLayout; ?>
                        </div>
                <?php endforeach; ?> 
                </div>
                <?php if ($settings->get('showNextPrev')) : ?>
                <a href="#" class="prev"><img src="<?php echo JURI::base().$mediaFolder; ?>img/arrow-prev.png" width="24" height="43" alt="Prev"></a>
                <a href="#" class="next"><img src="<?php echo JURI::base().$mediaFolder; ?>img/arrow-next.png" width="24" height="43" alt="Next"></a>
                <?php endif; ?> 
        </div>
        <div class="clr"></div>
</div>