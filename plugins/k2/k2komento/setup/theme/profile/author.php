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

defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<ul class="kmt-profile-activity reset-ul float-li clearfix" style="margin:15px 0">
	<li>
		<b><?php echo $count->totalComments; ?></b>
		<div><?php echo JText::_( 'COM_KOMENTO_USER_TOTAL_COMMENTS' ); ?></div>
	</li>
	<li>
		<b><?php echo $count->likesReceived; ?></b>
		<div><?php echo JText::_( 'COM_KOMENTO_USER_TOTAL_LIKES_RECEIVED' ); ?></div>
	</li>
	<li>
		<b><?php echo $count->likesGiven; ?></b>
		<div><?php echo JText::_( 'COM_KOMENTO_USER_TOTAL_LIKES_GIVEN' ); ?></div>
	</li>
</ul>
