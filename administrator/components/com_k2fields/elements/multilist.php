<?php
/**
* @copyright    Copyright (C) 2009 Open Source Matters. All rights reserved.
* @license      GNU/GPL
*/
 
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();
 
/**
 * Renders a multiple item select element
 *
 */

if(K2_JVERSION=='16'){
	class JFormFieldMultiList extends JFormField {
		function getInput(){
			return JElementMultiList::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}
}
 
class JElementMultiList extends JElement
{
        /**
        * Element name
        *
        * @access       protected
        * @var          string
        */
        var    $_name = 'MultiList';
 
        function fetchElement($name, $value, &$node, $control_name)
        {
                $attrMtd = K2_JVERSION=='16' ? 'getAttribute' : 'attributes';
                
                // Base name of the HTML control.
                $fieldName = ((K2_JVERSION=='16') ? $name : $control_name.'['.$name.']'.'[]');
 
                // Construct an array of the HTML OPTION statements.
                $options = array ();
                foreach ($node->children() as $option)
                {
                        $val = call_user_func(array($option, $attrMtd), 'value');
                        //$val   = $option->attributes('value');
                        $text  = $option->data();
                        $options[] = JHTML::_('select.option', $val, JText::_($text));
                }
 
                // Construct the various argument calls that are supported.
                $attribs       = ' ';
//                if ($v = $node->attributes( 'size' )) {
                $v = call_user_func(array($node, $attrMtd), 'size');
                if ($v) $attribs       .= 'size="'.$v.'"';
                $v = call_user_func(array($node, $attrMtd), 'class');
                if ($v) $attribs       .= 'class="'.$v.'"';
                else $attribs       .= 'class="inputbox"';
                
                $m = call_user_func(array($node, $attrMtd), 'multiple');
                if ($m) $attribs .= ' multiple="multiple"';
 
                // Render the HTML SELECT list.
                return JHTML::_('select.genericlist', $options, $fieldName, $attribs, 'value', 'text', $value, $control_name.$name );
        }
}
