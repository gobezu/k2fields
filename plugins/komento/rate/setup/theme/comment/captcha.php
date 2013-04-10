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
?>
<div id="captcha-instruction">
	<label>
		<?php echo JText::_('COM_KOMENTO_COMMENT_CAPTCHA_DESC'); ?>
		<a href="javascript:void(0);" class="kmt-captcha-reload"><?php echo JText::_( 'COM_KOMENTO_COMMENT_CAPTCHA_RELOAD' );?></a>
	</label>
</div>
<div class="clearfix">
	<img id="captcha-image" class="float-l" src="<?php echo KomentoRouterHelper::_( 'index.php?option=com_komento&controller=captcha&task=display&tmpl=component&captcha-id=' . $id );?>" />
	<input type="text" name="captcha-response" id="captcha-response" class="input text" maxlength="5" />
	<input type="hidden" name="captcha-id" id="captcha-id" value="<?php echo $this->escape($id);?>" />
</div>
