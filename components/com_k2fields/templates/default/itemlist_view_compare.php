<?php
/**
 * @version		$Id: category.php 569 2010-09-23 12:50:28Z joomlaworks $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.gr
 * @copyright	Copyright (c) 2006 - 2010 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div id="k2Container" class="itemListView<?php if($this->params->get('pageclass_sfx')) echo ' '.$this->params->get('pageclass_sfx'); ?>">
	<?php if($this->params->get('show_page_title')): ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>">
		<?php echo $this->escape($this->params->get('page_title')); ?>
	</div>
	<?php endif; ?>
        <div class="itemListCompare">
		<?php 
                if(isset($this->items) && count($this->items)) {
                        $tmplFile = preg_replace('#\.php$#', '', __FILE__);
                        $matrix = JprovenUtility::createComparisonMatrix($this->items);
                        $ui = '';
                        $rowCnt = -1;

                        foreach ($matrix as $section => $fields) {
                                $fieldCnt = -1;

                                foreach ($fields as $fieldId => $field) {
                                        $rowCnt++;
                                        $fieldCnt++;

                                        if ($rowCnt == 0) $ui .= '<thead>';
                                        else if ($rowCnt == 1) $ui .= '<tbody>';

                                        $ui .= JprovenUtility::renderComparisonRow($field, $rowCnt, $fieldCnt, $section);

                                        if ($rowCnt == 0) $ui .= '</thead>';
                                }
                        }

                        JprovenUtility::load('jpcompare.js', 'js');
                        $ui = '<table class="compare">'.$ui.'</tbody></table><script type="text/javascript">new JPCompare().comparize();</script>';

                        echo $ui;
                } 
                ?>
	</div>
</div>