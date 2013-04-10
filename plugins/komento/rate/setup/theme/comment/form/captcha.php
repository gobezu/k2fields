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

if( $system->config->get( 'antispam_captcha_enable' ) ) {
	if( ( $system->config->get( 'antispam_captcha_registered' ) && $system->my->id ) || !$system->my->id ){ ?>
	<div class="kmt-form-captcha">
		<?php if( $system->config->get( 'antispam_captcha_type' ) == 0 || ( $system->config->get( 'antispam_captcha_type') == 1 && $system->config->get('antispam_recaptcha_public_key') && $system->config->get('antispam_recaptcha_private_key') ) ) {
			echo Komento::getCaptcha()->getHTML();
		} else {
			if( $system->config->get( 'antispam_captcha_type') == 1 && ( !$system->config->get('antispam_recaptcha_public_key') || !$system->config->get('antispam_recaptcha_private_key') ) ) {
				echo JText::_( 'COM_KOMENTO_RECAPTCHA_MISSING_KEYS' );
			}
		} ?>
	</div>
	<?php }
}
