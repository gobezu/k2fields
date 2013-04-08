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

<table id="dashboard-flags" class="kmt-table">
<thead>
	<tr>
		<th><?php echo JText::_( 'COM_KOMENTO_COLUMN_NUM' ); ?></th>
		<th><?php echo JText::_( 'COM_KOMENTO_COLUMN_COMMENT' ); ?></th>
		<th><?php echo JText::_( 'COM_KOMENTO_COLUMN_FLAG_DETAILS' ); ?></th>
		<th><?php echo JText::_( 'COM_KOMENTO_COLUMN_FLAG_RESOLVE' ); ?></th>
	</tr>
</thead>
<tbody>
<?php
if($comments)
{
	$i = 0;
	foreach($comments as $row)
	{

		// $contentTitle = $componentModel->getContentTitle($row->component, $row->cid);

		// $this->set('contentTitle', $contentTitle);
		$this->set( 'row', $row );
		$this->set( 'pagination', $pagination );
		$this->set( 'i', $i );
		echo $this->fetch( 'dashboard/flags.item.php' );

		$i++;
	}
} else { ?>
	<tr>
		<td colspan="4"><?php echo JText::_( 'COM_KOMENTO_COMMENTS_NO_COMMENT' ); ?></td>
	</tr>
<?php } ?>
</tbody>
</table>

<?php if($comments && isset($pagination)) { ?>
	<?php if ( $pagination->getPagesLinks() ) { ?>
		<div class="pagination"><?php echo $pagination->getPagesLinks();?></div>
	<?php } ?>
<?php } ?>
