<?php
//$Copyright$
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class K2FieldsControllerFields extends JController {
	function retrieve() {
                $model = $this->getModel('fields');
                $fields = $model->getFieldsBasedOnRequest();
                $item = JRequest::getInt('id', 0);
                $cat = JRequest::getInt('cid', JRequest::getInt('catid'));
                $type = JRequest::getWord('type');
                $output = JprovenUtility::renderK2fieldsForm($fields, $type, false, $cat, $item);
                echo $output;
                JFactory::getApplication()->close();
	}
}

?>
