<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!JprovenUtility::checkPluginActive('k2fields', 'system', '', true)) {
        JError::raiseError('500', 'Unable to activate/locate k2fields system plugin which is required for proper functioning of k2fields. Please correct that and try again.');
        return;
}

JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2plugin.php');

class plgk2k2fields extends K2Plugin {
        var $pluginName = 'k2fields';
        var $pluginNameHumanReadable = 'Extending K2';
        
        function plgk2k2fields(&$subject, $params) {
                parent::__construct($subject, $params);
                $this->loadLanguage('', JPATH_ADMINISTRATOR);
        }

        /*** K2 plugin events ***/
        function onK2BeforeDisplay(&$item, &$params, $limitstart) {
                if (JprovenUtility::plgParam('k2fields', 'k2', 'override_itemmodel') != '1')
                        JprovenUtility::normalizeK2Parameters($item);
                
                $link = K2FieldsHelperRoute::getItemRoute($item->id.':'.urlencode($item->alias), $item->catid.':'.urlencode($item->category->alias));
		$item->link = urldecode(JRoute::_($link));
                
                $item->nonk2rating = 
                        JPluginHelper::importPlugin('jcomments', 'rate') || JPluginHelper::importPlugin('slicomments', 'rate');
                
                if (is_string($item->params)) {
                        jimport('joomla.html.parameter');
                        $item->params = new JParameter ($item->params);
                }
                
                self::setLayout($item);
                
                // $this->processSearchPlugins($item);
                self::processSocialbtns('BeforeDisplay', $item, $params, $limitstart);
                return self::processExtrafields('BeforeDisplay', $item, $params, $limitstart);
        }
        
        function onK2AfterDisplay(&$item, &$params, $limitstart) {
                self::processSocialbtns('AfterDisplay', $item, $params, $limitstart);
                return self::processExtrafields('AfterDisplay', $item, $params, $limitstart);
        }
        
        function onK2AfterDisplayTitle(&$item, &$params, $limitstart) {
                self::processSocialbtns('AfterDisplayTitle', $item, $params, $limitstart);
                return self::processExtrafields('AfterDisplayTitle', $item, $params, $limitstart);
        }
        
        function onK2BeforeDisplayContent(&$item, &$params, $limitstart) {
                self::processSocialbtns('BeforeDisplayContent', $item, $params, $limitstart);
                return self::processExtrafields('BeforeDisplayContent', $item, $params, $limitstart);
        }
        
        function onK2AfterDisplayContent(&$item, &$params, $limitstart) {
                self::processSocialbtns('AfterDisplayContent', $item, $params, $limitstart);
                return self::processExtrafields('AfterDisplayContent', $item, $params, $limitstart);
        }
        
        function onK2PrepareContent(& $item, & $params, $limitstart) {
        }

        function onK2CategoryDisplay(&$category, &$params, $limitstart) {
                JprovenUtility::normalizeK2Parameters($category, $params);
                // In view of itemlist layout is set after category plugin called
                // therefore we can't do it here and will rely on having this set in onK2BeforeDisplay
                // but if no items are present then this will not work
//                self::setLayout(null, $category->params);
        }
        
        function onBeforeK2Save(&$item, $isNew) {
                $model = JModel::getInstance('fields', 'K2FieldsModel');
                return $model->preSave($item);
        }
        
        function onAfterK2Save(&$item, $isNew) {
                $model = JModel::getInstance('fields', 'K2FieldsModel');
                $model->save($item, $isNew);
                
                $row = JTable::getInstance('K2Item', 'Table');
                $row->load($item->id);
                
                $app = JFactory::getApplication();
                $glue = self::param('appendtitleglue', ' / ');
                $title = $model->generateTitle($item, $glue);
                
                if ($title) {
                        $t = explode($glue, $row->title);
                        $row->title = $t[0] . $glue . $title;
                        $row->alias = '';
                        
                        if (!$row->check()) {
                                $app->redirect('index.php?option=com_k2&view=item&cid='.$row->id, $row->getError(), 'error');
                        }

                        if (!$row->store()) {
                                $app->redirect('index.php?option=com_k2&view=items', $row->getError(), 'error');
                        }
                        
                        $item->title = $title;
                }
                
                if ($app->isAdmin()) return;
                
                $action = self::param('actionaftersave', 'closeandload');
                $js = false;
                
                switch ($action) {
                        case 'closeandreload':
                                $js = "window.parent.document.location.reload();";
                                break;
                        case 'close':
                                $js = "window.parent.SqueezeBox.close();";
                                break;
                        case 'closeandload':
                        default:
                                $category = JTable::getInstance('K2Category', 'Table');
                                $category->load($item->catid);
                                $link = K2FieldsHelperRoute::getItemRoute($item->id.':'.urlencode($item->alias), $category->id.':'.urlencode($category->alias));
                                $js = "window.parent.document.location.href='".JRoute::_($link)."';";
                                break;
                }
                
                if ($js) {
                        $msg = $isNew ? JText::_('K2_ITEM_SAVED') : JText::_('K2_CHANGES_TO_ITEM_SAVED');
                        $app = JFactory::getApplication();
                        $app->enqueueMessage($msg, 'info');
                        $app->close("<script type='text/javascript'>".$js."</script>\n");
                }
        }        

        function onRenderAdminForm(&$item, $type, $tab = '') {
                if (JFactory::getApplication()->isAdmin() && ($type == 'item' || $type == 'category')) {
                        JprovenUtility::setLayout();
                }
                
                if ($type == 'item') {
//                        $input = JFactory::getApplication()->input;
//                        $option = $input->get('option');
//                        $view = $input->get('view');
//                         && $view == 'item'

                        if (JFactory::getApplication()->isSite()) JprovenUtility::setLayout(); 
                        
                        if ($tab == 'extra-fields') self::loadResources($tab, $item);
                } else if ($type == 'user') {
                        // return self::adjustUserFormLayout($item);
                }
        }
        
        function _v($arr, $ind, $def='') {
                $res = JprovenUtility::value($arr, $ind-1, $def);
                return trim($res);
        }
        
        private static function processSocialbtns($caller, &$item, &$params, $limitstart) {
                if (K2FieldsModelFields::value($item, 'k2item')) return;
                
                $view = JRequest::getCmd('view');
                $pos = $view == 'item' ? 'item' : 'list';
                $pos = self::param('social'.$pos.'position', 'AfterDisplay');
                
                if ($caller != $pos || $pos == 'none') return;
                
                $setting = self::param('socialincludecats', array());
                if (!empty($setting) && !in_array($item->catid, $setting)) return;
                
                $setting = self::param('socialincludeitems', '');
                if (!empty($setting)) {
                        if (!in_array($item->id, $setting)) return;
                }
                
                $setting = self::param('socialexcludecats', array());
                if (!empty($setting) && in_array($item->catid, $setting)) return;
                
                $setting = self::param('socialexcludeitems', '');
                if (!empty($setting)) {
                        if (in_array($item->id, $setting)) return;
                }
                
                $url = trim(self::param('socialfixedurl', ''));
                if (empty($url)) $url = JRoute::_($item->link);
                $url = urlencode($url);
                
                $btns = trim(self::param('socialdisplay', ''));
                $btns = explode(',', $btns);
                
                $ui = array();
                
                foreach ($btns as $btn) {
                        $uiPart = self::_processSocialbtns ($btn, $url, $item);
                        if (!empty($uiPart)) $ui[] = '<li class="k2f'.$btn.'">'.$uiPart.'</li>';
                }
                
                if (!empty($ui)) $ui = '<ul class="k2fsocialbtns">'.implode('', $ui).'</ul>';
                else $ui = '';
                
                $item->text .= $ui;
        }
        
        private static function _processSocialbtns($btn, $url, $item) {
                $ui = '';
                $view = JFactory::getApplication()->input->get('view', '');
                
                switch ($btn) {
                        case 'facebook':
                                $ui = '
<div id="fb-root"></div>
<script>(function(d, s, id) {
var js, fjs = d.getElementsByTagName(s)[0];
if (d.getElementById(id)) return;
js = d.createElement(s); js.id = id;
js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
fjs.parentNode.insertBefore(js, fjs);
}(document, "script", "facebook-jssdk"));</script>
<div class="fb-like" 
';
                                $facebookSend = self::param('socialfacebooksend', false) ? 'true' : 'false';
                                $facebookLayout = self::param('socialfacebooklayout', 'standard');
                                $facebookShowfaces = self::param('socialfacebookshow_faces', false) ? 'true' : 'false';
                                $facebookAction = self::param('socialfacebookaction', 'Like');
                                $facebookWidth = (int) self::param('socialfacebookwidth', 450);
                                
                                if ($facebookLayout == 'standard') {
                                        if ($facebookWidth < 265 && $facebookAction == 'recommend') {
                                                $facebookWidth = $facebookSend == 'true' ? 325 : 265;
                                        } else if ($facebookWidth < 285 && $facebookSend == 'true') {
                                                $facebookWidth = 285;
                                        } else if ($facebookWidth < 225) {
                                                $facebookWidth = 225;
                                        }
                                } else if ($facebookLayout == 'button_count') {
                                        if ($facebookWidth < 120 && $facebookAction == 'recommend') {
                                                $facebookWidth = $facebookSend == 'true' ? 180 : 120;
                                        } else if ($facebookWidth < 150 && $facebookSend == 'true') {
                                                $facebookWidth = 150;
                                        } else if ($facebookWidth < 90) {
                                                $facebookWidth = 90;
                                        }
                                } else if ($facebookLayout == 'box_count') {
                                        if ($facebookWidth < 85 && $facebookAction == 'recommend') {
                                                $facebookWidth = $facebookSend == 'true' ? 145 : 85;
                                        } else if ($facebookWidth < 145 && $facebookSend == 'true') {
                                                $facebookWidth = 145;
                                        } else if ($facebookWidth < 55) {
                                                $facebookWidth = 55;
                                        }
                                }
                                
                                $facebookFont = self::param('socialfacebookfont', false);
                                $facebookColorscheme = self::param('socialfacebookcolorscheme', false);

                                $ui .= ' data-href="'.$url.
                                        '" data-send="'.$facebookSend.
                                        '" data-layout="'.$facebookLayout.
                                        '" data-show-faces="'.$facebookShowfaces.
                                        '" data-width="'.$facebookWidth.
                                        '" data-action="'.$facebookAction.'" '.
                                        '" data-font="'.$facebookFont.'" '.
                                        '" data-color-scheme="'.$facebookColorscheme.'"></div>'
                                ;
                                
                                break;
                        case 'twitter':
                                $tweetText = self::param('socialtwittertext', '');
                                if (!empty($tweetText)) $tweetText = ' data-text="'.$tweetText.'"';

                                $tweetCount = self::param('socialtwittercount', false) ? '' : ' data-count="none"';

                                $tweetVia = self::param('socialtwittervia', '');
                                if (!empty($tweetVia)) $tweetVia = ' data-via="'.$tweetVia.'"';

                                $tweetRelated = self::param('socialtwitterrelated', '');
                                if (!empty($tweetRelated)) $tweetRelated = ' data-related="'.$tweetRelated.'"';

                                $tweetHash = self::param('socialtwitterhash', '');
                                if (!empty($tweetHash)) $tweetHash = ' data-hashtags="'.$tweetHash.'"';

                                $tweetButton = self::param('socialtwitterbutton', '');
                                if (!empty($tweetButton)) $tweetButton = ' data-size="'.$tweetButton.'"';

                                $ui = '
<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$url.'"'.$tweetText.$tweetCount.$tweetVia.$tweetRelated.$tweetHash.$tweetButton.'>Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>                                
';
                                break;
                        case 'linkedin':
                                $linkedinCounter = self::param('sociallinkedincounter', 'top');
                                $ui = '
<script src="http://platform.linkedin.com/in.js" type="text/javascript"></script>
<script type="IN/Share" data-url="'.$url.'" data-counter="'.$linkedinCounter.'"></script>
';
                                break;
                        case 'pinterest':
                                static $pinterestJSLoaded = false;
                                if (!$pinterestJSLoaded) {
                                        $ui .= '
<script type="text/javascript">
(function() {
    window.PinIt = window.PinIt || { loaded:false };
    if (window.PinIt.loaded) return;
    window.PinIt.loaded = true;
    function async_load(){
        var s = document.createElement("script");
        s.type = "text/javascript";
        s.async = true;
        if (window.location.protocol == "https:")
            s.src = "https://assets.pinterest.com/js/pinit.js";
        else
            s.src = "http://assets.pinterest.com/js/pinit.js";
        var x = document.getElementsByTagName("script")[0];
        x.parentNode.insertBefore(s, x);
    }
    if (window.attachEvent)
        window.attachEvent("onload", async_load);
    else
        window.addEventListener("load", async_load, false);
})();
</script>                                                
';
                                        $pinterestJSLoaded = true;
                                }
                                $pinterestCounter = self::param('socialpinterestcounter', 'top');
                                
                                $pinterestDescription = self::param('socialpinterestdescription', '');
                                if ($pinterestDescription == 'text') {
                                        $pinterestDescription = self::param('socialpinterestdescriptiontext', '');
                                } else if ($pinterestDescription == 'item') {
                                        $pinterestDescription = $item->title;
                                } else if ($pinterestDescription == 'image') {
                                        // TODO: which image?
                                }
                                $pinterestDescription = urlencode($pinterestDescription);
                                
                                // TODO: which image should be pinned, ie. media parameter in url below
                                $pinterestImage = self::param('socialpinterestcounter', 'top');
                                
                                $ui .= '
<a href="http://pinterest.com/pin/create/button/?url='.$url.'&media=urlofimage.com&description=Optionalpindescription" class="pin-it-button" count-layout="'.$pinCounter.'">Pin It</a>                                        
';
                                break;
                        case 'googleplus':
                                static $googleplusJSLoaded = false;
                                if (!$googleplusJSLoaded) {
                                        $ui = '
<script type="text/javascript">
  (function() {
    var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
    po.src = "https://apis.google.com/js/plusone.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);
  })();
</script>                                                
';
                                        $googleplusJSLoaded = true;
                                }
                                
                                $googleplusAnnotation = self::param('socialgoogleplusannotation', 'inline');
                                
                                $googleplusSize = self::param('socialgoogleplussize', '');
                                if (!empty($googleplusSize)) $googleplusSize = ' size="'.$googleplusSize.'"';
                                
                                $ui .= '<g:plusone '.$googleplusSize.' annotation="'.$googleplusAnnotation.'"></g:plusone>';
                                
                                break;
                        case 'readability':
                                if ($view != 'item') return '';
                                
                                $readabilityRead = self::param('socialreadabilityread', '0');
                                $readabilityPrint = self::param('socialreadabilityprint', '0');
                                $readabilityEmail = self::param('socialreadabilityemail', '0');
                                $readabilityKindle = self::param('socialreadabilitykindle', '0');
                                
                                if (!$readabilityEmail && !$readabilityRead && !$readabilityKindle && !$readabilityPrint) return '';
                                
                                $readabilityOrientation = self::param('socialreadabilityorientation', '0');
                                $readabilityColorText = self::param('socialreadabilitycolortext', '#5c5c5c');
                                $readabilityColorBg = self::param('socialreadabilitycolorbg', '#f3f3f3');
                                
                                $ui = '<div class="rdbWrapper" data-show-read="'.$readabilityRead.'" data-show-send-to-kindle="'.$readabilityKindle.'" data-show-print="'.$readabilityPrint.'" data-show-email="'.$readabilityEmail.'" data-orientation="'.$readabilityOrientation.'" data-version="1" data-text-color="'.$readabilityColorText.'" data-bg-color="'.$readabilityColorBg.'"></div>';
                                $ui .= '<script type="text/javascript">(function() {var s = document.getElementsByTagName("script")[0],rdb = document.createElement("script"); rdb.type = "text/javascript"; rdb.async = true; rdb.src = document.location.protocol + "//www.readability.com/embed.js"; s.parentNode.insertBefore(rdb, s); })();</script>';
                                break;
                        case 'flattr':
                                if ($view != 'item') return '';
                                
                                $flattrTitle = self::param('socialflattrtitle', $item->title);
                                $flattrDescription = self::param('socialflattrdescription', $item->metadesc);
                                $flattrUID = self::param('socialflattruid', '');
                                if (!empty($flattrUID)) $flattrUID = ';uid:'.$flattrUID;
                                $flattrCategory = self::param('socialflattrcategory', '');
                                if (!empty($flattrCategory)) $flattrCategory = ';category:'.$flattrCategory;
                                $flattrTags = self::param('socialflattrtags', '');
                                if (!empty($flattrTags)) $flattrTags = ';tags:'.$flattrTags;
                                $flattrHidden = self::param('socialflattrhidden', '');
                                if (!empty($flattrHidden)) $flattrHidden = ';hidden:1';
                                $flattrButton = self::param('socialflattrbutton', '');
                                if (!empty($flattrButton)) $flattrButton = ';button:compact';
                                
                                $ui = '
<a title="'.$flattrTitle.'" rel="flattr'.$flattrUID.$flattrCategory.$flattrTags.$flattrHidden.$flattrButton.';" href="'.$url.'" class="FlattrButton" style="display:none;">'.$flattrDescription.'</a>
<script type="text/javascript">
/* <![CDATA[ */
(function() {
    var s = document.createElement("script");
    var t = document.getElementsByTagName("script")[0];

    s.type = "text/javascript";
    s.async = true;
    s.src = "http://api.flattr.com/js/0.6/load.js?mode=auto";

    t.parentNode.insertBefore(s, t);
 })();
/* ]]> */
</script>
';
                                
                                
                                break;
                }
                
                return $ui;
        }
        
        private static function processExtrafields($caller, &$item, &$params, $limitstart) {
                if (K2FieldsModelFields::value($item, 'k2item')) return;
                
                $pos = K2FieldsModelFields::categorySetting($item->catid, 'catextrafieldsposition');
                
                if (!$pos) $pos = self::param('extrafieldsposition', 'AfterDisplay');
                
                if ($caller != $pos) return;
                
                $p = is_object($item->params) ? $item->params : $params;
                $view = JRequest::getCmd('view');
                
                $ef = is_array($item->extra_fields) && count($item->extra_fields) > 0 &&
                      (
                        $params->get('parsedInModule') || 
                        $view == 'itemlist' && $p->get('catItemExtraFields') || 
                        $view == 'item' && $p->get('itemExtraFields')
                      )
                        ;
                
                if (!$ef) return;
                
                $inText = false;
                
                if (preg_match('#(\{k2f[^\}]*})#i', $item->text, $plg)) {
                        $plg = $plg[0];
                        $inText = true;
                } else {
                        jimport('joomla.filesystem.file');

                        $plg = '';
                        $view = JFactory::getApplication()->input->get('view');
                        $file = JprovenUtility::createTemplateFileName($params->get('theme'), 'fields', array('i'.$item->id, 'c'.$item->catid));
                        
                        if ($file) {
                                $plg = JFile::read($file);
                                $plg = trim($plg);
                        }
                        
                        if (empty($plg)) $plg = '{k2f}';
                }
                
                $tmp = $item->text;
                $item->text = $plg;
                $item = JprovenUtility::replacePluginValues($item, 'k2f', false, array('parsedInModule'=>$params->get('parsedInModule')));
                // TOO obtrusive
                $item->extra_fields = array();
                $result = $item->text;
                
                $item->text = $inText ? str_replace($plg, $result, $tmp) : $tmp;
                
                if ($result) self::loadResources('item', $item);

                return $inText ? '' : $result;
        }
        
        // TODO: what happens when we have items from various categories, as in search results?
        private static function setLayout(&$item = null, $cparams = null) {
                $view = JFactory::getApplication()->input->get('view');
                
                if ($item) {
                        if ($item->params->get('parsedInModule')) return;
                        
                        $tabular = (array) self::param('tabularlayout');
                        $item->isItemlistTabular = $view == 'itemlist' && !empty($tabular) && in_array($item->catid, $tabular);
                }
                
                static $isLayoutSet = false;
                
                if ($isLayoutSet) return;
                
                $layout = self::param('specificLayout', 'yes');
                
                if ($layout == 'yes') {
                        $params = K2HelperUtilities::getParams('com_k2');
                        
                        if (!empty($item) && empty($cparams)) {
                                if (isset($item->categoryparams)) {
                                        $cparams = $item->categoryparams;
                                } else if (isset($item->category) && is_object ($item->category)) {
                                        $cparams = $item->category->params;
                                } else {
                                        $query = 'SELECT params FROM #__k2_categories WHERE id = '.$item->catid;
                                        $db = JFactory::getDBO();
                                        $db->setQuery($query);
                                        $cparams = $db->loadResult();
                                }
                                
                                if (!empty($cparams)) {
                                        if (is_string($cparams))
                                                $cparams = new JParameter($cparams);

                                        if ($cparams->get('inheritFrom')) {
                                                $masterCategory = &JTable::getInstance('K2Category', 'Table');
                                                $masterCategory->load($cparams->get('inheritFrom'));
                                                $cparams = new JParameter($masterCategory->params);
                                        }

                                        $params = $cparams;
                                }
                        } else if (!empty($cparams)) {
                                if (is_string($cparams))
                                        $cparams = new JParameter($cparams);
                                
                                $params = $cparams;
                        }
                        
                        $theme = $params->get('theme');
                        $addId = $view == 'item' ? $item->catid : -1;
                        $layout = JprovenUtility::setLayout($theme, null, null, null, $addId);
                }
                
                $isLayoutSet = true;
        }       
        
        private static function adjustUserFormLayout($item) {
                // TODO: http://mootools.net/forge/p/form_passwordstrength
                // Generate user profile fields based on definition in plugin setting
                $data = self::param('userprofilefields');
                $data = explode("\n", $data);
                $colName = 1;
                $colType = 2;
                $colLabel = 3;
                $colOptions = 4;
                $colDefault = 5;
                $colRequired = 6;
                $colClass = 7;

                $xml = '';

                foreach ($data as $d) {
                        $d = trim($d);

                        if (empty($d)) continue;

                        $d = explode(K2FieldsModelFields::VALUE_SEPARATOR, $d);

                        $name = self::_v($d, $colName);
                        $type = self::_v($d, $colType, 'text');
                        $label = self::_v($d, $colLabel);
                        $options = self::_v($d, $colOptions);
                        $default = self::_v($d, $colDefault, '');
                        $required = self::_v($d, $colRequired, '');
                        $class = self::_v($d, $colClass, '');

                        $class .= (!empty($required) ? ' required' : '');
                        if (!empty($class))
                                $class = ' class="'.trim($class).'"';

                        $xml .= '<param name="'.$name.'" type="'.$type.'" label="'.$label.'" default="'.$default.'"'.$class;

                        switch ($type) {
                                case 'sql':
                                        $xml .= ' query="'.$options.'">';
                                        break;
                                case 'list':
                                case 'radio':
                                case 'yesno':
                                case 'binary':
                                        $xml .= '>';
                                        if ($type == 'yesno' || $type == 'binary') $options = '0=No|1=Yes';
                                        $options = explode('|', $options);
                                        foreach ($options as $option) {
                                                $option = explode('=', $option);
                                                if (count($option) == 1) $option[] = $option[0];
                                                $xml .= '<option value="'.$option[0].'">'.$option[1].'</option>';
                                        }
                                        break;
                                case 'textarea':
                                        if (empty($options)) {
                                                $options = array(40, 10);
                                        } else {
                                                $options = explode('|', $options);
                                        }
                                        $xml .= ' col="'.$options[0].'" rows="'.$options[1].'">';
                                        break;
                                case 'text':
                                case 'hidden':
                                default:
                                        if (empty($options)) $options = 40;
                                        $xml .= ' size="'.$options.'">';
                                        break;
                        }
                        $xml .= '</param>';
                }

                $allow = self::param('userprofileallowgroup');
                $user = JFactory::getUser();

                if (isset($item->id) && $item->id == $user->id) {
                        // Editing
                        $allow = $allow == 'all';
                } else {
                        // Registering
                        $allow = $allow != 'never';
                }

                if ($allow) {
                        $where = '';

                        if ($user->guest || $user->gid < 23) {
                                 $where = " WHERE permissions LIKE '%editAll=0%'";
                        }

                        $k2Params = JComponentHelper::getParams('com_k2');
                        $groupDefault = $k2Params->get('K2UserGroup', 1);
                        $xml = '<param name="userprovidedgroup" type="sql" default="'.$groupDefault.'" query="SELECT id AS value, name AS k2fieldsuserprovidedgroup FROM #__k2_user_groups'.$where.'" label="Profile"></param>'.$xml;
                }

                if (empty($xml)) return null;

                $xml = 
'<?xml version="1.0" encoding="utf-8"?>
<k2fields>
<params group="k2fields" addpath="/administrator/components/com_k2fields/elements">
        '.$xml.'
</params>
</k2fields>';

                $xmlParser = JFactory::getXMLParser('Simple');
                // TODO: temporary fix with name isf $this->pluginName
                $form = new K2Parameter($item->plugins, '', 'k2fields');

                if ($xmlParser->loadString($xml)) {
                        if ($params = $xmlParser->document->params) {
                                foreach ($params as $param) {
                                        $form->setXML($param);
                                }
                        }
                }

                $fields = $form->render('plugins', 'k2fields');
                $plugin = new JObject;
                // TODO: temporary fix with human readable name ($this->pluginNameHumanReadable)
                $plugin->set('name', 'Extending K2');
                $plugin->set('fields', $fields);

                return $plugin;                
        }
        
//        private function addTemplatePathsForItem() {
//                $option = JRequest::getCmd('option');
//                $view = JRequest::getWord('view');
//                
//                if ($option != 'com_k2' || $view != 'item') return;
//                
//                // rating template
//                $controller = JprovenUtility::getK2Controller();
//                
//                if (empty($controller)) return;
//                
//                $app = JFactory::getApplication();
//
//                $dirs = array(
//                    JPATH_SITE.'/components/com_k2fields/templates',
//                    JPATH_SITE.'/components/com_k2fields/templates/default',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/templates',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/templates/default',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/item',
//                    JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2fields/item/tmpl'
//                );
//
//                $document = JFactory::getDocument();
//                $viewType = $document->getType();
//                $view = $controller->getView($view, $viewType);
//                
//                foreach ($dirs as $dir) 
//                        $view->_addPath('template', $dir);
//        }
        
        /*** utilities ***/
        public static function param($name, $value = '', $dir = 'get') {
                if ($dir == 'get') return JprovenUtility::plgParam('k2fields', 'k2', $name, $value, $dir);
                
                JprovenUtility::plgParam('k2fields', 'k2', $name, $value, $dir);
        }        
        
        static function getMode($tab) {
                return $tab == 'extra-fields' ? 'edit' : $tab;
        }
        
        static function getFieldPrefix($tab = null) {
                if (empty($tab)) {
                        $type = JRequest::getCmd('type', '');
                        
                        if ($type == 'searchfields') {
                                $tab = 'search';
                        }
                } 
                
                if ($tab == 'search' || $tab == 'menu') return 's';
                else if ($tab == 'editfields') return 'ef';
                else return 'K2ExtraField_';
//                return $tab == 'search' || $tab == 'menu' ? 's' : 'K2ExtraField_';
        }
        
        public static function loadResources($tab = null, $item = null, $addParams = null) {
                static $jsDone = false, $jsK2fDone = false, $includeDone = false, $itemDone = false, $compressedLoaded = false;
                
                $document = JFactory::getDocument();
                
                if ($item && !$itemDone) {
                        // TODO: fields are available only when category is known in 
                        // advance which in case of backend new content creation is not 
                        // known. Need to be loaded clientside synchronously by each type
                        
                        if (K2FieldsModelFields::isContainsType('map', $item->catid, $tab == 'search' || $tab == 'menu' ? 'search' : null) && $tab != 'search') {
                                $document->addScript('http://maps.google.com/maps/api/js?sensor=false');
                        }
                        
                        $itemDone = true;
                }
                
                if (!$includeDone) {
                        JprovenUtility::load('k2fields.css', 'css');
                        $includeDone = true;
                }
                
                if (!$jsDone) {
                        JprovenUtility::loc(true, true, 'lib/datepicker.js', true);
                        $theme = JprovenUtility::plgParam('k2fields', 'k2', 'datepickertheme', 'datepicker_dashboard');
                        JprovenUtility::loc(true, true, 'lib/datepicker/'.$theme.'/'.$theme.'.css', true, 'css');
                        
                        // Loading order here is important as there is dependency
                        if ($tab == 'editfields' || $tab == 'extra-fields') {
                                JprovenUtility::loc(true, true, 'lib/Formular/formular.js', true);
                                JprovenUtility::loc(true, true, 'lib/Formular/formular.css', true, 'css');
                        }
                        
                        JprovenUtility::loc(true, true, 'lib/autocompleter.js', true);
                        
                        if ($tab == 'menu') JprovenUtility::load('jpmenuitemhandler.js', 'js');
                        
                        if (JFile::exists(JPATH_SITE.'/media/k2fields/js/k2fields.all.js')) {
                                $ver = "$Ver$";
                                JprovenUtility::load('k2fields.all.js?v='.$ver, 'js');
                                $compressedLoaded = true;
                        } else {
                                JprovenUtility::load('jpform.js', 'js');
                                JprovenUtility::load('jputility.js', 'js');
                                JprovenUtility::load('jpsearch.js', 'js');
                                JprovenUtility::load('jpvalidator.js', 'js');
                                JprovenUtility::load('k2fields_options.js', 'js');
                                JprovenUtility::load('k2fields.js', 'js');
                                JprovenUtility::load('jpprocessor.js', 'js');
                        }
                        
                        $modalize = JprovenUtility::plgParam('k2fields', 'k2', 'modalizelinks');
                        
                        if (!empty($modalize)) {
                                $modalizes = explode("\n", $modalize);
                                $uri = JURI::getInstance();
                                $path = $uri->getPath();
                                if (strpos($path, 'index.php') === false) $path = $path . 'index.php';
                                
                                foreach ($modalizes as &$modalize) {
                                        $modalize = explode(K2FieldsModelFields::VALUE_SEPARATOR, $modalize);
                                        if ($modalize[0]) $modalize[0] = JURI::root(true).'/'.$modalize[0];
                                        if ($modalize[1]) $modalize[1] = $path.'?'.$modalize[1];
                                }
                        } else {
                                $modalizes = array();
                        }
                        
                        $returnvalue = JFactory::getURI();
			$returnvalue = $returnvalue->toString(array('path', 'query', 'fragment'));
                        $returnvalue = base64_encode($returnvalue);
                        
                        $returnvalue = array(array(JURI::root(true).'/logout', JURI::root(true).'/index.php?option=com_user&task=logout', $returnvalue));
                        
                        $document->addScriptDeclaration("\n".'window.addEvent("domready", function(){ new JPProcessor({"jmodal":'.json_encode($modalizes).', "returnvalue":'.json_encode($returnvalue).'}).process(); });');
                        
                        $jsDone = true;
                }
                
                if (in_array($tab, array('extra-fields', 'search', 'menu', 'editfields')) && !$jsK2fDone) {
                        $document = JFactory::getDocument();
                        
                        if (!$compressedLoaded && JprovenUtility::plgParam('k2fields', 'k2', 'preloadjsmodules', true) && $tab != 'k2fields-editor') {
                                static $modules = array('basic', 'complex', 'list', 'map', 'media', 'k2item');
                                
                                foreach ($modules as $module) {
                                        JprovenUtility::load('k2fields'.$module.'.js', 'js');
                                }
                        }
                        
                        JModel::getInstance('searchterms', 'k2fieldsmodel');
                        
                        $params = array(
                                'listItemSeparator' => K2FieldsModelFields::LIST_ITEM_SEPARATOR,
                                'listConditionSeparator' => K2FieldsModelFields::LIST_CONDITION_SEPARATOR,
                                'valueSeparator' => K2FieldsModelFields::VALUE_SEPARATOR,
                                'multiValueSeparator' => K2FieldsModelFields::MULTI_VALUE_SEPARATOR,
                                'userAddedValuePrefix' => K2FieldsModelFields::USERADDED,
                                'base' => JURI::root(),
                                'k2fbase' => JprovenUtility::loc(),
                                'mode' => self::getMode($tab),
                                'pre' => self::getFieldPrefix($tab),
                                'extendables' => self::getExtendables(),
                                'selfName' => K2FieldsModelFields::JS_VAR_NAME,
                                'maxListItem' => K2FieldsModelFields::setting('listmax'),
                                'datetimeFormat' => K2FieldsModelFields::setting('datetimeFormat'),
                                'dateFormat' => K2FieldsModelFields::setting('dateFormat'),
                                'timeFormat' => K2FieldsModelFields::setting('timeFormat'),
                                'weekstartson' => K2FieldsModelFields::setting('weekstartson'),
                                'autoFields' => K2FieldsModelFields::$autoFieldTypes,
                                'maxFieldLength' => K2FieldsModelFields::setting('alphafieldmaxlength')
                        );
                        
                        if (isset($addParams)) $params = array_merge($params, $addParams);
                        
                        $params = json_encode($params);
                        
                        $document->addScriptDeclaration('var '.K2FieldsModelFields::JS_VAR_NAME.' = new k2fields('.$params.');');
                        
                        if ($tab == 'editfields' || $tab == 'extra-fields') {
                                JprovenUtility::loc(true, true, 'lib/Form.AutoGrow.js', true);
                                JprovenUtility::loc(true, true, 'lib/Form.Placeholder.js', true);
                        }
                        
                        $jsK2fDone = true;
                        
                        if ($tab == 'search') JprovenUtility::load('jpsearch.js', 'js');
                }        
        }
        
        static function getExtendables() {
                jimport('joomla.filesystem.folder');
                
                $loc = JprovenUtility::loc(false, true) . 'js';
                $files = JFolder::files($loc, 'k2fields[a-z0-9]+\.js', false, false);
                
                foreach ($files as &$file) $file = str_replace(array('k2fields', '.js'), '', $file);
                
                return $files;
        }
        
        static function getK2Fields($value = null, $mode = 'group', $modeFilter = null) {
                if (is_object($value)) {
                        $value = $value->catid;
                        $mode = 'group';
                }

                $model = JModel::getInstance('fields', 'K2FieldsModel');
                $fields = $model->getFields($value, $mode, $modeFilter);
                
                return $fields;
        }
        
        function processSearchPlugins(&$item) {
                $searchRecord = K2FieldsModelFields::categorySetting($item->catid, 'autorelatedlistgenerate');
                $o = new stdClass();
                $plg = '';
                $o->text = $item->text;
                $o->id = $item->id;
                $plg = '';
                
                if (!empty($searchRecord)) {
                        $foundCat = JprovenUtility::firstKey($searchRecord);
                        $searchRecord = JprovenUtility::first($searchRecord);
                        $searchRecord = $searchRecord[0];
                        
                        // 0. search within categoryid
                        // 1. generate as(url,list)
                        // 2. based on (tag|keyword|density based keyword(TBI)|fieldids,fieldposition)
                        // 3. fixed values(if field)
                        // 4. search in (tag|keyword|density based keyword(TBI)|fieldids,fieldposition)
                        // 5. exclude specific sub-categories|all
                        $colAs = 1;
                        $colBase = 2;
                        $colFixedValue = 3;
                        $colSearchCat = 4;
                        $colSearchIn = 5;
                        $colPos = 6;
                        $colExcludeSubCat = 7;
                        $colExcludeSearchSubCat = 8;
                        
                        $excluded = false;
                        $excluded = self::_v($searchRecord, $colExcludeSubCat, false);
                        
                        if (!empty($excluded)) {
                                $excluded = explode(',', $excluded);

                                $excluded = in_array($item->catid, $excluded) || 
                                        (in_array('all', $excluded) && $foundCat != $item->catid);
                        }
                        
                        if (!$excluded) {
                                $plg = '{k2fsearch ';
                                
                                $catid = self::_v($searchRecord, $colSearchCat);
                                
                                if (!empty($catid))
                                        $plg .= ' cid='.$catid;
                                
                                $as = self::_v($searchRecord, $colAs, self::param('defaultrelatedas'));
                                
                                if (!empty($as))
                                        $plg .= ' as='.$as;
                                
                                $searchValues = self::_v($searchRecord, $colFixedValue);
                                $basedOn = self::_v($searchRecord, $colBase);
                                
                                if (empty($basedOn) && empty($searchValues)) {
                                        $basedOn = 'keyword';
                                }
                                
                                if ($basedOn == 'tag') {
                                        // TBI
                                        $query = 'SELECT DISTINCT t.name FROM #__k2_tags AS t, #__k2_tags_xref r WHERE t.id = r.tagID AND r.itemID = '.$item->id;
                                        $db = JFactory::getDBO();
                                        $db->setQuery($query);
                                        $searchValues = $db->loadResultArray();
                                } else if ($basedOn == 'keyword') {
                                        // TBI
                                        $searchValues = $item->metakey;
                                        
                                        if (!empty($searchValues)) {
                                                if (strpos($searchValues, '||')) {
                                                        $searchValues = explode('||', $searchValues);
                                                } else {
                                                        $searchValues = explode(',', $searchValues);
                                                }
                                        }
                                } else if ($basedOn == 'density') {
                                        // TBI
                                } else if (!empty($basedOn)) {
                                        $flds = explode('|', $basedOn);
                                        $basedOn = 'fields';
                                        JModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2fields/models/');
                                        $model = JModel::getInstance('fields', 'K2FieldsModel');
                                        $ids = array();
                                        
                                        foreach ($flds as $fld) {
                                                if (!is_numeric($fld)) 
                                                        list($fld, $pos) = explode(',', $fld);
                                                
                                                $ids[] = $fld;        
                                        }
                                        
                                        $_searchValues = $model->itemValues($item->id, $ids);
                                        $searchValues = array();
                                        
                                        foreach ($flds as $fld) {
                                                $pos = -1;
                                                
                                                if (!is_numeric($fld)) 
                                                        list($fld, $pos) = explode(',', $fld);
                                                
                                                $ids[] = $fld;
                                                
                                                if (isset($_searchValues[$fld])) {
                                                        $val = $_searchValues[$fld];
                                                        
                                                        foreach ($val as $v) {
                                                                if ($pos != -1 && $v->partindex == $pos || $v->partindex != -1) {
                                                                        $searchValues[] = $v->value;
                                                                        break;
                                                                }
                                                        }
                                                }
                                        }
                                }
                                
                                if (!empty($searchValues) && ($basedOn == 'keyword' || $basedOn == 'tag')) {
                                        $val = $searchValues;
                                        $searchValues = array();
                                        
                                        foreach ($val as $v)
                                                $searchValues[] = explode('|', $v);
                                        
                                        if ($basedOn == 'keyword') {
                                                $act = (bool) self::param('removekeywordrelated');
                                                
                                                if ($act) {
                                                        $item->metakey = '';
                                                } else {
                                                        $item->metakey = str_replace(array('|', '||'), ',', $item->metakey);
                                                }
                                        }
                                }
                                
                                if (empty($basedOn) || empty($searchValues)) {
                                        $searchValues = self::_v($searchRecord, $colFixedValue);
                                        $basedOn = 'fixed';
                                }
                                
                                if ($basedOn == 'fixed' && !empty($searchValues)) {
                                        $searchValues = explode('||', $searchValues);
                                        
                                        foreach ($searchValues as &$searchValue) 
                                                $searchValue = explode('|', $searchValue);
                                }
                                
                                $searchIn = self::_v($searchRecord, $colSearchIn, 'text');
                                
                                if ($searchIn == 'text') {
                                        $plg .= ' ft='.$searchValues;
                                } else if (!empty($searchIn)) {
                                        $sFlds = explode('||', $searchIn);
                                        $searchIn = 'fields';
                                        
                                        foreach ($sFlds as $i => $sFld) {
                                                foreach ($searchValues[$i] as $val)
                                                        $plg .= ' '.$sFld.'='.$val;
                                        }
                                }
                                
                                if (empty($searchIn)) 
                                        $plg = '';
                        }
                        
                        if (!empty($plg)) {
                                $plg .= '}';
                                
                                $pos = self::_v($searchRecord, $colPos, self::param('defaultrelatedposition'));
                                
                                switch ($pos) {
                                        case 'start':
                                        case 'first':
                                                $o->text = $plg . $o->text;
                                                
                                                break;
                                        case 'afterintro':
                                        case 'beforefull':
                                                list($intro, $full) = explode('{K2Splitter}', $o->text);
                                                
                                                if ($pos == 'afterintro') {
                                                        $intro .= $plg;
                                                } else if ($pos == 'beforefull') {
                                                        $full = $plg . $full;
                                                }
                                                
                                                $o->text = $intro . '{K2Splitter}' . $full;
                                                
                                                break;
                                        case 'last':
                                        case 'end':
                                        default:
                                                $o->text .= $plg;
                                                
                                                break;
                                }
                        }
                }
                
                $o = JprovenUtility::replacePluginValues($o, 'k2fsearch');
                $item->text = $o->text;
        }
}