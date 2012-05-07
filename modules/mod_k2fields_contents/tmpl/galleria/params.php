<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'galleria-1.2.7.js');

if (!$settings->get('theme')) $settings->set('theme', 'classic');

$theme = JURI::base().$mediaFolder.'themes/'.$settings->get('theme').'/galleria.'.$settings->get('theme').'.min.js';

$settings->set('theme', null);

if ($settings->get('autoplay')) {
        $settings->set('autoplay', $settings->get('autoplayInterval', 5000));
        $settings->set('autoplayInterval', null);
}

?>
<script type="text/javascript">
        jQuery.noConflict();
        (function($) {
                $(document).ready(function() {
                        Galleria.loadTheme('<?php echo $theme; ?>');
                        <?php foreach ($partitionIds as $partitionId) { ?>
                        Galleria.run('#<?php echo $partitionId; ?>', <?php echo $settings->toString(); ?>);
                        <?php } ?>
                }); 
        })(jQuery);
</script>