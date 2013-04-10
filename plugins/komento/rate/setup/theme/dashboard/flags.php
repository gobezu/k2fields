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

<script type="text/javascript">
Komento.require().script('dashboard.flag.item').done(function($)
{
	$('.kmt-item').each(function(index, element)
	{
		$(element).implement(
			'Komento.Controller.Dashboard.FlagItem',
			{
				'commentId': element.id,
				'permalink': 0
			}
		);
	});
});
</script>

<div id="komento-dashboard">
	<?php
	echo $this->fetch( 'dashboard/toolbar.php' );
	echo $this->fetch( 'dashboard/flags.toolbar.php' );
	echo $this->fetch( 'dashboard/flags.list.php' );
	?>
</div>
