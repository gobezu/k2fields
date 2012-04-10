<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class K2FieldsViewItemlist extends JView {
        function display($tpl = null) {
                $model = &$this->getModel('itemlist');
                $items = $model->getData();
                $total = $model->getTotal();
                
                //Prepare items (adapted from K2ViewItemlist)
                $user = &JFactory::getUser();
                $cache = &JFactory::getCache('com_k2_extended');
                $itemModel = $this->getModel('item');
                $n = sizeof($items);
                $view = JRequest::getWord('view');
                $task = JRequest::getWord('task');
        
                for ($i = 0; $i < $n; $i++) {
                        if ($user->guest){
                                $hits = $items[$i]->hits;
                                $items[$i]->hits = 0;
                                $items[$i] = $cache->call(array('K2ModelItem', 'prepareItem'), $items[$i], $view, $task);
                                $items[$i]->hits = $hits;
                        } else {
                                $items[$i] = $itemModel->prepareItem($items[$i], $view, $task);
                        }
                }
                
                $this->assignRef('items', $items);
                $this->assignRef('total', $total);
                $this->setLayout('json');
                
                $app = JFactory::getApplication();

                $this->_addPath('template', JPATH_COMPONENT.'/templates');
                $this->_addPath('template', JPATH_COMPONENT.'/templates/default');

                // Look for overrides in template folder (k2fields template structure, same structure as k2)
                $this->_addPath('template', JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/templates');
                $this->_addPath('template', JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/templates/default');

                // Look for overrides in template folder (k2fields template structure, same structure as k2)
                $this->_addPath('template', JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/default');
                $this->_addPath('template', JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields');
                
                parent::display($tpl);
        }
}
?>
