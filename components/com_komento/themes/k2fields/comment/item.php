<?php
/**
 * @package		Komento
 * @copyright	Copyright (C) 2012 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * Komento is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined( '_JEXEC' ) or die( 'Restricted access' ); ?>

<?php // KURO THEME

Komento::trigger( 'onBeforeProcessComment', array( 'component' => $row->component, 'cid' => $row->cid, 'comment' => &$row ) );

// Process data
$row = KomentoCommentHelper::process($row);

Komento::trigger( 'onAfterProcessComment', array( 'component' => $row->component, 'cid' => $row->cid, 'comment' => &$row ) );

// Usergroup CSS Classname control
$classname	= '';

if (Komento::getProfile( $row->created_by )->guest)
{
	$classname = ' ' . $system->config->get( 'layout_css_public' );
} else {
	$classname = ' ' . $system->config->get( 'layout_css_registered' );
}
if (Komento::getProfile( $row->created_by )->isAdmin())
{
	$classname = ' ' . $system->config->get( 'layout_css_admin' );
}
if( $row->created_by == $row->extension->getAuthorId() )
{
	$classname .= ' ' . $system->config->get( 'layout_css_author' );
}

// k2fields
if ($row->isAggregate) {
        if ($row->noRate) return;
        
        $classname .= ' aggr_rate';
}

$usergroups	= $row->author->getUsergroups();
if (is_array($usergroups) && !empty($usergroups))
{
	foreach ($usergroups as $usergroup) {
		$classname .= ' kmt-comment-item-usergroup-' . $usergroup;
	}
} ?>
<li id="kmt-<?php echo $row->id; ?>" class="kmt-<?php echo $row->id; ?> kmt-item kmt-child-<?php echo $row->depth; ?> <?php if($row->sticked) echo 'kmt-sticked'; ?> <?php echo $classname; ?> <?php echo $row->published == 1 ? 'kmt-published' : 'kmt-unpublished'; ?>" parentid="kmt-<?php echo $row->parent_id; ?>" depth="<?php echo $row->depth; ?>" childs="<?php echo $row->childs; ?>" published="<?php echo $row->published; ?>"<?php if( $system->konfig->get( 'enable_schema' ) ) echo ' itemscope itemtype="http://schema.org/Comment"'; ?>>

<?php // depth and indentation calculation
	$css = '';
	if( $system->config->get( 'enable_threaded' ) )
	{
		$unit = $system->konfig->get('thread_indentation');
		$total = $unit * $row->depth;

		$css = 'style="margin-left: ' . $total . 'px !important"';

		// support for RTL sites
		// forcertl = 1 for dev purposes
		if( JFactory::getDocument()->direction == 'rtl' || JRequest::getInt( 'forcertl' ) == 1 )
		{
			$css = 'style="margin-right: ' . $total . 'px !important"';
		}
	}
?>
<div class="kmt-wrap" <?php echo $css; ?>>

	<!-- Avatar div.kmt-avatar -->
	<?php echo $this->fetch( 'comment/item/avatar.php' ); ?>

	<div class="kmt-content">
                <?php 
                // k2fields
                if (!$row->isAggregate): ?>
		<div class="kmt-head">
			<?php echo $this->fetch( 'comment/item/id.php' ); ?>

			<!-- Name span.kmt-author -->
			<?php 
                        if (!$row->isAggregate)
                        echo $this->fetch( 'comment/item/author.php' ); 
                        ?>

			<!-- In reply to span.kmt-inreplyto -->
			<?php echo $this->fetch( 'comment/item/inreplyto.php' ); ?>

			<span class="kmt-option float-wrapper">
				<!-- Report Comment span.kmt-report-wrap -->
				<?php echo $this->fetch( 'comment/item/report.php' ); ?>

				<!-- Permalink span.kmt-permalink-wrap -->
				<?php echo $this->fetch( 'comment/item/permalink.php' ); ?>

				<!-- AdminTools span.kmt-admin-wrap -->
				<?php 
                                // k2fields
                                if (!$row->isAggregate) echo $this->fetch( 'comment/item/admintools.php' ); 
                                ?>
			</span>
		</div>
                <?php endif; ?>
		<div class="kmt-body">
			<i></i>

			<!-- Comment div.kmt-text -->
			<?php echo $this->fetch( 'comment/item/text.php' ); ?>

			<!-- Attachment div.kmt-attachments -->
			<?php echo $this->fetch( 'comment/item/attachment.php' ); ?>

			<!-- Info span.kmt-info -->
			<?php echo $this->fetch( 'comment/item/info.php' ); ?>
		</div>
                
                <?php 
                // k2fields
                if (!$row->isAggregate): ?>
		<div class="kmt-control">

			<div class="kmt-meta">
				<!-- Time span.kmt-time -->
				<?php echo $this->fetch( 'comment/item/time.php' ); ?>

				<!-- Location span.kmt-location -->
				<?php echo $this->fetch( 'comment/item/location.php' ); ?>
			</div>

			<div class="kmt-control-user float-wrapper">
				<!-- Likes span.kmt-like-wrap -->
				<?php echo $this->fetch( 'comment/item/likesbutton.php' ); ?>

				<!-- Share div.kmt-share-wrap -->
				<?php echo $this->fetch( 'comment/item/sharebutton.php' ); ?>

				<!-- Reply span.kmt-reply-wrap -->
				<?php echo $this->fetch( 'comment/item/replybutton.php' ); ?>
			</div>
		</div>
                <?php endif; ?>
	</div>
</div>
</li>
