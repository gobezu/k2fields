<?php
//$Copyright$

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class K2FieldsControllerFields extends JController {
	function retrieve() {
                $model = $this->getModel('fields');
                $fields = $model->getFieldsBasedOnRequest();
                $input = JFactory::getApplication()->input;
                $type = $input->get('type', '', 'word');
                $item = $input->get('id', 0, 'int');
                $cat = $input->get('catid', '', 'int');
                $cat = $input->get('cid', $cat, 'int');
                $output = JprovenUtility::renderK2fieldsForm($fields, $type, false, $cat, $item);
                echo $output;
                JFactory::getApplication()->close();
	}
}