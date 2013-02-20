<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgUserK2fields extends JPlugin {
        function plgUserK2fields(&$subject, $config) {
            parent::__construct($subject, $config);
        }

        function onAfterStoreUser($user, $isnew, $success, $msg) {
                $allowGroup = plgk2k2fields::param('userprofileallowgroup');
                $input = JFactory::getApplication()->input;
                $task = $input->get('task');
                
                if ($task == 'register_save' && in_array($allowGroup, array('register', 'always'))) {
                        $allowGroup = true;
                } else if ($task == 'save' && $allowGroup == 'always') {
                        $allowGroup = true;
                } else {
                        $allowGroup = false;
                }
                
                if ($allowGroup) {
                        $db = JFactory::getDBO();
                        $query = "SELECT id FROM #__k2_users WHERE userID=".$user['id'];
                        $db->setQuery($query);
                        $k2UserId = $db->loadResult();
                        
                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                        
                        $row = JTable::getInstance('K2User', 'Table');
                        $row->load($k2UserId);
                        
                        $params = new JRegistry($row->plugins);
                        $group = $params->get('k2fieldsuserprovidedgroup', $row->group);
                        
                        $row->group = $group;
                        $row->store();
                } 
                
                $tmpl = $input->get('tmpl', '', 'word');
                $from = $input->get('from', '', 'word');
                
                if ($tmpl == 'component' && $from == 'jpmodal') {
                        if ($task == 'register_save') {
                                $usersConfig = JComponentHelper::getParams( 'com_users' );
                                $useractivation = $usersConfig->get( 'useractivation' );
                                
                                if ($useractivation == 1) {
                                        $msg = JText::_('REG_COMPLETE_ACTIVATE');
                                } else {
                                        $msg = JText::_('REG_COMPLETE');
                                }                                
                        } else if ($task == 'save') {
                                $msg = JText::_( 'Your settings have been saved.' );
                        }
                        
                        $returnURL = $input->get('jpreturnurl', '', 'string');

                        $res = array('msg'=>$msg, 'url'=>$returnURL, 'failure'=>false);
                        
                        $app = JFactory::getApplication();
                        $app->close(JprovenUtility::jsonEncode($res));
                }
        }
        
        function onLoginUser($response, $options) {
                $input = JFactory::getApplication()->input;
                $tmpl = $input->get('tmpl', '', 'word');
                $from = $input->get('from', '', 'word');
                
                if ($tmpl == 'component' && $from == 'jpmodal') {
                        $message = $input->get('jpmodalmsg', '', 'string');
                        $returnURL = $input->get('jpreturnurl', isset($options['return']) ? $options['return'] : 'index.php?com_user', 'string');
                        
                        if ($message)
                                $message = JText::_($message);
                        
                        $res = array('msg'=>$message, 'url'=>$returnURL, 'failure'=>false);
                        $app = JFactory::getApplication();
                        
                        $app->close(JprovenUtility::jsonEncode($res));
                }
        }
}
