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

<ul class="kmt-list reset-child">
	<?php if( $comments ) {
                // k2fields
                $aggrComment = clone $comments[0];
                $aggrComment->id = -1;
                $aggrComment->parent_id = 0;
                $aggrComment->isAggregate = true;
                $co = array('layout_avatar_enable', 'enable_report', 'enable_permalink', 'enable_location', 'enable_likes', 'enable_share', 'enable_reply');
                $config = array();
                foreach ($co as $c) {
                        $config[$c] = $system->config->get($c);
                        $system->config->set($c, false);
                }
                $this->set('row', $aggrComment);
                echo $this->fetch('comment/item.php');
                
                foreach ($config as $k => $c) {
                        $system->config->set($k, $config[$k]);
                }
                
		foreach( $comments as $row ) {
			// Set all row data
                        $row->isAggregate = false;
			$this->set( 'row', $row );

			echo $this->fetch( 'comment/item.php' );
		}
	} else { ?>
		<li class="kmt-empty-comment">
			<?php echo JText::_( 'COM_KOMENTO_COMMENTS_NO_COMMENT' ); ?>
		</li>
	<?php } ?>
</ul>
