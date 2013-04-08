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

if( !isset( $comments ) ) {
	$comments = array();
}
if( !isset( $commentCount) ) {
	$commentCount = 0;
} ?>
<script type='text/javascript'>
Komento.ready(function($) {
	// declare master namespace variable for shared values
	Komento.component	= "<?php echo $component; ?>";
	Komento.cid			= "<?php echo $cid; ?>";
	Komento.contentLink	= "<?php echo $contentLink; ?>";
	Komento.sort		= "<?php echo JRequest::getCmd( 'kmt-sort', 'oldest' ); ?>";
	Komento.loadedCount	= parseInt(<?php echo count($comments); ?>);
	Komento.totalCount	= parseInt(<?php echo $commentCount; ?>);
});
</script>

<div id="section-kmt" class="theme-<?php echo $system->config->get( 'layout_theme' ); ?>">


<?php $this->fetch( $theme ); ?>

</div>
