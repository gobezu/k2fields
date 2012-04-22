<?php
//$Copyright$
 
// no direct access
defined('_JEXEC') or die('Restricted access');

$user = JFactory::getUser();

$lang = JFactory::getLanguage();
$lang->load('com_users', JPATH_SITE);

require_once JPATH_SITE.'/components/com_k2/helpers/permissions.php';

if (JRequest::getCmd('option') != 'com_k2') K2HelperPermissions::setPermissions();

$canAdd = K2HelperPermissions::canAddItem();

if ($canAdd):
        $app = JFactory::getApplication();
        $catId = $app->getUserStateFromRequest('com_k2itemsfilter_category', 'catid', 0, 'int');
        $canPublish = K2HelperPermissions::canPublishItem($catId);
        $width = $canPublish ? '990' : '700';
        $launcherID = 'k2fEditorLauncher';
        $categoriesSelector = JprovenUtility::getK2PostCategoriesSelector($launcherID, 'Post item...');
        $document = JFactory::getDocument();
        $document->addScriptDeclaration('
        window.addEvent("domready", function(){
                var sel = document.getElement("select[name='. $launcherID.']");
                sel.addEvent("change", function() {
                        window.parent.SqueezeBox.close();

                        var 
                                href = "'.JURI::base().'index.php?option=com_k2&view=item&task=add&tmpl=component", 
                                opt = document.id(this.options[this.selectedIndex]), 
                                catid = opt.get("value"), 
                                init = opt.get("init-state")
                                ;

                        if (!catid) {
                                alert("'.JText::_("Select category").'");
                                return false;
                        }

                        var _href = href + "&catid="+catid + (init ? "&" + init : "");

                        window.parent.SqueezeBox.open(_href, {handler:"iframe", size:{x:'. $width.',y:650}});

                        this.selectedIndex = 0;
                });
        });
        ');
endif;

$uri = JFactory::getURI();
$return = base64_encode($uri->toString(array('path', 'query', 'fragment')));
?>
<div id="k2fut<?php echo $module->id; ?>" class="k2futmod <?php echo $params->get('moduleclass_sfx'); ?>">
        <ul class="k2fut">
                <?php if ($canAdd): ?>
                <li id="k2futcategory">
                        <?php echo $categoriesSelector; ?>
                </li>
                <?php endif; ?>
                <li id="k2futitems">
                        <a href="<?php echo JRoute::_(K2FieldsHelperRoute::getUserRoute($user->id)); ?>"><?php echo JText::_('Your items'); ?></a>
                </li>
                <li id="k2futprofile">
                        <a title="<?php echo JText::_('COM_USERS_PROFILE_DEFAULT_LABEL') ?>" href="<?php echo JRoute::_('index.php?option=com_users&view=profile&layout=edit'); ?>"><?php echo JText::_('COM_USERS_PROFILE_DEFAULT_LABEL') ?></a>
                </li>          
                <li id="k2futlogout">
                        <form action="index.php" method="post">
                        <input type="submit" name="Submit" value="<?php echo JText::_('JLOGOUT'); ?>" />
                        <input type="hidden" name="option" value="com_users" />
                        <input type="hidden" name="task" value="user.logout" />
                        <input type="hidden" name="return" value="<?php echo $return; ?>" />
                        <?php echo JHTML::_( 'form.token' ); ?>
                        </form>
                </li>
        </ul>
</div>