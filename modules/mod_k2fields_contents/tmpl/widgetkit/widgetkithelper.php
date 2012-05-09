<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class K2fieldsModuleWidgetkitHelper {
        public static function render($items, $module, $k2Settings, $settings) {
                $res = self::isInstalled();
                
                if ($res !== true) return $res;
                
                $widget = self::get($module, $k2Settings, $settings, true);
                $widget->k2['items'] = $items;
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                $widgetId = $widget->id;
                
                if (!$widgetId || $settings['keepsynch']) {
                        $widgetDefinition = array(
                                'type' => $settings['type'], 
                                'id' => $widget->id,
                                'name' => $widget->name, 
                                'settings' => $widget->settings,
                                'style' => $settings['style'],
                                'k2' => $widget->k2
                        );

                        $widgetId = $wh->save($widgetDefinition);
                }
                
                $ui = $wh->render($widgetId);
                
                return $ui;
        }
        
        private static function get($module, $k2Settings, $settings, $overrideSettings = false) {
                $name = 'k2fields_auto_for_module_'.$module->id;
                
                if (isset($k2Settings['partby'])) 
                        $name .= '_part_'.$k2Settings['partby'].'_'.$k2Settings['partid'];
                
                $db = JFactory::getDbo();
                
                $db->setQuery('SELECT id, name, content FROM #__widgetkit_widget WHERE name = '.$db->quote($name));
                
                $rec = $db->loadObject();
                
                if ($rec) {
                        $rec->content = json_decode($rec->content);
                        $currentSettings = get_object_vars($rec->content->settings);
                        $rec->settings = $overrideSettings ? array_merge($currentSettings, $settings) : $currentSettings; 
                        $k2 = get_object_vars($rec->content->k2);
                        $k2['module'] = 'mod_k2fields_contents';
                        $rec->k2 = $k2;
                } else {
                        static $map = array(
                                'gallery' => array('style'=>'default','width'=>'auto','height'=>'auto','autoplay'=>1,'order'=>'default','interval'=>5000,'duration'=>500,'index'=>0,'navigation'=>1,'buttons'=>1,'slices'=>20,'animated'=>'randomSimple','caption_animation_duration'=>500,'lightbox'=>0),
                                'slideshow' => array('style'=>'default','width'=>'auto','height'=>'auto','autoplay'=>1,'order'=>'default','interval'=>5000,'duration'=>500,'index'=>0,'navigation'=>1,'buttons'=>1,'slices'=>20,'animated'=>'randomSimple','caption_animation_duration'=>500),
                                'slideset' => array('style'=>'default','width'=>'auto','height'=>'auto','autoplay'=>1,'interval'=>5000,'index'=>0,'navigation'=>1,'buttons'=>1,'title'=>1,'duration'=>300,'items_per_set'=>3,'effect'=>'slide'),
                                'accordion' => array('style'=>'default','width'=>'auto','order'=>'default','duration'=>500,'index'=>0,'collapseall'=>1,'matchheight'=>1)
                        );
                        
                        $type = $settings['type'];

                        $defaultSettings = $map[$type];
                        
                        if (!empty($settings)) $settings = (array) $settings;
                        else $settings = array();
                        
                        foreach ($defaultSettings as $key => $defaultSetting) {
                                if (!isset($settings[$key])) $settings[$key] = $defaultSetting;
                        }
                        
                        if (isset($settings['theme'])) $settings['style'] = $settings['theme'];

                        $rec = new stdClass();
                        $rec->id = '';
                        $rec->content = '';
                        $rec->settings = $settings;
                        $rec->name = $name;
                        $rec->k2 = $k2Settings;
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
