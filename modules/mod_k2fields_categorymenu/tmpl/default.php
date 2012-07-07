<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div id="k2ModuleBox<?php echo $module->id; ?>"<?php if($params->get('moduleclass_sfx')) echo ' class="'.$params->get('moduleclass_sfx').'"'; ?>>
        <?php echo $output; ?>
</div>