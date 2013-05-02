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

if( $system->config->get( 'upload_enable' ) && $system->my->allow( 'upload_attachment' ) ) { ?>
<div class="uploaderWrap">
	<div class="kmt-upload-area uploaderForm">
		<div class="kmt-upload-list uploadQueue"></div>
		<div id="uploadArea" class="kmt-upload-push uploadArea clearfix">
			<span class="uploadClick kmt-has-tip">
				<button class="uploadButton input button" type="button" href="javascript:void(0);"><?php echo JText::_( 'COM_KOMENTO_FORM_ATTACH_FILE' ); ?></button>
				<span class="kmt-tip">
					<i></i>
					<span>
						<b><?php echo JText::_( 'COM_KOMENTO_FORM_EXTENSION_RESTRICTION' ); ?></b>
						<?php echo JText::sprintf('COM_KOMENTO_FORM_EXTENSION_ALLOWED_LIST', implode(', ', array_map('trim', explode(',', $system->config->get('upload_allowed_extension'))))); ?>
					</span>
				</span>
			</span>
			<span class="dragDrop hidden"><?php echo JText::_( 'COM_KOMENTO_FORM_OR_DROP_FILES_HERE' ); ?></span>
			<span class="uploadLimit"><span class="fileCounter">0</span> / <?php echo $system->config->get( 'upload_max_file' ); ?></span>
		</div>
	</div>
</div>
<?php } ?>
