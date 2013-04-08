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

if ( false ) {  //($system->config->get( 'enable_registration' ) && $system->my->guest) { ?>
	<div class="kmt-form-group">
		<input type="checkbox" id="register-checkbox" name="register-checkbox" value="y" />
		<label id="register-checkbox-label">
			<?php echo JText::_( 'COM_KOMENTO_FORM_REGISTER' ); ?>
		</label>
	</div>
	<?php } ?>

	<?php if( false ) {// $system->config->get( 'enable_registration' ) && $system->my->guest) { ?>
	<div>
		<label><?php echo JText::_( 'COM_KOMENTO_FORM_USERNAME' ); ?></label>
		<div>
			<input id="register-username" class="input" type="text" />
		</div>
	</div>
<?php }
