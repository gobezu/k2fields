<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!JprovenUtility::checkPluginActive('k2fields', 'system', '', true)) {
        JError::raiseError('500', 'Unable to activate/locate k2fields system plugin which is required for proper functioning of k2fields. Please correct that and try again.');
        return;
}

class plgkomentorate extends JPlugin {
        private static $rater;
                
        function plgkomentorate(&$subject, $params) {
                parent::__construct($subject, $params);
                $this->loadLanguage('', JPATH_ADMINISTRATOR);
                JLoader::register('KomentoRate', JPATH_SITE.'/plugins/komento/rate/rate.class.php');
                self::$rater = new KomentoRate();
        }
        
        public function onAfterSaveComment($comment) {
//                if (JFactory::getApplication()->isAdmin()) return;
                jdbg::pfile('onAfterSaveComment');
                $cmt = $comment['comment'];
                self::$rater->rate($cmt);
        }
        
        public function onAfterUnpublishComment($comment) {
                $cmt = $comment['comment'];
                self::$rater->recalc($cmt->cid, $cmt->component);
        }
        
        public function onAfterPublishComment($comment) {
                $cmt = $comment['comment'];
                self::$rater->recalc($cmt->cid, $cmt->component);
        }
        
        public function onAfterDeleteComment($comment) {
                $cmt = $comment['comment'];
                self::$rater->removeRate($cmt, true);
        }
        
        public function onAfterProcessComment($comment) {
                $cmt = $comment['comment'];
//                if (JFactory::getApplication()->isAdmin()) return;
                
                $contentId = $cmt->{KomentoRate::CONTENTID_COL};
                $extensionName = $cmt->{KomentoRate::EXTENSIONNAME_COL};

                $definition = self::$rater->getDefinition($extensionName, $contentId);
                
                $cmt->noRate = false;
                
                if (!isset($cmt->isAggregate)) $cmt->isAggregate = false;
                
                if ($cmt->isAggregate) {
                        $rates = self::$rater->getRate($contentId, $extensionName);
                        reset($rates);
                        $rates[$cmt->id] = $rates[key($rates)];
                        if ($rates[$cmt->id]->content_id != $contentId) {
                                $cmt->noRate = true;
                                return false;
                        }
                } else {
                        $rates = self::$rater->getRates(null, $contentId, $extensionName);
                }
                
                if (empty($rates)) {
                        $cmt->noRate = true;
                        return false;
                }
                
                if (isset($rates[$cmt->id])) {
                        $rate = $rates[$cmt->id];
                        $cmt->rategroupCSS = $rate->rategroup;
                        $rate = KomentoRate::tmpl($rate, $definition, $cmt->isAggregate);
                } else {
                        $cmt->rategroupCSS = '';
                        $rate = '';
                }

                $cmt = $cmt->comment;
                $comment['comment']->comment = $rate;
                
                if ($comment['comment']->isAggregate) {
                        $comment['comment']->rategroupCSS .= ' aggrrate';
                } else {
                        $comment['comment']->comment .= '<div class="comment-body" itemprop="reviewBody">'.$cmt.'</div>';
                }
                
                return true;
        }
}