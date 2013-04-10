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

if( $system->config->get( 'show_website' ) == 2 || ( $system->config->get( 'show_website' ) == 1 && $system->my->guest ) ) { ?>
	<li class="col kmt-form-website">
		<label><?php echo JText::_( 'COM_KOMENTO_FORM_WEBSITE' ); ?><?php if( $system->config->get('require_website' ) == 2 || ( $system->config->get( 'require_website' ) == 1 && $system->my->guest ) ) echo ' (' . JText::_( 'COM_KOMENTO_FORM_REQUIRED' ) . ')'; ?>:</label>
		<div>
			<input id="register-website" class="input text" name="website" type="text" />
		</div>
	</li>
<?php }
