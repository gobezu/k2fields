<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require dirname(__FILE__) . '/NestedSetDbTable/Abstract.php';

class K2FieldsListOp extends NestedSetDbTable_Abstract {
        public function __construct($name, $primary, $left, $right) {
                $this->_name = $name;
                $this->_primary = $primary;
                $this->_left = $left;
                $this->_right = $right;
                
                $config = JFactory::getConfig();
                
                $dbh = new PDO(
                        'mysql:host='.$config->getValue('config.host').';dbname='.$config->getValue('config.db'), 
                        $config->getValue('config.user'), 
                        $config->getValue('config.password')
                );
                
                parent::__construct($dbh);                 
        }
}

?>
