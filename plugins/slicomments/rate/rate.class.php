<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class SliRate {
        const COL_NAME = 0;
        const COL_WEIGHT = 1;
        const COL_SCALES = 2;
        const COL_REQUIRED = 3;
        const COL_UI = 4;
        
        const COL_REQ_INTERVAL = 0;
        const COL_REQ_ACCESS = 1;
        const COL_REQ_MODERATE = 2;
        
        const PRECISION = 4;
        
        const K2_RATE_SCALE = 5;
        const CONTENT_RATE_SCALE = 5;
        
        const MAX_NO_CRITERIAS = 10;
        
        private $_db;
        
	public function __construct() {
                $this->_db = JFactory::getDBO();
	}
        
        public function getRate($commentIds) {
                $commentIds = (array) $commentIds;
                $commentIds = implode(',', $commentIds);
                $query = 'SELECT r.* FROM #__slicomments_item_rates r WHERE r.commentid IN (' . $commentIds . ')';
                $this->_db->setQuery($query);
                $rate = $this->_db->loadObjectList('commentid');
                return $rate;
        }
        
        public function getRates($item = null, $extension_name = null) {
                if (empty($item)) $item = JFactory::getApplication()->input->get('id', '', 'int');
                if (empty($extension_name)) $extension_name = JFactory::getApplication()->input->get('option', '', 'cmd');
                
                $itemId = is_object($item) ? $item->id : $item;
                
                if (empty($itemId)) JError::raiseError(404, JText::_("Item not found"));
                
                $query = 'SELECT * FROM #__slicomments_rates_by_item WHERE article_id = ' . $itemId.' AND extension_name = '.$this->_db->quote($extension_name);
                $this->_db->setQuery($query);
                $total = $this->_db->loadObject();
                $isInitial = empty($total);
                
                if ($isInitial) {
                        $total = new stdClass();
                        
                        $query = 'SHOW COLUMNS FROM #__slicomments_rates_by_item';
                        $this->_db->setQuery($query);
                        $cols = $this->_db->loadObjectList();
                        
                        foreach ($cols as $col)
                                $total->{$col->Field} = '';
                }
                
                return $total;
        }
        
        public function getRatingCriterias($catId) { return $this->getCriterias($catId); }
        
        public function getRatingDefinition($item, $extension_name, $includeRates = true) {
                $item = $this->getItem($item, $extension_name);
                $criterias = $this->getCriterias($item, $extension_name);
                
                if (empty($criterias)) return $criterias;
                
                if ($includeRates) $criterias['rates'] = $this->getRates($item, $extension_name);
                
                return $criterias;
        }
        
        // TODO: proper fetching of object based on extension_name
        private function getItem($item, $extension_name) {
                if (empty($item)) $item = JFactory::getApplication()->input->get('id', '', 'int');
                
                if (is_object($item)) {
                        if ($extension_name == 'com_k2') {
                                if (isset($item->category) && is_object($item->category)) {
                                        $item->catname = $item->category->name;
                                        return $item;
                                }
                        }
                }
                
                $itemId = is_object($item) ? $item->id : $item;
                
                if (empty($itemId)) JError::raiseError(404, JText::_("Item not found"));
                
                if ($extension_name == 'com_k2') {
                        $this->_db->setQuery('SELECT i.*, c.name as catname, c.access as cataccess, c.published as catpublished, c.trash as cattrash FROM #__k2_items AS i LEFT JOIN #__k2_categories AS c ON i.catid = c.id WHERE i.id = '.(int)$itemId);
                } else {
                        $this->_db->setQuery('SELECT i.*, c.id as catid, c.title as catname, c.access as cataccess, c.published as catpublished FROM #__content AS i LEFT JOIN #__categories AS c ON i.sectionid = c.id WHERE i.id = '.(int)$itemId);
                }
                
                $item = $this->_db->loadObject();
                
                return $item;
        }
        
        public function removeRates($comments, $reCalc = true) {
                if (empty($comments)) return;
                
                $commentIds = array_keys($comments);
                $this->_db->setQuery('DELETE FROM #__slicomments_item_rates WHERE commentid IN ('.$commentIds.')');
                $this->_db->query();
                
                $commentIds = array();
                $calced = array();
                
                if ($reCalc) {
                        foreach ($comments as $comment) {
                                $calc = $comment->extension_name.$comment->article_id;
                                
                                if (!in_array($calc, $calced)) {
                                        $this->calculateItemRate($comment->article_id, $comment->extension_name);
                                        $calced[] = $calc;
                                }
                        }
                }
        }
        
        public function rate($comment) {
                $extensionName = $comment->extension_name;
                $item = $this->getItem($comment->article_id, $comment->extension_name);
                $criterias = $this->getCriterias($item->catid, $comment->extension_name);
                
                $criteriaId = 'k2frate_'.$comment->article_id.'_';
                $rates = array();
                $ratesGrade = array();
                $ratesCols = array();
                $ratesGradeCols = array();
                $ratesCount = 0;
                
                foreach ($criterias as $i => $criteria) {
                        $rate = JRequest::getFloat($criteriaId . $i, 0);
                        
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
                        'INSERT INTO #__slicomments_item_rates(commentid, '.$ratesCols.', rate, rate_grade, rates_count, rate_max) VALUES (' . 
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
        
        public function calculateItemRate($itemId, $extensionName) {
                $criterias = $this->getRatingDefinition($itemId, $extensionName, true);
                $total = $criterias['rates'];
                
                unset($criterias['rates']);
                
                $cols = array('AVG(r.rate) as rate, AVG(r.rate_grade) as rate_grade, COUNT(r.rate) as count');
                
                foreach ($criterias as $i => $criteria) 
                        $cols[] = 'AVG(r.rate'.($i + 1).') as rates'.($i + 1).', AVG(r.rate'.($i + 1).'_grade) as rates'.($i + 1).'_grade, COUNT(r.rate'.($i + 1).') as counts'.($i + 1);
                
                $query = 
                        'SELECT c.article_id, '.implode(', ', $cols) .
                        ' FROM #__slicomments_item_rates r INNER JOIN #__slicomments c ON r.commentid = c.id ' .
                        ' WHERE c.article_id = '.$itemId.' AND c.extension_name = '.$this->_db->quote($extensionName).' AND c.status = 1 '.
                        ' GROUP BY c.article_id, c.extension_name';
                
                $this->_db->setQuery($query);
                $rateRow = $this->_db->loadObject();
                
                if (empty($rateRow)) {
                        $query = 'DELETE FROM #__slicomments_rates_by_item WHERE article_id = '.$itemId.' AND extension_name = '.$this->_db->quote($extensionName);
                        $this->_db->setQuery($query);
                        $this->_db->query();
                        
                        if ($extensionName == 'com_k2') {
                                $query = 'DELETE FROM #__k2_rating WHERE itemID = '.$itemId;
                        } else if ($extensionName == 'com_content') {
                                $query = 'DELETE FROM #__content_rating WHERE content_id = '.$itemId;
                        }
                        
                        $this->_db->setQuery($query);
                        $this->_db->query();
                        
                        return;
                }
                
                $itemId = $rateRow->article_id;
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
                $initial = empty($total['article_id']);
                $total['article_id'] = $itemId;
                $total['extension_name'] = $this->_db->quote($extensionName);
                $total = array_filter($total);
                
                if ($initial) {
                        $values = array_values($total);
                        $cols = array_keys($total);
                        $query = 'INSERT INTO #__slicomments_rates_by_item('.implode(', ', $cols).') VALUES ('.implode(', ', $values).')';
                } else {
                        $query = array();
                        
                        foreach ($total as $col => $value) {
                                if (in_array($col, array('article_id', 'extension_name'))) continue;
                                
                                $query[] = $col . ' = ' . $value;
                        }
                        
                        $query = 'UPDATE #__slicomments_rates_by_item SET '.implode(', ', $query).' WHERE article_id = ' . $itemId.' AND extension_name = '.$total['extension_name'];
                }
                
                $this->_db->setQuery($query);
                $this->_db->query();
                
                $count = $total['count'];
                $rate = $total['rate_grade'] / 100;
                //  * self::K2_RATE_SCALE * $count, self::PRECISION)
                if ($extensionName == 'com_k2') {
                        $input = JFactory::getApplication()->input; 
                        $oldItemId = $input->get('itemID', '', 'int');
                        
                        $rate *= self::K2_RATE_SCALE;
                        $input->set('itemID', $itemId);
                        $input->set('user_rating', $rate);

                        K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
                        $model = K2Model::getInstance('item', 'K2Model');
                        $model->vote();      
                        
                        $input->set('itemID', $oldItemId);
                        
                        // Update native K2 rating entry

//                        if ($initial) {
//                                $query = 'DELETE FROM #__k2_rating WHERE itemID = '.$itemId;
//                                $this->_db->setQuery($query);
//                                $this->_db->query();
//                                
//                                $query = 'INSERT INTO #__k2_rating(itemID, rating_sum, rating_count, lastip) VALUES ('.
//                                        $itemId . ',' .
//                                        $rate . ', '.$count.', ' .
//                                        $this->_db->Quote('') .
//                                ')';
//                        } else {
//                                $query = 'UPDATE #__k2_rating SET' .
//                                        ' rating_sum = '.$rate .
//                                        ', rating_count = ' . $count .
//                                        ', lastip = ' . $this->_db->Quote('') .
//                                        ' WHERE itemID = '.$itemId;
//                        }
                } else if ($extensionName == 'com_content') {
                        $rate *= self::CONTENT_RATE_SCALE;
                        K2Model::addIncludePath(JPATH_SITE.'/components/com_content/models');
                        $model = K2Model::getInstance('Article', 'ContentModel');
                        $model->storeVote($itemId, $rate);
                }
                
                $this->_db->setQuery($query);
                $this->_db->query();
        }
        
        // TODO: proper fetching of criteria based on extension_name
        private function getCriterias($data, $extension_name) {
                if (is_object($data)) $catId = $data->catid;
                else $catId = $data;
                
                $tokens = array('%item%', '%cat%');
                
                if (is_object($data)) {
                        $tokenValues = array($data->title, $data->catname);
                } else {
                        $tokenValues = array();
                }
                
                $criterias = plgSlicommentsRate::criterias($catId, $extension_name);
                
                if (empty($criterias)) return;
                
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

                        // requried
                        $criteria[self::COL_REQUIRED] = count($criteria) <= 3 ? false : (bool) $criteria[self::COL_REQUIRED];
                        $criteria[self::COL_UI] = trim(count($criteria) <= 4 ? 'select' : $criteria[self::COL_UI]);
                }
                
                return $criterias;
        }
        
        public static function getColumn($array, $index, $unique = false, $criterias = array(), $maintainIndex = false) {
                $result = array();
                
                $array = self::getRow($array, $criterias);
                
                if (is_array($array)) {
                        foreach ($array as $arrInd => $item) {
                                $passed = true;
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
