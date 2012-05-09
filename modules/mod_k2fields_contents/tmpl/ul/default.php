<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="k2fields-ul-wrapper">
        <div id="<?php echo $partitionId; ?>" style="<?php echo $moduleSize; ?>">
        <?php foreach ($list as $no => $item) : ?>
                <div><?php require $itemLayout; ?></div>
        <?php endforeach; ?>
        </div>
</div>