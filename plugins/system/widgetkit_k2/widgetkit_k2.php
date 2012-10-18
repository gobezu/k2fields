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
		$this->widgetkit['event']->bind('widgetoutput', array($this, '_applycontentplugins'));

	}

	public function init() {
		foreach ($this->widgetkit['path']->dirs('widgetkit_k2.widgets:') as $widget) {
			if ($file = $this->widgetkit['path']->path("widgetkit_k2.widgets:{$widget}/{$widget}.php")) {
				require_once $file;
			}
		}
	}

	public function loadAssets() {
		$this->widgetkit['asset']->addFile('css', 'widgetkit_k2.assets:css/style.css');
	}

	public function _applycontentplugins(&$text) {

		// import joomla content plugins
		JPluginHelper::importPlugin('content');

		$registry      = new JRegistry('');
		$dispatcher    = JDispatcher::getInstance();
		$article       = JTable::getInstance('content');
		$article->text = $text;

		$dispatcher->trigger('onPrepareContent', array(&$article, &$registry, 0));
		$dispatcher->trigger('onContentPrepare', array('com_widgetkit', &$article, &$registry, 0));

		$text = $article->text;
        }
        
        function onAfterRender() {
                if (!$this->widgetkit->removeK2jQueryUI) return;
                
                $xml = JFactory::getXML(JPATH_ADMINISTRATOR.'/components/com_k2/k2.xml');
                
                if (version_compare((string) $xml->version, '2.6.0', 'lt')) return;
                
                $body = JResponse::getBody();
                $body = preg_replace('#<script src="//ajax.googleapis.com/ajax/libs/jqueryui/[^\/]+/jquery-ui.min.js" type="text/javascript"></script>#', '', $body);
                
                if (preg_match("#(<script[^>]*media/k2/assets/js/k2\.noconflict\.js[^>]*)(\/>|><\/script>)#i", $body, $m)) {
                        $body = preg_replace("#(<script[^>]*media/k2/assets/js/k2\.noconflict\.js[^>]*)(\/>|><\/script>)#i", '', $body);
                        $body = preg_replace("#(<script[^>]*media/widgetkit/js/jquery\.js[^>]*)(\/>|><\/script>)#i", '$1$2'.$m[0], $body);
                }
                
                JResponse::setBody($body);
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
                $type = $this->type;
		$widget = $this->widgetkit[$this->type]->get($id ? $id : $this->widgetkit['request']->get('id', 'int'));
                $this->widgetkit['path']->register($this->widgetkit['path']->path('widgetkit_k2.root:layouts'), 'layouts');
                
                if (isset($widget->k2['synchronize']) && $widget->k2['synchronize']) {
                        $msg = JText::sprintf('Please refer to widgetkit section of the k2fields content module setting. Click <a href="index.php?option=com_modules&task=module.edit&id=%d">here</a> to go to module.', $widget->k2['module_id']);
                        
                        if (!empty($_SERVER['HTTP_REFERER'])) {
                                JFactory::getApplication()->redirect($_SERVER['HTTP_REFERER'], $msg, 'notice');
                                return;
                        }
                        
                        $doc = JFactory::getDocument();
                        $doc->addScriptDeclaration('alert("'.$msg.'"); history.go(-1);');
                        JFactory::getApplication()->close();
                }
                
                $style = isset($widget->settings['style']) ? $widget->settings['style'] : '';
                
                if (empty($style)) $style = 'default';
                
		$style_xml = simplexml_load_file($this->widgetkit['path']->path("{$this->type}:styles/{$style}/config.xml"));
                
                // reuse of module settings
                $module = $this->params->get('based_on', 'mod_k2_content');

                $lang = JFactory::getLanguage();
                $lang->load('com_modules');
                $lang->load($module, JPATH_SITE);

                $grp = 'params';

                $exclFlds = array(
                        'mod_k2_content'=>array('moduleclass_sfx', 'getTemplate'),
                        'mod_k2fields_contents'=>array()
                );

                $inclFldSets = array(
                        'mod_k2_content'=>'basic',
                        'mod_k2fields_contents'=>array('basic', 'itemdisplay')
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

                $k2xml = JFactory::getXML(JPATH_ADMINISTRATOR.'/components/com_k2/k2.xml');
                $wkxml = JFactory::getXML(JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.xml');
                
                if (version_compare((string) $k2xml->version, '2.6.0', 'ge') && version_compare((string) $wkxml->version, '1.3.0', 'ge')) {
                        $params = &K2HelperUtilities::getParams('com_k2');
                        $params->set('backendJQueryHandling', false);
                        $this->widgetkit->removeK2jQueryUI = true;
                } else {
                        $this->widgetkit->removeK2jQueryUI = false;
                }
                
                // jQuery is loaded by either Joomla! (v3+) or K2
                // $this->widgetkit['system']->application->set('jquery', true);
                
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
                
		echo $this->widgetkit['template']->render("edit", compact('widget', 'xml', 'style_xml', 'type', 'modHTML'));
	}

	public function render($widget) {
                if (isset($widget->k2) && $widget->type == $this->type) {
                        $widget->items = array();
                        
                        if (isset($widget->k2['synchronize']) && $widget->k2['synchronize']) {
                                $module = $widget->k2['module_id'];
                                $module = JprovenUtility::getModule($module);
                                $params = $module->params;
                                $params->set('partby', '');
                                $params->set('items', $widget->k2['items']);
                                $params->set('module_id', $widget->k2['module_id']);
                        } else {
                                $params = $this->widgetkit['data']->create($widget->k2);
                        }
                        
                        // in case of k2fields content module item is already prepared and supplied => we should reuse it?
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
