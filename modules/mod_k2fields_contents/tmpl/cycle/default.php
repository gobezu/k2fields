<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="k2fields-ul-wrapper">
        <ul id="<?php echo $partitionId; ?>" style="<?php echo $moduleSize; ?>">
        <?php foreach ($list as $no => $item) : ?>
                <li><?php require $itemLayout; ?></li>
        <?php endforeach; ?> 
        </ul>
</div>