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

if( $system->config->get( 'enable_lovies' ) && $system->my->allow( 'read_lovies' ) && ( !$system->konfig->get( 'enable_ajax_load_lovies' ) || (isset($ajaxcall) && $ajaxcall == 1) ) ) {

	if( !$system->konfig->get( 'enable_ajax_load_lovies' ) )
	{
		$loves = Komento::getModel( 'comments' )->getPopularComments( $component, $cid, array( 'limit' => $system->config->get( 'max_lovies', 5 ), 'threaded' => 0 ) );
	}
?>
<div class="loveList kmt-fame-list-wrap tabs" loaded="1">
	<h3 class="kmt-title"><?php echo JText::_( 'COM_KOMENTO_LOVIES_TITLE' ); ?></h3>

	<?php
		// Load comments ul.kmt-stick-list
		$this->set( 'loves', $loves );
		echo $this->fetch('comment/love/comments.php');
	?>
</div>
<?php } else { ?>
<div class="loveList kmt-fame-list-wrap hidden tabs">
	<h3 class="kmt-title"><?php echo JText::_( 'COM_KOMENTO_LOVIES_TITLE' ); ?></h3>
</div>
<?php }
