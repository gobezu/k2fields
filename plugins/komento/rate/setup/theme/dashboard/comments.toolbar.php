<?php
/**
 * @package		Komento
 * @copyright	Copyright ( C ) 2012 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * Komento is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

?>

<script type="text/javascript">
function resetform()
{
	Foundry('#filter-component option[value=all]').attr('selected', 'selected');
	Foundry('#filter-flag option[value=all]').attr('selected', 'selected');
	Foundry('#filter-status option[value=all]').attr('selected', 'selected');
	Foundry('#filter-sort option[value=latest]').attr('selected', 'selected');
	Foundry('#filter-search').val('');
}
</script>

<form id="dashboard-comments-filter" action="index.php?option=com_komento&view=dashboard" method="post">
<?php
	$status[] = JHTML::_( 'select.option', 'all', JText::_( 'COM_KOMENTO_ALL_STATUS' ) );
	$status[] = JHTML::_( 'select.option', '1', JText::_( 'COM_KOMENTO_PUBLISHED' ) );
	$status[] = JHTML::_( 'select.option', '0', JText::_( 'COM_KOMENTO_UNPUBLISHED' ) );
	$status[] = JHTML::_( 'select.option', '2', JText::_( 'COM_KOMENTO_MODERATE' ) );

	$model = Komento::getModel( 'comments' );
	$allComponents = $model->getUniqueComponents();

	$component[] = JHTML::_( 'select.option', 'all', JText::_( 'COM_KOMENTO_ALL_COMPONENTS' ) );

	foreach( $allComponents as $row )
	{
		$component[] = JHTML::_( 'select.option', $row, $row );
	}

	$flag[] = JHTML::_( 'select.option', 'all', JText::_( 'COM_KOMENTO_ALL_FLAGS' ) );
	$flag[] = JHTML::_( 'select.option', '0', JText::_( 'COM_KOMENTO_NOFLAG' ) );
	$flag[] = JHTML::_( 'select.option', '1', JText::_( 'COM_KOMENTO_SPAM' ) );
	$flag[] = JHTML::_( 'select.option', '2', JText::_( 'COM_KOMENTO_OFFENSIVE' ) );
	$flag[] = JHTML::_( 'select.option', '3', JText::_( 'COM_KOMENTO_OFFTOPIC' ) );

	$view[] = JHTML::_( 'select.option', 'latest', JText::_( 'COM_KOMENTO_SORT_LATEST' ) );
	$view[] = JHTML::_( 'select.option', 'oldest', JText::_( 'COM_KOMENTO_SORT_OLDEST' ) );

	echo JHTML::_( 'select.genericlist', $component, 'filter-component', 'class="inputbox" size="1"', 'value', 'text', $filter['component'] );

	echo JHTML::_( 'select.genericlist', $flag, 'filter-flag', 'class="inputbox" size="1"', 'value', 'text', $filter['flag'] );

	echo JHTML::_( 'select.genericlist', $status, 'filter-status', 'class="inputbox" size="1"', 'value', 'text', $filter['status'] );

	echo JHTML::_( 'select.genericlist', $view, 'filter-sort', 'class="inputbox" size="1"', 'value', 'text', $filter['sort'] );
?>
<br />
<label><?php echo JText::_( 'COM_KOMENTO_COMMENTS_SEARCH' ); ?> :</label>
<input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($filter['search']); ?>" class="inputbox" />
<button onclick="submitform()">Submit</button>
<button onclick="resetform()">Reset</button>
</form>
