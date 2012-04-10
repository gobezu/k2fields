<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_k2/elements/categoriesmultiple.php';

if(K2_JVERSION=='16'){
	class JFormFieldK2FMultiCategories extends JFormFieldCategoriesMultiple {
		function getInput(){
			return JElementK2FMultiCategories::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}
}

class JElementK2FMultiCategories extends JElementCategoriesmultiple {
        var $_name = 'k2fmulticategories';
}
