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

if( $system->config->get( 'enable_subscription' ) && ( !$system->my->guest || $system->config->get( 'show_email' ) > 0 ) && !$system->config->get( 'subscription_auto' ) ) {
	$subscribed = null;

	if( $system->my->id )
	{
		$subscribed = Komento::getModel( 'subscription' )->checkSubscriptionExist( $component, $cid, $system->my->id );
	} ?>

	<span class="<?php if( $subscribed !== null ) echo 'subscribed '; ?>subscribeForm kmt-form-subscription">
	<?php if( $subscribed !== null ) {
		if( $subscribed ) {
			echo JText::_( 'COM_KOMENTO_FORM_ALREADY_SUBSCRIBE' );
		} else {
			echo JText::_( 'COM_KOMENTO_FORM_SUBSCRIBE_PENDING' );
		} ?>.
		<a href="javascript:void(0);" class="unsubscribeButton kmt-form-unsubscribe"><?php echo JText::_( 'COM_KOMENTO_FORM_UNSUBSCRIBE' ); ?></a>.
	<?php } else { ?>
		<input type="checkbox" name="subscribe-checkbox" id="subscribe-checkbox" value="y" class="subscribeCheckbox input checkbox" />
		<label for="subscribe-checkbox"><?php echo JText::_( 'COM_KOMENTO_FORM_SUBSCRIBE' ); ?></label>
	<?php } ?>
	</span>
<?php }
