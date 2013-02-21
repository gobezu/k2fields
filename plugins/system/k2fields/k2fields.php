<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE.'/components/com_k2fields/helpers/utility.php';

if (JprovenUtility::checkPluginActive('k2fields', 'k2', '')) {
        $app = JFactory::getApplication();
        
        JLoader::register('K2FieldsHelperRoute', JPATH_SITE.'/components/com_k2fields/helpers/route.php');
        JLoader::register('K2FieldsHelper', JPATH_SITE.'/components/com_k2fields/helpers/helper.php');
        
        JLoader::register('K2Table', JPATH_ADMINISTRATOR.'/components/com_k2/tables/table.php');
        JLoader::register('K2Model', JPATH_ADMINISTRATOR.'/components/com_k2/models/model.php');
        JLoader::register('K2View', JPATH_ADMINISTRATOR.'/components/com_k2/views/view.php');
        
        if ($app->isSite() && JprovenUtility::plgParam('k2fields', 'k2', 'override_itemmodel') == '1') {
                $input = $app->input;
                $option = $input->get('option');
                $task = $input->get('task');

                if (empty($task)) {
                        $uri = clone JURI::getInstance();
                        $router = $app->getRouter();
                        $req = $router->parse($uri);
                        $task = isset($req['task']) ? $req['task'] : '';
                        $option = isset($req['option']) ? $req['option'] : '';
                }
                
                if (($option == 'com_k2' || $option == 'com_k2fields') && !in_array($task, array('edit', 'add', 'save'))) {
                        K2Model::addIncludePath(JPATH_SITE.'/components/com_k2fields/models/k2');
                        K2Model::getInstance('item', 'K2Model');
                }
        }
        
        K2Model::addIncludePath(JPATH_SITE.'/components/com_k2fields/models');
        K2Model::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/models');
        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/tables');
        
        JLoader::register('K2HelperRoute', JPATH_SITE.'/components/com_k2/helpers/route.php');
        JLoader::register('K2HelperPermissions', JPATH_SITE.'/components/com_k2/helpers/permissions.php');
        JLoader::register('K2HelperUtilities', JPATH_SITE.'/components/com_k2/helpers/utilities.php');
}

class plgSystemk2fields extends JPlugin {
        const SESSIONID = 'k2fields_ef_id';
        const IMPORT_FILE = '/tmp/k2fields_list_import.csv';
        
        function plgSystemk2fields(&$subject, $params) {
                parent::__construct($subject, $params);
                $this->loadLanguage('', JPATH_ADMINISTRATOR);
        }
        
        public static function param($name, $value = '', $dir = 'get') {
                return JprovenUtility::plgParam('k2fields', 'system', $name, $value, $dir);
        }         
        
        private static function processListImport() {
                jimport('joomla.filesystem.file');
                $file = JPATH_SITE.self::IMPORT_FILE;
                if (JFile::exists($file)) {
                        require_once JPATH_ADMINISTRATOR.'/components/com_k2fields/models/types/list.php';
                        $list = new K2FieldsList();
                        $list->import(self::IMPORT_FILE);
                        JFile::move($file, $file.'.imported');
                }
        }
        
        private static function checkQuota() {
                return;
                
                $app = JFactory::getApplication();
                
                if ($app->isAdmin()) return;
                
                $input = JFactory::getApplication()->input;
                
                $option = $input->get('option');
                $view = $input->get('view');
                $task = $input->get('task');
                
                if ($option != 'com_k2' || $view != 'item' || $task != 'add') return;
                
                require_once JPATH_SITE.'/components/com_k2/helpers/permissions.php';
                
                $user = JFactory::getUser();
                $k2user = K2HelperPermissions::getK2User($user->id);
                
                if (empty($k2user)) return;
                
                $quota = JprovenUtility::setting(
                        'accesssubmitquota', 
                        'k2fields', 
                        'k2', 
                        null, 
                        array(),
                        array('u'.$k2user->id, 'g'.$k2user->group), 
                        K2FieldsModelFields::VALUE_SEPARATOR
                );
                
                if (empty($quota)) return;
                
                $quota = JprovenUtility::first($quota);
                $quota = $quota[0];
                
                $colQuota = 1;
                $colHRef = 2;
                $colMessage = 3;
                
                $query = 'SELECT COUNT(*) FROM #__k2_items WHERE created_by = '.$user->id.' AND published = 1 AND trash = 0';
                $db = JFactory::getDBO();
                $db->setQuery($query);
                $cnt = $db->loadResult();
                $cntQuota = (int) self::_v($quota, $colQuota, -1);
                
                if ($cntQuota != -1 && $cntQuota < $cnt) {
                        $msg = self::_v($quota, $colMessage, 'PLG_K2FIELDS_QUOTA_EXCEEDED');
                        $userPage = K2FieldsHelperRoute::getUserRoute($user->id);
                        $msg = JText::sprintf($msg, $cntQuota, $userPage, $cnt);
                        
                        $href = self::_v($quota, $colHRef, K2FieldsModelFields::setting('accessdefaulthref'));
                        
                        if (strpos($href, 'user') === 0) {
                                $href = $userPage;
                        }
                        
                        self::quotaExceeded($href, $msg);
                }
        }
        
        private static function quotaExceeded($href, $msg) {
                $tmpl = JFactory::getApplication()->input->get('tmpl', '', 'word');
                $app = JFactory::getApplication();
                
                if (is_numeric($href)) {
                        $menu = JSite::getMenu();  
                        $href = $menu->getItem($href);

                        if ($href) {
                                $href = JRoute::_($href->link.'&Itemid='.$href->id, false);
                        }
                }

                if ($tmpl == 'component') { 
                        // opened in a lightbox (send a notice and close the lightbox or refer to provided url)
                        $js = '
                                <script type="text/javascript">
                                        alert("'.$msg.'");
                                ';

                        if (!empty($href)) {
                                $js .= 'window.parent.document.location.href = "'.$href.'";';
                        } else {
                                $js .= 'window.parent.document.getElementById(\'sbox-window\').close()';
                        }

                        $js .= '
                                </script>';

                        $app->close($js);
                }

                if (empty($href)) 
                        $href = JURI::root();

                $app->redirect($href, $msg, 'error');                
        }
        
        private static function _v($arr, $ind, $def='') {
                $res = JprovenUtility::value($arr, $ind-1, $def);
                return trim($res);
        }        
        
        /**
         * @@todo: move more of loading required modules to here
         */
        function onAfterInitialise() {
                if (!JprovenUtility::checkPluginActive('k2fields', 'k2')) return;
                
                jimport('joomla.application.helper');
                
                // Override the ugly and old calendar used by joomla!
                // JHTML::addIncludePath(JPATH_SITE.'/media/k2fields/mootools');
                
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $model->resetValues();
                $model->adjustUnpublishDates();
        }
        
        private static function saveFieldDefinition($task, $step) {
                /**
                 * First round:
                 * If new and no id assign
                 *      save the field definition temporarily
                 *      save the temporarily used id in session
                 * 
                 * Second round:
                 * If temporary id found in session
                 *      update that id to be the new actual defintion id
                 *      remove session
                 */
                $session = JFactory::getSession();
                
                $tbl = JTable::getInstance('K2ExtraFieldsDefinition', 'Table');
                $db = JFactory::getDbo();
                $model = K2Model::getInstance('fields', 'K2FieldsModel');

                if ($step == 1) {
                        $sid = $session->get(self::SESSIONID);
                        
                        if (!empty($sid)) {
                                $tbl->delete($sid);
                                $session->clear(self::SESSIONID);
                                $sid = null;
                        }
                        
                        $app = JFactory::getApplication();
                        $input = $app->input;

                        $id = $input->get('id', '', 'int');
                        $name = $input->get('name', '', 'string');
                        $definition = $input->get('definition', $name, 'string');
                        
                        $options = $model->mapFieldOptions($definition);
                        
                        if (!$options) {
//                                $search = K2FieldsModelFields::value($options, 'search', 'NOSEARCH');
//                                if ($search == 'false') $search = 'NOSEARCH';
//                                $section = K2FieldsModelFields::value($options, 'section', 'Additional info');
//                                $list = K2FieldsModelFields::value($options, 'list', 'NOLIST');
//                                $name = 'k2f'.K2FieldsModelFields::FIELD_SEPARATOR.K2FieldsModelFields::value($options, 'name').
//                                        ' in '.$section.
//                                        K2FieldsModelFields::FIELD_SEPARATOR.K2FieldsModelFields::value($options, 'valid').
//                                        K2FieldsModelFields::FIELD_SEPARATOR.$search.
//                                        K2FieldsModelFields::FIELD_SEPARATOR.$list;
//                                JRequest::setVar('name', $name);
//                        } else {
                                return;
                        }
                        
                        $isNew = empty($id);
                        $tbl->definition = $definition;
                        $defExists = false;
                        
                        if (!$isNew) {
                                $query = 'SELECT COUNT(*) AS cnt FROM #__k2_extra_fields_definition WHERE id = '.(int)$id;
                                $db->setQuery($query);
                                $defExists = $db->loadResult() == 1;
                        }
                        
                        if ($defExists) {
                                $tbl->id = $id;
                        }
                        
                        if (!$tbl->store()) {
                                $app->redirect('index.php?option=com_k2&view=extraFields', $tbl->getError(), 'error');
                                return false;
                        }

                        if ($isNew || !$defExists) {
                                $sid = $tbl->id;
                                $session->set(self::SESSIONID, $sid);
                        }
                } else if ($step == 2) {
                        $sid = $session->get(self::SESSIONID);
                        
                        if (!empty($sid)) {
                                $accId = JFactory::getApplication()->input->get('cid');
                                
                                if (empty($accId)) {
                                        $query = 'SELECT MAX(id) FROM #__k2_extra_fields';
                                        $db->setQuery($query);
                                        $accId = $db->loadResult();
                                }
                                
                                $query = 'UPDATE #__k2_extra_fields_definition SET id = '.(int)$accId.' WHERE id = '.(int)$sid;
                                $db->setQuery($query);
                                $db->query();
                                
                                $session->clear(self::SESSIONID);
                        }
                }
                
                if ($task == 'remove') {
                        $cid = JFactory::getApplication()->input->get('cid', '', 'array');
                        foreach ($cid as $i => $id) {
                                $tbl->load($id);
                                $tbl->delete($id);
                        }
                }                
        }
        
        function onAfterRoute() {
                if (!JprovenUtility::checkPluginActive('k2fields', 'k2')) return;
                
                if (!defined('K2_JVERSION')) define('K2_JVERSION', '16');
                
                // JLoader::register('K2HelperPermissions', JPATH_SITE.'/components/com_k2/helpers/permissions.j16.php');
                
                self::checkQuota();
                self::processListImport();
                
                if (JFactory::getApplication()->isSite()) return;
                
                $input = JFactory::getApplication()->input;
                
                $option = $input->get('option');
                $view = $input->get('view');
                $task = $input->get('task');
                
                if ($option != 'com_k2' || $view != 'extrafield') return;
                
                if (!JprovenUtility::checkPluginActive('k2fields', 'k2')) return;
                
                self::saveFieldDefinition($task, $task == 'save' || $task == 'apply' ? 1 : -1);
        }
        
        function onAfterRender() {
        }
        
        private static function upgradeMootools($debug = null) {
                if (!(bool) self::param('upgradeMootools')) return;
                
                static $loaded = false;
                
		if ($loaded) return;
                
                // make sure to load currently available mootools
                JHtml::_('behavior.framework');
                
                $adds = array(
                        JURI::root().'media/k2fields/mootools/mootools-core-1.4.5', 
                        JURI::root().'media/k2fields/mootools/mootools-more-1.4.0.1'
                );
                
                $removes = array(
                        JURI::root(true).'/media/system/js/mootools-core',
                        JURI::root(true).'/media/system/js/mootools-more'
                );
                
                if ($debug === null) {
			$debug = JFactory::getConfig()->get('config.debug');
		}
                
                foreach ($adds as &$add)
                        $add .= ($debug ? '-uncompressed' : '') . '.js';
                
                foreach ($removes as &$remove)
                        $remove .= ($debug ? '-uncompressed' : '') . '.js';
                
                JprovenUtility::replaceResourcesInDocument('js', $removes, $adds, 'document', null, '', false);
                
                $loaded = true;
        }
        
        /**
         * Adds (loads) K2 stylesheet file (k2.css) for current theme by looking for it
         * in each possible template override folder
         */
        private function addResources() {
                if (plgk2k2fields::param('specificCSS', 'no') == 'yes') JprovenUtility::loadK2SpecificResources();
        }
        
        /**
         * 1. provides ability to remove username requirement by generating random
         *    username and thereby practically filling it for the user and hiding the
         *    username field
         * 
         * 2. provides ability to remove Native K2 fields user fields
         * 
         * Note: Make sure to set the order of this plugin after K2s system plugin
         */
        private function extendUserForm() {
                return;
		$mainframe = &JFactory::getApplication();

		if($mainframe->isAdmin()) return;
                
                $this->loadLanguage('com_k2');
                $input = JFactory::getApplication()->input;
                
		$params = &JComponentHelper::getParams('com_k2');
		$option = $input->get('option');
                
		if(!$params->get('K2UserProfile') || $option != 'com_user') return;
                
		$view = $input->get('view');
		$task = $input->get('task');
		$layout = $input->get('layout');
		$user = &JFactory::getUser();
                
                if (!JprovenUtility::checkPluginActive('k2fields', 'k2', 'PLG_K2FIELDS_PLUGIN_INACTIVE')) return;
                
                $removeK2Fields = plgk2k2fields::param('userprofileremovek2fields');
                $removeUsername = plgk2k2fields::param('userprofileremoveusername');
                
		if ($view == 'register') {
                        $removeK2Fields = in_array($removeK2Fields, array('registration', 'always'));
                        
                        if ($removeUsername == 'random') {
                                jimport('joomla.user.helper');
                                $add = JUserHelper::genRandomPassword(6);                                
                                $user->username = 'user'.$add;
                        }
                        
			if(!$user->guest){
				$mainframe->redirect(JURI::root(),JText::_('You are already registered as a member.'),'notice');
				$mainframe->close();
			}
			require_once (JPATH_SITE.DS.'components'.DS.'com_user'.DS.'controller.php');
			$controller = new UserController;
			$view = $controller->getView('register', 'html');
			$view->_addPath('template', JPATH_SITE.DS.'components'.DS.'com_k2fields'.DS.'templates');
			$view->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2fields'.DS.'templates');
			$view->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2fields');
			$view->setLayout('register');

			$K2User = new JObject;

			$K2User->description = '';
			$K2User->gender = 'm';
			$K2User->image = '';
			$K2User->url = '';
			$K2User->plugins = '';

			$wysiwyg = &JFactory::getEditor();
			$editor = $wysiwyg->display('description', $K2User->description, '100%', '250', '40', '5', false);
			$view->assignRef('editor', $editor);

			$lists = array();
			$genderOptions[] = JHTML::_('select.option', 'm', JText::_('Male'));
			$genderOptions[] = JHTML::_('select.option', 'f', JText::_('Female'));
			$lists['gender'] = JHTML::_('select.radiolist', $genderOptions, 'gender', '', 'value', 'text', $K2User->gender);

			$view->assignRef('lists', $lists);

			JPluginHelper::importPlugin('k2');
			$dispatcher = &JDispatcher::getInstance();
			$K2Plugins = $dispatcher->trigger('onRenderAdminForm', array(&$K2User, 'user'));
			$view->assignRef('K2Plugins', $K2Plugins);

			$view->assignRef('K2User', $K2User);
                        $view->assignRef('removeK2Fields', $removeK2Fields);
                        $view->assignRef('removeUsername', $removeUsername);

			$pathway = &$mainframe->getPathway();
			$pathway->setPathway(NULL);

			ob_start();
			$view->display();
			$contents = ob_get_clean();
			$document = &JFactory::getDocument();
			$document->setBuffer($contents, 'component');

		}

		if ($view == 'user' && ($task == 'edit' || $layout=='form')) {
                        $removeK2Fields = $removeK2Fields == 'always';
                        
			require_once (JPATH_SITE.DS.'components'.DS.'com_user'.DS.'controller.php');
			$controller = new UserController;
			$view = $controller->getView('user', 'html');
			$view->_addPath('template', JPATH_SITE.DS.'components'.DS.'com_k2fields'.DS.'templates');
			$view->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2fields'.DS.'templates');
			$view->_addPath('template', JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_k2fields');
			$view->setLayout('profile');

			require_once (JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'models'.DS.'itemlist.php');
			$model = new K2ModelItemlist;
			$K2User = $model->getUserProfile($user->id);
			if (!is_object($K2User)) {
				$K2User = new Jobject;
				$K2User->description = '';
				$K2User->gender = 'm';
				$K2User->url = '';
				$K2User->image = NULL;
			}
			$wysiwyg = &JFactory::getEditor();
			$editor = $wysiwyg->display('description', $K2User->description, '100%', '250', '40', '5', false);
			$view->assignRef('editor', $editor);

			$lists = array();
			$genderOptions[] = JHTML::_('select.option', 'm', JText::_('Male'));
			$genderOptions[] = JHTML::_('select.option', 'f', JText::_('Female'));
			$lists['gender'] = JHTML::_('select.radiolist', $genderOptions, 'gender', '', 'value', 'text', $K2User->gender);

			$view->assignRef('lists', $lists);

			JPluginHelper::importPlugin('k2');
			$dispatcher = &JDispatcher::getInstance();
			$K2Plugins = $dispatcher->trigger('onRenderAdminForm', array(&$K2User, 'user'));
			$view->assignRef('K2Plugins', $K2Plugins);

			$view->assignRef('K2User', $K2User);
                        $view->assignRef('removeK2Fields', $removeK2Fields);
                        $view->assignRef('removeUsername', $removeUsername);
                        
			ob_start();
			$view->_displayForm();
			$contents = ob_get_clean();
			$document = &JFactory::getDocument();
			$document->setBuffer($contents, 'component');
		}
	}     
//        
//        protected static function adjustAdminForms() {
//                $app = JFactory::getApplication();
//                
//                if ($app->isSite()) return;
//                
//                JprovenUtility::setLayout();
//        }
        
        function onAfterDispatch() {
//                JprovenUtility::reverseFromValues(95, 12);
                
                if (!JprovenUtility::checkPluginActive('k2fields', 'k2', '')) return;
                
                self::upgradeMootools();
                
                $input = JFactory::getApplication()->input;
                
                $option = $input->get('option');
                $view = $input->get('view');
                
                $this->extendUserForm();
                $this->addResources();
                
                $app = JFactory::getApplication();
                
                if ($app->isSite()) {
                        jimport('joomla.application.component.model');
                        K2Model::getInstance('searchterms', 'K2FieldsModel');
                        K2FieldsModelSearchterms::addPathWay();
                }
                
                if ($app->isAdmin() && $option == 'com_k2' && $view == 'items') {
                        jimport('joomla.application.component.model');
                        K2Model::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/models/');
                        $model = K2Model::getInstance('fields', 'K2FieldsModel');
                        $model->maintainExtended();
                }
                
                $not = 
                        !$app->isAdmin() || 
                        $option != 'com_k2' || 
                        $view != 'extrafield';
                
                if ($not) return;
                
                self::saveFieldDefinition('', 2);
                
                JHTML::_('behavior.mootools');
                
                static $editorDone = false;

                if ($editorDone !== true) {
                        plgk2k2fields::loadResources('editfields');

                        JprovenUtility::load('k2fields_editor.js', 'js');
                        
                        $id = $input->get('cid', '', 'int');
                        $options = array();
                        
                        if ($id) {
                                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/tables/');
                                $tbl = JTable::getInstance('K2ExtraFieldsDefinition', 'Table');
                        
                                $tbl->load($input->get('cid', '', 'int'));
                                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                                $options = $model->mapFieldOptions($tbl);
                        }
                        
                        $document = JFactory::getDocument();
                        
                        Jloader::register('K2FieldsControllerEditor', JPATH_ADMINISTRATOR.'/components/com_k2fields/controllers/editor.php');
                        $ctrl = new K2FieldsControllerEditor();
                        
                        $document->addScriptDeclaration('
                                var k2fseditor = new k2fieldseditor({
                                        def: '.($id ? json_encode($tbl->definition) : 'null').',
                                        emptysectionname:"'.K2FieldsModelFields::setting('emptysectionname').'",
                                        fieldSeparator: "' . K2FieldsModelFields::FIELD_SEPARATOR . '",
                                        options: '.json_encode($ctrl->retrieve(false, $options)).'
                                });
                        ');
                        
                        $editorDone = true;
                }
        }
        
        function onLoginFailure($response) {
                if (!JprovenUtility::checkPluginActive('k2fields', 'k2')) return;

                $input = JFactory::getApplication()->input;
                
                $tmpl = $input->get('tmpl', '', 'word');
                $from = $input->get('from', '', 'word');
                
                if ($tmpl == 'component' && $from == 'jpmodal') {
                        $message = $response['error_message'];
                        $return = $input->get('jpreturn', '', 'word');
                        $returnURL = $return != 'current' ? JFactory::getApplication()->input->get('jpreturnurl', '', 'string') : '';
                        
                        if ($message)
                                $message = JText::_($message);
                        
                        $res = array('msg'=>$message, 'url'=>$returnURL, 'failure'=>true);
                        $app = JFactory::getApplication();
                        
                        $app->close(json_encode($res));
                }
        }        
}
?>
