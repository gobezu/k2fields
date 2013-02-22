<?php
//$Copyright$

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');
JFormHelper::loadFieldClass('folderlist');

class JFormFieldDependentFolderList extends JFormFieldFolderList {
        public $type = 'DependentFolderList';
        
        public function getInput() {
                $dependsOn = $this->form->getField($this->element['dependson'], 'params');
                
                if (empty($dependsOn->value)) return 'Depends on :'.$dependsOn->name;
                
                return parent::getInput();
        }
        
        protected function getOptions() {
                $dependsOn = $this->form->getField($this->element['dependson'], 'params');
                
                if (empty($dependsOn->value)) return array();
                        
                $path = (string) $this->element['directory'];
                
                $reg = (string) $this->element['reg'];
                $replaceWith = '';
                
                if (!empty($reg)) {
                        $reg = '#'.$reg.'#';
                        preg_match($reg, $dependsOn->value, $m);
                        $replaceWith = $m[0];
                } else {
                        $replaceWith = $dependsOn->value;
                }
                
                $path = str_replace('%replace%', $replaceWith, $path);
                
                $this->element['directory'] = $path;
                
                jimport('joomla.filesystem.folder');
                
                if (!JFolder::exists(JPATH_SITE.'/'.$path)) {
                        $missing = (string) $this->element['missing'];
                        if (!$missing) $missing = 'INCORRECT_FOLDER';
                        return array(JHtml::_('select.option', '', JText::_($missing)));
                }
                
                return parent::getOptions();
        }
        
        public function getLabel() {
                $dependsOn = $this->form->getField($this->element['dependson'], 'params');
                
                $this->element['label'] = (string) $this->element['label'] . '<br />(Depends on <strong>'.(string) $dependsOn->element['label'].'</strong>. Please save that field before setting this one.)';
                
                return parent::getLabel();
        }
}