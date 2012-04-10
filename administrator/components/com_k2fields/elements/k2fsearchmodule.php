<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.parameter.element');

class JElementK2FSearchmodule extends JElement {
        var $_name = 'k2fsearchmodule';
        
        function fetchElement($name, $value, &$node, $control_name) {
                jimport('joomla.plugins.helper');
                JPluginHelper::importPlugin('k2', 'k2fields');
                $module = JprovenUtility::getModule('mod_k2fields', false, 'site');
                plgk2k2fields::loadResources('menu', null, $module->id);
                
                jimport('joomla.html.paramters');
                
                $module->params->set('showftsearch', 0);

                // TODO: check for template override
		$module->module = preg_replace('/[^A-Z0-9_\.-]/i', '', $module->module);
		$path = JPATH_SITE.'/modules/'.$module->module.'/'.$module->module.'.php';

		if (!$module->user && file_exists($path) && empty($module->content)) {
			$lang = JFactory::getLanguage();
			$lang->load($module->module);

			$content = '';
			ob_start();
			require $path;
			$module->content = ob_get_contents().$content;
			ob_end_clean();
		}
                
                return $module->content;
        }
}
