<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JFormFieldMSQL extends JFormField {
        function getInput(){
		$db = JFactory::getDBO();
                $v = (string) $this->element->attributes()->query;
		$db->setQuery($v);
                $v = (string) $this->element->attributes()->key_field;
		$key = ($v ? $v : 'value');
                $val = (string) $this->element->attributes()->value_field;
                if (!$val) $val = (string) $this->element->attributes()->name;
                $v = $db->loadObjectList();
		return JHTML::_('select.genericlist',  $v, $this->name.'[]', 'class="inputbox" multiple="multiple" size="10"', $key, $val, $this->value, $this->options['control'].$this->name);
        }
}
