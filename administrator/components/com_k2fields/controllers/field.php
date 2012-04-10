<?php
//$Copyright$
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class K2FieldsControllerField extends JController {
	function retrieve() {
                $model = $this->getModel('fields');
                $id = JRequest::getInt('id');
                $result = $model->retrieveList($id);
                echo $result;
                $app = JFactory::getApplication();
                $app->close();
	}
        
        function retrievepath() {
                $this->retrieve();
        }
        
        function autocomplete() {
                $input = JFactory::getApplication()->input;
                
                $id = $input->get('id', '', 'int');
                $value = $input->get('value', '', 'string');
                $pos = $input->get('position', '', 'int');
                $isSearch = $input->get('search', false, 'bool');
                $type = $input->get('type', 'basic', 'string');
                $method = $input->get('method', 'm', 'string');
                $isReverse = $input->get('reverse', '', 'string');
                $isReverse = $isReverse == '1' || $isReverse == 'true';
                
                $model = $this->getModel('fields');
                $completions = $model->autocomplete($id, $value, $type, $pos, $method, $isSearch, $isReverse);
                $completions = json_encode($completions);
                
                $app = JFactory::getApplication();
                $app->close($completions);
        }
        
        
}

?>
