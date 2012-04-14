<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

require_once JPATH_SITE.'/components/com_k2/views/itemlist/view.html.php';

class K2FieldsViewItemlist extends K2ViewItemlist {
        function display($tpl = null) {

                // Only task == search is routed here
                
                // Since JPATH_COMPONENT is used in parent view/K2ViewItemlist, 
                // redo only those parts so that com_k2 paths are included
                
                //Look for template files in component folders
//                $this->_addPath('template', JPATH_SITE.'/components/com_k2/templates');
//                $this->_addPath('template', JPATH_SITE.'/components/com_k2/default');
                $this->addTemplatePath(JPATH_SITE.'/components/com_k2/templates');

                //Look for specific K2 theme files
                $params = JComponentHelper::getParams('com_k2');
                if ($params->get('theme')) {
                        $this->addTemplatePath(JPATH_SITE.'/components/com_k2/templates/'.$params->get('theme'));
                }
                
                $app = JFactory::getApplication();
                $tmpl = $app->getTemplate();

                // Look for overrides in template folder (k2fields template structure, same structure as k2)
                $this->addTemplatePath(JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/templates');
                $this->addTemplatePath(JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/templates/default');

                // Look for overrides in template folder (k2fields template structure, same structure as k2)
                $this->addTemplatePath(JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields/default');
                $this->addTemplatePath(JPATH_SITE.'/templates/'.$tmpl.'/html/com_k2fields');
                
                $tpl = JRequest::getWord('style', NULL);

                parent::display($tpl);
        }
}
?>
