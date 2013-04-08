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

<li id="kmt-<?php echo $row->id; ?>" class="kmt-item" parentid="kmt-<?php echo $row->parent_id; ?>">

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

		<!-- Content Title -->
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
	</ul>

	<div class="kmt-body">
		<?php // parseBBcode to HTML
			$row->comment = KomentoCommentHelper::parseBBCode($row->comment);
			$row->comment = nl2br($row->comment);
		?>
		<span class="kmt-text"><?php echo $row->comment; ?></span>
	</div>

	<ul class="kmt-info">
		<?php if($row->modified_by != 0) { ?>
		<li>
			Comment last edited on <?php echo $row->modified; ?> by <?php echo Komento::getProfile($row->modified_by)->getName(); ?>
		</li>
		<?php } ?>

	</ul>

	<div class="kmt-control">
		<ul class="kmt-control-admin">
			<li>
				<?php if( $system->my->allow( 'publish_all_comment', $row->component ) || ( $row->created_by == $system->my->id && $system->my->allow( 'publish_own_comment', $row->component ) ) ) { ?>
				<a href="javascript:void(0);" class="kmt-status">
				<?php } ?>
				<?php switch ($row->published)
				{
					case 0:
						echo JText::_( 'COM_KOMENTO_UNPUBLISHED' );
						break;
					case 1:
						echo JText::_( 'COM_KOMENTO_PUBLISHED' );
						break;
					default:
						echo JText::_( 'COM_KOMENTO_MODERATE' );
						break;
				} ?>
				<?php if( $system->my->allow( 'publish_all_comment', $row->component ) || ( $row->created_by == $system->my->id && $system->my->allow( 'publish_own_comment', $row->component ) ) ) { ?>
				</a>

				<ul class="kmt-status-options hidden">
					<li><a href="javascript:void(0);" class="kmt-unpublish<?php if( $row->published == 0 ) { echo ' hidden'; } ?>"><?php echo JText::_( 'COM_KOMENTO_UNPUBLISH' ); ?></a></li>

					<li><a href="javascript:void(0);" class="kmt-publish<?php if( $row->published == 1 ) { echo ' hidden'; } ?>"><?php echo JText::_( 'COM_KOMENTO_PUBLISH' ); ?></a></li>
				</ul>
				<?php } ?>
			</li>

			<li>
				<?php if( $system->my->allow( 'manage_flag', $row->component ) ) { ?>
				<a href="javascript:void(0);" class="kmt-mark">
				<?php } ?>
				<?php switch ($row->flag)
				{
					case 0:
						echo JText::_( 'COM_KOMENTO_NOFLAG' );
						break;
					case 1:
						echo JText::_( 'COM_KOMENTO_SPAM' );
						break;
					case 2:
						echo JText::_( 'COM_KOMENTO_OFFENSIVE' );
						break;
					case 3:
						echo JText::_( 'COM_KOMENTO_OFFTOPIC' );
						break;
					default:
						echo JText::_( 'COM_KOMENTO_OTHERS' );
						break;
				} ?>
				<?php if( $system->my->allow( 'manage_flag', $row->component ) ) { ?>
				</a>

				<ul class="kmt-mark-options hidden">
					<li><a href="javascript:void(0);" class="kmt-mark-noflag<?php if($row->flag == 0) { echo ' hidden'; } ?>"><?php echo JText::_( 'COM_KOMENTO_NOFLAG' ); ?></a></li>

					<li><a href="javascript:void(0);" class="kmt-mark-spam<?php if($row->flag == 1) { echo ' hidden'; } ?>"><?php echo JText::_( 'COM_KOMENTO_SPAM' ); ?></a></li>

					<li><a href="javascript:void(0);" class="kmt-mark-offensive<?php if($row->flag == 2) { echo ' hidden'; } ?>"><?php echo JText::_( 'COM_KOMENTO_OFFENSIVE' ); ?></a></li>

					<li><a href="javascript:void(0);" class="kmt-mark-offtopic<?php if($row->flag == 3) { echo ' hidden'; } ?>"><?php echo JText::_( 'COM_KOMENTO_OFFTOPIC' ); ?></a></li>
				</ul>
				<?php } ?>
			</li>

			<?php if( $system->my->allow( 'edit_all_comment', $row->component ) || ( $row->created_by == $system->my->id && $system->my->allow( 'edit_own_comment', $row->component ) ) ) { ?>
			<li><a class="kmt-edit" href="javascript:void(0)"><?php echo JText::_( 'COM_KOMENTO_COMMENT_EDIT' ) ; ?></a></li>
			<?php } ?>

			<?php if( $system->my->allow( 'delete_all_comment', $row->component ) || ( $row->created_by == $system->my->id && $system->my->allow( 'delete_own_comment', $row->component ) ) ) { ?>
			<li><a class="kmt-delete" href="javascript:void(0)"><?php echo JText::_( 'COM_KOMENTO_COMMENT_DELETE' ) ; ?></a></li>
			<?php } ?>
		</ul>
	</div>
</li>
