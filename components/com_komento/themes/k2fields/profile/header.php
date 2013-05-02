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

<div class="kmt-profile-info">
	<h1 class="kmt-profile-name reset-h"><?php echo $profile->getName(); ?></h1>
	<div class="kmt-profile-account float-wrapper">
		<span><?php echo JText::_('COM_KOMENTO_MEMBER_SINCE'); ?> <?php echo KomentoDateHelper::getLapsedTime($profile->registerDate); ?></span>
		<span><?php echo JText::_('COM_KOMENTO_LAST_LOGIN'); ?> <?php echo KomentoDateHelper::getLapsedTime($profile->lastvisitDate); ?></span>
	</div>
</div>
