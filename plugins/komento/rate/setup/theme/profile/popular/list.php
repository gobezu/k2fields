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

foreach( $items as $row )
{
	$row = Komento::getHelper( 'comment' )->process( $row );
	if( $row === false )
	{
		continue;
	}

	$this->set( 'row', $row );
?>
	<li id="kmt-<?php echo $row->id; ?>" class="kmt-<?php echo $row->id; ?>">

		<div class="stream-head stream-popular">
			<i class="stream-type">
				<?php echo $row->likes; ?>
				<b><?php echo JText::_( 'COM_KOMENTO_COMMENT_LIKES' ); ?></b>
			</i>

			<?php echo JText::_( 'COM_KOMENTO_ACTIVITY_COMMENTED_ON' ); ?>
			<a href="<?php echo $row->extension->getContentPermalink(); ?>"><?php echo $row->extension->getContentTitle(); ?></a>
			<?php // echo JText::_( 'COM_KOMENTO_ACTIVITY_COMMENTED_IN' ); ?>
			<?php // echo Komento::loadApplication( $row->component )->getComponentName(); ?>
		</div>
		<div class="stream-body">
			<div class="kmt-comment-text"><?php echo $row->comment; ?></div>
		</div>
		<div class="stream-foot">
			<a href="<?php echo $row->permalink; ?>"><?php echo $row->created; ?></a>
		</div>
	</li>
<?php } ?>

