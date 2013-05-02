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

foreach( $items as $activity )
{
	$activity->comment = Komento::getComment( $activity->comment_id, $process = true );

	if( $activity->comment === false )
	{
		continue;
	}

	$this->set( 'activity', $activity ); ?>
<li>
	<?php echo $this->fetch('profile/activities/' . $activity->type . '.php'); ?>
</li>
<?php }
