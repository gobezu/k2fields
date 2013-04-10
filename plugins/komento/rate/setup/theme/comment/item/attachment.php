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
defined('_JEXEC') or die('Restricted access');

$filehelper = Komento::getHelper( 'file' );
$attachments = $filehelper->getAttachments( $row->id );
if( $attachments !== false && count( $attachments ) > 0 ) { ?>
	<div class="kmt-attachments attachmentWrap kmt-view-list">
	<strong class="kmt-attachments-title"><?php echo JText::_( 'COM_KOMENTO_COMMENT_ATTACHMENTS' ); ?> :</strong>
	<?php if( $system->my->allow( 'download_attachment' ) ) { ?>
		<ul class="kmt-attachments-list reset-ul attachmentList">
			<?php foreach( $attachments as $attachment ) { ?>
				<li class="kmt-attachment-item attachmentFile file-<?php echo $attachment->id; ?>" attachmentid="<?php echo $attachment->id; ?>" attachmentname="<?php echo $attachment->filename; ?>">
					<?php if( $system->my->allow( 'delete_attachment' ) ) { ?>
						<a href="javascript:void(0);" class="attachmentDelete">
							<i></i>
						</a>
					<?php } ?>
					<a href="<?php echo $attachment->link; ?>" class="attachmentDetail">
						<i class="icon-mime type-<?php echo $attachment->class; ?>"></i>
						<?php echo $attachment->filename; ?>
					</a>
				</li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		<div><?php echo JText::_( 'COM_KOMENTO_COMMENT_ATTACHMENTS_NO_PERMISSION_TO_VIEW' ); ?></div>
	<?php } ?>
	</div>
<?php } ?>
