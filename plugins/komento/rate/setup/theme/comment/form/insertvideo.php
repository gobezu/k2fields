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
defined('_JEXEC') or die('Restricted access'); ?>
<script type="text/javascript">
Komento.require().script('komento.insertvideo').done(function($) {
	$('#kmt-insert-video-form').implement('Komento.Controller.InsertVideo', {
		caretPosition: "<?php echo $caretPosition; ?>",
		element: "<?php echo $element; ?>"
	});
});

</script>
<div id="kmt-insert-video">
	<p><?php echo JText::_( 'COM_KOMENTO_INSERT_VIDEO_DESC' );?></p>
	<ul class="reset-ul float-li video-providers clearfix">
		<li class="video-youtube"><?php echo JText::_( 'COM_KOMENTO_VIDEO_YOUTUBE' );?></li>
		<li class="video-vimeo"><?php echo JText::_('COM_KOMENTO_VIDEO_VIMEO' );?></li>
		<li class="video-dailymotion"><?php echo JText::_('COM_KOMENTO_VIDEO_DAILYMOTION' );?></li>
		<li class="video-google"><?php echo JText::_('COM_KOMENTO_VIDEO_GOOGLE' );?></li>
		<li class="video-liveleak"><?php echo JText::_( 'COM_KOMENTO_VIDEO_LIVELEAK' );?></li>
		<li class="video-metacafe"><?php echo JText::_( 'COM_KOMENTO_VIDEO_METACAFE' );?></li>
		<li class="video-nicovideo"><?php echo JText::_( 'COM_KOMENTO_VIDEO_NICOVIDEO' );?></li>
		<li class="video-yahoo"><?php echo JText::_( 'COM_KOMENTO_VIDEO_YAHOO' );?></li>
	</ul>
	<div id="kmt-insert-video-form">
		<form id="videoForm" name="videoForm" class="video-form">
		<label for="videoURL"><strong><?php echo JText::_( 'COM_KOMENTO_INSERT_VIDEO_URL' );?></strong>:</label>
		<input type="text" id="videoURL" value="" class="videoUrl" />
		<div class="dialog-buttons">
			<input type="button" value="<?php echo JText::_( 'COM_KOMENTO_INSERT_VIDEO' ); ?>" class="kmt-video-form-add insertVideo" />
			<input type="button" value="<?php echo JText::_('COM_KOMENTO_INSERT_VIDEO_CANCEL');?>" class="kmt-video-form-cancel cancelVideo" />
		</div>
		</form>
	</div>
</div>
