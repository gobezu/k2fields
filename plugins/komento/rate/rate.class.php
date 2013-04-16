<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class KomentoRate {
        const COL_NAME = 0;
        const COL_WEIGHT = 1;
        const COL_SCALES = 2;
        const COL_REQUIRED = 3;
        const COL_UI = 4;
        const COL_SHOWAS = 5;
        const COL_LOCALMAXS = 6;
        const COL_MAXS = 7;
        const COL_COLLAPSE = 8;
        
        const COL_REQ_INTERVAL = 0;
        const COL_REQ_ACCESS = 1;
        const COL_REQ_MODERATE = 2;
        
        const PRECISION = 4;
        
        const RATE_SCALE = 5;
        
        const MAX_NO_CRITERIAS = 10;
        
        private $_db;
        
        const CONTENTID_COL = 'cid';
        const EXTENSIONNAME_COL = 'component';
        const USER_COL = 'created_by';
        const COMMENT_TBL = '#__komento_comments';
        const STAR_WIDTH = 16;
        
	public function __construct() {
                $this->_db = JFactory::getDBO();
	}
        
        public function getDefinition($extensionName = 'com_k2', $content = null, $params = null) {
                if (empty($extensionName)) $extensionName = JFactory::getApplication()->input->get('option');
                if (empty($content)) $content = JFactory::getApplication()->input->get('id', '', 'int');
                
                $content = $this->getContent($content, $extensionName);
                
                if (!$params) {
                        jimport('joomla.plugin.helper');
                        $plugin = JPluginHelper::getPlugin('k2', 'k2komento');
                        $params = new JRegistry($plugin->params);
                }
                
                $groups = $params->get($extensionName.'_ratingcategories');
                $criterias = $params->get($extensionName.'_ratingcriterias');
                $separator = $params->get($extensionName.'_separator');
                $showAs = $params->get($extensionName.'_showas');
                $definition = self::parseCriterias($content->catid, $groups, $criterias, $separator, $showAs);
                
                return $definition;
        }
        
        public function getMax($definition) {
                $criteriasScales = JprovenUtility::getColumn($definition, self::COL_SCALES);
                $aggrMin = $aggrMax = 0;
                $count = count($criteriasScales);
                
                for ($i = 0; $i < $count; $i++) {
                        $scales = JprovenUtility::getColumn($criteriasScales[$i], 0);
                        $min = min($scales);
                        $max = max($scales);
                        $aggrMin += $min;
                        $aggrMax += $max;                        
                }
                
                return array('min'=>$aggrMin/$count, 'max'=>$aggrMax/$count);
        }
        
        private static function parseCriterias($catId, $groups, $criterias, $separator, $showAs) {
                if (empty($catId)) $catId = 'all';
                
                if (is_object($catId)) {
                        $tokenValues = array($catId->title, $catId->catname);
                        $catId = $catId->catid;
                } else {
                        $tokenValues = array();
                }
                
                $catPath = JprovenUtility::getK2CategoryPath($catId);
                
                $criteriaGroup = self::setting($groups, (array) $catPath, 'all', '', $separator);
                
                if (empty($criteriaGroup)) return;
                
                $criteriaGroup = $criteriaGroup[0];
                
                $excludedCategories = array();
                        
                if (count($criteriaGroup) > 1 && !empty($criteriaGroup[1])) $excludedCategories = explode(',', $criteriaGroup[1]);
                        
                if (in_array($catId, $excludedCategories)) return;
                
                $_showAs = count($criteriaGroup) > 2 ? $criteriaGroup[2] : '';
                if (!empty($_showAs)) $showAs = $_showAs;
                $isPercentage = $showAs == 'percentage' || $showAs == 'p';
                $isCollapse = count($criteriaGroup) > 3 ? $criteriaGroup[3] : '0';
                $isCollapse = trim($isCollapse);
                $isCollapse = $isCollapse == '1' || $isCollapse == 'yes';
                $criterias = self::setting($criterias, $criteriaGroup[0], 'all', '', $separator);
                
                if (empty($criterias)) return;
                
                $tokens = array('%item%', '%cat%');
                $aggrMin = $aggrMax = 0;
                
                foreach ($criterias as &$criteria) {
                        // name
                        $criteria[self::COL_NAME] = str_replace($tokens, $tokenValues, JText::_($criteria[self::COL_NAME]));

                        // weight
                        $criteria[self::COL_WEIGHT] = (int) $criteria[self::COL_WEIGHT] / 100;

                        // scales (value1=label1,value2=label2,....,valueN=labelN)
                        $scales = count($criteria) < 2 || empty($criteria[self::COL_SCALES]) ? range(1, 5, 1) : explode(',', $criteria[self::COL_SCALES]);

                        
                        foreach ($scales as &$scale) {
                                $scale = explode('=', $scale);

                                if (count($scale) == 1) $scale[] = $scale[0];

                                $scale[0] = (float) $scale[0];

                                $scale[1] = str_replace($tokens, $tokenValues, JText::_($scale[1]));
                        }

                        $criteria[self::COL_SCALES] = $scales;
                        
                        $values = JprovenUtility::getColumn($scales, 0);
                        $min = min($values);
                        $aggrMin += $min;
                        $max = max($values);
                        $aggrMax += $max;
                        
                        $criteria[self::COL_LOCALMAXS] = array(
                            'min'=>$isPercentage ? 0 : $min, 
                            'max'=>$isPercentage ? 100 : $max, 
                            'maxvalue'=>$max
                        );

                        // requried
                        $criteria[self::COL_REQUIRED] = count($criteria) <= 3 ? false : (bool) $criteria[self::COL_REQUIRED];
                        $criteria[self::COL_UI] = trim(count($criteria) <= 4 ? 'select' : $criteria[self::COL_UI]);
                        $criteria[self::COL_SHOWAS] = $isPercentage;
                        $criteria[self::COL_COLLAPSE] = $isCollapse;
                }
                
                $count = count($criterias);
                $max = array('min'=>$isPercentage ? 0 : $aggrMin/$count, 'max'=>$isPercentage ? 100 : $aggrMax/$count, 'maxvalue'=>$aggrMax/$count);
                
                for ($i = 0; $i < $count; $i++) $criterias[$i][self::COL_MAXS] = $max;
                
                return $criterias;
        }
        
        public static function tmpl($rate, $definition, $isContent = false) {
                $prefix = $isContent ? 's' : '';
                $title = $isContent ? '<h5>'.JText::sprintf('Average user rating from: %d users', $rate->count).'</h5>' : '';
                
                if (!$rate) return JText::_('Not rated');
                
                $isPercentage = $definition[0][self::COL_SHOWAS];
                $col = $isPercentage ? 'rate_grade' : 'rate';
                $unit = $isPercentage ? '%' : '';
                $r = $rate->$col;
                $max = $definition[0][self::COL_MAXS]['max'];
                $min = $definition[0][self::COL_MAXS]['min'];
                $maxValue = $definition[0][self::COL_MAXS]['maxvalue'];
                
                if ($r == 0) {
                        $r = '0';
                        $rr = '0';
                } else $rr = round($r, 1);
                
                $ui = '';
                $collapsed = $definition[0][self::COL_COLLAPSE] ? ' jpcollapse' : '';
                
                $ui .= $title.'
        <ul class="rating_table rateoverall'.$collapsed.'">
                <li itemprop="'.($isContent ? 'aggregateRating' : 'reviewRating').'" itemscope itemtype="http://schema.org/'.($isContent ? 'AggregateRating' : 'Rating').'">
                        <span class="rating_label">'. JText::_('Overall rating') .'&nbsp;</span>
                        <meta itemprop="worstRating" content="'.$min.'">
                        <meta itemprop="bestRating" content="'.$max.'">
                        <span class="rating_value"><span class="rating_value_value"><span class="nofloat" itemprop="ratingValue">'. $rr.'</span>'.$unit.'</span>'.
                                ($isContent ? '<span class="rating_value_count">(<span class="nofloat" itemprop="ratingCount">'.$rate->count.'</span>)</span>' : '').'</span>
                        <span class="rating_starc" style="width:'.(self::STAR_WIDTH*$maxValue).'px;">
                                <div class="rating_star_user">
                                        <div style="width: '. ($isPercentage ? $r : $r/$maxValue * 100) .'%;">&nbsp;</div>
                                </div>
                        </span>
                        <div class="clr">&nbsp;</div>
                </li>
        </ul>
        <ul class="rating_table">
                ';
                
                foreach ($definition as $i => $criteria) {
                        $col = 'rate'.$prefix.($i+1).($isPercentage ? '_grade' : '');
                        $r = $rate->$col;
                        if ($r == 0) {
                                $r = '0';
                                $rr = '0';
                        } else $rr = round($r, 1);
                        $rr .= $unit;
                        $maxValue = $definition[$i][self::COL_LOCALMAXS]['maxvalue'];
                        $ui .= '<li>
                                <span class="rating_label">'. $criteria[self::COL_NAME].'</span>
                                <span class="rating_value"><span class="rating_value_value">'. 
                                                $rr.'</span>'.
                                                ($isContent ? '<span class="rating_value_count">('.$rate->{'counts'.($i + 1)}.')</span>' : '').
                                '</span>
                                <span class="rating_starc" style="width:'.(self::STAR_WIDTH*$maxValue).'px;">
                                        <div class="rating_star_user">
                                                <div style="width: '.($isPercentage ? $r : $r/$maxValue * 100).'%;">&nbsp;</div>
                                        </div>
                                </span>
                                <div class="clr">&nbsp;</div>
                        </li>
                        ';
                }
                
                $ui .= '        </ul>
                
';
                
                return $ui;
        }
        
        public function form() {
                if (JFactory::getApplication()->isAdmin()) return;
                
                static $komento = false;
                
                if ($komento) return;
                
                $document = JFactory::getDocument();
                
                $document->addStyleSheet(JURI::root().'media/plg_komento_rate/rate.css');
                $document->addScript(JURI::root().'media/plg_komento_rate/rate.js');
                $document->addScript(JURI::root().'media/plg_komento_rate/MooStarRating/Source/moostarrating.js');
                $criterias = $this->getDefinition();
                
                $document->addScriptDeclaration("
                window.addEvent('load', function(){ 
                        new K2KomentoRate({
                                base:'".JURI::root()."media/plg_komento_rate/',
                                rateDefinition:".json_encode($criterias)."
                        });
                });
                ");
                
                $komento = true;
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
        
        // Get individual rates
        public function getRates($commentIds = null, $content = null, $extensionName = null) {
                if (!empty($content) && !empty($extensionName)) {
                        $commentIds = 
                                'SELECT c.id FROM '.self::COMMENT_TBL.' c'.
                                ' WHERE c.'.self::CONTENTID_COL.' = '.(int) $content.' AND c.'.self::EXTENSIONNAME_COL.' = '.$this->_db->quote($extensionName).' AND c.published = 1 ';
                } else {
                        $commentIds = (array) $commentIds;
                        $commentIds = implode(',', $commentIds);
                }
                
                $query = 'SELECT r.* FROM #__content_rates r WHERE r.commentid IN (' . $commentIds . ')';
                $this->_db->setQuery($query);
                $rate = $this->_db->loadObjectList('commentid');
                
                return $rate;
        }
        
        // Get aggregated rate
        public function getRate($content = null, $extensionName = null) {
                if (empty($content)) $content = JFactory::getApplication()->input->get('id', '', 'int');
                if (empty($extensionName)) $extensionName = JFactory::getApplication()->input->get('option', '', 'cmd');
                
                $contentId = is_object($content) ? $content->id : $content;
                
                if (empty($contentId)) JError::raiseError(404, JText::_("Item not found"));
                
                $query = 'SELECT * FROM #__content_rate WHERE content_id = ' . $contentId.' AND extension_name = '.$this->_db->quote($extensionName);
                $this->_db->setQuery($query);
                $total = $this->_db->loadObjectList();
                $isInitial = empty($total);
                
                if ($isInitial) {
                        $total = new stdClass();
                        
                        $query = 'SHOW COLUMNS FROM #__content_rate';
                        $this->_db->setQuery($query);
                        $cols = $this->_db->loadObjectList();
                        
                        foreach ($cols as $col)
                                $total->{$col->Field} = '';
                                
                        $total = array($total);
                }
                
                return $total;
        }
        
        public function getContent($content, $extensionName) {
                if (empty($content)) $content = JFactory::getApplication()->input->get('id', '', 'int');
                
                if (is_object($content)) {
                        if ($extensionName == 'com_k2') {
                                if (isset($content->category) && is_object($content->category)) {
                                        $content->catname = $content->category->name;
                                        return $content;
                                }
                        }
                }
                
                $contentId = is_object($content) ? $content->id : $content;
                
                if (empty($contentId)) JError::raiseError(404, JText::_("Item not found"));
                
                if ($extensionName == 'com_k2') {
                        $this->_db->setQuery('SELECT i.*, c.name as catname FROM #__k2_items AS i LEFT JOIN #__k2_categories AS c ON i.catid = c.id WHERE i.id = '.(int)$contentId);
                } else {
                        $this->_db->setQuery('SELECT i.*, c.id as catid, c.title as catname FROM #__content AS i LEFT JOIN #__categories AS c ON i.sectionid = c.id WHERE i.id = '.(int)$contentId);
                }
                
                $content = $this->_db->loadObject();
                
                return $content;
        }
        
        public function removeRate($comment, $reCalc = true) {
                if (empty($comment)) return;
                
                $this->_db->setQuery('DELETE FROM #__content_rates WHERE commentid = '.$comment->id)->query();
                
                if ($reCalc) $this->recalc($comment->{self::CONTENTID_COL}, $comment->{self::EXTENSIONNAME_COL});
        }
        
        public function rate($comment, $criterias = null) {
                $contentId = $comment->{self::CONTENTID_COL};
                
                if (!$criterias) {
                        $criterias = $this->getDefinition($comment->{self::EXTENSIONNAME_COL}, $comment->{self::CONTENTID_COL});
                }
                
                $criteriaId = 'k2frate_'.$contentId.'_';
                $rates = array();
                $ratesGrade = array();
                $ratesCols = array();
                $ratesGradeCols = array();
                $ratesCount = 0;
                $input = JFactory::getApplication()->input; 
                
                foreach ($criterias as $i => $criteria) {
                        $rate = $input->get($criteriaId . $i, 0, 'float');
                        
                        if ($rate == 0) {
                                unset($criterias[$i]);
                                continue;
                        }
                        
                        $ratesCols[] = 'rate'.($i + 1);
                        $ratesGradeCols[] = 'rate'.($i + 1).'_grade';
                        $rates[$i] = $rate;
                        $ratesCount++;
                        
                        $scales = self::getColumn($criteria[self::COL_SCALES], 0);
                        $maxScale = max($scales);
                        $ratesGrade[$i] = $rate / $maxScale * 100;
                }
                
                if ($ratesCount == 0) return;
                
                $avg = self::getWeightedAvg($criterias, $rates);
                $thisRate = $avg[0] * $avg[1];
                $thisRateGrade = $avg[0] * 100;
                $ratesCols = implode(',', $ratesCols) . ', ' . implode(',', $ratesGradeCols);
                $rates = implode(',', $rates) . ', ' . implode(',', $ratesGrade);
                $rateGroup = self::getRateGroup($comment);
                $rateGroup = $this->_db->quote($rateGroup);
                
                $query = 
                        'INSERT INTO #__content_rates(commentid, '.$ratesCols.', rate, rate_grade, rates_count, rate_max, rategroup) VALUES (' . 
                                $comment->id . ',' .
                                $rates . ',' . 
                                $thisRate . ',' .
                                $thisRateGrade . ',' .
                                $ratesCount . ',' .
                                $avg[1] . ',' .
                                $rateGroup .
                        ')';
                
                $this->_db->setQuery($query);
                $this->_db->query();
        }
        
        private static function getWeightedAvg($criterias, $rates) {
                $scale = $avg = 0;
                $weightSum = (array) self::getColumn($criterias, self::COL_WEIGHT);
                $weightSum = array_sum($weightSum);
                
                foreach ($criterias as $i => $criteria) {
                        $scales = self::getColumn($criteria[self::COL_SCALES], 0);
                        $maxScale = max($scales);
                        $weight = $criteria[self::COL_WEIGHT] / $weightSum;
                        $avg +=  $weight * $rates[$i] / $maxScale;
                        $scale += $maxScale * $weight;
                }
                
                return array($avg, $scale);
        }
        
        private static $groups;
        
        private static function setRateGroups() {
                if (!isset(self::$groups)) {
                        $user = false;
                        $plg = JPluginHelper::getPlugin('k2', 'k2komento');
                        $params = new JRegistry($plg->params);
                        $groups = $params->get('rategroups');
                        
                        if (!empty($groups)) {
                                $groups = explode("\n", $groups);

                                foreach ($groups as &$group) {
                                        $group = explode('%%', $group);
                                        
                                        if (isset($group[2])) {
                                                $group[2] = explode(',', $group[2]);
                                        } else {
                                                $group[2] = array('all');
                                                $group[3] = array('all');
                                                continue;
                                        }

                                        if (!isset($group[3]) || trim($group[3]) == '') {
                                                $group[3] = array('all');
                                                continue;
                                        }

                                        $group[3] = explode(',', $group[3]);
                                }
                                
                                self::$groups = $groups;
                                unset($group);
                                unset($groups);
                        }
                }
        }
        
        public static function getRateGroup($comment = null) {
                self::setRateGroups();
                
                if (empty(self::$groups)) return '';
                
                $userId = $comment ? $comment->{KomentoRate::USER_COL} : JUser::getInstance()->id;
                $reviewerCSS = '';
                
                if ($userId) {
                        foreach (self::$groups as $group) {
                                if (in_array($userId, $group[3])) {
                                        $reviewerCSS = $group[1];
                                        break;
                                }
                        }

                        if (!$reviewerCSS) {
                                $commentor = new JUser($userId);
                                $commentorViews = $commentor->getAuthorisedViewLevels();

                                foreach (self::$groups as $group) {
                                        foreach ($commentorViews as $commentorView) {
                                                if (in_array($commentorView, $group[2])) {
                                                        $reviewerCSS = $group[1];
                                                        break;
                                                }
                                        }
                                        if (!empty($reviewerCSS)) break;
                                }
                        }
                }

                if (empty($reviewerCSS)) {
                        $commentor = 'all';

                        foreach (self::$groups as $group) {
                                if (in_array($commentor, $group[2])) {
                                        $reviewerCSS = $group[1];
                                        break;
                                }
                        }       
                }
                
                return $reviewerCSS;
        }
        
        public function recalc($contentId, $extensionName) {
                $criterias = $this->getDefinition($extensionName, $contentId);
                
                $cols = array('AVG(r.rate) as rate, AVG(r.rate_grade) as rate_grade, COUNT(r.rate) as count');
                
                foreach ($criterias as $i => $criteria) 
                        $cols[] = 'AVG(r.rate'.($i + 1).') as rates'.($i + 1).', AVG(r.rate'.($i + 1).'_grade) as rates'.($i + 1).'_grade, COUNT(r.rate'.($i + 1).') as counts'.($i + 1);
                
                $query = 
                        'SELECT c.'.self::CONTENTID_COL.', r.rategroup, '.implode(', ', $cols) .
                        ' FROM #__content_rates r INNER JOIN '.self::COMMENT_TBL.' c ON r.commentid = c.id ' .
                        ' WHERE c.'.self::CONTENTID_COL.' = '.$contentId.' AND c.'.self::EXTENSIONNAME_COL.' = '.$this->_db->quote($extensionName).' AND c.published = 1 '.
                        ' GROUP BY c.'.self::EXTENSIONNAME_COL.', c.'.self::CONTENTID_COL.', r.rategroup';
                
                $this->_db->setQuery($query);
                $rateRows = $this->_db->loadObjectList();
                
                $query = 'DELETE FROM #__content_rate WHERE content_id = '.$contentId.' AND extension_name = '.$this->_db->quote($extensionName);
                $this->_db->setQuery($query)->query();
                
                if (empty($rateRows)) {
                        if ($extensionName == 'com_k2') {
                                $query = 'DELETE FROM #__k2_rating WHERE itemID = '.$contentId;
                        } else if ($extensionName == 'com_content') {
                                $query = 'DELETE FROM #__content_rating WHERE content_id = '.$contentId;
                        }
                        
                        $this->_db->setQuery($query)->query();
                        
                        return;
                }
                
                foreach ($rateRows as $rateRow) {
                        $contentId = $rateRow->{self::CONTENTID_COL};
                        $total = array(
                                'rate' => $rateRow->rate,
                                'rate_grade' => $rateRow->rate_grade,
                                'count' => $rateRow->count,
                                'rategroup' => $this->_db->quote($rateRow->rategroup),
                                'content_id' => $contentId,
                                'extension_name' => $this->_db->quote($extensionName)
                        );

                        foreach ($criterias as $i => $criteria) {
                                $rate = $rateRow->{'rates'.($i + 1)};
                                $isRated = !empty($rate);

                                $total['rates'.($i + 1)] = $isRated ? $rate : 0;
                                $total['rates'.($i + 1).'_grade'] = $isRated ? $rateRow->{'rates'.($i + 1).'_grade'} : 0;
                                $total['counts'.($i + 1)] = $isRated ? $rateRow->{'counts'.($i + 1)} : 0;
                        }
                        
                        $values = array_values($total);
                        $cols = array_keys($total);
                        $query = 'INSERT INTO #__content_rate('.implode(', ', $cols).') VALUES ('.implode(', ', $values).')';

                        $this->_db->setQuery($query)->query();

                        $count = $total['count'];
                        $rate = $total['rate_grade'] / 100 * self::RATE_SCALE * $count;

                        if ($extensionName == 'com_k2') {
                                $query = 'DELETE FROM #__k2_rating WHERE itemID = '.$contentId;
                                $this->_db->setQuery($query)->query();

                                $query = 'INSERT INTO #__k2_rating(itemID, rating_sum, rating_count, lastip) VALUES ('.
                                        $contentId . ',' .
                                        $rate . ', '.$count.', ' .
                                        $this->_db->Quote('') .
                                ')';

                                $this->_db->setQuery($query)->query();
                        } else if ($extensionName == 'com_content') {
                                K2Model::addIncludePath(JPATH_SITE.'/components/com_content/models');
                                $model = K2Model::getInstance('Article', 'ContentModel');
                                $model->storeVote($contentId, $rate);
                        }
                }
        }
        
        public static function getColumn($array, $index, $unique = false, $criterias = array(), $maintainIndex = false) {
                $result = array();
                
                $array = self::getRow($array, $criterias);
                
                if (is_array($array)) {
                        foreach ($array as $arrInd => $item) {
                                if (is_array($item) && isset($item[$index])) {
                                        $result[$maintainIndex ? $arrInd : count($result)] = $item[$index];
                                } elseif (is_object($item) && isset($item->$index)) {
                                        $result[$maintainIndex ? $arrInd : count($result)] = $item->$index;
                                }
                        }
                }
                
                if ($unique) {
                        $result = array_unique($result);
                }

                return count($result) == 1 ? $result[0] : $result;
        }    
        
        public static function getRow($array, $criterias = array()) {
                if (empty($criterias)) return $array;
                
                $result = array();
                
                if (is_array($array)) {
                        foreach ($array as $item) {
                                $passed = true;
                                if (is_array($item)) {
                                        if (!empty($criterias)) {
                                                foreach ($criterias as $criteriaCol => $criteria) {
                                                        if ($item[$criteriaCol] != $criteria) {
                                                                $passed = false;
                                                        }
                                                }
                                        }
                                        if ($passed) $result[] = $item;
                                } elseif (is_object($item)) {
                                        if (!empty($criterias)) {
                                                foreach ($criterias as $criteriaCol => $criteria) {
                                                        if ($item->$criteriaCol != $criteria) {
                                                                $passed = false;
                                                        }
                                                }
                                        }
                                        if ($passed) $result[] = $item;
                                }
                        }
                }
                
                return $result;                
        }      
}