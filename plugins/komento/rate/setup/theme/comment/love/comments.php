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
?>

<ul class="kmt-fame-list kmt-list reset-child">
	<?php if( count( $loves ) > 0 ) {
		foreach( $loves as $row ) {

			// Set all row data
			$this->set( 'row', $row );

			echo $this->fetch( 'comment/item.php' );
		}
	} else { ?>
		<li class="kmt-empty-comment">
			<?php echo JText::_( 'COM_KOMENTO_COMMENTS_NO_COMMENT' ); ?>
		</li>
	<?php } ?>
</ul>
