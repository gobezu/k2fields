<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if(K2_JVERSION=='16'){
	class JFormFieldMSQL extends JFormField {
		function getInput(){
			return JElementMSQL::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}
}
 
class JElementMSQL extends JElement {
	var $_name = 'msql';

	function fetchElement($name, $value, &$node, $control_name){
		$db = JFactory::getDBO();
                $attrMtd = K2_JVERSION=='16' ? 'getAttribute' : 'attributes';
                $v = call_user_func(array($node, $attrMtd), 'query');
		$db->setQuery($v);
                $v = call_user_func(array($node, $attrMtd), 'key_field');
		$key = ($v ? $v : 'value');
                $v = call_user_func(array($node, $attrMtd), 'value_field');
		$val = ($v ? $v : '');
                if ($val == '') $val = call_user_func(array($node, $attrMtd), 'name');
                $v = $db->loadObjectList();
                $fieldName = ((K2_JVERSION=='16') ? $name : $control_name.'['.$name.']').'[]';
		return JHTML::_('select.genericlist',  $v, $fieldName, 'class="inputbox" multiple="multiple" size="10"', $key, $val, $value, $control_name.$name);
	}
}
