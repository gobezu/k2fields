<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 
if (!empty($item->image)) : 
?>
<img src="<?php echo $item->image; ?>" alt="<?php echo $item->title; ?>"/>
<?php
else:
        echo $item->title;
endif;
?>
