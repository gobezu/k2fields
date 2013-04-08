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
<div class="kmt-author-detail">
	<h3 class="kmt-author">
		<a href="<?php echo Komento::getProfile()->getProfileLink(); ?>">
		<?php echo $system->my->getName(); ?>
		</a>
	</h3>
	<br />
	<span class="kmt-author-time"><?php echo Komento::getDate()->toFormat('%A, %B %d, %Y') ?></span>
</div>
