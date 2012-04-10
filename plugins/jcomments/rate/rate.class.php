<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class JcommentsRate {
        const COL_NAME = 0;
        const COL_WEIGHT = 1;
        const COL_SCALES = 2;
        const COL_REQUIRED = 3;
        const COL_UI = 4;
        
        const COL_REQ_INTERVAL = 0;
        const COL_REQ_ACCESS = 1;
        const COL_REQ_MODERATE = 2;
        
        const PRECISION = 4;
        
        const RATE_SCALE = 5;
        
        const MAX_NO_CRITERIAS = 10;
        
        private $_db;
        
        const CONTENTID_COL = 'object_id';
        const EXTENSIONNAME_COL = 'object_group';
        const COMMENT_TBL = '#__jcomments';
        
	public function __construct() {
                $this->_db = JFactory::getDBO();
	}
        
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
        
        public function getRate($content = null, $extensionName = null) {
                if (empty($content)) $content = JFactory::getApplication()->input->get('id', '', 'int');
                if (empty($extensionName)) $extensionName = JFactory::getApplication()->input->get('option', '', 'cmd');
                
                $contentId = is_object($content) ? $content->id : $content;
                
                if (empty($contentId)) JError::raiseError(404, JText::_("Item not found"));
                
                $query = 'SELECT * FROM #__content_rate WHERE content_id = ' . $contentId.' AND extension_name = '.$this->_db->quote($extensionName);
                $this->_db->setQuery($query);
                $total = $this->_db->loadObject();
                $isInitial = empty($total);
                
                if ($isInitial) {
                        $total = new stdClass();
                        
                        $query = 'SHOW COLUMNS FROM #__content_rate';
                        $this->_db->setQuery($query);
                        $cols = $this->_db->loadObjectList();
                        
                        foreach ($cols as $col)
                                $total->{$col->Field} = '';
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
                
                if ($reCalc) $this->calculateContentRate($comment->{self::CONTENTID_COL}, $comment->{self::EXTENSIONNAME_COL});
        }
        
        public function rate($comment) {
                $contentId = $comment->{self::CONTENTID_COL};
                $criterias = plgJcommentsRate::$criterias;
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
                
                $query = 
                        'INSERT INTO #__content_rates(commentid, '.$ratesCols.', rate, rate_grade, rates_count, rate_max) VALUES (' . 
                                $comment->id . ',' .
                                $rates . ',' . 
                                $thisRate . ',' .
                                $thisRateGrade . ',' .
                                $ratesCount . ',' .
                                $avg[1] .
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
        
        public function calculateContentRate($contentId, $extensionName) {
                $criterias = plgJcommentsRate::$criterias;
                $total = $this->getRate($contentId, $extensionName);
                
                $cols = array('AVG(r.rate) as rate, AVG(r.rate_grade) as rate_grade, COUNT(r.rate) as count');
                
                foreach ($criterias as $i => $criteria) 
                        $cols[] = 'AVG(r.rate'.($i + 1).') as rates'.($i + 1).', AVG(r.rate'.($i + 1).'_grade) as rates'.($i + 1).'_grade, COUNT(r.rate'.($i + 1).') as counts'.($i + 1);
                
                $query = 
                        'SELECT c.'.self::CONTENTID_COL.', '.implode(', ', $cols) .
                        ' FROM #__content_rates r INNER JOIN '.self::COMMENT_TBL.' c ON r.commentid = c.id ' .
                        ' WHERE c.'.self::CONTENTID_COL.' = '.$contentId.' AND c.'.self::EXTENSIONNAME_COL.' = '.$this->_db->quote($extensionName).' AND c.published = 1 '.
                        ' GROUP BY c.'.self::EXTENSIONNAME_COL.', c.'.self::CONTENTID_COL;
                
                $this->_db->setQuery($query);
                $rateRow = $this->_db->loadObject();
                
                if (empty($rateRow)) {
                        $query = 'DELETE FROM #__content_rate WHERE content_id = '.$contentId.' AND extension_name = '.$this->_db->quote($extensionName);
                        $this->_db->setQuery($query);
                        $this->_db->query();
                        
                        if ($extensionName == 'com_k2') {
                                $query = 'DELETE FROM #__k2_rating WHERE itemID = '.$contentId;
                        } else if ($extensionName == 'com_content') {
                                $query = 'DELETE FROM #__content_rating WHERE content_id = '.$contentId;
                        }
                        
                        $this->_db->setQuery($query);
                        $this->_db->query();
                        
                        return;
                }
                
                $contentId = $rateRow->{self::CONTENTID_COL};
                $total->rate = $rateRow->rate;
                $total->rate_grade = $rateRow->rate_grade;
                $total->count = $rateRow->count;
                
                foreach ($criterias as $i => $criteria) {
                        $rate = $rateRow->{'rates'.($i + 1)};
                        $isRated = !empty($rate);
                        
                        $total->{'rates'.($i + 1)} = $isRated ? $rate : 0;
                        $total->{'rates'.($i + 1).'_grade'} = $isRated ? $rateRow->{'rates'.($i + 1).'_grade'} : 0;
                        $total->{'counts'.($i + 1)} = $isRated ? $rateRow->{'counts'.($i + 1)} : 0;
                }
                
                $total = get_object_vars($total);
                $initial = empty($total['content_id']);
                $total['content_id'] = $contentId;
                $total['extension_name'] = $this->_db->quote($extensionName);
                $total = array_filter($total);
                
                if ($initial) {
                        $values = array_values($total);
                        $cols = array_keys($total);
                        $query = 'INSERT INTO #__content_rate('.implode(', ', $cols).') VALUES ('.implode(', ', $values).')';
                } else {
                        $query = array();
                        
                        foreach ($total as $col => $value) {
                                if (in_array($col, array('content_id', 'extension_name'))) continue;
                                
                                $query[] = $col . ' = ' . $value;
                        }
                        
                        $updated = array_keys($total);
                        
                        for ($i = 1; $i <= self::MAX_NO_CRITERIAS; $i++) {
                                if (!in_array('rates'.$i, $updated)) {
                                        $query[] = 'rates'.$i.' = 0';
                                        $query[] = 'rates'.$i.'_grade = 0';
                                        $query[] = 'counts'.$i.' = 0';
                                }
                        }
                        
                        $query = 'UPDATE #__content_rate SET '.implode(', ', $query);
                        
                        $query .= ' WHERE content_id = ' . $contentId.' AND extension_name = '.$total['extension_name'];
                }
                
                $this->_db->setQuery($query);
                $this->_db->query();
                
                $count = $total['count'];
                $rate = $total['rate_grade'] / 100 * self::RATE_SCALE * $count;
                
                if ($extensionName == 'com_k2') {
                        if ($initial) {
                                $query = 'DELETE FROM #__k2_rating WHERE itemID = '.$contentId;
                                $this->_db->setQuery($query);
                                $this->_db->query();
                                
                                $query = 'INSERT INTO #__k2_rating(itemID, rating_sum, rating_count, lastip) VALUES ('.
                                        $contentId . ',' .
                                        $rate . ', '.$count.', ' .
                                        $this->_db->Quote('') .
                                ')';
                        } else {
                                $query = 'UPDATE #__k2_rating SET' .
                                        ' rating_sum = '.$rate.
                                        ', rating_count = ' . $count .
                                        ' WHERE itemID = '.$contentId;
                        }
                        
                        $this->_db->setQuery($query);
                        $this->_db->query();
                } else if ($extensionName == 'com_content') {
                        jimport('joomla.application.component.model');
                        JModel::addIncludePath(JPATH_SITE.'/components/com_content/models');
                        $model = JModel::getInstance('Article', 'ContentModel');
                        $model->storeVote($contentId, $rate);
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
?>
