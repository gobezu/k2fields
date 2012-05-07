<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
$partitionI = 0;
echo JHtml::_('sliders.start', "cat-pane-".$module->id);
foreach ($itemList as $catId => $list) {
        $partitionId = $partitionIds[$partitionI];
        $partitionI++;
        echo JHtml::_('sliders.panel', $list[0]->categoryname, "cat-page-".$catId);
        require $template;
}
echo JHtml::_('sliders.end');

?>
