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
<div class="parentContainer hidden" loaded="<?php echo $parent ? '1' : '0'; ?>">
<?php
	if( !empty($parent) ) {
		$this->set( 'row', $parent );

		// todo: configurable
		echo $this->fetch( 'comment/item/avatar.php' );
		echo $this->fetch( 'comment/item/author.php' );
		echo $this->fetch( 'comment/item/time.php' );
		echo $this->fetch( 'comment/item/text.php' );
	}
?>
</div>
