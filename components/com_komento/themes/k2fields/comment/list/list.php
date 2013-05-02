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
defined('_JEXEC') or die('Restricted access'); ?>
<script type='text/javascript'>
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
	'komento.commentlist'
)
.done(function($) {
	if($('.commentList').exists()) {
		Komento.options.element.commentlist = new Komento.Controller.CommentList($('.commentList'), {
			view: {
				editForm: 'comment/item/edit.form',
				deleteDialog: 'dialogs/delete.affectchild',
				publishDialog: 'dialogs/publish.affectchild',
				unpublishDialog: 'dialogs/unpublish.affectchild',
				deleteAttachment: 'dialogs/delete.attachment'
			}
		});
		Komento.options.element.commentlist.kmt = Komento.options.element;
	}
});
</script>
<div class="mainList kmt-fame-list-wrap tabs" loaded="1">

	<?php if( $system->my->allow( 'read_comment' ) ) {
		echo $this->fetch( 'comment/conversationbar.php' );
		echo $this->fetch( 'comment/tools.php' ); ?>

	<div class="commentList kmt-list-wrap">
		<?php if( !$system->konfig->get( 'enable_ajax_load_list' ) || (isset($ajaxcall) && $ajaxcall == 1) ) {
			// Load previous comments button a.kmt-btn-loadmore
			echo $this->fetch('comment/list/loadpreviousbutton.php');

			// Load comments ul.kmt-list
			echo $this->fetch('comment/list/comments.php');

			// Load more comments button a.kmt-btn-loadmore
			echo $this->fetch('comment/list/loadmorebutton.php');
		} ?>
	</div>
	<?php } else {
		if( $system->konfig->get( 'enable_warning_messages' ) ) { ?>
			<div class="kmt-not-allowed"><?php echo JText::_( 'COM_KOMENTO_COMMENT_NOT_ALLOWED' ); ?></div>
		<?php }
	} ?>
</div>

