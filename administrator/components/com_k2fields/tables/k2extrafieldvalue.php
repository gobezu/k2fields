<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class TableK2ExtraFieldValue extends JTable {
	var $id = null;
	var $itemid = null;
	var $fieldid = null;
	var $listindex = null;
	var $partindex = null;
        var $index = null;
	var $value = null;
        var $lat = null;
        var $lng = null;
        var $txt = null;
        var $img = null;
        var $datum = null;
        var $related = null;
        var $duration = null;

	function __construct(&$db) {
		parent::__construct('#__k2_extra_fields_values', 'id', $db);
	}
}

?>
