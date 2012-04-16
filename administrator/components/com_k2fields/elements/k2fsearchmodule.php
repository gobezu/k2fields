<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JFormFieldK2FSearchmodule extends JFormField {
        function getInput(){
                return JElementK2FSearchmodule::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
        }
}

jimport('joomla.html.parameter.element');

class JElementK2FSearchmodule extends JElement {
        var $_name = 'k2fsearchmodule';
        
        function fetchElement($name, $value, &$node, $control_name) {
                jimport('joomla.plugins.helper');
                JPluginHelper::importPlugin('k2', 'k2fields');
                $module = JprovenUtility::getModule('mod_k2fields', false, 'site');
		$module->module = preg_replace('/[^A-Z0-9_\.-]/i', '', $module->module);
		$path = JPATH_SITE.'/modules/'.$module->module.'/'.$module->module.'.php';

		if (!$module->user && file_exists($path) && empty($module->content)) {
			$lang = JFactory::getLanguage();
			$lang->load($module->module);
                        $params = &$module->params;
                        $params->set('categoryselector', 1);
                        $params->set('showftsearch', 0);
                        $params->set('defaultmode', 'active');
                        $params->set('whentogglerempty', 'active');
                        $tab = 'menu';
			ob_start();
			require $path;
			$module->content = ob_get_contents();
			ob_end_clean();
		}
                
                JFactory::getDocument()->addStyleDeclaration('#ascontainer {clear:both;}');
                
                return $module->content;
        }
}
