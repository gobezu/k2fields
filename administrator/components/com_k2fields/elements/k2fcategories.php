<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_k2/elements/categories.php';

if(K2_JVERSION=='16'){
	class JFormFieldK2FCategories extends JFormFieldCategories {
		function getInput(){
			return JElementK2FCategories::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}
}

class JElementK2FCategories extends JElementCategories {
        var $_name = 'k2fcategories';
        function fetchElement($name, $value, &$node, $control_name) {
                $els = parent::fetchElement($name, $value, $node, $control_name);
                $els = explode('<option ', $els);
                $elsFirst = array_slice($els, 0, 2);
                $els = array_slice($els, 2, count($els));
                $elsFirst[] = 'value="stickymenu"'.($value == 'stickymenu' ? ' selected="selected"' : '').'>&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.JText::_('Sticky:menu').'&nbsp;&nbsp;&lt;</option>';
                $elsFirst[] = 'value="stickycat"'.($value == 'stickycat' ? ' selected="selected"' : '').'>&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.JText::_('Sticky:category').'&nbsp;&nbsp;&lt;</option>';
                $elsFirst[] = 'value="stickyitemtag"'.($value == 'stickyitemtag' ? ' selected="selected"' : '').'>&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.JText::_('/TBI/ Sticky:item/tag').'&nbsp;&nbsp;&lt;</option>';
                $elsFirst[] = 'value="stickyitemkeyword"'.($value == 'stickyitemkeyword' ? ' selected="selected"' : '').'>&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.JText::_('/TBI/ Sticky:item/keyword').'&nbsp;&nbsp;&lt;</option>';
                $elsFirst[] = 'value="stickyitemfield"'.($value == 'stickyitemfield' ? ' selected="selected"' : '').'>&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.JText::_('/TBI/ Sticky:item/field').'&nbsp;&nbsp;&lt;</option>';
                $els = array_merge($elsFirst, $els);
                $els = implode('<option ', $els);
                return $els;
        }
}