<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!JprovenUtility::checkPluginActive('k2fields', 'system', '', true)) {
        JError::raiseError('500', 'Unable to activate/locate k2fields system plugin which is required for proper functioning of k2fields. Please correct that and try again.');
        return;
}

JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2plugin.php');

class plgk2k2komento extends K2Plugin {
        var $pluginName = 'k2komento';
        var $pluginNameHumanReadable = 'K2/Komento integration';
                
        function plgk2k2komento(&$subject, $params) {
                parent::__construct($subject, $params);
                $this->loadLanguage('', JPATH_ADMINISTRATOR);
        }
        
        public function onK2CommentsBlock(&$item, &$params, $limitstart) {
                JLoader::register('KomentoRate', JPATH_SITE.'/plugins/komento/rate/rate.class.php');
                $rater = new KomentoRate();
                $rater->form();
        }
}