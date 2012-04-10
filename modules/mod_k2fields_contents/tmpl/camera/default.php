<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'camera.js');
$document->addScript(JURI::base().$mediaFolder.'jquery.easing.1.3.js');
$document->addStyleSheet(JURI::base().$mediaFolder.'css/camera.css');

if ($isPartitioned) {
        $partitionIds = array();
        
        jimport( 'joomla.html.pane' );
        
        $pane = JPane::getInstance('tabs');

        echo $pane->startPane("mod_k2fields_contents_".$module->id);

        foreach ($itemList as $catId => $list) {
                $partitionId = 'camera_wrap-'.$module->id.'-'.$catId;
                $partitionIds[] = $partitionId;
                echo $pane->startPanel($list[0]->categoryname, "cat-page-".$catId );
                require dirname(__FILE__).'/partition.php';
                echo $pane->endPanel();
        }

        echo $pane->endPane();
} else {
        $catId = 0;
        $partitionId = 'camera_wrap-'.$module->id.'-'.$catId;
        $partitionIds = array($partitionId);
        $list = $itemList;
        require dirname(__FILE__).'/partition.php';
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