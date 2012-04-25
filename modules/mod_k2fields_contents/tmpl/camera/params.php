<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'camera.js');
$document->addScript(JURI::base().$mediaFolder.'jquery.easing.1.3.js');
$document->addStyleSheet(JURI::base().$mediaFolder.'css/camera.css');

if ($isPartitioned) {
        $partitionIds = array();
        
        foreach ($itemList as $catId => $list) {
                $partitionId = 'camera_wrap-'.$module->id.'-'.$catId;
                $partitionIds[] = $partitionId;
        }
} else {
        $catId = 0;
        $partitionId = 'camera_wrap-'.$module->id.'-'.$catId;
        $partitionIds = array($partitionId);
}
?>
<script type="text/javascript">
        jQuery.noConflict();
        (function($) {
                $(document).ready(function() {
                        <?php foreach ($partitionIds as $partitionId) { ?>
                        $("#<?php echo $partitionId; ?>").camera(<?php echo $settings->toString(); ?>);
                        <?php } ?>
                }); 
        })(jQuery);
</script>