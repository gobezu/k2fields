<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JFormFieldWidgetkitsettings extends JFormField {
	protected $type = 'widgetkitsettings';

	function getInput() {
		if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php')) {
			return 'Widgtekit not installed';
		}

                $type = $this->form->getValue('widgetkit_type', 'params');
                if (!$type) return;
                $path = JPATH_SITE.'/media/widgetkit/widgets/'.$type.'/'.$type.'.xml';
                $type_xml = simplexml_load_file($path);
                $type_settings = $type_xml->xpath('settings/setting');
                $settings = array();
                $ignore_settings = array('style', 'width', 'height');

                foreach ($type_settings as $setting) {
                        $name = (string) $setting->attributes()->name;

                        if (in_array($name, $ignore_settings)) continue;

                        $settings[] = $setting;
                }

                $style = $this->form->getValue('widgetkit_theme', 'params');
                $path = JPATH_SITE.'/media/widgetkit/widgets/'.$type.'/styles/'.$style.'/config.xml';

                if (JFile::exists($path)) {
                        $style_xml = JFile::exists($path) ? simplexml_load_file($path) : false;
                        $style_settings = $style_xml->xpath('settings/setting');
                        $settings = array_merge($settings, $style_settings);
                }

                $sep = '%%';
                $pre = 'k2fields_widgetkit_';
                $values = (string) $this->value;
                //jdbg::pe($values);
                $values = !empty($values) ? explode($sep, $values) : array_fill (0, count($settings), null);

                $html = array();
                $widgetkit = Widgetkit::getInstance();

                foreach ($settings as $i => $setting) {
                        $type = (string) $setting->attributes()->type;
                        $label = (string) $setting->attributes()->label;
                        $name = (string) $setting->attributes()->name;
                        $default = (string) $setting->attributes()->default;
                        $value = $values[$i];

                        if (!isset($value)) $value = $default;
                        else $value = str_replace($name.'==', '', $value);

                        $html[] = '<div class="wkoption"><h4>' . $label . '</h4><div class="value">'
                                . $widgetkit['field']->render($type, 'k2fields_widgetkit_' . $name, $value, $setting)
                                . '</div></div>';
                }

                $html[] = '<input type="hidden" value="'.(string) $this->value.'" name="'.$this->name.'" id="'.$this->id.'" />';
                $html[] = '<input type="hidden" value="'.$pre.'" name="" id="wk_k2fields_pre" />';
                $html[] = '<input type="hidden" value="'.$sep.'" name="" id="wk_k2fields_sep" />';
                $html[] = '<input type="hidden" value="'.$this->id.'" name="" id="wk_k2fields_id" />';

                $doc = JFactory::getDocument();
                $doc->addStyleSheet(JURI::base().'components/com_widgetkit/css/admin.css');
                $doc->addStyleSheet(JURI::base().'components/com_widgetkit/css/system.css');
                $doc->addScriptDeclaration("
                window.addEvent('domready', function(){
                        var
                                cont = document.id(document.id('wk_k2fields_id').get('value')),
                                namePre = document.id('wk_k2fields_pre').get('value'),
                                sep = document.id('wk_k2fields_sep').get('value'),
                                els = $$('[name^='+namePre+']'),
                                nm
                                ;

                        els.each(function(el) {
                                el.addEvent('change', function(){
                                        var vals = {};

                                        els.each(function(ei) {
                                                nm = ei.get('name');

                                                if (!(ei.get('tag') == 'input' && ei.get('type') == 'radio') || ei.get('checked')) {
                                                        vals[nm] = nm.replace(namePre, '')+'=='+ei.get('value');
                                                }
                                        }.bind(this));

                                        vals = new Hash(vals).getValues();
                                        vals = vals.join(sep);

                                        cont.set('value', vals);
                                }.bind(this));
                        }.bind(this));
                });
                ");

                $doc->addStyleDeclaration(".wkoption { clear:both; width:100%; }");

                return '<div id="widgetkit">'.implode('', $html).'</div>';
	}
}
