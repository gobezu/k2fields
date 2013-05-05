<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class modK2FieldsNavigatorHelper {
        public static function replace($value, $format, $imageFormat, $addScale = false, $maxCount = 0, $scale = 0) {
                static $elements = array('[text]', '[image]', '[value]', '[count]');

                if ($imageFormat && $value->img) {
                        $format = str_replace('[image]', $imageFormat, $format);
                } else {
                        $format = str_replace('[image]', '', $format);
                }

                $text = K2FieldsModelFields::value($value, 'text');
                $val = K2FieldsModelFields::value($value, 'value');
                $image = K2FieldsModelFields::value($value, 'img');
                $count = K2FieldsModelFields::value($value, 'count');

                $format = str_replace(
                        $elements,
                        array($text, $image, $val, $value->count),
                        $format
                );

                if ($addScale && $maxCount) {
                        if (!$scale) $scale = 10;
                        $scale = (int) ($count / $maxCount * $scale);
                        $format = '<span class="navtag'.$scale.'">'.$format.'</span>';
                }

                return $format;
        }

        public static function getFieldValues(
                $fieldId,
                $cats,
                $excludeValues,
                $dontShowEmpty,
                $showCount) {

                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $field = $model->getFieldsById($fieldId);
                $valid = K2FieldsModelFields::value($field, 'valid');
                $values = K2FieldsModelFields::value($field, $valid == 'list' ? 'tree' : 'values');

                $db = JFactory::getDbo();
                $now = JFactory::getDate()->toMySQL();
                $user = JFactory::getUser();
                $nullDate = $db->getNullDate();

                $query = 'SELECT value, COUNT(itemid) AS cnt FROM (SELECT DISTINCT v.value, v.itemid FROM #__k2_extra_fields_values AS v INNER JOIN #__k2_items AS i ON v.itemid = i.id ';

                if ($cats) {
                        $query .= ' INNER JOIN #__k2_categories c ON i.catid = c.id ';
                }

                $query .= ' WHERE (v.fieldid = '.$fieldId.') AND ';
                $query .= ' (i.published = 1 AND i.trash = 0) AND ';
                $query .= ' (i.publish_up = '.$db->quote($nullDate).' OR i.publish_up <= '.$db->quote($now).') AND ';
                $query .= ' (i.publish_down = '.$db->quote($nullDate).' OR i.publish_down >= '.$db->quote($now).') AND ';
                $query .= ' (i.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')) AND ';

                if ($cats) {
                        $query .= ' (c.id IN (' . implode(',', $cats) . ')) AND ';
                        $query .= ' (c.published = 1 AND c.trash = 0) AND ';
                        $query .= ' (c.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')) ';
                }

                $query .= ') AS c GROUP BY value';

                $db->setQuery($query);
                $counts = $db->loadObjectList('value');

                $isCustomValues = false;

                if (!$values) {
                        $values = $counts;
                        $isCustomValues = true;
                }

                foreach ($values as $i => $value) {
                        if (in_array($value->value, $excludeValues)) {
                                unset($values[$i]);
                                continue;
                        }

                        $count = isset($counts[$value->value]) ? $counts[$value->value]->cnt : 0;

                        if ($dontShowEmpty && $count == 0) {
                                unset($values[$i]);
                                continue;
                        }

                        K2FieldsModelFields::setValue($value, 'count', $count);

                        if ($isCustomValues) {
                                K2FieldsModelFields::setValue($value, 'text', $value->value);
                                K2FieldsModelFields::setValue($value, 'img', '');
                        }
                }

                $values = array_filter($values);

                return $values;
        }

}
