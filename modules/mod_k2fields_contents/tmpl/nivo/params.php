<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'jquery.nivo.slider.js');
$document->addStyleSheet(JURI::base().$mediaFolder.'nivo-slider.css');
$document->addStyleSheet(JURI::base().$mediaFolder.'themes/'.$themeName.'/'.$themeName.'.css');

if ($isPartitioned) {
        $partitionIds = array();
        
        foreach ($itemList as $catId => $list) {
                $partitionId = 'nivo_wrap-'.$module->id.'-'.$catId;
                $partitionIds[] = $partitionId;
        }
} else {
        $catId = 0;
        $partitionId = 'nivo_wrap-'.$module->id.'-'.$catId;
        $partitionIds = array($partitionId);
}

$styles = array('width'=>$settings->get('width'), 'height'=>$settings->get('height'));
$styles = array_filter($styles);

if (!empty($styles)) {
        $_styles = array();
        foreach ($styles as $key => $style) $_styles[] = $key.':'.$style;
        $styles = ' style="'.implode(';', $_styles).'"';
} else {
        $styles = '';
}

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