<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldK2FParams extends JFormField {
        var	$type = 'k2fparams';

                // TODO: add for k2items
                // K2_LAYOUT_TEMPLATE
        function getInput(){
                $form = JForm::getInstance(
                        'k2fparams', 
                        JPATH_ADMINISTRATOR.'/components/com_k2/models/category.xml', 
                        array(), 
                        true, 
                        '/form'
                );    
                
                $options = array();
                
                $options[] = JHTML::_('select.option',  '', JText::_('K2_CATEGORY_ITEM_LAYOUT'), 'value', 'text', true);
                $opts = self::_renderOptions($form, 'category-item-layout', 'itemlist');
                $options = array_merge($options, $opts);
                
                $options[] = JHTML::_('select.option',  '', JText::_('K2_CATEGORY_VIEW_OPTIONS'), 'value', 'text', true);
                $opts = self::_renderOptions($form, 'category-view-options', 'itemlist');
                $options = array_merge($options, $opts);
                
                $options[] = JHTML::_('select.option',  '', JText::_('Category setting'), 'value', 'text', true);
                $opts = self::_renderOptions($form, 'item-view-options-listings', 'itemlist', JText::_('In itemlist view'));
                $options = array_merge($options, $opts);
                $opts = self::_renderOptions($form, 'item-view-options', 'itemlist', JText::_('In item view'));
                $options = array_merge($options, $opts);
                
                $form = JForm::getInstance(
                        'k2fparams', 
                        JPATH_ADMINISTRATOR.'/components/com_k2/models/item.xml', 
                        array(), 
                        true, 
                        '/form'
                );     
                
                $options[] = JHTML::_('select.option',  '', JText::_('Item setting'), 'value', 'text', true);
                $opts = self::_renderOptions($form, 'item-view-options-listings', 'item', JText::_('In itemlist view'));
                $options = array_merge($options, $opts);
                $opts = self::_renderOptions($form, 'item-view-options', 'item', JText::_('In item view'));
                $options = array_merge($options, $opts);
                
                $output = JHTML::_('select.genericlist',  $options, $this->name.'[]', 'class="inputbox" style="width:90%;" multiple="multiple" size="10"', 'value', 'text', $this->value);
                
                return $output;
        }
        
        private static function _renderOptions($form, $group, $view, $groupLbl = '', $indent = '>&nbsp;&nbsp;&nbsp;') {
                $options = array();
                
                if (!empty($groupLbl)) {
                        $options[] = JHTML::_('select.option',  '', $indent.$groupLbl, 'value', 'text', true);
                        $indentOpt=$indent.$indent;
                } else {
                        $indentOpt=$indent;
                }
                
                $flds = $form->getFieldset($group);
                $excludes = array('JFormFieldHeader', 'JFormFieldSpacer');
                
                foreach ($flds as $fldName => $fld) {
                        if (in_array(get_class($fld), $excludes)) continue;
                        $options[] = JHTML::_('select.option',  $view.$fld->fieldname, $indentOpt.$fld->getTitle());
                }
                
                return $options;
        }     
}