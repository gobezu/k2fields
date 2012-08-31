<?php
//$Copyright$
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class K2FieldsControllerEditor extends JController {
	function retrieve($send = true, $field = array()) {
                $pkg = array();
                $pkg['lists'] = $this->lists();
                $pkg['listslevels'] = $this->listslevels();
                $pkg['menuitems'] = $this->menuitems();
                $pkg['categories'] = $this->categories();
                $pkg['mediaplugins'] = $this->mediaplugins();
                $pkg['fields'] = $this->fields();
                $pkg['aclviewgroups'] = $this->aclviewgroups();
                $pkg['widgetkit_k2'] = $this->widgetkitparams($field);
                // aclviewgroups
                if (!$send) return $pkg;
                echo json_encode($pkg);
                JFactory::getApplication()->close();
	}
        
        function listslevels($send = false) {
                $query = "
select v.`list`, concat(vn.`list`, ': ', substring(vn.val, 1, 25), if(length(vn.val) > 25, '...', '')) as listname, concat(v.`list`, ':', v.depth + 1) as value, concat(v.depth + 1, ':', v.path) as text
from #__k2_extra_fields_list_values as v, 
(
        select `list`, depth, min(lft) as lft
        from #__k2_extra_fields_list_values as inv
        group by `list`, depth
) as vv,
(
        select `list`, group_concat(val separator ', ') as val
        from #__k2_extra_fields_list_values
        where depth = 0
        group by `list`
) as vn
where v.`list` = vv.`list` and vn.`list` = v.`list` and v.depth = vv.depth and v.lft = vv.lft
";
                $db = JFactory::getDbo();
                $db->setQuery($query);
                $res = $db->loadObjectList();
                
                if (empty($res)) {
                        $res = array(array('value' => 0, 'text' => JText::_('No list defined')));
                } else {
                        $res = JprovenUtility::makeOptions($res, 'list', 'listname', 'label', 'List: ');
                }
                
                if (!$send) return $res;
                
                echo json_encode($res);
                JFactory::getApplication()->close();
        }
        
        function lists($send = false) {
                $query = "
select `list` as value, concat(`list`, ': ', substring(val, 1, 25), if(length(val) > 25, '...', '')) as text 
from (
        select `list`, group_concat(val separator ',') as val
        from #__k2_extra_fields_list_values
        where depth = 0
        group by `list`
) as s";
                $db = JFactory::getDbo();
                $db->setQuery($query);
                $res = $db->loadObjectList();
                
                if (empty($res)) $res = array(array('value' => 0, 'text' => JText::_('No list defined')));
                
                if (!$send) return $res;
                
                echo json_encode($res);
                JFactory::getApplication()->close();
        }
        
        // TODO: check availability
        function mediaplugins($send = false) {
                $list = array(
                    'pic' => array(
                        "widgetkit_k2" => "Widgetkit",
                        "jw_sigpro" => "Simple Image Gallery Pro (by JoomlaWorks)",
                        "jw_simpleImageGallery" => "Simple Image Gallery (by JoomlaWorks)",
                        "sige" => "SIGE",
                        "sigplus" => "Image gallery - sigplus",
                        "verysimpleimagegallery" => "Very Simple Image Gallery",
                        "cssgallery" => "CSS Gallery",
                        "cdwebgallery" => "Core Design Web Gallery plugin",
                        "ppgallery" => "pPgallery",
                        "jcemediabox" => "JCE Mediabox - thumb",
                        "img" => "Image (HTML-tag)",
                        "source" => "Source for consumption elsewhere"                        
                    ), 
                    'provider' => array(
                        "jw_allvideos" => "AllVideos (by JoomlaWorks)"
                    )
                );
                
                $res = array('pic' => array(), 'provider' => array());
                
                $db = JFactory::getDBO();
                $_plgs = array();
                
                foreach ($res as $mt => &$plgs) {
                        $xplgs = $list[$mt];
                        
                        foreach ($xplgs as $i => $xplg) {
                                $plgs[] = array('value'=>$i, 'text'=>$xplg);
                                $_plgs[] = $db->Quote($i);
                        }
                }
                
                $query = 'SELECT element, folder, enabled FROM #__extensions WHERE element IN (' . implode(',', $_plgs) . ') AND folder IN ("content", "system")';
                $db->setQuery($query);
                $folders = $db->loadObjectList('element');
                $natives = array('img', 'source');
                
                foreach ($res as $mt => &$plgs) {
                        foreach ($plgs as &$plg) {
                                if (in_array($plg['value'], $natives)) continue;
                                
                                if (!isset($folders[$plg['value']])) {
                                        $plg['text'] = '('.JText::_('NOT INSTALLED').') '.$plg['text'];
                                } else {
                                        if (!$folders[$plg['value']]->enabled)
                                                $plg['text'] = '('.JText::_('NOT ENABLED').') '.$plg['text'];
                                }
                        }
                }
                
                if (!$send) return $res;
                
                echo json_encode($res);
                
                JFactory::getApplication()->close();
        }
        
        function menuitems($send = false) {
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);
//                $query->select('menutype AS value, title AS text');
//                $query->from($db->quoteName('#__menu_types'));
//                $query->order('title');
//                $db->setQuery($query);
//                $menus = $db->loadObjectList();

                $query->clear();
                $query->select('a.id AS value, a.title AS text, a.level, a.menutype');
                $query->from('#__menu AS a');
                $query->where('a.parent_id > 0');
                $query->where('a.type <> ' . $db->quote('url'));
                $query->where('a.client_id = 0');

                // Filter on the published state
                if (isset($config['published']))
                {
                        if (is_numeric($config['published']))
                        {
                                $query->where('a.published = ' . (int) $config['published']);
                        }
                        elseif ($config['published'] === '')
                        {
                                $query->where('a.published IN (0,1)');
                        }
                }

                $query->order('a.lft');

                $db->setQuery($query);
                $res = $db->loadObjectList();
                
                foreach ($res as &$item) {
                        $item->text = str_repeat('- ', $item->level) . $item->text;
                }
                
                if (empty($res)) {
                        $res = array(array('value' => 0, 'text' => JText::_('No menu items defined')));
                } else {
                        $res = JprovenUtility::makeOptions($res, 'menutype', 'menutype', 'label');
                }
                
                if (!$send) return $res;
                
                echo json_encode($res);
                
                JFactory::getApplication()->close();
        }
        
        function categories($send = false) {
                $res = JprovenUtility::getK2CategoriesSelector(
                        1, 
                        0, 
                        array(), 
                        false,
                        '',
                        true,
                        false,
                        true,
                        true
                ); 
                
                if (empty($res)) $res = array(array('value' => 0, 'text' => JText::_('No categories defined')));
                
                if (!$send) return $res;

                echo json_encode($res);
                JFactory::getApplication()->close(); 
        }
        
        function fields($send = false) {
                $id = JRequest::getInt('cid');
                $id = $id ? ' and e.id <> '.$id : '';
                $query = "
select eg.id as groupid, eg.name as groupname, e.id as value, concat('(', e.id, ') ', trim(substr(definition, length(definition) - instr(reverse(definition), '---') + 2))) as `text`
from #__k2_extra_fields e, #__k2_extra_fields_groups eg, #__k2_extra_fields_definition d
where e.id = d.id and e.`group` = eg.id {$id} 
order by groupid, `text`
";
                $db = JFactory::getDbo();
                $db->setQuery($query);
                $res = $db->loadObjectList();
                
                if (empty($res)) {
                        $res = array(array('value' => 0, 'text' => JText::_('No fields defined')));
                } else {
                        $res = JprovenUtility::makeOptions($res, 'groupid', 'groupname', 'label');
                }
                
                if (!$send) return $res;
                
                echo json_encode($res);
                JFactory::getApplication()->close();
        }  
        
        function aclviewgroups($send = false) {
                $db = JFactory::getDbo();
                $query = $db->getQuery(true)->select('id as value, LOWER(title) as text')->from('#__viewlevels')->order('ordering');
                $db->setQuery((string) $query);
                $res = $db->loadObjectList();
                if (!$send) return $res;
                echo json_encode($res);
                JFactory::getApplication()->close();
        }
        
        function widgetkitparams($field) {
                if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php')) {
			return array();
		}
                
                if (!isset($field['picplg']) || $field['picplg'] != 'widgetkit_k2') return array();
                
                $type = 'gallery';
                
                if (isset($field['widgetkit_k2_type'])) $type = $field['widgetkit_type'];
                
                $path = JPATH_SITE.'/media/widgetkit/widgets/'.$type.'/'.$type.'.xml';
                $type_xml = simplexml_load_file($path);
                $type_settings = $type_xml->xpath('settings/setting');
                $settings = array();
                $ignore_settings = array();
                
                foreach ($type_settings as $setting) {
                        $name = (string) $setting->attributes()->name;
                        
                        if (in_array($name, $ignore_settings)) continue;
                        
                        $settings[] = $setting;
                }
                
                $style = 'default';
                if (isset($field['widgetkit_k2_style'])) $style = $field['widgetkit_k2_style'];
                
                $path = JPATH_SITE.'/media/widgetkit/widgets/'.$type.'/styles/'.$style.'/config.xml';
                
                if (JFile::exists($path)) {
                        $style_xml = JFile::exists($path) ? simplexml_load_file($path) : false;
                        $style_settings = $style_xml->xpath('settings/setting');
                        $settings = array_merge($settings, $style_settings);
                }
                
                $widgetkit = Widgetkit::getInstance();
                
                foreach ($settings as $i => $setting) {
                        // type = style read list of folders
                        // type = text
                        // else assume options are present
                        $settings[$i] = array(
                            'type' => (string) $setting->attributes()->type,
                            'label' => (string) $setting->attributes()->label,
                            'name' => (string) $setting->attributes()->name,
                            'default' => (string) $setting->attributes()->default
                        );
                        $settings[$i]['rendered'] = $widgetkit['field']->render(
                                $settings[$i]['type'], 'k2fields_widgetkit_' . $settings[$i]['name'], '', $setting
                        );
                }
                
                return $settings;
        }
}

?>
