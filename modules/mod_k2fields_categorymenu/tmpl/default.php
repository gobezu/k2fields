<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
$css = 'k2fcatmenu'.($params->get('moduleclass_sfx') ? ' ' . $params->get('moduleclass_sfx') : '');
?>
<div id="k2ModuleBox<?php echo $module->id; ?>" class="<?php echo $css; ?>">
        <?php echo $output; ?>
</div>