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
defined( '_JEXEC' ) or die( 'Restricted access' );

$tncGroup		= $system->config->get( 'show_tnc', '');

if( !is_array( $tncGroup ) )
{
	$tncGroup = explode( ',', $tncGroup );
}

$usergids		= $system->my->getUsergroups();
$requiresTnc	= false;

foreach( $usergids as $gid )
{
	if( in_array( $gid, $tncGroup ) )
	{
		$requiresTnc = true;
		break;
	}
}

if( $requiresTnc ) { ?>
	<span class="kmt-form-terms">
		<input type="checkbox" name="tnc-checkbox" id="tnc-checkbox" value="y" class="tncCheckbox input checkbox" />
		<label for="tnc-checkbox"><?php echo JText::_( 'COM_KOMENTO_FORM_AGREE_TNC' ); ?></label>
		<a class="tncRead kmt-tnc-read" href="javascript:void(0);"><?php echo JText::_( 'COM_KOMENTO_FORM_READ_TNC' ); ?></a>.
	</span>
<?php }
