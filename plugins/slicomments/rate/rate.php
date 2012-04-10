<?php
// $copyright$

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

JLoader::register('SliRate', dirname(__FILE__).'/rate.class.php');

class plgSlicommentsRate extends JPlugin {
        static $isLoaded = false;
        protected static $groups, $separator, $criterias;
        
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_content_slicomments.sys');
                
                $input = JFactory::getApplication()->input; 
                $extension_name = $input->get('option', '', 'cmd');
                
                $comp = array('com_k2' => array('k2', 'slicomments'), 'com_content' => array('content', 'slicomments'));
                
                if (!JPluginHelper::importPlugin($comp[$extension_name][0], $comp[$extension_name][1])) return;
                
                $task = $input->get('task', '', 'cmd');
                $view = $input->get('view', '', 'cmd');
                
                if ($extension_name == 'com_slicomments') {
                        if ($task == 'post') {
                                $extension_name = $input->get('extension_name', '', 'cmd');
                                self::setAttributes($this->params, $extension_name);
                        }
                        
                        return;
                }
                
                if (self::$isLoaded) return;
                
                $document = JFactory::getDocument();
                $document->addStylesheet(JURI::root().'media/plg_slicomments_rate/rate.css');
                
                if (!(
                        ($extension_name == 'com_k2' && $view == 'item') 
                     || ($extension_name == 'com_content' && $view == 'comments' && $task == 'comments.display')
                )) return;
                
                $document->addScript(JURI::root().'media/plg_slicomments_rate/rate.js');
                $document->addScript(JURI::root().'media/plg_slicomments_rate/MooStarRating/Source/moostarrating.js');
                $rater = new SliRate();
                
                $item = $input->get('id', '', 'int');
                
                self::setAttributes($this->params, $extension_name);
                
                $criterias = $rater->getRatingDefinition($item, $extension_name, true);
                
                $document->addScriptDeclaration("
                window.addEvent('domready', function(){ 
                        new SliRate({
                                base:'".JURI::root()."media/plg_slicomments_rate/',
                                rateDefinition:".json_encode($criterias)."
                        });
                });
                ");
                
                self::$isLoaded = true;
	}
        
        protected static function setAttributes($params, $extension_name) {
                self::$groups = $params->get($extension_name.'_ratingcategories');
                self::$criterias = $params->get($extension_name.'_ratingcriterias');
                self::$separator = $params->get($extension_name.'_separator');                
        }

	public function onBeforeSaveComment($comment) {
	}
        
	public function onAfterSaveComment($comment) {
                $rater = new SliRate();
                $rateId = $rater->rate($comment);
        }
        
        public function onBeforeChangeCommentState($comment, $newState) {
        }

	public function onAfterChangeCommentState($comment, $earlierState) {
                $rater = new SliRate();
                self::setAttributes($this->params, $comment->extension_name);
                $rater->calculateItemRate($comment->article_id, $comment->extension_name);
		return true;
	}
        
        public function onDeleteComments($comments) {
                $rater = new SliRate();
                $rater->removeRates($comments, true);
        }
        
        public function onDeleteComment($comment) {
                $rater = new SliRate();
                $rater->removeRates(array($comments), true);
        }
        
        public function onProcessComments(&$comments) {
                if (empty($comments)) return;
                
                $rater = new SliRate();
                
                $criterias = $rater->getRatingDefinition($comments[0]->article_id, $comments[0]->extension_name,  true);
                $rates = $criterias['rates'];
                unset($criterias['rates']);
                $html = self::_tmpl($rates, $criterias, true);
                //$criterias = $rater->getRatingDefinition($comments[0]->article_id, $comments[0]->extension_name, true);
                
                $aggrRate = new stdClass();
                $aggrRate->name = '';
                $aggrRate->email = '';
                $aggrRate->id = '';
                $aggrRate->rating = '';
                $aggrRate->user_id = '';
                $aggrRate->created = '';
                $aggrRate->text = $html;
                $aggrRate->isAggrRate = true;
                
                // $criterias = $rater->getRatingDefinition($comment->article_id, false);
                $commentIds = SliRate::getColumn($comments, 'id');
                $rates = $rater->getRate($commentIds);
                
                foreach ($comments as &$comment) {
                        $html = self::_tmpl($rates[$comment->id], $criterias);
                        $comment->text = '<div class="thecomment">'.$comment->text.'</div>'.$html;
                }
                
                array_unshift($comments, $aggrRate);
        }
        
        private static function _tmpl($rate, $criterias, $isItem = false) {
                $prefix = $isItem ? 's' : '';
                $title = $isItem ? '<h5>'.JText::sprintf('Average user rating from: %d users', $rate->count).'</h5>' : '';
                if (!$rate) {
                        return '<div class="ratingInfo'.($isItem ? 'Item' : '').'">'.JText::_('Not rated').'</div>';
                }
                $r = $rate->rate_grade;
                if ($r == 0) {
                        $r = '0';
                        $rr = '0';
                } else $rr = round($r, 1);
                $ui = '<div class="ratingInfo'.($isItem ? 'Item' : '').'">'.$title.'
        <ul class="rating_table">
                <li>
                        <span class="rating_label">'. JText::_('Overall rating') .'&nbsp;</span>
                        <span class="rating_value">'. $rr.
                                ($isItem ? '&nbsp;&nbsp;('.$rate->count.')' : '').'</span>
                        <span class="rating_starc">
                                <div class="rating_star_user">
                                        <div style="width: '. $r .'%;">&nbsp;</div>
                                </div>
                        </span>
                </li>
                ';
                foreach ($criterias as $i => $criteria) {
                        $r = $rate->{'rate'.$prefix.($i+1).'_grade'};
                        if ($r == 0) {
                                $r = '0';
                                $rr = '0';
                        } else $rr = round($r, 1);
                        $ui .= '<li>
                                <span class="rating_label">'. $criteria[SliRate::COL_NAME].'&nbsp;</span>
                                <span class="rating_value">'. 
                                                $rr.
                                                ($isItem ? '&nbsp;&nbsp;('.$rate->{'counts'.($i + 1)}.')' : '').
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
        <div class="cf"></div>	
</div>';
                
                return $ui;
        }
        
        protected static function criteriaGroup($catId, $extension_name) {
                if (empty($catId)) $catId = 'all';
                return self::setting(self::$groups, (array) $catId);
        }
        
        public static function criterias($catId, $extension_name) {
                if (empty($catId)) $catId = 'all';
                
                $criteriaGroup = self::criteriaGroup($catId, $extension_name);
                
                if (empty($criteriaGroup)) return;
                
                $excludedCategories = array();
                        
                if (count($criteriaGroup) > 1 && !empty($criteriaGroup[1])) $excludedCategories = explode(',', $criteriaGroup[1]);
                        
                if (in_array($catId, $excludedCategories)) return;
                
                $criterias = self::setting(self::$criterias, $criteriaGroup[0]);
                
                return $criterias;
        }
        
        protected static function setting($val, $assertedKeys = null, $allKey = 'all', $name = '') {
                $assertedKeys = (array) $assertedKeys;
                
                $isAllKeyValue = false;
                
                if (!empty($assertedKeys) && is_string($val)) {
                        $result = array();
                        $vals = explode("\n", $val);
                        $keys = $assertedKeys;
                        
                        if (!empty($allKey)) $keys[] = $allKey;
                        
                        foreach ($vals as $_val) {
                                $_val = explode(self::$separator, $_val);
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
        
//        protected static function toMatrix($csv, $rowSep = "\n", $colSep = '%%') {
//                $rows = explode($rowSep, $csv);
//                $cols = array();
//                for ($i = 0, $r = count($rows); $i < $r; $i++) {
//                        $rc = explode($colSep, $rows[$i]);
//                        for ($j = 0, $c = count($rc); $j < $c; $j++) {
//                                if (!isset($cols[$c])) $cols[$rc[$c]] = array();
//                                $cols[$rc[$c]][] = $
//                        }
//                }
//        }
        
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
