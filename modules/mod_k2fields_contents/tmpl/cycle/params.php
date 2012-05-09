<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'jquery.cycle.all.js');

?>
<script type="text/javascript">
        jQuery.noConflict();
        (function($) {
                $(document).ready(function() {
                        <?php foreach ($partitionIds as $partitionId) { ?>
                        $("#<?php echo $partitionId; ?>").cycle(<?php echo $settings->toString(); ?>);
                        <?php } ?>
                }); 
        })(jQuery);
</script>