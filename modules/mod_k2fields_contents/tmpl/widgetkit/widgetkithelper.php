<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2fieldsModuleWidgetkitHelper {
        public static function render($items, $module, $settings) {
                $res = self::isInstalled();
                
                if ($res !== true) return $res;
                
                $widget = self::get($module, $settings);
                $widget->k2['items'] = $items;
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                $widgetId = $widget->id;
                
                $widgetDefinition = array(
                        'id' => $widget->id,
                        'name' => $widget->name, 
                        'type' => $settings['type'], 
                        'style' => $settings['style'],
                        'settings' => $widget->settings,
                        'k2' => $widget->k2
                );
                
                $widgetId = $wh->save($widgetDefinition);
                $ui = $wh->render($widgetId);
                
                return $ui;
        }
        
        private static function get($module, $settings) {
                $name = 'k2fields_auto_for_module_'.$module->id;
                
                if (isset($settings['partby'])) 
                        $name .= '_part_'.$settings['partby'].'_'.$settings['partid'];
                
                $db = JFactory::getDbo();
                
                $db->setQuery('SELECT id, name, content FROM #__widgetkit_widget WHERE name = '.$db->quote($name));
                
                $rec = $db->loadObject();
                
                if ($rec) {
                        $rec->settings = $settings; 
                        $rec->content = json_decode($rec->content);
                        $rec->k2 = get_object_vars($rec->content->k2);
                } else {
                        $rec = new stdClass();
                        $rec->id = '';
                        $rec->name = $name;
                        $rec->content = '';
                        $rec->k2 = $settings['k2'];
                        unset($settings['k2']);
                        $rec->settings = $settings;
                }
                
                return $rec;
        }
        
        private static function isInstalled() {
                if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php')) {
                        return K2FieldsMedia::error('Widgetkit is not installed.');
                }
                
                require_once JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php';
                
                return true;
        }
}

?>
