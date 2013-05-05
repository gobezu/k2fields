<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
$counts = JprovenUtility::getColumn($values, 'count');
$maxCount = max($counts);
$scale = $params->get('scale', 5);
?>
<div id="k2fModuleBox<?php echo $module->id; ?>" class="k2fNavigator <?php if($params->get('moduleclass_sfx')) echo $params->get('moduleclass_sfx'); ?>">
<ul>
<?php foreach ($values as $key => $value): ?>
        <li>
        <?php
        echo modK2FieldsNavigatorHelper::replace(
                $value,
                $showFormat,
                $imageFormat,
                true,
                $maxCount,
                $scale
        );
        ?>
        </li>
<?php endforeach; ?>
</ul>
</div>