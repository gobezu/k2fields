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

<div id="section-kmt">

	<script type="text/javascript">
	Komento.require().script('komento.profile', 'komento.language').done(function($) {
		Komento.options.element.profile = new Komento.Controller.Profile($('#kmt-profile-tabs'), {
			uid: <?php echo $profile->id; ?>
		});

		Komento.options.element.profile.kmt = Komento.options.element;
	});
	</script>

	<?php echo $this->fetch( 'profile/sidebar.php' ); ?>

	<!-- tabs -->
	<div id="kmt-profile-tabs">
		<ul class="kmt-profile-tab reset-ul float-li clearfix">
			<?php if( $system->konfig->get( 'profile_tab_activities' ) ) { ?>
			<li><a href="javascript:void(0);" class="navActivities navs" tab="kmt-activities" func="loadActivities" title="kmt-activities"><?php echo JText::_( 'COM_KOMENTO_PROFILE_TAB_ACTIVITIES' ); ?></a></li>
			<?php } ?>

			<?php if( $system->konfig->get( 'profile_tab_popular' ) ) { ?>
			<li><a href="javascript:void(0);" class="navPopular navs" tab="kmt-popular" func="loadPopular" title="kmt-popular"><?php echo JText::_( 'COM_KOMENTO_PROFILE_TAB_POPULAR' ); ?></a></li>
			<?php } ?>

			<?php if( $system->konfig->get( 'profile_tab_sticked' ) ) { ?>
			<li><a href="javascript:void(0);" class="navSticked navs" tab="kmt-sticked" func="loadSticked" title="kmt-sticked"><?php echo JText::_( 'COM_KOMENTO_PROFILE_TAB_STICKED' ); ?></a></li>
			<?php } ?>
		</ul>

		<div id="kmt-activities" class="tabs"></div>
		<div id="kmt-popular" class="tabs"></div>
		<div id="kmt-sticked" class="tabs"></div>
	</div>
</div>
