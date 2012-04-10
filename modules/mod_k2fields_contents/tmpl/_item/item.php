<?php 
//$Copyright$

/** ORIGINAL copyright fleshed out item specific parts and removed annoying html comments
 * @version		$Id: default.php 1499 2012-02-28 10:28:38Z lefteris.kavadas $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2012 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access'); 
?>

<?php echo $item->event->BeforeDisplay; ?>
<?php echo $item->event->K2BeforeDisplay; ?>
<?php if ($params->get('itemAuthorAvatar')): ?>
<a class="k2Avatar moduleItemAuthorAvatar" href="<?php echo $item->authorLink; ?>">
        <img src="<?php echo $item->authorAvatar; ?>" alt="<?php echo $item->author; ?>" style="width:<?php echo $_avatarWidth; ?>px;height:auto;" />
</a>
<?php endif;
if ($params->get('itemTitle')):
        $tag = $params->get('itemTitleTag');
        ?>
        <a class="moduleItemTitle" href="<?php echo $item->link; ?>">
        <?php echo $tag ? '<' . $tag . ' class="moduleItemTitle">' : ''; ?>
        <?php echo $item->title; ?>
        <?php echo $tag ? '</' . $tag . '>' : ''; ?>
        </a>
<?php endif; ?>
<?php if ($params->get('itemAuthor')) : ?>
<div class="moduleItemAuthor">
        <?php 
        echo K2HelperUtilities::writtenBy($item->authorGender);
        if (isset($item->authorLink)): ?>
                <a href="<?php echo $item->authorLink; ?>"><?php echo $item->author; ?></a>
        <?php else: ?>
                <?php echo $item->author; ?>
        <?php endif; ?>
</div>
<?php endif; ?>
<?php echo $item->event->AfterDisplayTitle; ?>
<?php echo $item->event->K2AfterDisplayTitle; ?>
<?php echo $item->event->BeforeDisplayContent; ?>
<?php echo $item->event->K2BeforeDisplayContent; ?>
<?php if ($params->get('itemImage') || $params->get('itemIntroText')): ?>
<div class="moduleItemIntrotext">
        <?php if ($params->get('itemImage') && isset($item->image)): ?>
        <a class="moduleItemImage" href="<?php echo $item->link; ?>" title="<?php echo JText::_('Continue reading'); ?> &quot;<?php echo K2HelperUtilities::cleanHtml($item->title); ?>&quot;">
                <img src="<?php echo $item->image; ?>" alt="<?php echo $item->title; ?>"/>
        </a>
        <?php 
        endif;
        if ($params->get('itemIntroText')) { echo $item->introtext; } ?>
</div>
<?php endif; ?>
<?php if ($params->get('itemExtraFields') && count($item->extra_fields)): ?>
<div class="moduleItemExtraFields">
        <b><?php echo JText::_('Additional Info'); ?></b>
        <ul>
        <?php foreach ($item->extra_fields as $extraField): ?>
                <li class="type<?php echo ucfirst($extraField->type); ?> group<?php echo $extraField->group; ?>">
                        <span class="moduleItemExtraFieldsLabel"><?php echo $extraField->name; ?></span>
                        <span class="moduleItemExtraFieldsValue"><?php echo $extraField->value; ?></span>
                        <div class="clr"></div>
                </li>
        <?php endforeach; ?>
        </ul>
</div>
<?php endif; ?>
<div class="clr"></div>
<?php if ($params->get('itemVideo')): ?>
<div class="moduleItemVideo">
        <?php echo $item->video; ?>
        <span class="moduleItemVideoCaption"><?php echo $item->video_caption; ?></span>
        <span class="moduleItemVideoCredits"><?php echo $item->video_credits; ?></span>
</div>
<?php endif; ?>
<div class="clr"></div>
<?php echo $item->event->AfterDisplayContent; ?>
<?php echo $item->event->K2AfterDisplayContent; ?>
<?php if ($params->get('itemDateCreated')): ?>
<span class="moduleItemDateCreated"><?php echo JText::_('Written on'); ?> <?php echo JHTML::_('date', $item->created, JText::_('DATE_FORMAT_LC2')); ?></span>
<?php endif; ?>
<?php if ($params->get('itemCategory')): ?>
<?php echo JText::_('in'); ?> <a class="moduleItemCategory" href="<?php echo $item->categoryLink; ?>"><?php echo $item->categoryname; ?></a>
<?php endif; ?>
<?php if ($params->get('itemTags') && count($item->tags) > 0): ?>
<div class="moduleItemTags">
        <b><?php echo JText::_('Tags'); ?>:</b>
        <?php foreach ($item->tags as $tag): ?>
                <a href="<?php echo $tag->link; ?>"><?php echo $tag->name; ?></a>
        <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($params->get('itemAttachments') && count($item->attachments)): ?>
<div class="moduleAttachments">
        <?php foreach ($item->attachments as $attachment): ?>
                <a title="<?php echo $attachment->titleAttribute; ?>" href="<?php echo JRoute::_('index.php?option=com_k2&view=item&task=download&id=' . $attachment->id); ?>"><?php echo $attachment->title; ?></a>
        <?php endforeach; ?>
</div>
<?php 
endif; 
if ($params->get('itemCommentsCounter') && $componentParams->get('comments')): 
        if (!empty($item->event->K2CommentsCounter)):
                echo $item->event->K2CommentsCounter; 
        else:
                if ($item->numOfComments > 0): ?>
                        <a class="moduleItemComments" href="<?php echo $item->link . '#itemCommentsAnchor'; ?>">
                        <?php echo $item->numOfComments; ?> <?php if ($item->numOfComments > 1)
                                echo JText::_('comments'); else
                                echo JText::_('comment'); ?>
                        </a>
                <?php else: ?>
                <a class="moduleItemComments" href="<?php echo $item->link . '#itemCommentsAnchor'; ?>">
                        <?php echo JText::_('Be the first to comment!'); ?>
                </a>
                <?php 
                endif; 
        endif;
endif;
if ($params->get('itemHits')): ?>
        <span class="moduleItemHits">
        <?php echo JText::_('Read'); ?> <?php echo $item->hits; ?> <?php echo JText::_('times'); ?>
        </span>
<?php 
endif; 
if ($params->get('itemReadMore') && $item->fulltext): ?>
        <a class="moduleItemReadMore" href="<?php echo $item->link; ?>">
        <?php echo JText::_('Read more...'); ?>
        </a>
<?php 
endif; 
echo $item->event->AfterDisplay; 
echo $item->event->K2AfterDisplay; 
?>
<div class="clr"></div>