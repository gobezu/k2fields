<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JFormFieldK2FSearchmodule extends JFormField {
        function getInput(){
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
