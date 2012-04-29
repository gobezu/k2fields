<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die;

class modK2fieldsContentsHelper {
        static $templateSettingMap = array(
                /*'awkwardshowcase' => array('content_width','content_height','fit_to_parent','auto','interval','continuous','loading','tooltip_width','tooltip_icon_width','tooltip_icon_height','tooltip_offsetx','tooltip_offsety','arrows','buttons','btn_numbers','keybord_keys','mousetrace','pauseonover','stoponclick','transition','transition_delay','transition_speed','show_caption','thumbnails','thumbnails_position','thumbnails_direction','thumbnails_slidex','dynamic_height','speed_change','viewline','custom_function'),*/
                'awkwardshowcase_defaults' => array(
                        'normal' => array('content_width'=>700,'content_height'=>470,'fit_to_parent'=>0,'auto'=>1,'interval'=>3000,'continuous'=>0,'loading'=>1,'tooltip_width'=>200,'tooltip_icon_width'=>32,'tooltip_icon_height'=>32,'tooltip_offsetx'=>18,'tooltip_offsety'=>0,'arrows'=>1,'buttons'=>1,'btn_numbers'=>1,'keybord_keys'=>1,'mousetrace'=>0,'pauseonover'=>1,'stoponclick'=>0,'transition'=>'hslide','transition_delay'=>0,'transition_speed'=>500,'show_caption'=>'onload','thumbnails'=>0,'thumbnails_position'=>'outslide-last','thumbnails_direction'=>'vertical','thumbnails_slidex'=>1,'dynamic_height'=>0,'speed_change'=>1,'viewline'=>0,'custom_function'=>null),
                        'vertical_thumbnails' => array('content_width'=>700,'content_height'=>470,'fit_to_parent'=>0,'auto'=>0,'interval'=>3000,'continuous'=>0,'loading'=>1,'tooltip_width'=>200,'tooltip_icon_width'=>32,'tooltip_icon_height'=>32,'tooltip_offsetx'=>18,'tooltip_offsety'=>0,'arrows'=>1,'buttons'=>1,'btn_numbers'=>1,'keybord_keys'=>1,'mousetrace'=>0,'pauseonover'=>1,'stoponclick'=>1,'transition'=>'vslide','transition_delay'=>300,'transition_speed'=>500,'show_caption'=>'onhover','thumbnails'=>1,'thumbnails_position'=>'inside-last','thumbnails_direction'=>'vertical','thumbnails_slidex'=>0,'dynamic_height'=>0,'speed_change'=>1,'viewline'=>0,'custom_function'=>null),
                        'horizontal_thumbnails' => array('content_width'=>700,'content_height'=>470,'fit_to_parent'=>0,'auto'=>0,'interval'=>3000,'continuous'=>0,'loading'=>1,'tooltip_width'=>200,'tooltip_icon_width'=>32,'tooltip_icon_height'=>32,'tooltip_offsetx'=>18,'tooltip_offsety'=>0,'arrows'=>1,'buttons'=>1,'btn_numbers'=>1,'keybord_keys'=>1,'mousetrace'=>0,'pauseonover'=>1,'stoponclick'=>1,'transition'=>'hslide','transition_delay'=>300,'transition_speed'=>500,'show_caption'=>'onhover','thumbnails'=>1,'thumbnails_position'=>'outside-last','thumbnails_direction'=>'horizontal','thumbnails_slidex'=>0,'dynamic_height'=>0,'speed_change'=>0,'viewline'=>0,'custom_function'=>null),
                        'dynamic_height' => array('content_width'=>700,'fit_to_parent'=>1,'auto'=>3000,'interval'=>3000,'continuous'=>0,'loading'=>1,'tooltip_width'=>200,'tooltip_icon_width'=>32,'tooltip_icon_height'=>32,'tooltip_offsetx'=>18,'tooltip_offsety'=>0,'arrows'=>1,'buttons'=>1,'btn_numbers'=>0,'keybord_keys'=>1,'mousetrace'=>0,'pauseonover'=>1,'stoponclick'=>'fade','transition'=>500,'transition_delay'=>'onhover','transition_speed'=>300,'show_caption'=>1,'thumbnails'=>'inside-last','thumbnails_position'=>'outside-first','thumbnails_direction'=>'horizontal','thumbnails_slidex'=>1,'dynamic_height'=>0,'speed_change'=>0,'viewline'=>0,'custom_function'=>null),
                        'hundred_percent' => array('content_width'=>700,'content_height'=>470,'fit_to_parent'=>1,'auto'=>0,'interval'=>3000,'continuous'=>0,'loading'=>1,'tooltip_width'=>200,'tooltip_icon_width'=>32,'tooltip_icon_height'=>32,'tooltip_offsetx'=>18,'tooltip_offsety'=>0,'arrows'=>1,'buttons'=>1,'btn_numbers'=>1,'keybord_keys'=>1,'mousetrace'=>0,'pauseonover'=>1,'stoponclick'=>1,'transition'=>'hslide','transition_delay'=>300,'transition_speed'=>500,'show_caption'=>'onhover','thumbnails'=>1,'thumbnails_position'=>'outside-last','thumbnails_direction'=>'horizontal','thumbnails_slidex'=>0,'dynamic_height'=>1,'speed_change'=>0,'viewline'=>0,'custom_function'=>null),
                        'viewline' => array('content_height'=>470,'fit_to_parent'=>1,'auto'=>0,'interval'=>3000,'continuous'=>0,'loading'=>1,'tooltip_width'=>200,'tooltip_icon_width'=>32,'tooltip_icon_height'=>32,'tooltip_offsetx'=>18,'tooltip_offsety'=>0,'arrows'=>1,'buttons'=>1,'btn_numbers'=>1,'keybord_keys'=>1,'mousetrace'=>0,'pauseonover'=>1,'stoponclick'=>1,'transition'=>'hslide','transition_delay'=>300,'transition_speed'=>500,'show_caption'=>'onhover','thumbnails'=>1,'thumbnails_position'=>'outside-last','thumbnails_direction'=>'horizontal','thumbnails_slidex'=>0,'dynamic_height'=>0,'speed_change'=>0,'viewline'=>1,'custom_function'=>null)
                )
        );
        
        static $fromParamToTemplateMap = array(
            'awkwardshowcase' => array(
                'content_width'=>'item_width'
            )
        );
        
        public static function settings($params, $template = '') {
                $arr = $params->toArray();
                
                if (empty($template)) $template = $params->get('template');
                
                $settings = array();
                
                foreach ($arr as $key => $val) {
                        if (strpos($key, $template.'_') === 0) {
                                if (is_numeric($val)) {
                                        if (strpos($val, '.') !== false) {
                                                $val = (float) $val;
                                        } else {
                                                $val = (int) $val;
                                        }
                                }
                                $key = str_replace($template.'_', '', $key);
                                $settings[$key] = $val;
                        }
                }
                
                return new K2fieldsContentsSettings($settings);
//                
//                $theme = $params->get($template.'_theme', '');
//                $defaults = isset(self::$templateSettingMap[$template.'_defaults']) ? 
//                        self::$templateSettingMap[$template.'_defaults'][$theme] : array();
//                $map = isset(self::$templateSettingMap[$template]) ? self::$templateSettingMap[$template] : array_keys($defaults);
//                $fromParam = isset(self::$fromParamToTemplateMap[$template]) ? self::$fromParamToTemplateMap[$template] : array();
//                $settings = array();
//                
//                foreach ($map as $i => $paramName) {
//                        $fromName = !empty($fromParam) && isset($fromParam[$paramName]) ? $fromParam[$paramName] : $template.'_'.$paramName;
//                        
//                        $settings[$paramName] = $params->get($fromName, $defaults[$paramName]);
//                }
//                
//                return new K2fieldsContentsSettings($settings);
        }
        
        public static function theme($module, $params, $template = '') {
                if (empty($template)) $template = $params->get('template');
                $theme = $params->get($template.'_theme', 'normal');
                $theme = JModuleHelper::getLayoutPath($module->module, $template.'/theme_'.$theme);
                return $theme;
        }
        
        public static function layout($module, $params, $type) {
                $type = '_'.$type;
                $itemLayout = JModuleHelper::getLayoutPath($module->module, $type.'/'.$params->get('item_layout', 'item'));
                return $itemLayout;
        }
}

class K2fieldsContentsSettings extends JObject {
        public function toString() { 
                $arr = get_object_vars($this);
                return json_encode($arr);
        }
}
?>