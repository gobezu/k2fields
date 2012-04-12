<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_k2/elements/item.php';

class JFormFieldK2FItem extends JFormField {
        function getInput(){
                return JElementK2FItem::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
        }
}

class JElementK2FItem extends JElementItem {
        var $_name = 'k2fitem';
}
