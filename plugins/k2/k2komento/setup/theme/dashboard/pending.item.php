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

defined('_JEXEC') or die('Restricted access');
?>

<tr id="kmt-<?php echo $row->id; ?>" class="kmt-item" parentid="kmt-<?php echo $row->parent_id; ?>">
<!-- Checkbox -->
<td><?php echo $pagination->getRowOffset( $i ); ?></td>

<!-- Comment details -->
<td>
	<ul class="kmt-head">
		<!-- Avatar -->
		<?php if( $system->config->get( 'layout_avatar_enable' ) ) { ?>
		<li class="kmt-avatar">
			<?php if( !Komento::getProfile( $row->created_by )->guest ) { ?>
				<a href="<?php echo Komento::getProfile( $row->created_by )->getProfileLink(); ?>">
			<?php } ?>
			<img src="<?php echo Komento::getProfile( $row->created_by )->getAvatar(); ?>" class="avatar" />
			<?php if( !Komento::getProfile( $row->created_by )->guest ) { ?>
				</a>
			<?php } ?>
		</li>
		<?php } ?>

		<li class="kmt-content-title"><a href="<?php echo Komento::loadApplication( $row->component )->load( $row->cid )->getContentPermalink(); ?>"><?php echo Komento::getExtension( $row->component )->load( $row->cid )->getContentTitle(); ?></a></li>


		<!-- Name -->
		<li class="kmt-author">
			<?php if( !Komento::getProfile( $row->created_by )->guest ) { ?>
				<a href="<?php echo Komento::getProfile( $row->created_by )->getProfileLink(); ?>">
			<?php }

				echo Komento::getProfile( $row->created_by )->getName();

				if( !Komento::getProfile( $row->created_by )->guest) { ?>
				</a>
			<?php } ?>
		</li>

		<!-- Time -->
		<li class="kmt-date">
			<?php if( $system->config->get( 'enable_lapsed_time') ) {
				echo KomentoDateHelper::getLapsedTime( $row->created );
			} else {
				echo $row->created;
			} ?>
		</li>

		<!-- Permalink -->
		<li class="kmt-permalink"><a href="<?php echo Komento::loadApplication( $row->component )->load( $row->cid )->getContentPermalink() . '#kmt-' . $row->id; ?>"><?php echo JText::_( 'COM_KOMENTO_COMMENT_PERMALINK' ) ; ?></a></li>

		<!-- Status -->
		<li class="kmt-status"><?php echo $row->published ? JText::_( 'COM_KOMENTO_PUBLISHED' ) : JText::_( 'COM_KOMENTO_UNPUBLISHED' );?></li>
	</ul>

	<div class="kmt-body">

		<?php // parseBBcode to HTML
			$row->comment = KomentoCommentHelper::parseBBCode($row->comment);
			$row->comment = nl2br($row->comment);
		?>
		<span class="kmt-text"><?php echo $row->comment; ?></span>

	</div>
</td>

<!-- Action -->
<td>
	<ul class="kmt-action">
		<?php if( $system->my->allow( 'publish_all_comment', $row->component ) || ( $row->created_by == $system->my->id && $system->my->allow( 'publish_own_comment', $row->component ) ) ) { ?>
		<li><a href="javascript:void(0);" class="kmt-publish"><?php echo JText::_( 'COM_KOMENTO_PUBLISH' ); ?></a></li>
		<?php } ?>

		<?php if( $system->my->allow( 'delete_all_comment', $row->component ) || ( $row->created_by == $system->my->id && $system->my->allow( 'delete_own_comment', $row->component ) ) ) { ?>
		<li><a href="javascript:void(0)" class="kmt-delete"><?php echo JText::_( 'COM_KOMENTO_DELETE' ) ; ?></a></li>
		<?php } ?>
		<!-- <li><a href="javascript:void(0);" class="kmt-noflag"><?php echo JText::_( 'COM_KOMENTO_NOFLAG' ); ?></a></li>
		<li><a href="javascript:void(0);" class="kmt-spam"><?php echo JText::_( 'COM_KOMENTO_SPAM' ); ?></a></li>
		<li><a href="javascript:void(0);" class="kmt-offensive"><?php echo JText::_( 'COM_KOMENTO_OFFENSIVE' ); ?></a></li>
		<li><a href="javascript:void(0);" class="kmt-offtopic"><?php echo JText::_( 'COM_KOMENTO_OFFTOPIC' ); ?></a></li> -->
	</ul>
</td>
</tr>
