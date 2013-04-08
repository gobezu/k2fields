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
$tabs = 0;

if( $system->config->get( 'tabbed_comments' ) && ( $system->my->allow( 'read_comment' ) || $system->konfig->get( 'enable_warning_messages' ) ) ) {
	$tabs += 1;
}

if( $system->config->get( 'enable_stickies' ) && $system->my->allow( 'read_stickies' ) && $commentCount > 0 ) {
	$tabs += 1;
}

if( $system->config->get( 'enable_lovies' ) && $system->my->allow( 'read_lovies' ) && $commentCount > 0 ) {
	$tabs += 1;
}

if( $tabs > 0 ) { ?>
<script type="text/javascript">
Komento.require()
.library('dialog')
.view(
	'dialogs/delete.single',
	'dialogs/delete.affectchild',
	'dialogs/unpublish.affectchild',
	'comment/item/edit.form',
	'dialogs/delete.attachment'
)
.script(
	'komento.language',
	'komento.common',
	'komento.famelist'
)
.done(function($) {
	if($('.fameList').exists()) {
		Komento.options.element.famelist = new Komento.Controller.FameList($('.fameList'), {
			view: {
				editForm: 'comment/item/edit.form',
				deleteDialog: 'dialogs/delete.affectchild',
				publishDialog: 'dialogs/publish.affectchild',
				unpublishDialog: 'dialogs/unpublish.affectchild',
				deleteAttachment: 'dialogs/delete.attachment'
			}
		});
		Komento.options.element.famelist.kmt = Komento.options.element;
	}
});
</script>

<div id="kmt-fame" class="fameList">
	<ul class="kmt-fame-tabs reset-ul col<?php echo $tabs; ?>">

	<?php if( $system->config->get( 'tabbed_comments' ) && ( $system->my->allow( 'read_comment' ) || $system->konfig->get( 'enable_warning_messages' ) ) ) { ?>
		<li>
			<a href="javascript:void(0);" class="navMain navs" func="loadMainList" tab="mainList">
				<i></i>
				<b><?php echo JText::_( 'COM_KOMENTO_COMMENTS' ); ?></b>
			</a>
		</li>
	<?php } ?>

	<?php if( $system->config->get( 'enable_stickies' ) && $system->my->allow( 'read_stickies' ) && $commentCount > 0 ) { ?>
		<li>
			<a href="javascript:void(0);" class="navStickies navs" func="loadStickList" tab="stickList">
				<i></i>
				<b><?php echo JText::_( 'COM_KOMENTO_STICKIES_TITLE' ); ?></b>
			</a>
		</li>
	<?php }
	if( $system->config->get( 'enable_lovies' ) && $system->my->allow( 'read_lovies' ) && $commentCount > 0 ) { ?>
		<li>
			<a href="javascript:void(0);" class="navLovies navs" func="loadLoveList" tab="loveList">
				<i></i>
				<b><?php echo JText::_( 'COM_KOMENTO_LOVIES_TITLE' ); ?></b>
			</a>
		</li>
	<?php } ?>
	</ul>
	<?php if( $system->config->get( 'tabbed_comments' ) ) {
		echo $this->fetch( 'comment/list/list.php' );
	}
	echo $this->fetch( 'comment/stick/list.php' );
	echo $this->fetch( 'comment/love/list.php' ); ?>

</div>

<?php }
