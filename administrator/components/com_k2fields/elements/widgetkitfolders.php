<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JFormFieldWidgetkitfolders extends JFormFieldFolderList {
	public $type = 'widgetkitfolders';
        
	function getInput() {
                $this->element['directory'] = 'plugins/system/widgetkit_k2/widgets';
                $path = (string) $this->element['directory'];
                
		if (!is_dir($path))
		{
			$path = JPATH_ROOT . '/' . $path;
		}
                
                if (!is_dir($path))
		{
                        return 'Widgetkit K2 plugin is not installed';
                }

                return parent::getInput();
	}
}
