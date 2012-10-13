<?php
//$Copyright$

/** Original copyright
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
                // Construct an array of the HTML OPTION statements.
                $options = array ();
                foreach ($this->element->children() as $option)
                {
                        $val = (string) $option->attributes()->value;
                        //$val   = $option->attributes('value');
                        $text  = (string) $option;
                        $options[] = JHTML::_('select.option', $val, JText::_($text));
                }
 
                // Construct the various argument calls that are supported.
                $attribs       = ' ';
                $v = (string) $this->element->attributes()->size;
                if ($v) $attribs       .= 'size="'.$v.'"';
                $v = (string) $this->element->attributes()->class;
                if ($v) $attribs       .= 'class="'.$v.'"';
                else $attribs       .= 'class="inputbox"';
                
                $m = (string) $this->element->attributes()->multiple;
                if ($m) $attribs .= ' multiple="multiple"';
 
                // Render the HTML SELECT list.
                return JHTML::_('select.genericlist', $options, $this->name, $attribs, 'value', 'text', $this->value, $this->options['control'].$this->name);
        }
}
