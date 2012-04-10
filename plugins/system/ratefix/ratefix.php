<?php
// $copyright$

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgSystemRatefix extends JPlugin {
        function onAfterRoute() {
                self::fixJCommentsEvents();
        }
        
        private static function fixJCommentsEvents() {
                $app = JFactory::getApplication();
                
                if (!$app->isAdmin()) return;
                
                $input = $app->input;
                $opt = $input->get('option');
                
                if ($opt != 'com_jcomments') return;
                
                $task = $input->get('task');
                
                if (!JPluginHelper::importPlugin('jcomments', 'rate')) return;
                
                if ($task == 'comments.apply' || $task == 'comments.save') {
                        $id = $input->get('id', '', 'int');
                        
                        if ($id) {
                                $session = JFactory::getSession();
                                $session->set('JCOMMENTS.SAVED', $id);
                        }
                } else if ($task == 'comments' || $task == 'comments.edit') {
                        $session = JFactory::getSession();
                        $id = $session->get('JCOMMENTS.SAVED');
                        
                        if ($id) {
                                $db = JFactory::getDBO();
                                $comment = $db->setQuery('SELECT * FROM '.$db->quoteName(JcommentsRate::COMMENT_TBL).' WHERE id = '.$id)->loadObject();
                                $dispatcher = JDispatcher::getInstance();
                                $dispatcher->trigger('onJCommentsCommentSave', array(&$comment));
                                $session->clear('JCOMMENTS.SAVED');
                        }
                }
                
                if ($task == 'comments.remove') {
                        $ids = $input->get('cid', array(), 'array');
                        
                        if ($ids) {
                                $session = JFactory::getSession();
                                foreach ($ids as &$id) $id = (int) $id;
                                $ids = array_filter($ids);
                                $ids = implode(',', $ids);
                                $db = JFactory::getDBO();
                                $comments = $db->setQuery('SELECT id, '.$db->quoteName(JcommentsRate::CONTENTID_COL).', '.$db->quoteName(JcommentsRate::EXTENSIONNAME_COL).' FROM '.JcommentsRate::COMMENT_TBL.' WHERE id IN ('.$ids.')')->loadObjectList();
                                $comments = serialize($comments);
                                $session->set('JCOMMENTS.DELETE', $comments);
                        }
                } else if ($task == 'comments') {
                        $session = JFactory::getSession();
                        $comments = $session->get('JCOMMENTS.DELETE');
                        
                        if ($comments) {
                                $dispatcher = JDispatcher::getInstance();
                                $comments = unserialize($comments);
                                
                                foreach ($comments as $comment)
                                        $dispatcher->trigger('onJCommentsCommentAfterDelete', array(&$comment));
                                
                                $session->clear('JCOMMENTS.DELETE');
                        }
                }
        }
}
