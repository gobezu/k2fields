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
if ( $row->published == 1 && $system->acl->allow( 'unpublish', $row ) ) { ?>
<li>
	<a class="unpublishButton kmt-unpublish" href="javascript:void(0);"><?php echo JText::_( 'COM_KOMENTO_COMMENT_UNPUBLISH' ); ?></a>
</li>
<?php } else if( $row->published == 0 && $system->acl->allow( 'publish', $row ) ) { ?>
<li>
	<a class="publishButton kmt-publish" href="javascript:void(0);"><?php echo JText::_( 'COM_KOMENTO_COMMENT_PUBLISH' ); ?></a>
</li>
<?php }
