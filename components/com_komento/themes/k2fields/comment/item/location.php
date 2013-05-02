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

if( $system->config->get( 'enable_location' ) ) { ?>
<span class="kmt-location"<?php if( $system->konfig->get( 'enable_schema' ) ) echo ' itemprop="contentLocation" itemscope itemtype="http://schema.org/Place"'; ?>>
	<?php if( $row->address && $row->latitude && $row->longitude ) { ?>
		<?php echo JText::_( 'COM_KOMENTO_COMMENT_FROM' );?> <a href="http://maps.google.com/maps?z=15&amp;q=<?php echo $row->latitude;?>,<?php echo $row->longitude;?>" target="_blank"><?php echo $row->address;?></a>
	<?php } else { ?>
		<?php if( $row->address ) { ?>
			<?php echo JText::_( 'COM_KOMENTO_COMMENT_FROM' ); ?><span<?php if( $system->konfig->get( 'enable_schema' ) ) echo ' itemprop="address"'; ?>><?php echo $row->address; ?></span>
		<?php } ?>
	<?php } ?>

	<!-- Extended data for schema purposes -->
	<?php if( $system->konfig->get( 'enable_schema' ) ) { ?>
	<span class="hidden" itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
		<span itemprop="latitude"><?php echo $row->longitude; ?></span>
		<span itemprop="longitude"><?php echo $row->latitude; ?></span>
	</span>
	<span class="hidden" itemprop="map">http://maps.google.com/maps?z=15&q=<?php echo $row->latitude;?>,<?php echo $row->longitude;?></span>
	<?php } ?>
</span>
<?php }
