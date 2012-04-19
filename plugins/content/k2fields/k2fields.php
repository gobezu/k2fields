<?php
//$Copyright$

defined('_JEXEC') or die;

class plgContentK2fields extends JPlugin {
        public function onContentPrepare($context, &$article, &$params, $page = 0) {
                $result = JprovenUtility::replacePluginValues($article->text, 'k2f', false, array('parsedInModule'=>true));
                
                if ($result === false) return true;
        }
}
?>
