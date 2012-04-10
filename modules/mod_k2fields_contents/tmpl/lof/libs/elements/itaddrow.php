<?php
/**
 * $ModDesc
 * 
 * @version		$Id: helper.php $Revision
 * @package		modules
 * @subpackage	mod_lofflashcontent
 * @copyright	Copyright (C) JAN 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>. All rights reserved.
 * @license		GNU General Public License version 2
 */
// no direct access
defined('_JEXEC') or die ('Restricted access');
  
jimport('joomla.html.parameter.element');

  class JElementItaddrow extends JElement {
  	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'ItAddRow';
	
	function fetchElement($name, $values, &$node, $control_name)
	{
		
		$cname = $control_name.'['.$name.'][]';
		
		if( !is_array($values) && empty($values) ){
			$values = array();
		}
		$values = !is_array($values) ? array($values):$values;
		$row ='';
		foreach( $values as $key=> $value ){
			$row .= '
				<div class="row">
					<span class="spantext">'.($key+1).'</span>
					<input type="text" value="'.$value.'" name="'.$cname.'">
					<span class="remove"></span>
				</div>
			';
		}
		return '<fieldset class="it-addrow-block"><legend><span id="btna-'.$name.'" class="add">Add Row</span></legend>'.$row.'</fieldset>';
	}
  }

?>
