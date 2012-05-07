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
<div class="easyslider-wrapper">
        <div id="<?php echo $partitionId; ?>" style="<?php echo $_moduleSize; ?>">
                <ul>
                <?php foreach ($list as $no => $item) : 
                        ?>
                        <li style="<?php echo $_moduleSize; ?>">
                                <?php require $itemLayout; ?> 
                        </li>
                <?php endforeach; ?> 
                </ul>
        </div>
</div>