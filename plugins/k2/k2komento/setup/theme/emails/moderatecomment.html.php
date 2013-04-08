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
$document = JFactory::getDocument(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
</head>

<body style="margin:0;padding:0;background:#ddd;">
	<div style="width:100%;background:#ddd;margin:0;padding:50px 0 80px;color:#798796;font-family:'Lucida Grande',Tahoma,Arial;font-size:12px;">

	<center style="display:block;padding:30px 0">
		<table cellpadding="0" cellspacing="0" border="0" style="width:590px;background:#fff;border:1px solid #b5bbc1;border-bottom-color:#9ba3ab;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;">
			<tbody>
				<tr>
					<td style="padding:20px;border-bottom:1px solid #b5bbc1;;background:#f5f5f5;border-radius:3px 3px 0 0;-moz-border-radius:3px 3px 0 0;-webkit-border-radius:3px 3px 0 0;<?php echo $document->direction == 'rtl' ? 'text-align:right;' : ''; ?>">
						<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_HELLO' ) . ' ' . $options['recipient']->fullname; ?>,
						<br /><br />
						<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_NEW_COMMENT_PENDING' ); ?>
						<br />
						<a href="<?php echo $contentPermalink;?>" style="font-weight:bold;color:#477fda;text-decoration:none;font-size:16px;line-height:20px"><?php echo $contentTitle; ?></a>
						<br />
						<br />
						<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_NEW_COMMENT_SNIPPET' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding:15px 20px;line-height:1.5;color:#555;font-family:'Lucida Grande',Tahoma,Arial;font-size:12px;text-align:<?php echo $document->direction == 'rtl' ? 'right' : 'left'; ?>">
						<div style="display:inline-block;width:100%;padding-bottom:20px;">
							<img src="<?php echo $commentAuthorAvatar; ?>" width="50" style="float:<?php echo $document->direction == 'rtl' ? 'right' : 'left'; ?>;width:50px;height:auto;border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;" />
							<div style="margin-<?php echo $document->direction == 'rtl' ? 'right' : 'left'; ?>:60px">
								<span style="font-weight:bold;color:#477fda;text-decoration:none"><?php echo $commentAuthorName; ?></span>
								<span style="color:#999">- <?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_POSTED_ON' ); ?> <?php echo $commentDate; ?></span>
								<div style="font-size:12px;margin-top:3px;">
									<?php echo $commentContent; ?>
								</div>
							</div>
						</div>

						<div style="color:#555;clear:both;border-top:1px solid #ddd;padding:20px 0 10px">
							<a href="<?php echo $approveLink;?>" target="_blank" style="display:inline-block;padding:5px 15px;background:#fc0;border:1px solid #caa200;border-bottom-color:#977900;color:#534200;text-shadow:0 1px 0 #ffe684;font-weight:bold;box-shadow:inset 0 1px 0 #ffe064;-moz-box-shadow:inset 0 1px 0 #ffe064;-webkit-box-shadow:inset 0 1px 0 #ffe064;border-radius:2px;moz-border-radius:2px;-webkit-border-radius:2px;text-decoration:none!important">
								<?php echo JText::_( 'COM_KOMENTO_NOTIFICATION_APPROVE_COMMENT' ); ?>
							</a>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</center>

	</div>
</body>
</html>

