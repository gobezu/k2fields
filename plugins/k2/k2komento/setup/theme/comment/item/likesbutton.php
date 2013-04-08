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

if( $system->config->get( 'enable_likes' ) ) { ?>
	<span class="kmt-like-wrap">
		<!-- Likes counter -->
		<b class="likesCounter kmt-like-counter"><i></i><span><?php echo $row->likes; ?></span></b>

	<?php if ($system->my->allow( 'like_comment') ) { ?>
		<!-- Like/Unlike button -->
		<?php if( $row->liked ) { ?>
			<a class="likeButton kmt-btn kmt-like cancel" href="javascript:void(0);" title="<?php echo JText::_( 'COM_KOMENTO_COMMENT_UNLIKE' ); ?>"><span><?php echo JText::_( 'COM_KOMENTO_COMMENT_UNLIKE' ); ?></span></a>
		<?php } else { ?>
			<a class="likeButton kmt-btn kmt-like" href="javascript:void(0);" title="<?php echo JText::_( 'COM_KOMENTO_COMMENT_LIKE' ); ?>"><span><?php echo JText::_( 'COM_KOMENTO_COMMENT_LIKE' ); ?></span></a>
		<?php } ?>
	<?php } else { ?>
		<a class="kmt-btn kmt-like kmt-disabled" href="javascript:void(0);" title="<?php echo JText::_( 'COM_KOMENTO_COMMENT_LIKE' ); ?>"><span><?php echo JText::_( 'COM_KOMENTO_COMMENT_LIKE' ); ?></span></a>
	<?php } ?>
	</span>
<?php }
