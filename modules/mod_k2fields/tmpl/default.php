<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="k2fSearchForm" id="k2fSearchForm<?php echo $module->id; ?>">
<form name="searchForm" method="get" action="index.php">
        <ul id="searchFormContainer">
        <? if ($showsearchcount): ?>
        <li id="k2fsearchcount"><span><?php echo JText::_('Search count') ?></span><span></span></li>
        <? endif; ?>

        <? if ($categoryselector || $showsearchfields): ?>
        <li id="k2categoriesContainer"><?php echo $categories; ?></li>
        <? endif; ?>

        <? if ($showfreetextsearch): ?>
        <li id="searchboxContainer"><input type="text" value="<?php echo $ft; ?>" name="ft" /></li>
        <? endif; ?>

        <? if ($showorderby): ?>
        <li id="orderByContainer">
                <?php echo modK2FieldsHelper::getOrderBys(); ?>
                <label for="k2f_rdir"><?php echo JText::_('Reverse'); ?></label>
                <input type="checkbox" name="rdir" id="k2f_rdir"<?php echo JFactory::getApplication()->input->get('rdir', false, 'bool') ? ' CHECKED' : ''; ?>/>
        </li>
        <? endif; ?>

        <? if ($showsearchfields): ?>
        <li id="ascontainer">
                <? if ($showfreetextsearch): ?>
                <div id="jpsearchtoggler" class="jpcollapse jppersist jpdefault<?php echo $defaultmode; ?>"><?php echo JText::_('Advanced search'); ?></div>
                <? endif; ?>
                <div class="element">
                        <div id="extraFieldsContainer">
                                <?php echo $renderedFields; ?>
                        </div>
                </div>
        </li>
        <? endif; ?>

        <li id="searchActionsContainer">
                <button id="k2fSearchBtn" class="k2fbtn k2fsearchbtn" type="button"><?php echo JText::_('Search'); ?></button>
        </li>

        </ul>
<?php if (!$app->isAdmin()) : ?>
        <input type="hidden" name="option" value="com_k2fields" />
        <input type="hidden" name="module" value="<?php echo $module->id; ?>" />
        <input type="hidden" name="view" value="itemlist" />
        <input type="hidden" name="task" value="search" />
        <input type="hidden" name="Itemid" value="<?php echo $useItemid; ?>" />
        <input type="hidden" name="exclfldft" value="<?php echo $exclfldft; ?>" />
<?php endif; ?>
</form>
</div>