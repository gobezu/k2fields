<?php
//$Copyright$

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

JLoader::register('JcommentsRate', dirname(__FILE__).'/rate.class.php');

class plgJcommentsRate extends JPlugin {
        public static $criterias = null;
        protected static $isLoaded = false, $rater;
        
	public function __construct(&$subject, $config = array()) {
                parent::__construct($subject, $config);
                
                $input = JFactory::getApplication()->input;
                $extensionName = $input->get('option');
                
                self::$rater = new JcommentsRate();
                
                if ($extensionName == 'com_jcomments') {
                        $jtxf = $input->get('jtxf');
                        
                        if ($jtxf == 'JCommentsShowPage') {
                                list($content, $extensionName) = $input->get('jtxa', array('', ''), 'array');
                        } else {
                                $content = $input->get(JcommentsRate::CONTENTID_COL, '', 'int');
                                $extensionName = $input->get(JcommentsRate::EXTENSIONNAME_COL);
                        }
                        
                        self::setAttributes($content, $extensionName, $this->params);
                        return;
                }
                
                $comp = array(
                        'com_k2' => array('k2', 'jcomments'), 
                        'com_k2fields' => array('k2', 'jcomments'), 
                        'com_content' => array('content', 'jcomments')
                );
                
                if (
                        !isset($comp[$extensionName]) ||
                        !JPluginHelper::importPlugin($comp[$extensionName][0], $comp[$extensionName][1])
                ) return;
                
                $task = $input->get('task', '', 'cmd');
                $view = $input->get('view', '', 'cmd');
                
                if (self::$isLoaded) return;
                
                $document = JFactory::getDocument();
                $document->addStylesheet(JURI::root().'media/plg_jcomments_rate/rate.css');
                
                if (!(
                        ($extensionName == 'com_k2' && $view == 'item') 
                     || ($extensionName == 'com_content' && $view == 'article')
                )) return;
                
                $document->addScript(JURI::root().'media/plg_jcomments_rate/rate.js');
                $document->addScript(JURI::root().'media/plg_jcomments_rate/MooStarRating/Source/moostarrating.js');
                
                $content = $input->get('id', '', 'int');
                
                self::setAttributes($content, $extensionName, $this->params);
                
                $document->addScriptDeclaration("
                window.addEvent('domready', function(){ 
                        new JcommentsRate({
                                base:'".JURI::root()."media/plg_jcomments_rate/',
                                rateDefinition:".json_encode(self::$criterias)."
                        });
                });
                ");
                
                self::$isLoaded = true;
	}
        
        protected static function setAttributes($content, $extensionName, $params) {
                if (isset(self::$criterias) || empty($content) || empty($extensionName)) return;
                
                $content = self::$rater->getContent($content, $extensionName);
                
                $groups = $params->get($extensionName.'_ratingcategories');
                $criterias = $params->get($extensionName.'_ratingcriterias');
                $separator = $params->get($extensionName.'_separator');
                
                self::$criterias = self::parseCriterias($content->catid, $groups, $criterias, $separator);
        }

        function onJCommentsCommentsPrepare(&$comments) {
                $contentId = $comments[0]->{JcommentsRate::CONTENTID_COL};
                $extensionName = $comments[0]->{JcommentsRate::EXTENSIONNAME_COL};
                $rate = self::$rater->getRate($contentId, $extensionName);
                
                $aggrRate = clone $comments[0];
                
                $aggrRate->id = -1;
                $aggrRate->comment = self::_tmpl($rate, true);
                $aggrRate->_skip_prepare = true;
                $aggrRate->isContent = false;
                
                $rates = self::$rater->getRates(null, $contentId, $extensionName);
                
                foreach ($comments as $comment) {
                        if (isset($rates[$comment->id])) {
                                $rate = $rates[$comment->id];
                                $rate = self::_tmpl($rate);
                        } else $rate = '';
                        
                        $comment->comment = '<div class="areview"><div class="arate">'.$rate.'</div><div class="acomment">'.$comment->comment.'</div></div>';
                        $comment->_skip_prepare = true;
                }
                
                array_unshift($comments, $aggrRate);
        }
        
        function onJCommentsCommentAfterAdd(&$comment) {
                self::setAttributes(
                        $comment->{JcommentsRate::CONTENTID_COL}, 
                        $comment->{JcommentsRate::EXTENSIONNAME_COL}, 
                        $this->params
                );
                
                self::$rater->rate($comment);
        }  
        
        function onJCommentsCommentAfterDelete(&$comment) {
                self::setAttributes(
                        $comment->{JcommentsRate::CONTENTID_COL}, 
                        $comment->{JcommentsRate::EXTENSIONNAME_COL}, 
                        $this->params
                );
                
                self::$rater->removeRate($comment, true);                
        }
        
        function onJCommentsCommentAfterPublish(&$comment) {
                $this->changeState($comment);
        }   
        
        function onJCommentsCommentAfterUnpublish(&$comment) {
                $this->changeState($comment);
        }
        
        function onJCommentsCommentSave(&$comment) {
                $this->changeState($comment);
        }
        
        function changeState($comment) {
                self::setAttributes(
                        $comment->{JcommentsRate::CONTENTID_COL}, 
                        $comment->{JcommentsRate::EXTENSIONNAME_COL}, 
                        $this->params
                );
                        
                self::$rater->calculateContentRate(
                        $comment->{JcommentsRate::CONTENTID_COL}, 
                        $comment->{JcommentsRate::EXTENSIONNAME_COL}
                );
        }
        
        private static function _tmpl($rate, $isContent = false) {
                $prefix = $isContent ? 's' : '';
                $title = $isContent ? '<h5>'.JText::sprintf('Average user rating from: %d users', $rate->count).'</h5>' : '';
                
                if (!$rate) return '<div class="ratingInfo'.($isContent ? 'Content' : '').'">'.JText::_('Not rated').'</div>';
                
                $r = $rate->rate_grade;
                
                if ($r == 0) {
                        $r = '0';
                        $rr = '0';
                } else $rr = round($r, 1);
                
                $ui = '
<div class="ratingInfo'.($isContent ? 'Content' : '').'">'.$title.'
        <ul class="rating_table">
                <li>
                        <span class="rating_label">'. JText::_('Overall rating') .'&nbsp;</span>
                        <span class="rating_value">'. $rr.
                                ($isContent ? '&nbsp;&nbsp;('.$rate->count.')' : '').'</span>
                        <span class="rating_starc">
                                <div class="rating_star_user">
                                        <div style="width: '. $r .'%;">&nbsp;</div>
                                </div>
                        </span>
                </li>
                ';
                
                foreach (self::$criterias as $i => $criteria) {
                        $r = $rate->{'rate'.$prefix.($i+1).'_grade'};
                        if ($r == 0) {
                                $r = '0';
                                $rr = '0';
                        } else $rr = round($r, 1);
                        $ui .= '<li>
                                <span class="rating_label">'. $criteria[JcommentsRate::COL_NAME].'&nbsp;</span>
                                <span class="rating_value">'. 
                                                $rr.
                                                ($isContent ? '&nbsp;&nbsp;('.$rate->{'counts'.($i + 1)}.')' : '').
                                '</span>
                                <span class="rating_starc">
                                        <div class="rating_star_user">
                                                <div style="width: '.$r.'%;">&nbsp;</div>
                                        </div>
                                </span>
                        </li>
                        ';
                }
                
                $ui .= '        </ul>
        <div class="clr"></div>	
</div>';
                
                return $ui;
        }
        
        private static function parseCriterias($catId, $groups, $criterias, $separator) {
                if (empty($catId)) $catId = 'all';
                
                if (is_object($catId)) {
                        $tokenValues = array($catId->title, $catId->catname);
                        $catId = $catId->catid;
                } else {
                        $tokenValues = array();
                }
                
                $criteriaGroup = self::setting($groups, (array) $catId, 'all', '', $separator);
                
                if (empty($criteriaGroup)) return;
                
                $excludedCategories = array();
                        
                if (count($criteriaGroup) > 1 && !empty($criteriaGroup[1])) $excludedCategories = explode(',', $criteriaGroup[1]);
                        
                if (in_array($catId, $excludedCategories)) return;
                
                $criterias = self::setting($criterias, $criteriaGroup[0], 'all', '', $separator);
                
                if (empty($criterias)) return;
                
                $tokens = array('%item%', '%cat%');
                
                foreach ($criterias as &$criteria) {
                        // name
                        $criteria[JcommentsRate::COL_NAME] = str_replace($tokens, $tokenValues, JText::_($criteria[JcommentsRate::COL_NAME]));

                        // weight
                        $criteria[JcommentsRate::COL_WEIGHT] = (int) $criteria[JcommentsRate::COL_WEIGHT] / 100;

                        // scales (value1=label1,value2=label2,....,valueN=labelN)
                        $scales = count($criteria) < 2 || empty($criteria[JcommentsRate::COL_SCALES]) ? range(1, 5, 1) : explode(',', $criteria[JcommentsRate::COL_SCALES]);

                        foreach ($scales as &$scale) {
                                $scale = explode('=', $scale);

                                if (count($scale) == 1) $scale[] = $scale[0];

                                $scale[0] = (float) $scale[0];

                                $scale[1] = str_replace($tokens, $tokenValues, JText::_($scale[1]));
                        }

                        $criteria[JcommentsRate::COL_SCALES] = $scales;

                        // requried
                        $criteria[JcommentsRate::COL_REQUIRED] = count($criteria) <= 3 ? false : (bool) $criteria[JcommentsRate::COL_REQUIRED];
                        $criteria[JcommentsRate::COL_UI] = trim(count($criteria) <= 4 ? 'select' : $criteria[JcommentsRate::COL_UI]);
                }
                
                return $criterias;
        }
        
        protected static function setting($val, $assertedKeys = null, $allKey = 'all', $name = '', $separator) {
                $assertedKeys = (array) $assertedKeys;
                
                $isAllKeyValue = false;
                
                if (!empty($assertedKeys) && is_string($val)) {
                        $result = array();
                        $vals = explode("\n", $val);
                        $keys = $assertedKeys;
                        
                        if (!empty($allKey)) $keys[] = $allKey;
                        
                        foreach ($vals as $_val) {
                                $_val = explode($separator, $_val);
                                $_val[0] = trim($_val[0]);
                                
                                if (in_array($_val[0], $keys)) {
                                        $__val = $_val[0];
                                        array_shift($_val);
                                        
                                        if (!isset($result[$__val])) {
                                                $result[$__val] = array();
                                        }
                                        
                                        $result[$__val][] = $_val;
                                }
                        }
                }
                
                if (!empty($assertedKeys)) {
                        if (empty($result)) $result = null;
                        
                        return self::first($result);
                }
                
                return self::first($val);
        }
        
        protected static function first($array) {
                if (empty($array)) return;
                $key = self::firstKey($array);
                return $array[$key];
        }
        
        protected static function firstKey($array) {
                $keys = array_keys($array);
                $index = 0;
                if ($keys[$index] == 'all' && count($keys) > 1 && !empty($array[$keys[1]])) $index = 1;
                return $keys[$index];
        }        
}
