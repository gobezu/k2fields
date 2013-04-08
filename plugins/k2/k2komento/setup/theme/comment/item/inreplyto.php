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

if( $row->parent_id != 0 ) { ?>
<span class="inReplyTo kmt-inreplyto">
	<?php
		if( $system->config->get( 'enable_threaded' ) )
		{
			$name = '';
			$parent = Komento::getComment( $row->parent_id, true );
			echo JText::sprintf( 'COM_KOMENTO_COMMENT_IN_REPLY_TO_NAME', $row->parentlink, $parent->name );
		}
		else
		{
			// non threaded no need to show name, because will have parent comment as a popup when hover over comment id
			echo JText::sprintf( 'COM_KOMENTO_COMMENT_IN_REPLY_TO', $row->parentlink, $row->parent_id );
		}


		$parent = '';

		if( $system->konfig->get( 'parent_preload' ) ) {
			$parent = Komento::getComment( $row->parent_id );
		}

		$parentTheme = Komento::getTheme();
		$parentTheme->set( 'parent', $parent );
		echo $parentTheme->fetch( 'comment/item/parent.php' );
		?>
</span>
<?php }
