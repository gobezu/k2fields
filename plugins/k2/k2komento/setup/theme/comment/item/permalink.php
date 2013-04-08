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

defined( '_JEXEC' ) or die( 'Restricted access' ); ?>
<?php if( $system->config->get( 'enable_permalink' ) && !$system->config->get( 'datetime_permalink' ) ) { ?>
<span class="kmt-permalink-wrap"><a class="kmt-permalink" href="<?php echo $row->permalink; ?>" alt="<?php echo JText::_( 'COM_KOMENTO_COMMENT_PERMALINK' ); ?>" title="<?php echo JText::_( 'COM_KOMENTO_COMMENT_PERMALINK' ); ?>"<?php if( $system->konfig->get( 'enable_schema' ) ) echo 'itemprop="url"'; ?>><?php echo JText::_( 'COM_KOMENTO_COMMENT_PERMALINK' ); ?></a></span>
<?php } ?>
