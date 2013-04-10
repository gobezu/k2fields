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
if( $system->my->allow( 'read_comment' ) && $system->config->get( 'load_previous' ) && !($commentCount == count( $comments ) ) ) {

	if( isset( $options['limitstart'] ) && $options['limitstart'] != 0 )
	{
		$limitcount = $system->config->get( 'max_comments_per_page' );
		$startcount = $options['limitstart'] - $limitcount;
		if( $startcount < 0 )
		{
			$limitcount = $limitcount + $startcount;
			$startcount = 0;
		} ?>
		<a class="loadMore kmt-btn-loadmore" href="#!kmt-start=<?php echo $startcount; ?>"><b><?php echo JText::_( 'COM_KOMENTO_LIST_LOAD_PREVIOUS' ); ?></b></a>
	<?php }
}
