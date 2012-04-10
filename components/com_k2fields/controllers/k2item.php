<?php
// $Copyright$
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class K2FieldsControllerK2item extends JController {
        function __construct($config = array()) {
                $config['base_path'] = JPATH_SITE.'/components/com_k2fields';
                parent::__construct($config);
        }
}
