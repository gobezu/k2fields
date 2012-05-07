<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

JHtml::_('behavior.framework', true);

$document->addScript(JURI::base().$mediaFolder.'Dependencies/rpflorence-Loop-fbc5aec/Source/Loop.js');
$document->addScript(JURI::base().$mediaFolder.'Source/SlideShow.js');
?>
<script type="text/javascript">
        window.addEvent('domready', function() {
                new SlideShowCreator(<?php echo json_encode($partitionIds); ?>, <?php echo $settings->toString(); ?>)
        });
</script>
