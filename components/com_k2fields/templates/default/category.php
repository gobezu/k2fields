<?php
// $Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
// Used only when no result have been rendered
$cid = JRequest::getInt('cid');
$advice = $tries = '';
if ($cid) {
        $link = K2HelperRoute::createCategoryLink($cid);
        $advice = JText::_('Search again with other parameter or please try one of the following related pages:');
        /**
         * TODO: This will not suffice for broader categories containing specializations 
         * and we would need to search for matching items among menu entries
         */
        $tries = array($link);
        $tries = '<ul class="advicetries"><li>'.implode('</li><li>', $tries).'</li></ul>';
} else {
        $advice = JText::_('Please try to search again with other search options.');
}
?>
<div class="emptysearchresult">
        <p class="status"><?php echo JText::_('Sorry no result.'); ?></p>
        <p class="advice"><?php echo $advice; ?></p>
        <?php echo $tries; ?>
</div>