<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class modK2FieldsHelper {
        public static function getCategoriesSelector(
                $categoryselector,
                $defaultCategory,
                $excludes,
                $includeDefaultMenuItem,
                $categoriesId = 'cid',
                $text = '',
                $keepDefaultCategoryInHome) {
                return JprovenUtility::getK2CategoriesSelector(
                        $categoryselector,
                        $defaultCategory,
                        $excludes,
                        $includeDefaultMenuItem,
                        $categoriesId,
                        false,
                        $text ? JText::_($text) : '',
                        false,
                        false,
                        true,
                        $keepDefaultCategoryInHome
                );
        }

        public static function getOrderBys() {
                $noneFields = array(
                    array('value' => '', 'text' => JText::_('Order by')),
                    array('value' => 'alpha', 'text' => JText::_('Title')),
                    array('value' => 'hits', 'text' => JText::_('Most read')),
                    array('value' => 'best', 'text' => JText::_('Highest rated')),
                    array('value' => 'date', 'text' => JText::_('Published')),
                    array('value' => 'mod', 'text' => JText::_('Modified')),
                    array('value' => 'edate', 'text' => JText::_('Ends on')),
                    array('value' => 'rand', 'text' => JText::_('Feeling lucky'))
                );

                $ord = JFactory::getApplication()->input->get('ord', '', 'word');

                return JHTML::_('select.genericlist', $noneFields, 'ord', '', 'value', 'text', $ord);
        }

        public static function getFields($defaultCategory, $isBasedOnMenu, $includedefaultmenuitem, $excludes, $keepDefaultCategoryInHome) {
                $cid = JprovenUtility::getK2CurrentCategory($defaultCategory, $isBasedOnMenu, $includedefaultmenuitem, $excludes, $keepDefaultCategoryInHome);
                $model = K2Model::getInstance('fields', 'K2FieldsModel');
                $fields = $model->getFieldsByGroup($cid, 'search');

                $output = JprovenUtility::renderK2fieldsForm($fields, 'searchfields', true, $cid);

                return $output;
        }
}
