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
if( $system->config->get( 'enable_info' ) && $row->modified_by ) { ?>
<span class="commentInfo kmt-info">
	<?php $modified = $row->modified;
	if( $system->config->get( 'enable_lapsed_time' ) ) {
		$modified = Komento::getHelper( 'date' )->getLapsedTime( $row->modified );
	} ?>
	<?php echo JText::sprintf( 'COM_KOMENTO_COMMENT_EDITTED_BY', $modified, Komento::getProfile( $row->modified_by )->getName() ); ?>

	<!-- Extended data for schema purposes -->
	<?php if( $system->konfig->get( 'enable_schema' ) ) { ?>
	<span class="hidden" itemprop="editor" itemscope itemtype="http://schema.org/Person">
		<span itemprop="name"><?php echo Komento::getProfile( $row->modified_by )->getName(); ?></span>
	</span>
	<time class="hidden" itemprop="dateModified" datetime="<?php echo $this->formatDate( 'c', $row->modified ); ?>"></time>
	<?php } ?>
</span>
<?php } else { ?>
<span class="commentInfo kmt-info hidden"></span>
<?php }
