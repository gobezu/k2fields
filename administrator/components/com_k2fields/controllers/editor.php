<?php
//$Copyright$
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class K2FieldsControllerEditor extends JController {
	function retrieve($send = true) {
                $pkg = array();
                $pkg['lists'] = $this->lists();
                $pkg['listslevels'] = $this->listslevels();
                $pkg['menuitems'] = $this->menuitems();
                $pkg['categories'] = $this->categories();
                $pkg['mediaplugins'] = $this->mediaplugins();
                $pkg['fields'] = $this->fields();
                $pkg['timeformats'] = $this->timeformats();
                $pkg['aclviewgroups'] = $this->aclviewgroups();
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
                $xml = simplexml_load_file(JPATH_PLUGINS.'/k2/k2fields/k2fields.xml');
                $res = array('pic' => array(), 'provider' => array());
                
                $db = JFactory::getDBO();
                $_plgs = array();
                $_plgs_ind = array('pic' => array(), 'provider' => array());
                
                foreach ($res as $mt => &$plgs) {
                        $xplgs = $xml->xpath('config/fields/fieldset/field[@name="'.$mt.'plg"]/option');
                        
                        foreach ($xplgs as $i => $xplg) {
                                $nm = (string) $xplg->attributes()->value;
                                $plgs[] = array('value'=>$nm, 'text'=>(string) $xplg);
                                $_plgs[] = $db->Quote($nm);
                                $_plgs_ind[$mt][$nm] = $i;
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
        
        function timeformats($send = false) {
                $xml = simplexml_load_file(JPATH_PLUGINS.'/k2/k2fields/k2fields.xml');
                $res = array('time' => array(), 'date' => array(), 'datetime' => array());
                
                foreach ($res as $mt => &$fmts) {
                        $xfmts = $xml->xpath('config/fields/fieldset/field[@name="'.$mt.'Format"]/option');
                        
                        foreach ($xfmts as $fmt) {
                                $fmts[] = array('value'=>(string) $fmt->attributes()->value, 'text'=>(string) $fmt);
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
}

?>
