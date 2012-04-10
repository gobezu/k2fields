<?php
//$Copyright$

/** ORIGINAL copyright adapted from corresponding Joomla! plugin
* @package   Widgetkit
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemWidgetkit_K2 extends JPlugin {
	public $widgetkit;

	public function onAfterInitialise() {
		jimport('joomla.filesystem.file');
		if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/classes/widgetkit.php')
				|| !JComponentHelper::getComponent('com_widgetkit', true)->enabled) {
			return;
		}
                
		require_once JPATH_ADMINISTRATOR.'/components/com_widgetkit/classes/widgetkit.php';

		$this->widgetkit = Widgetkit::getInstance();

		$path = JPATH_ROOT.'/plugins/system/widgetkit_k2/';
		$this->widgetkit['path']->register($path, 'widgetkit_k2.root');
		$this->widgetkit['path']->register($path.'widgets', 'widgetkit_k2.widgets');
		$this->widgetkit['path']->register($path.'assets', 'widgetkit_k2.assets');

		require_once $path.'helper.php';
                
		$this->widgetkit['event']->bind('admin', array($this, 'init'));
		$this->widgetkit['event']->bind('site', array($this, 'init'));
		$this->widgetkit['event']->bind('site', array($this, 'loadAssets'));
	}

	public function init() {
		foreach ($this->widgetkit['path']->dirs('widgetkit_k2.widgets:') as $widget) {
			if ($file = $this->widgetkit['path']->path("widgetkit_k2.widgets:{$widget}/{$widget}.php")) {
				require_once($file);
			}
		}
	}

	public function loadAssets() {
		$this->widgetkit['asset']->addFile('css', 'widgetkit_k2.assets:css/style.css');
	}
}

class K2Widget {
	public $widgetkit;
	public $type;
	public $options;
        private $params;
        
	public function __construct() {
		$this->widgetkit = Widgetkit::getInstance();
		$this->type    = strtolower(str_replace('K2', '', get_class($this)));
		$this->options = $this->widgetkit['system']->options;

		$this->widgetkit['event']->bind('dashboard', array($this, 'dashboard'));
		$this->widgetkit['event']->bind("render", array($this, 'render'));
		$this->widgetkit['event']->bind("task:edit_{$this->type}_k2", array($this, 'edit'));
		$this->widgetkit['event']->bind("task:save_{$this->type}_k2", array($this, 'save'));

                $this->widgetkit['path']->register($this->widgetkit['path']->path('widgetkit_k2.widgets:'.$this->type), "k2{$this->type}");
                
                jimport('joomla.plugin');
                
                if ($plg = JPluginHelper::getPlugin('system', 'widgetkit_k2')) {
                        $params = new JRegistry($plg->params);
                        $this->params = $params;
                }
 	}

	public function dashboard() {
                $this->widgetkit['asset']->addFile('js', 'widgetkit_k2.assets:js/dashboard.js');
		$widget_ids = array();
                
		foreach ($this->widgetkit['widget']->all($this->type) as $widget) {
			if (isset($widget->k2)) {
				$widget_ids[] = $widget->id;
			}
		}

		$this->widgetkit['asset']->addString('js', 'jQuery(function($) { $(\'div.dashboard #'.$this->type.'\').K2Dashboard({edit_ids : '.json_encode($widget_ids).'}); });');
	}
        
	public function edit($id = null) {
                $xml    = simplexml_load_file($this->widgetkit['path']->path("{$this->type}:{$this->type}.xml"));
		$widget = $this->widgetkit[$this->type]->get($id ? $id : $this->widgetkit['request']->get('id', 'int'));
                
		$style = isset($widget->settings['style']) ? $widget->settings['style'] : 'default';
		$style_xml = simplexml_load_file($this->widgetkit['path']->path("{$this->type}:styles/{$style}/config.xml"));
                
                // reuse of module settings
                $module = $this->params->get('based_on', 'mod_k2_content');

                $lang = JFactory::getLanguage();
                $lang->load('com_modules');
                $lang->load($module, JPATH_SITE);
                
                $grp = 'params';
                
                $exclFlds = array(
                        'mod_k2_content'=>array('moduleclass_sfx', 'getTemplate'),
                        'mod_k2fields_content'=>array()
                );
                
                $inclFldSets = array(
                        'mod_k2_content'=>'basic',
                        'mod_k2fields_contents'=>'itemsoptions'
                );
                
                
                $exclFlds = (array) $exclFlds[$module];
                $inclFldSets = (array) $inclFldSets[$module];
                
                $frm = JForm::getInstance(
                        'k2widget', 
                        JPATH_SITE.'/modules/'.$module.'/'.$module.'.xml', 
                        array(), 
                        true, 
                        '//config'
                );
                
                $fss = $frm->getFieldsets();
                $modHTML = array(JHtml::_('sliders.start', 'module-sliders'));
                $addedModule = false;
                
                foreach ($fss as $fsName => $fs) {
                        if (!in_array($fsName, $inclFldSets)) continue;
                        
                        $label = JText::_(!empty($fs->label) ? $fs->label : 'COM_MODULES_'.$fsName.'_FIELDSET_LABEL');
                        $modHTML[] = JHtml::_('sliders.panel', $label, $fsName.'-options');
                        
                        if (isset($fs->description) && trim($fs->description)) {
                                $modHTML[] = '<p class="tip">'.htmlspecialchars(JText::_($fs->description), ENT_COMPAT, 'UTF-8').'</p>';
                        }
                        
                        $flds = $frm->getFieldset($fsName);
                        
                        $modHTML[] = '<fieldset class="panelform">';
                        $hiddenFlds = array();
                        
                        foreach ($flds as $fldName => $fld) {
                                $fldName = str_replace($grp.'_', '', $fldName);
                                
                                if (in_array($fldName, $exclFlds)) continue;
                                
                                $value = isset($widget->k2[$fldName]) ? $widget->k2[$fldName] : null;
                                $input = $frm->getInput($fldName, $grp, $value);
                                
                                if (!$fld->hidden) {
                                        $modHTML[] = $fld->label.$input;
                                } else {
                                        $hiddenFlds[] = $input;
                                }
                        }
                        
                        if (!$addedModule) {
                                $hiddenFlds[] = '<input type="hidden" name="params[module]" value="'.$module.'" />';
                                $moduleId = isset($widget->k2['module_id']) ? $widget->k2['module_id'] : '';
                                $hiddenFlds[] = '<input type="hidden" name="params[module_id]" value="'.$moduleId.'" />';
                                $addedModule = true;
                        }
                        
                        if (!empty($hiddenFlds)) $modHTML[] = implode('', $hiddenFlds);
                        
                        $modHTML[] = '</fieldset>';
                }
                
                $modHTML[] = JHtml::_('sliders.end');
                $modHTML = implode('', $modHTML);
                
		$type = $this->type;

		$this->widgetkit['path']->register($this->widgetkit['path']->path('widgetkit_k2.root:layouts'), 'layouts');
                
		echo $this->widgetkit['template']->render("edit", compact('widget', 'xml', 'style_xml', 'type', 'modHTML'));
	}

	public function render($widget) {
                if (isset($widget->k2) && $widget->type == $this->type) {
                        $widget->items = array();
                        $params = $this->widgetkit['data']->create($widget->k2);
                        $items = $this->widgetkit['widgetkitk2']->getList($params);
                        $widgetItems = self::renderItems($items, $params, $this->widgetkit);
                        $widget->items = $widgetItems;         
                }
	}
        
        static protected function renderItems($items, $params, $widgetKit) {
                $i = 0;
                $widgetItems = array();
                foreach($items as $i => $item) {
                        $widgetItems[$i]['title'] = $item->title;
                        $widgetItems[$i]['content'] = $widgetKit['widgetkitk2']->renderItem($item, $params);
                        $widgetItems[$i]['navigation'] = $item->title;
                        $widgetItems[$i]['caption'] = '';
                }
                return $widgetItems;
        }

	public function save() {
		// save data
		$data['type']     = $this->type;
		$data['id']       = $this->widgetkit['request']->get('id', 'int');
		$data['name']     = $this->widgetkit['request']->get('name', 'string');
		$data['settings'] = $this->widgetkit['request']->get('settings', 'array');
                $data['partsettings'] = $this->widgetkit['request']->get('partsettings', 'array');
		$data['style']    = $this->widgetkit['request']->get('settings.style', 'array');
		$data['k2']	  = $this->widgetkit['request']->get('params', 'array');

		// convert numeric strings to real integers
		if (isset($data["settings"]) && is_array($data["settings"])) {
			$data["settings"] = array_map(create_function('$item', 'return is_numeric($item) ? (float)$item : $item;'), $data["settings"]);
		}

		$this->edit($this->widgetkit['widget']->save($data));
	}

}
