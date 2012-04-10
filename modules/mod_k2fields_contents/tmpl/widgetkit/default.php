<?php 
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access'); 

$settings->style = $settings->theme;
$settings = get_object_vars($settings);

require_once dirname(__FILE__).'/widgetkithelper.php';

if ($isPartitioned) {
        $partTitles = array('category'=>'categoryname', 'author'=>'author');
        
        jimport( 'joomla.html.pane' );
        
        $pane = JPane::getInstance('tabs');
        
        $partitionId = "mod_k2fields_contents_".$module->id;
        $document->addScriptDeclaration('new WKK2fields("'.$partitionId.'");');

        echo $pane->startPane($partitionId);

        foreach ($itemList as $partId => $list) {
                $partTitle = $list[0]->{$partTitles[$partBy]};
                echo $pane->startPanel($partTitle, $partBy."_page_".$partId );
                require dirname(__FILE__).'/partition.php';
                echo $pane->endPanel();
        }

        echo $pane->endPane();
} else {
        $list = $itemList;
        require dirname(__FILE__).'/partition.php';
        $document->addScriptDeclaration('new WKK2fields();');
}
