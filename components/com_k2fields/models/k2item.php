<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_k2/models/item.php';

class K2fieldsModelK2item extends K2ModelItem {
        function prepareItem($item, $view, $task) {
                $item = parent::prepareItem($item, $view, $task);
                
                $params = clone $item->params;
                $paramvars = get_object_vars($params->_registry['_default']['data']);
                $keys = array_keys($paramvars);
                
                // K2item parameter values are copied to corresponding K2 parameter
                for ($i = 0, $n = count($keys); $i < $n; $i++) {
                        $fromKey = $keys[$i];
                        if (preg_match('#(.+)K2item(.+)#', $fromKey, $m)) {
                                unset($keys[$i]);
                                $key = $m[1].$m[2];
                                $val = $paramvars[$fromKey];
                                $params->set($key, $val);
                        }
                }
                
                $view = JFactory::getApplication()->input->get('view', '', 'cmd');
                if ($view != 'item') {
                        for ($i = 0, $n = count($keys); $i < $n; $i++) {
                                $fromKey = $keys[$i];
                                
                                if (preg_match('#^catItem(.+)#', $fromKey, $m)) {
                                        // category parameter values are copied to corresponding item parameter
                                        $key = 'item'.$m[1];
                                        $val = $params->get($fromKey);
                                        $params->set($key, $val);
                                } else if (preg_match('#^item(.+)#', $fromKey, $m)) {
                                        // none-category item parameters are set to none
                                        $key = 'catItem'.$m[1];
                                        if (!in_array($key, $keys)) {
                                                $params->set($fromKey, '');
                                        }
                                }
                        }
                }
                
                $item->params = $params;
                $item->breakPluginLoop = $item->id;
                
                return $item;
        }
}