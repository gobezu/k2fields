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

<ul id="komento-dashboard-menu" class="navigation-menu">
	<li><a href="<?php echo JRoute::_( 'index.php?option=com_komento&view=dashboard' ) ; ?>"><?php echo JText::_( 'COM_KOMENTO_MENU_COMMENTS' ); ?></a></li>
	<li><a href="<?php echo JRoute::_( 'index.php?option=com_komento&view=reports' ) ; ?>"><?php echo JText::_( 'COM_KOMENTO_MENU_FLAGS' ); ?></a></li>
	<li><a href="<?php echo JRoute::_( 'index.php?option=com_komento&view=pending' ) ; ?>"><?php echo JText::_( 'COM_KOMENTO_MENU_PENDING' ); ?></a></li>
	<!-- <li><a href="<?php echo JRoute::_( 'index.php?option=com_komento&view=subscribers' ) ; ?>"><?php echo JText::_( 'COM_KOMENTO_MENU_SUBSCRIBERS' ); ?></a></li> -->
</ul>
