<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

interface K2FieldType {
        var $parameterFilters;
        function getParameter($field, $options);
        function maintain();
        function render($item, $values, $field, $helper, $rule = null);
        function createField($field, $helper, $rule = null);
        function createSearchField($field, $helper, $rule = null);
        function complete($field, $value);
        function reverseComplete($field, $value);
}

?>
