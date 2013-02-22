<?php
/**
 * @version		$Id: generic.php 478 2010-06-16 16:11:42Z joomlaworks $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.gr
 * @copyright	Copyright (c) 2006 - 2010 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

if (count($this->items)) :
        $itemsLimit = JRequest::getInt('limit', 10);	
	$charLimit = JRequest::getInt('acmc', 60); 
        $items = array();
        $count = count($this->items);
        $i = 0;
        foreach ($this->items as $item):
                $i++;
                
                // strip text
                $text = str_replace(array("\r\n", "\n", "\r", "\t"), "", $item->introtext);
                $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
                $text = preg_replace('/{.+?}/', '', $text);
                $text = substr(trim(strip_tags($text)), 0, $charLimit);

                // create item
                $item = array(
                    'title' => $item->title,
                    'text' => substr_replace($text, '...', strrpos($text, ' ')),
                    'url' => $item->link,
                    'total' => (int) $this->total,
                    'count' => $count
                );
                
                $items[] = $item;
                
                if ($i >= $itemsLimit) break;
        endforeach;
        
        echo json_encode($items);
endif;