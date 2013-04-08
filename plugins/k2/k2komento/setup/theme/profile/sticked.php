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
if( count( $items ) > 0 ) { ?>
<ul class="kmt-stream for-sticky reset-child">
	<?php echo $this->fetch( 'profile/sticked/list.php' ); ?>
</ul>

<?php echo $this->fetch( 'profile/loadmore.php' );
} else { ?>
	<p><?php echo JText::_('COM_KOMENTO_NO_COMMENTS_FOUND'); ?></p>
<?php }
