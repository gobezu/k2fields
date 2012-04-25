<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

echo JHtml::_('sliders.start', "cat-pane-".$module->id);
foreach ($itemList as $catId => $list) {
        echo JHtml::_('sliders.panel', $list[0]->categoryname, "cat-page-".$catId);
        require $template;
}
echo JHtml::_('sliders.end');

?>
