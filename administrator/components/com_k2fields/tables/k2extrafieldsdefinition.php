<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class TableK2ExtraFieldsDefinition extends JTable {
	var $id = null;
	var $definition = null;

	function __construct(&$db) {
		parent::__construct('#__k2_extra_fields_definition', 'id', $db);
	}
}

?>
