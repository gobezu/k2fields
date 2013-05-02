<?php

//$Copyright$

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

require_once JPATH_SITE . '/components/com_k2/controllers/itemlist.php';

class K2fieldsControllerItemlist extends K2ControllerItemlist {
        function count() {
                $module = JFactory::getApplication()->input->get('module', '', 'int');
                $module = JprovenUtility::getModule($module);
                $st = K2Model::getInstance('searchterms', 'K2FieldsModel');
                $max = $module->params->get('searchcountmax', '');
                $cat = JFactory::getApplication()->input->get('cid', '', 'int');
                $cat = JprovenUtility::getK2CategoryPath($cat);
                $max = JprovenUtility::_setting($max, $cat, '%%', 'all');
                $max = $max ? current(current(current($max))) : 0;
                $result = array('count'=>(int)$st->getTotal(), 'max'=>(int)$max);
                $result = json_encode($result);
                $app = JFactory::getApplication();
                $app->close($result);
        }

        function display() {
                $input = JFactory::getApplication()->input;
                $cid = $input->getInt('cid', -1);

                if ($cid == 0) $cid = -1;

                $task = $input->getCmd('task');
                $input->set('k2fieldstask', $task);
                $params = JComponentHelper::getParams('com_k2');
                $this->adjustLimits($params, $cid);

                if ($cid != -1) {
                        $input->set('task', 'category');
                        $input->set('layout', 'category');
                        $input->set('id', $cid);
                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                }
//                else {
//                        $input->set('task', '');
//                }

                $lim = $input->getInt('limit');

                if ($lim && $lim <= (int) K2FieldsModelFields::setting('maximumresultlistsize')) {
                        $input->set('_limit_', $lim);
                }

                parent::display();
                $input->set('task', $task);
        }

        function adjustLimits(&$params, $categoryId) {
                $layout = JFactory::getApplication()->input->get('layout', 'category', 'word');

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