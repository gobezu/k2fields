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
class JFormFieldMultiList extends JFormField {
        function getInput(){
                return JElementMultiList::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
        }
}
 
jimport('joomla.html.parameter.element');

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
                // Construct an array of the HTML OPTION statements.
                $options = array ();
                foreach ($node->children() as $option)
                {
                        $val = (string) $option->attributes()->value;
                        //$val   = $option->attributes('value');
                        $text  = $option->data();
                        $options[] = JHTML::_('select.option', $val, JText::_($text));
                }
 
                // Construct the various argument calls that are supported.
                $attribs       = ' ';
                $v = (string) $node->attributes()->size;
                if ($v) $attribs       .= 'size="'.$v.'"';
                $v = (string) $node->attributes()->class;
                if ($v) $attribs       .= 'class="'.$v.'"';
                else $attribs       .= 'class="inputbox"';
                
                $m = (string) $node->attributes()->multiple;
                if ($m) $attribs .= ' multiple="multiple"';
 
                // Render the HTML SELECT list.
                return JHTML::_('select.genericlist', $options, $name, $attribs, 'value', 'text', $value, $control_name.$name );
        }
}
