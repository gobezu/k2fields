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

if( !isset( $options['lock'] ) ) {
	$options['lock'] = false;
}

if( !$options['lock'] && $system->config->get( 'enable_reply' ) && $system->my->allow( 'add_comment' ) ) { ?>
	<?php if( $system->config->get( 'max_threaded_level' ) == 0 || $row->depth < ( $system->config->get( 'max_threaded_level' ) - 1 ) ) { ?>
		<span class="kmt-reply-wrap">
			<a class="replyButton kmt-btn kmt-reply" href="javascript:void(0);" title="<?php echo JText::_( 'COM_KOMENTO_COMMENT_REPLY' ); ?>"><span><?php echo JText::_( 'COM_KOMENTO_COMMENT_REPLY' ); ?></span></a>
		</span>
	<?php }
}
