<?php
/**
* @package		Komento
* @copyright	Copyright (C) 2012 Stack Ideas Private Limited. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Komento is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Restricted access' );

if( $system->config->get( 'enable_share' ) && $system->my->allow( 'share_comment' ) ) { ?>
	<?php
		$permalink = $row->permalink;
		$shortlink = $row->shortlink;
		$encodedlink = urlencode($shortlink);
		$temp = $row->comment;
		$temp = nl2br( $temp );
		$temp = strip_tags( $temp );
		$title = JString::substr( $temp, 0, 30 );

		if( JString::strlen($temp) > 30 )
		{
			$title .= '...';
		}
	?>
	<span class="kmt-share-wrap">
		<!-- Share button -->
		<a class="shareButton kmt-btn kmt-share" href="javascript:void(0);"><span><?php echo JText::_( 'COM_KOMENTO_COMMENT_SHARE' ); ?></span></a>


		<span class="kmt-share-balloon">
			<span>
				<i></i>
				<span class="kmt-share-url">
					<span><?php echo JText::_( 'COM_KOMENTO_COMMENT_SHORT_URL' ); ?>:</span>
					<input class="short-url input text" type="text" value="<?php echo $row->shortlink; ?>" />
				</span>
				<span class="kmt-share-social">
					<i>
						<?php if($system->config->get( 'share_facebook' ) ) { ?>
						<a class="socialButton share-facebook" type="facebook" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Facebook</a>
						<?php } ?>

						<?php if($system->config->get( 'share_twitter' ) ) { ?>
						<a class="socialButton share-twitter" type="twitter" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Twitter</a>
						<?php } ?>

						<?php if($system->config->get( 'share_googleplus' ) ) { ?>
						<a class="socialButton share-googleplus" type="googleplus" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Google Plus</a>
						<?php } ?>
						<?php if($system->config->get( 'share_linkedin' ) ) { ?>
						<a class="socialButton share-linkedin" type="linkedin" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">LinkedIn</a>
						<?php } ?>

						<?php if($system->config->get( 'share_tumblr' ) ) { ?>
						<a class="socialButton share-tumblr" type="tumblr" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Tumblr</a>
						<?php } ?>

						<?php if($system->config->get( 'share_digg' ) ) { ?>
						<a class="socialButton share-digg" type="digg" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Digg</a>
						<?php } ?>

						<?php if($system->config->get( 'share_delicious' ) ) { ?>
						<a class="socialButton share-delicious" type="delicious" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Delicious</a>
						<?php } ?>

						<?php if($system->config->get( 'share_reddit' ) ) { ?>
						<a class="socialButton share-reddit" type="reddit" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">Reddit</a>
						<?php } ?>

						<?php if($system->config->get( 'share_stumbleupon' ) ) { ?>
						<a class="socialButton share-stumbleupon" type="stumbleupon" url="<?php echo $shortlink; ?>" content="<?php echo $title; ?>" commentid="<?php echo $row->id; ?>">StumbleUpon</a>
						<?php } ?>
					</i>
				</span>
			</span>
		</span>
	</span>
<?php }
