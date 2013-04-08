<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!JprovenUtility::checkPluginActive('k2fields', 'system', '', true)) {
        JError::raiseError('500', 'Unable to activate/locate k2fields system plugin which is required for proper functioning of k2fields. Please correct that and try again.');
        return;
}

JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2plugin.php');

class plgk2k2komento extends K2Plugin {
        var $pluginName = 'k2komento';
        var $pluginNameHumanReadable = 'K2/Komento rating';
        protected static $rater;
                
        function plgk2k2komento(&$subject, $params) {
                parent::__construct($subject, $params);
                $this->loadLanguage('', JPATH_ADMINISTRATOR);
                /*
                 * In components/com_komento/bootstrap.php replace 
                 * 
                 * require_once( KOMENTO_CLASSES . DIRECTORY_SEPARATOR . 'comment.php' );
                 * 
                 * with
                 * 
                 * JLoader::register('KomentoComment', KOMENTO_CLASSES . DIRECTORY_SEPARATOR . 'comment.php');
                 * 
                 * and uncomment the following row
                 * 
                 */
                JLoader::register('KomentoComment', JPATH_SITE.'/plugins/k2/k2komento/comment.php');
                JLoader::register('KomentoRate', JPATH_SITE.'/plugins/k2/k2komento/rate.class.php');
                self::$rater = new KomentoRate();
        }
        
        public function onK2CommentsBlock(&$item, &$params, $limitstart) {
                self::$rater->form();
        }
        
        public function onAfterSaveComment(&$comment) {
                if (JFactory::getApplication()->isAdmin()) return;
                self::$rater->rate($comment);
        }
        
        public function onAfterUnpublishComment(&$comment) {
                self::$rater->recalc($comment->cid, $comment->component);
        }
        
        public function onAfterPublishComment(&$comment) {
                $this->onAfterUnpublishComment($comment);
        }
        
        public function onAfterDeleteComment(&$comment) {
                self::$rater->removeRate($comment, true);
        }
        
        public function onAfterProcessComment(&$comment) {
                if (JFactory::getApplication()->isAdmin()) return;
                
                $contentId = $comment->{KomentoRate::CONTENTID_COL};
                $extensionName = $comment->{KomentoRate::EXTENSIONNAME_COL};

                $definition = self::$rater->getDefinition($extensionName, $contentId);
                
                $comment->noRate = false;
                
                if (!isset($comment->isAggregate)) $comment->isAggregate = false;
                
                if ($comment->isAggregate) {
                        $rates = self::$rater->getRate($contentId, $extensionName);
                        reset($rates);
                        $rates[$comment->id] = $rates[key($rates)];
                        if ($rates[$comment->id]->content_id != $contentId) {
                                $comment->noRate = true;
                                return false;
                        }
                } else {
                        $rates = self::$rater->getRates(null, $contentId, $extensionName);
                }
                
                if (empty($rates)) {
                        $comment->noRate = true;
                        return false;
                }
                
                if (isset($rates[$comment->id])) {
                        $rate = $rates[$comment->id];
                        $comment->rategroupCSS = $rate->rategroup;
                        $rate = KomentoRate::tmpl($rate, $definition, $comment->isAggregate);
                } else {
                        $comment->rategroupCSS = '';
                        $rate = '';
                }

                $cmt = $comment->comment;
                $comment->comment = $rate;
                
                if ($comment->isAggregate) {
                        $comment->rategroupCSS .= ' aggrrate';
                } else {
                        $comment->comment .= '<div class="comment-body" itemprop="reviewBody">'.$cmt.'</div>';
                }
                
                return true;
        }        

        /*** K2 plugin events ***/
        function onK2BeforeDisplay(&$item, &$params, $limitstart) {}
        
        function onK2AfterDisplay(&$item, &$params, $limitstart) {}
        
        function onK2AfterDisplayTitle(&$item, &$params, $limitstart) {}
        
        function onK2BeforeDisplayContent(&$item, &$params, $limitstart) {}
        
        function onK2AfterDisplayContent(&$item, &$params, $limitstart) {}
        
        function onK2PrepareContent(& $item, & $params, $limitstart) {}

        function onK2CategoryDisplay(&$category, &$params, $limitstart) {}
        
        function onBeforeK2Save(&$item, $isNew) {}
        
        function onAfterK2Save(&$item, $isNew) {}        

        function onRenderAdminForm(&$item, $type, $tab = '') {}
}