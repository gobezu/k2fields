<?php
// $Copyright$
/* based on the following original copyright and where editing have made to accommodate:
 * 1. removal of certain tabs
 * 2. k2item provision
 * 3. removal of J15 dependency
 */
/**
 * @version		$Id: itemform.php 1812 2013-01-14 18:45:06Z lefteris.kavadas $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
JprovenUtility::load('k2fieldsform.css', 'css');
// $template = JApplication::getInstance('site')->getTemplate();
// $theme = JFactory::getApplication()->input->get('theme', 'default', 'word');
// $dirs = array(
//     JPATH_SITE.'/templates/'.$template.'/html/com_k2fields/templates/'.$theme.'/',
//     JPATH_SITE.'/templates/'.$template.'/css/'
// );

// JprovenUtility::incl($dirs, 'k2fieldsform', 'css', '', true);

// honoring preselected catid
$catId = $app->getUserStateFromRequest('com_k2itemsfilter_category', 'catid', 0, 'int');
$catTitle = '';

if (!$this->row->id && !empty($catId)) {
        $this->row->catid = $catId;
        $pat = '#(\<option\s+value=([\"\'])'.$catId.'\2)([^\>]\>)#i';
        $this->lists['categories'] = preg_replace($pat, '$1 SELECTED $3', $this->lists['categories']);
} else if ($this->row->id) {
        $catId = $this->row->catid;
}

if ($catId) {
        $category = JTable::getInstance('K2Category', 'Table');
        $category->load($catId);
        $catTitle = $category->name;
        $catImage = $category->image;
        if ($catImage) {
                $catImage = '<img style="display:inline;margin:0px;vertical-align:middle;" src="'.JURI::root(true).'/media/k2/categories/'.$category->image.'" alt="'.$category->name.'" class="k2AdminImage" />';
        }
//        $pat = '#\<option\s+value=([\"\'])'.$catId.'\1[^\>]+\>([^\<]+)\<\/option\>#i';
//        preg_match($pat, $this->lists['categories'], $catTitle);
//        $catTitle = str_replace(array('&nbsp;', '- '), '', $catTitle[2]);
//        $catTitle = JprovenUtility::nize($catTitle, 1);
}

$canPublish = $app->isSite() ? K2HelperPermissions::canPublishItem($catId) : true;

require_once JPATH_SITE.'/components/com_k2fields/helpers/helper.php';

$tabs = K2FieldsHelper::getTabs($catId);
$availableTabs = K2FieldsHelper::$availableTabs;

$parameterNames = array(
    'content' => array('showContentTab', 'K2_CONTENT'),
    'image' => array('showImageTab', 'K2_IMAGE'),
    'gallery'=> array('showImageGalleryTab', 'K2_IMAGE_GALLERY'),
    'video'=> array('showVideoTab', 'K2_VIDEO'),
    'extrafields'=>array('showExtraFieldsTab', 'K2_EXTRA_FIELDS'),
    'attachments'=>array('showAttachmentsTab', 'K2_ATTACHMENTS'),
    'plugins'=>array('showK2Plugins', 'K2_PLUGINS')
);

$tabsCnt = '';

if (!empty($tabs) && !empty($catId)) {
        $excludes = $tabs['excludes'];
        $tabs = $tabs['tabs'];
        $tabsCnt = ' class="tabscnt'.count($tabs).'"';

        if (!in_array($catId, $excludes) && !empty($tabs)) {
                $names = array();

                foreach ($tabs as $i => &$tab) {
                        $tab = explode('=', $tab);
                        if (count($tab) == 2) $names[$i] = $tab[1];
                        $tab = $tab[0];
                }

                unset($tab);

                foreach ($availableTabs as $i => $tab) {
                        $ind = array_search($tab, $tabs);

                        if ($ind === false) {
                                unset($availableTabs[$i]);
                                $this->params->set($parameterNames[$tab][0], false);
                        } else if (isset($names[$ind])) {
                               $parameterNames[$tab][1] = $names[$ind];
                        }
                }
        }
}

foreach ($availableTabs as $tab) {
        $this->params->set($parameterNames[$tab][0], true);
        $this->params->set($tab.'tabname', $parameterNames[$tab][1]);
}

JHTML::_('behavior.tooltip');

// k2item form
$k2itemForm = null;
jimport('joomla.form.form');
$k2itemForm = JForm::getInstance('k2itemForm', JPATH_ADMINISTRATOR.'/components/com_k2fields/models/item.xml');
$values = array('params'=>json_decode($this->row->params));
$k2itemForm->bind($values);

$document = & JFactory::getDocument();
$document->addScriptDeclaration("
	Joomla.submitbutton = function(pressbutton){
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}
		if (\$K2.trim(\$K2('#title').val()) == '') {
			alert( '".JText::_('K2_ITEM_MUST_HAVE_A_TITLE', true)."' );
		}
		else if (\$K2.trim(\$K2('#catid').val()) == '0') {
			alert( '".JText::_('K2_PLEASE_SELECT_A_CATEGORY', true)."' );
		}
		else {
			syncExtraFieldsEditor();
			\$K2('#selectedTags option').attr('selected', 'selected');
			submitform( pressbutton );
		}
	}
");

$_input = JFactory::getApplication()->input;
$task = $_input->get('task', '', 'word');

if ($task == 'edit') {
        $sel = $this->lists['categories'];
        $sel = trim($sel);
        $sel = str_ireplace('<select ', '<select disabled="disabled" ', $sel);
        $this->lists['categories'] = $sel;
} else if ($task == 'add') {
        $this->lists['categories'] = JprovenUtility::getK2PostCategoriesSelector('catid', JText::_('K2_SELECT_CATEGORY'));
}

$isAdmin = JFactory::getApplication()->isAdmin();

?>
<form action="<?php echo $isAdmin ? "" : JURI::root(true)."/"; ?>index.php" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">
	<?php if($this->mainframe->isSite()): ?>
	<div id="k2FrontendContainer" <?php echo $tabsCnt;?>>
		<div id="k2Frontend">
			<table class="k2FrontendToolbar" cellpadding="2" cellspacing="4">
				<tr>
					<td id="toolbar-save" class="button">
						<a class="toolbar" href="#" onclick="Joomla.submitbutton('save'); return false;"> <span title="<?php echo JText::_('K2_SAVE'); ?>" class="icon-32-save icon-save"></span> <?php echo JText::_('K2_SAVE'); ?> </a>
					</td>
					<td id="toolbar-cancel" class="button">
                                                <a class="toolbar" href="#"> <span title="<?php echo JText::_('K2_CANCEL'); ?>" class="icon-32-cancel"></span> <?php echo JText::_('K2_CLOSE'); ?> </a>
					</td>
				</tr>
			</table>
			<div id="k2FrontendEditToolbar">
				<h2 class="header<?php echo $catImage ? '' : ' icon-48-k2'; ?>">
                                        <?php if ($catImage):  echo $catImage; endif; ?>
					<?php echo str_replace('Item', $catTitle, ($_input->get('cid', '', 'int')) ? JText::_('K2_EDIT_ITEM') : JText::_('K2_ADD_ITEM')); ?>
				</h2>
			</div>
			<div class="clr"></div>
			<hr class="sep" />
			<?php if(!$this->permissions->get('publish')): ?>
			<div id="k2FrontendPermissionsNotice">
				<p><?php echo JText::_('K2_FRONTEND_PERMISSIONS_NOTICE'); ?></p>
			</div>
			<?php endif; ?>
			<?php endif; ?>
                        <?php if ($canPublish) : ?>
			<div id="k2ToggleSidebarContainer"> <a href="#" id="k2ToggleSidebar"><?php echo JText::_('K2_TOGGLE_SIDEBAR'); ?></a> </div>
                         <?php
                                else:
                                $this->params->set('sideBarDisplayFrontend', 0);
                                endif;
                        ?>
			<table cellspacing="0" cellpadding="0" border="0" class="adminFormK2Container">
				<tbody>
					<tr>
						<td>
							<table class="adminFormK2<?php echo $isAdmin ? " table" : ""; ?>">
								<tr>
									<td class="adminK2LeftCol">
										<label for="title"><?php echo JText::_('K2_TITLE'); ?></label>
									</td>
									<td class="adminK2RightCol">
										<input class="text_area k2TitleBox" type="text" name="title" id="title" maxlength="250" value="<?php echo $this->row->title; ?>" />
									</td>
								</tr>
								<tr>
									<td class="adminK2LeftCol">
										<label for="alias"><?php echo JText::_('K2_TITLE_ALIAS'); ?></label>
									</td>
									<td class="adminK2RightCol">
										<input class="text_area k2TitleAliasBox" type="text" name="alias" id="alias" maxlength="250" value="<?php echo $this->row->alias; ?>" />
									</td>
								</tr>
								<tr>
									<td class="adminK2LeftCol">
										<label><?php echo JText::_('K2_CATEGORY'); ?></label>
									</td>
									<td class="adminK2RightCol">
										<?php echo $this->lists['categories']; ?>
									</td>
								</tr>
								<tr>
									<td class="adminK2LeftCol">
										<label><?php echo JText::_('K2_TAGS'); ?></label>
									</td>
									<td class="adminK2RightCol">
										<?php if($this->params->get('taggingSystem')): ?>
										<!-- Free tagging -->
										<ul class="tags">
											<?php if(isset($this->row->tags) && count($this->row->tags)): ?>
											<?php foreach($this->row->tags as $tag): ?>
											<li class="tagAdded">
												<?php echo $tag->name; ?>
												<span title="<?php echo JText::_('K2_CLICK_TO_REMOVE_TAG'); ?>" class="tagRemove">x</span>
												<input type="hidden" name="tags[]" value="<?php echo $tag->name; ?>" />
											</li>
											<?php endforeach; ?>
											<?php endif; ?>
											<li class="tagAdd">
												<input type="text" id="search-field" />
											</li>
											<li class="clr"></li>
										</ul>
										<span class="k2Note"> <?php echo JText::_('K2_WRITE_A_TAG_AND_PRESS_RETURN_OR_COMMA_TO_ADD_IT'); ?> </span>
										<?php else: ?>
										<!-- Selection based tagging -->
										<?php if( !$this->params->get('lockTags') || $this->user->gid>23): ?>
										<div style="float:left;">
											<input type="text" name="tag" id="tag" />
											<input type="button" id="newTagButton" value="<?php echo JText::_('K2_ADD'); ?>" />
										</div>
										<div id="tagsLog"></div>
										<div class="clr"></div>
										<span class="k2Note"> <?php echo JText::_('K2_WRITE_A_TAG_AND_PRESS_ADD_TO_INSERT_IT_TO_THE_AVAILABLE_TAGS_LISTNEW_TAGS_ARE_APPENDED_AT_THE_BOTTOM_OF_THE_AVAILABLE_TAGS_LIST_LEFT'); ?> </span>
										<?php endif; ?>
										<table cellspacing="0" cellpadding="0" border="0" id="tagLists">
											<tr>
												<td id="tagListsLeft">
													<span><?php echo JText::_('K2_AVAILABLE_TAGS'); ?></span> <?php echo $this->lists['tags'];	?>
												</td>
												<td id="tagListsButtons">
													<input type="button" id="addTagButton" value="<?php echo JText::_('K2_ADD'); ?> &raquo;" />
													<br />
													<br />
													<input type="button" id="removeTagButton" value="&laquo; <?php echo JText::_('K2_REMOVE'); ?>" />
												</td>
												<td id="tagListsRight">
													<span><?php echo JText::_('K2_SELECTED_TAGS'); ?></span> <?php echo $this->lists['selectedTags']; ?>
												</td>
											</tr>
										</table>
										<?php endif; ?>
									</td>
								</tr>
								<?php if($this->mainframe->isAdmin() || ($this->mainframe->isSite() && $this->permissions->get('publish'))): ?>
								<tr>
									<td class="adminK2LeftCol">
										<label for="featured"><?php echo JText::_('K2_IS_IT_FEATURED'); ?></label>
									</td>
									<td class="adminK2RightCol">
										<?php echo $this->lists['featured']; ?>
									</td>
								</tr>
								<tr>
									<td class="adminK2LeftCol">
										<label><?php echo JText::_('K2_PUBLISHED'); ?></label>
									</td>
									<td class="adminK2RightCol">
										<?php echo $this->lists['published']; ?>
									</td>
								</tr>
								<?php endif; ?>
							</table>

							<!-- Tabs start here -->
							<div class="simpleTabs" id="k2Tabs">
								<ul class="simpleTabsNavigation">
                                                                        <?php if ($this->params->get('showContentTab')): ?>
									<li id="tabContent"><a href="#k2Tab1"><?php echo JText::_($this->params->get('contenttabname')); ?></a></li>
                                                                        <?php endif; ?>
									<?php if ($this->params->get('showImageTab')): ?>
									<li id="tabImage"><a href="#k2Tab2"><?php echo JText::_($this->params->get('imagetabname')); ?></a></li>
									<?php endif; ?>
									<?php if ($this->params->get('showImageGalleryTab')): ?>
									<li id="tabImageGallery"><a href="#k2Tab3"><?php echo JText::_($this->params->get('gallerytabname')); ?></a></li>
									<?php endif; ?>
									<?php if ($this->params->get('showVideoTab')): ?>
									<li id="tabVideo"><a href="#k2Tab4"><?php echo JText::_($this->params->get('videotabname')); ?></a></li>
									<?php endif; ?>
									<?php if ($this->params->get('showExtraFieldsTab')): ?>
									<li id="tabExtraFields"><a href="#k2Tab5"><?php echo JText::_($this->params->get('extrafieldstabname')); ?></a></li>
									<?php endif; ?>
									<?php if ($this->params->get('showAttachmentsTab')): ?>
									<li id="tabAttachments"><a href="#k2Tab6"><?php echo JText::_($this->params->get('attachmentstabname')); ?></a></li>
									<?php endif; ?>
									<?php if(count(array_filter($this->K2PluginsItemOther)) && $this->params->get('showK2Plugins')): ?>
									<li id="tabPlugins"><a href="#k2Tab7"><?php echo JText::_($this->params->get('pluginstabname')); ?></a></li>
									<?php endif; ?>
								</ul>

                                                                <?php if ($this->params->get('showContentTab')): ?>
								<!-- Tab content -->
								<div class="simpleTabsContent" id="k2Tab1">
									<?php if($this->params->get('mergeEditors')): ?>
									<div class="k2ItemFormEditor"> <?php echo $this->text; ?>
										<div class="dummyHeight"></div>
										<div class="clr"></div>
									</div>
									<?php else: ?>
									<div class="k2ItemFormEditor"> <span class="k2ItemFormEditorTitle"> <?php echo JText::_('K2_INTROTEXT_TEASER_CONTENTEXCERPT'); ?> </span> <?php echo $this->introtext; ?>
										<div class="dummyHeight"></div>
										<div class="clr"></div>
									</div>
									<div class="k2ItemFormEditor"> <span class="k2ItemFormEditorTitle"> <?php echo JText::_('K2_FULLTEXT_MAIN_CONTENT'); ?> </span> <?php echo $this->fulltext; ?>
										<div class="dummyHeight"></div>
										<div class="clr"></div>
									</div>
									<?php endif; ?>
									<?php if (count($this->K2PluginsItemContent)): ?>
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemContent as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
									<div class="clr"></div>
								</div>
                                                                <?php endif; ?>
								<?php if ($this->params->get('showImageTab')): ?>
								<!-- Tab image -->
								<div class="simpleTabsContent" id="k2Tab2">
									<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>">
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_ITEM_IMAGE'); ?>
											</td>
											<td>
												<input type="file" name="image" class="fileUpload" />
												<i>(<?php echo JText::_('K2_MAX_UPLOAD_SIZE'); ?>: <?php echo ini_get('upload_max_filesize'); ?>)</i>
												<br />
												<br />
												<input type="text" name="existingImage" id="existingImageValue" class="text_area" readonly />
												<input type="button" value="<?php echo JText::_('K2_BROWSE_SERVER'); ?>" id="k2ImageBrowseServer"  />
												<br />
												<br />
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_ITEM_IMAGE_CAPTION'); ?>
											</td>
											<td>
												<input type="text" name="image_caption" size="30" class="text_area" value="<?php echo $this->row->image_caption; ?>" />
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_ITEM_IMAGE_CREDITS'); ?>
											</td>
											<td>
												<input type="text" name="image_credits" size="30" class="text_area" value="<?php echo $this->row->image_credits; ?>" />
											</td>
										</tr>
										<?php if (!empty($this->row->image)): ?>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_ITEM_IMAGE_PREVIEW'); ?>
											</td>
											<td>
												<a class="modal" rel="{handler: 'image'}" href="<?php echo $this->row->image; ?>" title="<?php echo JText::_('K2_CLICK_ON_IMAGE_TO_PREVIEW_IN_ORIGINAL_SIZE'); ?>">
                                                                                                        <img alt="<?php echo $this->row->title; ?>" src="<?php echo $this->row->thumb; ?>" class="k2AdminImage" />
                                                                                                </a>
												<input type="checkbox" name="del_image" id="del_image" />
												<label for="del_image"><?php echo JText::_('K2_CHECK_THIS_BOX_TO_DELETE_CURRENT_IMAGE_OR_JUST_UPLOAD_A_NEW_IMAGE_TO_REPLACE_THE_EXISTING_ONE'); ?></label>
											</td>
										</tr>
										<?php endif; ?>
									</table>
									<?php if (count($this->K2PluginsItemImage)): ?>
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemImage as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php if ($this->params->get('showImageGalleryTab')): ?>
								<!-- Tab image gallery -->
								<div class="simpleTabsContent" id="k2Tab3">
									<?php if ($this->lists['checkSIG']): ?>
									<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>" id="item_gallery_content">
										<tr>
											<td align="right" valign="top" class="key">
												<?php echo JText::_('K2_UPLOAD_A_ZIP_FILE_WITH_IMAGES'); ?>
											</td>
											<td valign="top">
												<input type="file" name="gallery" class="fileUpload" />
												<i>(<?php echo JText::_('K2_MAX_UPLOAD_SIZE'); ?>: <?php echo ini_get('upload_max_filesize'); ?>)</i>
												<br />
                                                                                                <?php if($isAdmin && $this->sigPro): ?>
												<a class="modal" rel="{handler: 'iframe', size: {x: 940, y: 560}}" href="index.php?option=com_sigpro&view=galleries&task=create&newFolder=<?php echo $this->sigProFolder; ?>&type=k2&tmpl=component">SIGPRO</a>
                                                <input name="sigProFolder" type="hidden" value="<?php echo $this->sigProFolder; ?>" />
												<?php endif; ?>
												<br />
												<?php echo JText::_('K2_OR_ENTER_A_FLICKR_SET_URL'); ?>
												<input type="text" name="flickrGallery" size="50" value="<?php echo ($this->row->galleryType == 'flickr')? $this->row->galleryValue : ''; ?>" />
												<?php if (!empty($this->row->gallery)): ?>
												<div id="itemGallery">
                                                                                                        <?php echo $this->row->gallery; ?>
                                                                                                        <br />
													<input type="checkbox" name="del_gallery" id="del_gallery"/>
													<label for="del_gallery"><?php echo JText::_('K2_CHECK_THIS_BOX_TO_DELETE_CURRENT_IMAGE_GALLERY_OR_JUST_UPLOAD_A_NEW_IMAGE_GALLERY_TO_REPLACE_THE_EXISTING_ONE'); ?></label>
												</div>
												<?php endif; ?>
											</td>
										</tr>
									</table>
									<?php else: ?>
                                                                                <?php if(K2_JVERSION == '25'): ?>
										<div id="system-message-container">
											<dl id="system-message">
												<dt class="notice"><?php echo JText::_('K2_NOTICE'); ?></dt>
												<dd class="notice message">
													<ul>
														<li><?php echo JText::_('K2_NOTICE_PLEASE_INSTALL_JOOMLAWORKS_SIMPLE_IMAGE_GALLERY_PRO_PLUGIN_IF_YOU_WANT_TO_USE_THE_IMAGE_GALLERY_FEATURES_OF_K2'); ?></li>
													</ul>
												</dd>
											</dl>
										</div>
										<?php else: ?>
										<div class="alert">
											<h4 class="alert-heading"><?php echo JText::_('K2_NOTICE'); ?></h4>
											<div><p><?php echo JText::_('K2_NOTICE_PLEASE_INSTALL_JOOMLAWORKS_SIMPLE_IMAGE_GALLERY_PRO_PLUGIN_IF_YOU_WANT_TO_USE_THE_IMAGE_GALLERY_FEATURES_OF_K2'); ?></p></div>
										</div>
										<?php endif; ?>
									<?php endif; ?>
									<?php if (count($this->K2PluginsItemGallery)): ?>
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemGallery as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php if ($this->params->get('showVideoTab')): ?>
								<!-- Tab video -->
								<div class="simpleTabsContent" id="k2Tab4">
									<?php if ($this->lists['checkAllVideos']): ?>
									<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>" id="item_video_content">
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_SOURCE'); ?>
											</td>
											<td>
												<div id="k2VideoTabs" class="simpleTabs">
													<ul class="simpleTabsNavigation">
														<li><a href="#k2VideoTab1"><?php echo JText::_('K2_UPLOAD'); ?></a></li>
														<li><a href="#k2VideoTab2"><?php echo JText::_('K2_BROWSE_SERVERUSE_REMOTE_MEDIA'); ?></a></li>
														<li><a href="#k2VideoTab3"><?php echo JText::_('K2_MEDIA_USE_ONLINE_VIDEO_SERVICE'); ?></a></li>
														<li><a href="#k2VideoTab4"><?php echo JText::_('K2_EMBED'); ?></a></li>
													</ul>
													<div id="k2VideoTab1" class="simpleTabsContent">
														<div class="panel" id="Upload_video">
															<input type="file" name="video" class="fileUpload" />
															<i>(<?php echo JText::_('K2_MAX_UPLOAD_SIZE'); ?>: <?php echo ini_get('upload_max_filesize'); ?>)</i> </div>
													</div>
													<div id="k2VideoTab2" class="simpleTabsContent">
														<div class="panel" id="Remote_video"> <a id="k2MediaBrowseServer" href="index.php?option=com_k2&view=media&type=video&tmpl=component&fieldID=remoteVideo"><?php echo JText::_('K2_BROWSE_VIDEOS_ON_SERVER')?></a> <?php echo JText::_('K2_OR'); ?> <?php echo JText::_('K2_PASTE_REMOTE_VIDEO_URL'); ?>
															<br />
															<br />
															<input type="text" size="50" name="remoteVideo" id="remoteVideo" value="<?php echo $this->lists['remoteVideo'] ?>" />
														</div>
													</div>
													<div id="k2VideoTab3" class="simpleTabsContent">
														<div class="panel" id="Video_from_provider"> <?php echo JText::_('K2_SELECT_VIDEO_PROVIDER'); ?> <?php echo $this->lists['providers']; ?> <br/><br/> <?php echo JText::_('K2_AND_ENTER_VIDEO_ID'); ?>
															<input type="text" size="50" name="videoID" value="<?php echo $this->lists['providerVideo'] ?>" />
															<br />
															<br />
															<a class="modal" rel="{handler: 'iframe', size: {x: 990, y: 600}}" href="http://www.joomlaworks.net/allvideos-documentation"><?php echo JText::_('K2_READ_THE_ALLVIDEOS_DOCUMENTATION_FOR_MORE'); ?></a> </div>
													</div>
													<div id="k2VideoTab4" class="simpleTabsContent">
														<div class="panel" id="embedVideo">
															<?php echo JText::_('K2_PASTE_HTML_EMBED_CODE_BELOW'); ?>
															<br />
															<textarea name="embedVideo" rows="5" cols="50" class="textarea"><?php echo $this->lists['embedVideo']; ?></textarea>
														</div>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_CAPTION'); ?>
											</td>
											<td>
												<input type="text" name="video_caption" size="50" class="text_area" value="<?php echo $this->row->video_caption; ?>" />
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_CREDITS'); ?>
											</td>
											<td>
												<input type="text" name="video_credits" size="50" class="text_area" value="<?php echo $this->row->video_credits; ?>" />
											</td>
										</tr>
										<?php if($this->row->video): ?>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_PREVIEW'); ?>
											</td>
											<td>
												<?php echo $this->row->video; ?>
												<br />
												<input type="checkbox" name="del_video" id="del_video" />
												<label for="del_video"><?php echo JText::_('K2_CHECK_THIS_BOX_TO_DELETE_CURRENT_VIDEO_OR_USE_THE_FORM_ABOVE_TO_REPLACE_THE_EXISTING_ONE'); ?></label>
											</td>
										</tr>
										<?php endif; ?>
									</table>
									<?php else: ?>
                                                                                <?php if(K2_JVERSION == '25'): ?>
										<div id="system-message-container">
											<dl id="system-message">
												<dt class="notice"><?php echo JText::_('K2_NOTICE'); ?></dt>
												<dd class="notice message">
													<ul>
														<li><?php echo JText::_('K2_NOTICE_PLEASE_INSTALL_JOOMLAWORKS_ALLVIDEOS_PLUGIN_IF_YOU_WANT_TO_USE_THE_FULL_VIDEO_FEATURES_OF_K2'); ?></li>
													</ul>
												</dd>
											</dl>
										</div>
										<?php else: ?>
										<div class="alert">
											<h4 class="alert-heading"><?php echo JText::_('K2_NOTICE'); ?></h4>
											<div><p><?php echo JText::_('K2_NOTICE_PLEASE_INSTALL_JOOMLAWORKS_ALLVIDEOS_PLUGIN_IF_YOU_WANT_TO_USE_THE_FULL_VIDEO_FEATURES_OF_K2'); ?></p></div>
										</div>
										<?php endif; ?>
									<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>" id="item_video_content">
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_SOURCE'); ?>
											</td>
											<td>
												<div id="k2VideoTabs" class="simpleTabs">
													<ul class="simpleTabsNavigation">
														<li><a href="#k2VideoTab4"><?php echo JText::_('K2_EMBED'); ?></a></li>
													</ul>
													<div class="simpleTabsContent" id="k2VideoTab4">
														<div class="panel" id="embedVideo">
															<?php echo JText::_('K2_PASTE_HTML_EMBED_CODE_BELOW'); ?>
															<br />
															<textarea name="embedVideo" rows="5" cols="50" class="textarea"><?php echo $this->lists['embedVideo']; ?></textarea>
														</div>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_CAPTION'); ?>
											</td>
											<td>
												<input type="text" name="video_caption" size="50" class="text_area" value="<?php echo $this->row->video_caption; ?>" />
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_CREDITS'); ?>
											</td>
											<td>
												<input type="text" name="video_credits" size="50" class="text_area" value="<?php echo $this->row->video_credits; ?>" />
											</td>
										</tr>
										<?php if($this->row->video): ?>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_MEDIA_PREVIEW'); ?>
											</td>
											<td>
												<?php echo $this->row->video; ?>
												<br />
												<input type="checkbox" name="del_video" id="del_video" />
												<label for="del_video"><?php echo JText::_('K2_USE_THE_FORM_ABOVE_TO_REPLACE_THE_EXISTING_VIDEO_OR_CHECK_THIS_BOX_TO_DELETE_CURRENT_VIDEO'); ?></label>
											</td>
										</tr>
										<?php endif; ?>
									</table>
									<?php endif; ?>
									<?php if (count($this->K2PluginsItemVideo)): ?>
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemVideo as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php if ($this->params->get('showExtraFieldsTab')): ?>
								<!-- Tab extra fields -->
								<div class="simpleTabsContent" id="k2Tab5">
									<div id="extraFieldsContainer">
										<?php if (count($this->extraFields)): ?>
										<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>" id="extraFields">
											<?php foreach($this->extraFields as $extraField): ?>
											<?php if($extraField->type == 'header'): ?>
											<tr>
												<td colspan="2" ><h4 class="k2ExtraFieldHeader"><?php echo $extraField->name; ?></h4></td>
											</tr>
											<?php else: ?>
											<tr>
												<td align="right" class="key">
													<label for="K2ExtraField_<?php echo $extraField->id; ?>"><?php echo $extraField->name; ?></label>
												</td>
												<td>
													<?php echo $extraField->element; ?>
												</td>
											</tr>
											<?php endif; ?>
											<?php endforeach; ?>
										</table>
										<?php else: ?>
                                                                                        <?php if (K2_JVERSION == '25'): ?>
											<div id="system-message-container">
												<dl id="system-message">
													<dt class="notice"><?php echo JText::_('K2_NOTICE'); ?></dt>
													<dd class="notice message">
														<ul>
															<li><?php echo JText::_('K2_PLEASE_SELECT_A_CATEGORY_FIRST_TO_RETRIEVE_ITS_RELATED_EXTRA_FIELDS'); ?></li>
														</ul>
													</dd>
												</dl>
											</div>
											<?php else: ?>
											<div class="alert">
												<h4 class="alert-heading"><?php echo JText::_('K2_NOTICE'); ?></h4>
												<div>
													<p><?php echo JText::_('K2_PLEASE_SELECT_A_CATEGORY_FIRST_TO_RETRIEVE_ITS_RELATED_EXTRA_FIELDS'); ?></p>
												</div>
											</div>
											<?php endif; ?>
										<?php endif; ?>
									</div>
									<?php if (count($this->K2PluginsItemExtraFields)): ?>
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemExtraFields as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php if ($this->params->get('showAttachmentsTab')): ?>
								<!-- Tab attachements -->
								<div class="simpleTabsContent" id="k2Tab6">
									<div class="itemAttachments">
										<?php if (count($this->row->attachments)): ?>
										<table class="adminlist">
											<tr>
												<th>
													<?php echo JText::_('K2_FILENAME'); ?>
												</th>
												<th>
													<?php echo JText::_('K2_TITLE'); ?>
												</th>
												<th>
													<?php echo JText::_('K2_TITLE_ATTRIBUTE'); ?>
												</th>
												<th>
													<?php echo JText::_('K2_DOWNLOADS'); ?>
												</th>
												<th>
													<?php echo JText::_('K2_OPERATIONS'); ?>
												</th>
											</tr>
											<?php foreach($this->row->attachments as $attachment): ?>
											<tr>
												<td class="attachment_entry">
													<?php echo $attachment->filename; ?>
												</td>
												<td>
													<?php echo $attachment->title; ?>
												</td>
												<td>
													<?php echo $attachment->titleAttribute; ?>
												</td>
												<td>
													<?php echo $attachment->hits; ?>
												</td>
												<td>
													<a href="<?php echo $attachment->link; ?>"><?php echo JText::_('K2_DOWNLOAD'); ?></a> <a class="deleteAttachmentButton" href="<?php echo JURI::base(true); ?>/index.php?option=com_k2&amp;view=item&amp;task=deleteAttachment&amp;id=<?php echo $attachment->id?>&amp;cid=<?php echo $this->row->id; ?>"><?php echo JText::_('K2_DELETE'); ?></a>
												</td>
											</tr>
											<?php endforeach; ?>
										</table>
										<?php endif; ?>
									</div>
									<div id="addAttachment">
										<input type="button" id="addAttachmentButton" value="<?php echo JText::_('K2_ADD_ATTACHMENT_FIELD'); ?>" />
										<i>(<?php echo JText::_('K2_MAX_UPLOAD_SIZE'); ?>: <?php echo ini_get('upload_max_filesize'); ?>)</i> </div>
									<div id="itemAttachments"></div>
									<?php if (count($this->K2PluginsItemAttachments)): ?>
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemAttachments as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php if(count(array_filter($this->K2PluginsItemOther)) && $this->params->get('showK2Plugins')): ?>
								<!-- Tab other plugins -->
								<div class="simpleTabsContent" id="k2Tab7">
									<div class="itemPlugins">
										<?php foreach($this->K2PluginsItemOther as $K2Plugin): ?>
										<?php if(!is_null($K2Plugin)): ?>
										<fieldset>
											<legend><?php echo $K2Plugin->name; ?></legend>
											<?php echo $K2Plugin->fields; ?>
										</fieldset>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
								</div>
								<?php endif; ?>
							</div>
							<!-- Tabs end here -->

							<input type="hidden" name="isSite" value="<?php echo (int)$this->mainframe->isSite(); ?>" />
							<?php if($this->mainframe->isSite()): ?>
							<input type="hidden" name="lang" value="<?php echo $_input->get('lang', '', 'word'); ?>" />
							<?php endif; ?>
							<input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
							<input type="hidden" name="option" value="com_k2" />
							<input type="hidden" name="view" value="item" />
							<input type="hidden" name="task" value="<?php echo $_input->get('task'); ?>" />
                                                        <input type="hidden" name="Itemid" value="<?php echo $_input->get('Itemid', '', 'int'); ?>" />
							<?php echo JHTML::_('form.token'); ?>
						</td>
						<td id="adminFormK2Sidebar"<?php if($this->mainframe->isSite() && !$this->params->get('sideBarDisplayFrontend')): ?> style="display:none;"<?php endif; ?> class="xmlParamsFields">
							<?php if($this->row->id): ?>
							<table class="sidebarDetails">
								<tr>
									<td>
										<strong><?php echo JText::_('K2_ITEM_ID'); ?></strong>
									</td>
									<td>
										<?php echo $this->row->id; ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_PUBLISHED'); ?></strong>
									</td>
									<td>
										<?php echo ($this->row->published > 0) ? JText::_('K2_YES') : JText::_('K2_NO'); ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_FEATURED'); ?></strong>
									</td>
									<td>
										<?php echo ($this->row->featured > 0) ? JText::_('K2_YES'):	JText::_('K2_NO'); ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_CREATED_DATE'); ?></strong>
									</td>
									<td>
										<?php echo $this->lists['created']; ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_CREATED_BY'); ?></strong>
									</td>
									<td>
										<?php echo $this->row->author; ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_MODIFIED_DATE'); ?></strong>
									</td>
									<td>
										<?php echo $this->lists['modified']; ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_MODIFIED_BY'); ?></strong>
									</td>
									<td>
										<?php echo $this->row->moderator; ?>
									</td>
								</tr>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_HITS'); ?></strong>
									</td>
									<td>
										<?php echo $this->row->hits; ?>
										<?php if($this->row->hits): ?>
										<input id="resetHitsButton" type="button" value="<?php echo JText::_('K2_RESET'); ?>" class="button" name="resetHits" />
										<?php endif; ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if($this->row->id): ?>
								<tr>
									<td>
										<strong><?php echo JText::_('K2_RATING'); ?></strong>
									</td>
									<td>
										<?php echo $this->row->ratingCount; ?> <?php echo JText::_('K2_VOTES'); ?>
										<?php if($this->row->ratingCount): ?>
										<br />
										(<?php echo JText::_('K2_AVERAGE_RATING'); ?>: <?php echo number_format(($this->row->ratingSum/$this->row->ratingCount),2); ?>/5.00)
										<?php endif; ?>
										<input id="resetRatingButton" type="button" value="<?php echo JText::_('K2_RESET'); ?>" class="button" name="resetRating" />
									</td>
								</tr>
							</table>
							<?php endif; ?>
							<div id="k2Accordion">
								<h3><a href="#"><?php echo JText::_('K2_AUTHOR_PUBLISHING_STATUS'); ?></a></h3>
								<div>
									<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>">
										<?php if(isset($this->lists['language'])): ?>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_LANGUAGE'); ?>
											</td>
											<td>
												<?php echo $this->lists['language']; ?>
											</td>
										</tr>
										<?php endif; ?>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_AUTHOR'); ?>
											</td>
											<td id="k2AuthorOptions">
												<span id="k2Author"><?php echo $this->row->author; ?></span>
												<?php if($this->mainframe->isAdmin() || ($this->mainframe->isSite() && $this->permissions->get('editAll'))): ?>
												<a class="modal" rel="{handler:'iframe', size: {x: 800, y: 460}}" href="index.php?option=com_k2&amp;view=users&amp;task=element&amp;tmpl=component"><?php echo JText::_('K2_CHANGE'); ?></a>
												<input type="hidden" name="created_by" value="<?php echo $this->row->created_by; ?>" />
												<?php endif; ?>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_AUTHOR_ALIAS'); ?>
											</td>
											<td>
												<input class="text_area" type="text" name="created_by_alias" maxlength="250" value="<?php echo $this->row->created_by_alias; ?>" />
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_ACCESS_LEVEL'); ?>
											</td>
											<td>
												<?php echo $this->lists['access']; ?>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_CREATION_DATE'); ?>
											</td>
											<td class="k2ItemFormDateField">
												<?php echo $this->lists['createdCalendar']; ?>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_START_PUBLISHING'); ?>
											</td>
											<td class="k2ItemFormDateField">
												<?php echo $this->lists['publish_up']; ?>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_FINISH_PUBLISHING'); ?>
											</td>
											<td class="k2ItemFormDateField">
												<?php echo $this->lists['publish_down']; ?>
											</td>
										</tr>
									</table>
								</div>
								<h3><a href="#"><?php echo JText::_('K2_METADATA_INFORMATION'); ?></a></h3>
								<div>
									<table class="admintable<?php echo $isAdmin ? " table" : ""; ?>">
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_DESCRIPTION'); ?>
											</td>
											<td>
												<textarea name="metadesc" rows="5" cols="20"><?php echo $this->row->metadesc; ?></textarea>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_KEYWORDS'); ?>
											</td>
											<td>
												<textarea name="metakey" rows="5" cols="20"><?php echo $this->row->metakey; ?></textarea>
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_ROBOTS'); ?>
											</td>
											<td>
												<input type="text" name="meta[robots]" value="<?php echo $this->lists['metadata']->get('robots'); ?>" />
											</td>
										</tr>
										<tr>
											<td align="right" class="key">
												<?php echo JText::_('K2_AUTHOR'); ?>
											</td>
											<td>
												<input type="text" name="meta[author]" value="<?php echo $this->lists['metadata']->get('author'); ?>" />
											</td>
										</tr>
									</table>
								</div>
								<?php if($this->mainframe->isAdmin()): ?>
								<h3><a href="#"><?php echo JText::_('K2_ITEM_VIEW_OPTIONS_IN_CATEGORY_LISTINGS'); ?></a></h3>
								<div>
                                                                        <fieldset class="panelform">
										<ul class="adminformlist">
											<?php foreach($this->form->getFieldset('item-view-options-listings') as $field): ?>
											<li>
												<?php if($field->type=='header'): ?>
												<div class="paramValueHeader"><?php echo $field->input; ?></div>
												<?php elseif($field->type=='Spacer'): ?>
												<div class="paramValueSpacer">&nbsp;</div>
												<div class="clr"></div>
												<?php else: ?>
												<div class="paramLabel"><?php echo $field->label; ?></div>
												<div class="paramValue"><?php echo $field->input; ?></div>
												<div class="clr"></div>
												<?php endif; ?>
											</li>
											<?php endforeach; ?>
										</ul>
									</fieldset>
								</div>
								<h3><a href="#"><?php echo JText::_('K2_ITEM_VIEW_OPTIONS'); ?></a></h3>
								<div>
									<fieldset class="panelform">
										<ul class="adminformlist">
											<?php foreach($this->form->getFieldset('item-view-options') as $field): ?>
											<li>
												<?php if($field->type=='header'): ?>
												<div class="paramValueHeader"><?php echo $field->input; ?></div>
												<?php elseif($field->type=='Spacer'): ?>
												<div class="paramValueSpacer">&nbsp;</div>
												<div class="clr"></div>
												<?php else: ?>
												<div class="paramLabel"><?php echo $field->label; ?></div>
												<div class="paramValue"><?php echo $field->input; ?></div>
												<div class="clr"></div>
												<?php endif; ?>
											</li>
											<?php endforeach; ?>
										</ul>
									</fieldset>
								</div>
                                                                <h3><a href="#"><?php echo JText::_('K2item view options in category listings').'(k2item)'; ?></a></h3>
								<div>
									<fieldset class="panelform">
										<ul class="adminformlist">
											<?php foreach($k2itemForm->getFieldset('k2item-item-view-options-listings') as $field): ?>
											<li>
												<?php if($field->type=='header'): ?>
												<div class="paramValueHeader"><?php echo $field->input; ?></div>
												<?php elseif($field->type=='Spacer'): ?>
												<div class="paramValueSpacer">&nbsp;</div>
												<div class="clr"></div>
												<?php else: ?>
												<div class="paramLabel"><?php echo $field->label; ?></div>
												<div class="paramValue"><?php echo $field->input; ?></div>
												<div class="clr"></div>
												<?php endif; ?>
											</li>
											<?php endforeach; ?>
										</ul>
									</fieldset>
								</div>
                                                                <h3><a href="#"><?php echo JText::_('K2item view options in Item view').'(k2item)'; ?></a></h3>
								<div>
									<fieldset class="panelform">
										<ul class="adminformlist">
											<?php foreach($k2itemForm->getFieldset('k2item-item-view-options') as $field): ?>
											<li>
												<?php if($field->type=='header'): ?>
												<div class="paramValueHeader"><?php echo $field->input; ?></div>
												<?php elseif($field->type=='Spacer'): ?>
												<div class="paramValueSpacer">&nbsp;</div>
												<div class="clr"></div>
												<?php else: ?>
												<div class="paramLabel"><?php echo $field->label; ?></div>
												<div class="paramValue"><?php echo $field->input; ?></div>
												<div class="clr"></div>
												<?php endif; ?>
											</li>
											<?php endforeach; ?>
										</ul>
									</fieldset>
								</div>
								<?php endif; ?>
                                                                <?php if($this->aceAclFlag): ?>
								<h3><a href="#"><?php echo JText::_('AceACL') . ' ' . JText::_('COM_ACEACL_COMMON_PERMISSIONS'); ?></a></h3>
								<div><?php AceaclApi::getWidget('com_k2.item.'.$this->row->id, true); ?></div>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="clr"></div>
			<?php if($this->mainframe->isSite()): ?>
		</div>
	</div>
	<?php endif; ?>
</form>
