<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_k2/elements/categories.php';

class JFormFieldK2FCategories extends JFormFieldCategories {
        function getInput(){
                return JElementK2FCategories::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
        }
}

jimport('joomla.html.parameter.element');

class JElementK2FCategories extends JElementCategories {
        var $_name = 'k2fcategories';
}