<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

echo JHtml::_('tabs.start', "cat-pane-".$module->id);
$partitionIds = 0;
foreach ($itemList as $catId => $list) {
        $partitionId = $partitionIds[$partitionI];
        $partitionI++;
        echo JHtml::_('tabs.panel', $list[0]->categoryname, "cat-page-".$catId);
        require $template;
}
echo JHtml::_('tabs.end');

?>
