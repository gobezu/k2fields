<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldK2FParams extends JFormField {
        var	$type = 'k2fparams';

        function getInput(){
                return JElementK2FParams::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
        }
}

jimport('joomla.html.parameter.element');

class JElementK2FParams extends JElement {
        var $_name = 'k2fparams';
        
        function fetchElement($name, $value, &$node, $control_name){
                $k2CategoryForm = new JParameter('', JPATH_ADMINISTRATOR.'/components/com_k2/models/category.xml');
                $k2ItemForm = new JParameter('', JPATH_ADMINISTRATOR.'/components/com_k2/models/item.xml');
                // TODO: add for k2items
//                $k2itemCategoryForm = new JParameter('', JPATH_ADMINISTRATOR.'/components/com_k2fields/models/category.xml');
//                $k2itemForm = new JParameter('', JPATH_ADMINISTRATOR.'/components/com_k2fields/models/item.xml');
                // K2_LAYOUT_TEMPLATE
                $options = array();
                
                $options[] = JHTML::_('select.option',  '', JText::_('K2_CATEGORY_ITEM_LAYOUT'), 'value', 'text', true);
                $opts = JElementK2FParams::_renderOptions($k2CategoryForm, 'category-item-layout', 'itemlist');
                $options = array_merge($options, $opts);
                
                $options[] = JHTML::_('select.option',  '', JText::_('K2_CATEGORY_VIEW_OPTIONS'), 'value', 'text', true);
                $opts = JElementK2FParams::_renderOptions($k2CategoryForm, 'category-view-options', 'itemlist');
                $options = array_merge($options, $opts);
                
                $options[] = JHTML::_('select.option',  '', JText::_('Category setting'), 'value', 'text', true);
                $opts = JElementK2FParams::_renderOptions($k2CategoryForm, 'item-view-options-listings', 'itemlist', JText::_('In itemlist view'));
                $options = array_merge($options, $opts);
                $opts = JElementK2FParams::_renderOptions($k2CategoryForm, 'item-view-options', 'itemlist', JText::_('In item view'));
                $options = array_merge($options, $opts);
                
                $options[] = JHTML::_('select.option',  '', JText::_('Item setting'), 'value', 'text', true);
                $opts = JElementK2FParams::_renderOptions($k2ItemForm, 'item-view-options-listings', 'item', JText::_('In itemlist view'));
                $options = array_merge($options, $opts);
                $opts = JElementK2FParams::_renderOptions($k2ItemForm, 'item-view-options', 'item', JText::_('In item view'));
                $options = array_merge($options, $opts);
                
                $fieldName = $name;
                $output = JHTML::_('select.genericlist',  $options, $fieldName, 'class="inputbox" style="width:90%;" multiple="multiple" size="10"', 'value', 'text', $value);
                
                return $output;
        }
        
        public static function _renderOptions($form, $group, $view, $groupLbl = '', $indent = '>&nbsp;&nbsp;&nbsp;') {
                $options = array();
                
                $ps = $form->getParams('params', $group);
                
                if (!empty($groupLbl)) {
                        $options[] = JHTML::_('select.option',  '', $indent.$groupLbl, 'value', 'text', true);
                        $indentOpt=$indent.$indent;
                } else {
                        $indentOpt=$indent;
                }
                
                foreach ($ps as $p) {
                        $v = trim($p[5]);
                        if (!empty($v) && $v != '@spacer' && $p[1] != '<hr />')
                                $options[] = JHTML::_('select.option',  $view.$v, $indentOpt.JText::_($p[3]));
                }
                
                return $options;
        }
}
