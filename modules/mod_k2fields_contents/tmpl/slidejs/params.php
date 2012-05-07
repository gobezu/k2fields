<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'slides.jquery.js');

$start = $settings->get('start');

if ($start == 'first') {
        $start = 1;
} else {
        $start = rand(1, count($list));
}

$settings->set('start', $start);

$settings->set('effect', $settings->get('effectNextPrev', 'slide') . ',' . $settings->get('effectPage', 'slide'));

$settings->set('play', $settings->get('autoplay') ? $settings->get('autoplayTime', 500) : 0);

$settings->set('pause', $settings->get('pauseAutoplay') ? $settings->get('pauseAutoplayTime', 500) : 0);

?>
<script type="text/javascript">
        jQuery.noConflict();
        (function($) {
                $(document).ready(function() {
                        <?php foreach ($partitionIds as $partitionId) { ?>
                        $("#<?php echo $partitionId; ?>").slides(<?php echo $settings->toString(); ?>);
                        <?php } ?>
                }); 
        })(jQuery);
</script>