<?php

//$Copyright$
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

require_once JPATH_SITE . '/components/com_k2/controllers/itemlist.php';

class K2fieldsControllerItemlist extends K2ControllerItemlist {
        function display() {
                $cid = JRequest::getInt('cid', -1);
                
                if ($cid == 0) $cid = -1;
                
                $task = JRequest::getCmd('task');
                JRequest::setVar('k2fieldstask', $task);
                $params = JComponentHelper::getParams('com_k2');                
                $this->adjustLimits($params, $cid);
                
                if ($cid != -1) {
                        JRequest::setVar('task', 'category');
                        JRequest::setVar('layout', 'category');
                        JRequest::setVar('id', $cid);
                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                } else {
                        JRequest::setVar('task', '');
                }
                
                $lim = JRequest::getInt('limit');
                
                if ($lim && $lim <= (int) K2FieldsModelFields::setting('maximumresultlistsize')) {
                        JRequest::setVar('_limit_', $lim);
                }
                
                parent::display();
                JRequest::setVar('task', $task);
        }
        
        function adjustLimits(&$params, $categoryId) {
                $layout = JRequest::getWord('layout', 'category');
                
                if ($categoryId != -1 && !preg_match('#map#', $layout)) return;
                
                $num = K2FieldsModelFields::setting('numLeadingItems', null, 1, $layout);
                if (is_array($num)) $num = $num[$layout][0][0];
                $params->set('num_leading_items', $num);
                
                $num = K2FieldsModelFields::setting('numPrimaryItems', null, 1, $layout);
                if (is_array($num)) $num = $num[$layout][0][0];
                $params->set('num_primary_items', $num);
                
                $num = K2FieldsModelFields::setting('numSecondaryItems', null, 1, $layout);
                if (is_array($num)) $num = $num[$layout][0][0];
                $params->set('num_secondary_items', $num);
                
                $num = K2FieldsModelFields::setting('numLinks', null, 1, $layout);
                if (is_array($num)) $num = $num[$layout][0][0];
                $params->set('num_links', $num);
        }
}
?>
