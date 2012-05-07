<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'jquery.nivo.slider.js');
$document->addStyleSheet(JURI::base().$mediaFolder.'nivo-slider.css');
$document->addStyleSheet(JURI::base().$mediaFolder.'themes/'.$themeName.'/'.$themeName.'.css');

?>
<script type="text/javascript">
        jQuery.noConflict();
        (function($) {
                $(document).ready(function() {
                        <?php foreach ($partitionIds as $partitionId) { ?>
                        $("#<?php echo $partitionId; ?>").nivoSlider(<?php echo $settings->toString(); ?>);
                        <?php } ?>
                }); 
        })(jQuery);
</script>