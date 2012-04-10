<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_k2/elements/items.php';

if(K2_JVERSION=='16'){
	class JFormFieldK2FItems extends JFormField {
		function getInput(){
			return JElementK2FItems::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}
}

class JElementK2FItems extends JElementItems {
        var $_name = 'k2fitems';
}
