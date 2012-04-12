<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JFormFieldMSQL extends JFormField {
        function getInput(){
                return JElementMSQL::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
        }
}
 
jimport('joomla.html.parameter.element');

class JElementMSQL extends JElement {
	var $_name = 'msql';

	function fetchElement($name, $value, &$node, $control_name){
		$db = JFactory::getDBO();
                $v = (string) $node->attributes()->query;
		$db->setQuery($v);
                $v = (string) $node->attributes()->key_field;
		$key = ($v ? $v : 'value');
                $val = (string) $node->attributes()->value_field;
                if (!$val) $val = (string) $node->attributes()->name;
                $v = $db->loadObjectList();
		return JHTML::_('select.genericlist',  $v, $name.'[]', 'class="inputbox" multiple="multiple" size="10"', $key, $val, $value, $control_name.$name);
	}
}
