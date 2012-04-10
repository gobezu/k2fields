<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$document->addScript(JURI::base().$mediaFolder.'jquery.aw-showcase.js');
$document->addStyleSheet(JURI::base().$mediaFolder.'css/style.css');

$theme = $settings->get('theme');
$moduleWidth = $settings->get('content_width');
$padding = '';
$css = '';

if ($settings->get('arrows')) {
        if ($settings->get('arrows_position') == 'inside') {
                $css = 'arrows-inside';
        }
}

if ($theme == 'hundred_percent' || $theme == 'viewline') 
        $document->addStyleSheet(JURI::base().$mediaFolder.'css/style-index_'.$theme.'.css');

if ($isPartitioned) {
        $showcaseIds = array();
        
        jimport( 'joomla.html.pane' );
        
        $pane = JPane::getInstance('tabs');

        echo $pane->startPane("mod_k2fields_contents_".$module->id);

        foreach ($itemList as $catId => $list) {
                $showcaseId = 'awshowcase-'.$module->id.'-'.$catId;
                $showcaseIds[] = $showcaseId;
                echo $pane->startPanel($list[0]->categoryname, "cat-page-".$catId );
                require dirname(__FILE__).'/partition.php';
                echo $pane->endPanel();
        }

        echo $pane->endPane();
} else {
        $catId = 0;
        $showcaseId = 'awshowcase-'.$module->id.'-'.$catId;
        $showcaseIds = array($showcaseId);
        $list = $itemList;
        require dirname(__FILE__).'/partition.php';
}
?>
<script type="text/javascript">
        jQuery.noConflict();
        (function($) {
                $(document).ready(function() {
                        <?php foreach ($showcaseIds as $showcaseId) { ?>
                        $("#<?php echo $showcaseId; ?>").awShowcase(<?php echo $settings->toString(); ?>);
                        <?php } ?>
                }); 
        })(jQuery);
</script>