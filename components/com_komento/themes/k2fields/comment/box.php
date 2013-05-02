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
defined('_JEXEC') or die('Restricted access'); ?>

<?php Komento::trigger( 'onBeforeKomentoBox', array( 'component' => $component, 'cid' => $cid, 'system' => &$system, 'comments' => &$comments ) ); ?>

<?php if( false ) {
return true;
} ?>

<script type='text/javascript'>
Komento.ready(function($) {
	// declare master namespace variable for shared values
	Komento.component	= "<?php echo $component; ?>";
	Komento.cid			= "<?php echo $cid; ?>";
	Komento.contentLink	= "<?php echo $contentLink; ?>";
	Komento.sort		= "<?php echo JRequest::getCmd( 'kmt-sort', $system->config->get( 'default_sort', 'oldest' ) ); ?>";
	Komento.loadedCount	= parseInt(<?php echo count($comments); ?>);
	Komento.totalCount	= parseInt(<?php echo $commentCount; ?>);

	if( Komento.options.konfig.enable_shorten_link == 0 ) {
		Komento.shortenLink = Komento.contentLink;
	}
});
</script>

<div id="section-kmt" class="theme-<?php echo $system->config->get( 'layout_theme' ); ?>">
<?php if( $componentHelper->getCommentAnchorId() ) { ?><a id="<?php echo $componentHelper->getCommentAnchorId(); ?>"></a><?php } ?>

<?php if( !$system->my->allow( 'read_comment' ) && !$system->my->allow( 'add_comment' ) && !$system->my->allow( 'read_stickies' ) && !$system->my->allow( 'read_lovies' ) && $system->konfig->get( 'enable_warning_messages' ) ) { ?>
	<div class="kmt-not-allowed">
		<?php echo JText::_( 'COM_KOMENTO_COMMENT_FORM_NOT_ALLOWED' ); ?>
	</div>
<?php } else {

	// Form (based on form_position)
	if( $system->config->get( 'tabbed_comments' ) && $system->config->get( 'form_position' ) == 0 )
	{
		echo $this->fetch( 'comment/form.php' );
	}

	// Tabbed fame list
	echo $this->fetch( 'comment/famelist.php' );

	// if tabbed comments is not on, then load normal comments container
	if( !$system->config->get( 'tabbed_comments' ) )
	{
		// Conversation Bar
		echo $this->fetch( 'comment/conversationbar.php' );

		// Form (based on form_position)
		if( $system->config->get( 'form_position' ) == 0 )
		{
			echo $this->fetch( 'comment/form.php' );
		}

		// Comment Toolbar
		echo $this->fetch('comment/tools.php');

		// List body
		echo $this->fetch( 'comment/list.php' );
	}

	// Form (based on form_position)
	if( $system->config->get( 'form_position' ) == 1 )
	{
		echo $this->fetch( 'comment/form.php' );
	}
}
?>

</div><!--/section-kmt-->
