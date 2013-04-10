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

if( $system->config->get( 'show_name' ) == 2 || ( $system->config->get( 'show_name' ) == 1 && $system->my->guest ) ) { ?>
	<li class="col kmt-form-name">
		<label><?php echo JText::_( 'COM_KOMENTO_FORM_NAME' ); ?><?php if( $system->config->get('require_name' ) == 2 || ( $system->config->get( 'require_name' ) == 1 && $system->my->guest ) ) echo ' (' . JText::_( 'COM_KOMENTO_FORM_REQUIRED' ) . ')'; ?>:</label>
		<div>
			<input id="register-name" class="input text" name="name" type="text" <?php if(!$system->my->guest) echo 'value="' . $this->escape($system->my->name) . '"';?> />
		</div>
	</li>
<?php }
