<?php
/**
* @package		Komento
* @copyright	Copyright (C) 2012 Stack Ideas Private Limited. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Komento is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Restricted access' );
?>

<span class="kmt-time">
	<?php if( $system->konfig->get( 'enable_schema' ) ) { ?>
	<time itemprop="dateCreated" datetime="<?php echo $this->formatDate( 'c' , $row->unformattedDate ); ?>">
	<?php }
	if( $system->config->get( 'datetime_permalink' ) ) { ?>
		<a class="kmt-timepermalink" href="<?php echo $row->permalink; ?>" alt="<?php echo JText::_( 'COM_KOMENTO_COMMENT_PERMALINK' ); ?>" title="<?php echo JText::_( 'COM_KOMENTO_COMMENT_PERMALINK' ); ?>"<?php if( $system->konfig->get( 'enable_schema' ) ) echo 'itemprop="url"'; ?>>
	<?php }
	echo $row->created;
	if( $system->config->get( 'datetime_permalink' ) ) { ?>
		</a>
	<?php }
	if( $system->konfig->get( 'enable_schema' ) ) { ?>
	</time>
	<?php } ?>

	<!-- Extended data for schema purposes -->
	<?php if( $system->konfig->get( 'enable_schema' ) ) { ?>
	<time class="hidden" itemprop="datePublished" datetime="<?php echo $this->formatDate( 'c', $row->publish_up ); ?>"></time>
	<?php } ?>
</span>
