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
<?php if( isset( $options['lock'] ) && !$options['lock'] ) {
	if( $system->my->allow( 'add_comment' ) ) { ?>
	<script type="text/javascript">
	Komento.require()
	.library('dialog')
	.script(
		'komento.language',
		'komento.common',
		'komento.commentform'
	)
	.done(function($) {
		if($('.commentForm').exists()) {
			Komento.options.element.form = new Komento.Controller.CommentForm($('.commentForm'));
			Komento.options.element.form.kmt = Komento.options.element;
		}
	});

	</script>
	<div id="kmt-form" class="commentForm kmt-form clearfix">
		<?php if( $system->config->get( 'form_toggle_button' ) ) { ?>
		<a class="addCommentButton kmt-form-addbutton" href="javascript:void(0);"><b><?php echo JText::_( 'COM_KOMENTO_FORM_ADD_COMMENTS' ); ?></b></a>
		<?php } ?>
		<div class="formArea kmt-form-area<?php if( $system->config->get( 'form_toggle_button' ) ) echo ' hidden'; ?>">
			<h3 class="kmt-title"><?php echo JText::_( 'COM_KOMENTO_FORM_LEAVE_YOUR_COMMENTS' ); ?></h3>
			<a name="commentform" id="commentform"></a>

			<form>
				<?php
					// Form alert ul.kmt-form-alert
					echo $this->fetch( 'comment/form/alert.php' );
				?>
				<?php if( ( $system->config->get( 'show_name' ) == 2 || $system->config->get( 'show_email' ) == 2 || $system->config->get( 'show_website' ) == 2 ) || ( $system->my->guest && ( $system->config->get( 'show_name' ) == 1 || $system->config->get( 'show_email' ) == 1 || $system->config->get( 'show_website' ) == 1 ) ) ) { ?>
					<ul class="kmt-form-author reset-ul float-li clearfix">
						<?php
							// Name field li.kmt-form-name
							echo $this->fetch( 'comment/form/namefield.php' );

							// Email field li.kmt-form-email
							echo $this->fetch( 'comment/form/emailfield.php' );

							// Website field li.kmt-form-website
							echo $this->fetch( 'comment/form/websitefield.php' );
						?>
					</ul>
				<?php } ?>

				<?php if( !Komento::getProfile()->guest ) { ?>
				<div class="kmt-form-author clearfix">
					<?php
						// Avatar div.kmt-avatar
						echo $this->fetch( 'comment/form/avatar.php' );

						// Author div.kmt-comment-detail
						echo $this->fetch( 'comment/form/author.php' );
					?>
				</div>
				<?php } ?>

				<div class="kmt-form-content">
					<?php
						// Comment Editor div.kmt-form-editor
						echo $this->fetch( 'comment/form/editor.php' );
					?>
					<div class="kmt-form-addon">
					<?php
						// Maximum Length Countdown div.kmt-form-length
						echo $this->fetch( 'comment/form/length.php' );

						// Comment Location div.kmt-form-location
						echo $this->fetch( 'comment/form/location.php' );
					?>
					</div>
				</div>

				<div class="kmt-form-upload">
					<?php
						// Upload Form
						echo $this->fetch( 'comment/form/upload.php' );
					?>
				</div>

				<?php
					// Captcha div.kmt-form-captcha
					echo $this->fetch( 'comment/form/captcha.php' );
				?>

				<div class="kmt-form-submit clearfix float-wrapper">
					<?php
						// Submit button button.kmt-btn-submit
						echo $this->fetch( 'comment/form/submitbutton.php' );

						// Subscription field span.kmt-form-subscription
						echo $this->fetch( 'comment/form/subscriptionfield.php' );

						// Tnc field span.kmt-form-terms
						echo $this->fetch( 'comment/form/tncfield.php');
					?>
				</div>

				<!-- <input type="hidden" name="extension" value="" />
				<input type="hidden" name="cid" value="" /> -->
				<input type="hidden" name="parent" value="0" />
				<input type="hidden" name="task" value="commentSave" />
				<input type="hidden" name="pageItemId" class="pageItemId" value="<?php echo JRequest::getInt( 'Itemid' ); ?>" />
				<!--a href="javascript:void(0);" class="kmt-form-post">Post</a-->
			</form>
		</div>
	</div>
	<?php } else {
		if( $system->konfig->get( 'enable_warning_messages' ) ) { ?>
		<div id="kmt-form" class="commentForm kmt-form clearfix">
			<div class="kmt-not-allowed">
				<?php if( $system->my->guest ) {
					echo JText::_( 'COM_KOMENTO_FORM_GUEST_NOT_ALLOWED' );
				} else {
					echo JText::_( 'COM_KOMENTO_FORM_NOT_ALLOWED' );
				} ?>
			</div>
		</div>
		<?php }
	}
} else { ?>
	<div id="kmt-form" class="commentForm kmt-form clearfix">
		<h3 class="kmt-title"><?php echo JText::_( 'COM_KOMENTO_FORM_LEAVE_YOUR_COMMENTS' ); ?></h3>
		<a name="commentform" id="commentform"></a>
		<div class="kmt-locked-wrap">
			<i class="kmt-comment-locked"></i><?php echo JText::_( 'COM_KOMENTO_FORM_LOCKED' ); ?>
		</div>
	</div>
<?php }
