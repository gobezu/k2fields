<?php

//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

abstract class K2fieldsFieldType {
        var $_db = null; 
        
	public function __construct($db = null) {
                if (empty($db)) $db = JFactory::getDBO();
                $this->_db = $db;
	}
}
