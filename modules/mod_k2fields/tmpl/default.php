<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<form name="searchForm" method="get" action="index.php">
        <ul id="searchFormContainer">
        <? if ($categoryselector || $showsearchfields): ?>
        <li id="k2categoriesContainer"><?= $categories ?></li>
        <? endif; ?>
        
        <? if ($showfreetextsearch): ?>
        <li id="searchboxContainer"><input type="text" value="<?= $ft ?>" name="ft" /></li>
        <? endif; ?>
        
        <? if ($showorderby): ?>
        <li id="orderByContainer">
                <?= modK2FieldsHelper::getOrderBys(); ?>
                <label for="k2f_rdir"><?= JText::_('Reverse') ?></label>
                <input type="checkbox" name="rdir" id="k2f_rdir"<?= JRequest::getBool('rdir', false) ? ' CHECKED' : '' ?>/>
        </li>
        <? endif; ?>
        
        <? if ($showsearchfields): ?>
        <li id="ascontainer">
                <div id="jpsearchtoggler" class="jpcollapse jppersist jpdefault<?= $defaultmode ?>"><?= JText::_('Advanced search') ?></div>
                <div class="element">
                        <div id="extraFieldsContainer">
                                <?php echo $renderedFields; ?>
                        </div>
                </div>
        </li>
        <? endif; ?>
        
        <li id="searchActionsContainer">
                <button id="k2fSearchBtn" class="k2fbtn k2fsearchbtn" type="submit"><?= JText::_('Search') ?></button>
        </li>
        
        </ul>
<?php if (!$app->isAdmin()) : ?>  
        <input type="hidden" name="option" value="com_k2fields" />
        <input type="hidden" name="module" value="<?= $module->id ?>" />
        <input type="hidden" name="view" value="itemlist" />
        <input type="hidden" name="task" value="search" />
        <input type="hidden" name="Itemid" value="<?= $useItemid ?>" />
        <input type="hidden" name="exclfldft" value="<?= $exclfldft ?>" />
<?php endif; ?>        
</form>