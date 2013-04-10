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
?>
<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_HELLO' ) . ' ' . $options['recipient']->fullname; ?>,



<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_NEW_COMMENT_PENDING' ); ?>

<?php echo $contentTitle; ?>



<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_NEW_COMMENT_SNIPPET' ); ?>


<?php echo $commentAuthorName; ?> - <?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_POSTED_ON' ); ?> <?php echo $commentDate; ?>

<?php echo $commentContent; ?>


<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_APPROVE_COMMENT' ); ?>: <?php echo $approveLink; ?>
