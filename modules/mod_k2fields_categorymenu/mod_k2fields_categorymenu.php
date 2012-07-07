<?php
//$Copyright$
 
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once dirname(__FILE__).'/helper.php';
$output = modK2fieldsCategoryMenuHelper::treerecurse($params, 0, 0, true);
require JModuleHelper::getLayoutPath('mod_k2fields_categorymenu', 'default');

?>