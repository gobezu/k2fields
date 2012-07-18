<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/media/k2fields/lib/mime.php';

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
                
class K2FieldsMedia {
        const CAPTIONPOS = 0;
        const SRCTYPEPOS = 1; 
        const MEDIATYPEPOS = 2; 
        const CAPTIONNAMINGPOS = 3;
        const SRCPOS = 4;
        const THUMBSRCPOS = 5;
        const REMOTEURLDL = 6;
        
        public static $extMime, $mimeExt, $mimeType, $pluginType;
        
        private static $files, $collectedFiles;
        
        protected static function reformatFileName($file) {
                if (empty($file)) return '';
                $site = JPath::clean(JPATH_SITE, '/');
                $file = JPath::clean($file, '/');
                $file = substr(str_replace(array($site, '\\'), array('', '\\\\'), $file), 1);
                return $file;
        }
        
        protected static function renameFile($fileName, $options) {
                if ((bool) $options['renamefiles']) {
                        $pat = '#[^\d\w-_]#i';
                        $replaceTo = '_';
                        $fileName = preg_replace($pat, $replaceTo, $fileName);
                }
                
                return $fileName;
        }
        
        protected static function collectFilesToSave($item, $fields) {
                if (!isset(self::$files)) self::$files = JRequest::get('files');
                
                if (isset(self::$collectedFiles)) return;
                
                $files = array();
                $pat = "#^k2fieldsmedia_(\d+)$#";
                $patThumb = "#^k2fieldsmedia_(\d+)_thumb$#";
                
                foreach (self::$files as $fieldName => $__files) {
                        if (($fileFound = preg_match($pat, $fieldName, $fieldId)) 
                         || ($thumbFound = preg_match($patThumb, $fieldName, $fieldId))) {  // k2fields file
                                $fieldId = $fieldId[1];
                                
                                foreach ($__files['error'] as $i => $error) {
                                        if ($error === 0) {
                                                if (!isset($files[$fieldId])) {
                                                        $files[$fieldId] = array();   
                                                }
                                                
                                                if (!isset($thumbFound))
                                                        $thumbFound = preg_match($patThumb, $fieldName);
                                                
                                                $files[$fieldId][] = array(
                                                    'index'=>$i, 
                                                    'thumb'=>$thumbFound?true:false, 
                                                    'is'=>$thumbFound?'thumb':'file'
                                                );
                                                
                                                $ml = K2FieldsModelFields::value($fields[$fieldId], 'medialimit');
                                                
                                                if (isset($ml[$item->catid])) {
                                                        $lim = $ml[$item->catid];
                                                } else {
                                                        $lim = $ml['_all_'];
                                                }
                                                
                                                if ($lim != 0 && count($files[$fieldId]) > $lim) {
                                                        $item->setError(self::error(
                                                                JText::sprintf(
                                                                        "Maximum number of media uploads of %d is exceeded.", 
                                                                        $lim
                                                                ),
                                                                '',
                                                                false
                                                        ));
                                                        return false;
                                                }
                                                
                                                if (!$thumbFound) {
                                                        $thName = 'k2fieldsmedia_'.$fieldId.'_thumb';

                                                        if (key_exists($thName, self::$files)) {
                                                                $__files = self::$files[$thName];
                                                                $files[$fieldId][count($files[$fieldId]) - 1]['thumb'] = $__files['error'][$i] === 0;
                                                        }
                                                }
                                        }
                                }
                        }
                        
                        $fileFound = $thumbFound = false;
                }
                
                self::$collectedFiles = $files;
        }
        
        protected static function saveForField($item, $fieldOptions, $fieldValues, $uploadDir) {
                $fieldId = $fieldOptions['id'];
                
                if (!isset(self::$collectedFiles[$fieldId])) return false;
                
                $dstDir = self::getStorageDirectory($fieldOptions, $item, false);

                if (!is_string($dstDir)) return $dstDir;
                
                $fileRecords = self::$collectedFiles[$fieldId];                
                $fileInd = 'k2fieldsmedia_'.$fieldId;
                
                foreach ($fileRecords as $fileRecord) {
                        $is = $fileRecord['is'];
                        
                        if ($is != 'file') continue;
                        
                        $ind = $fileRecord['index'];
                        $thumbUploaded = $fileRecord['thumb'];
                        $thumbName = '';
                        $resultsThumb = '';
                        $results = array();
                        $thumbFilled = false;

                        // save main file if available
                        if ($is == 'file') {
                                $fileName = self::$files[$fileInd]['name'][$ind];
                                $mimeType = self::$files[$fileInd]['type'][$ind];
                                
                                $results = self::saveUploadedFile(
                                        $item, 
                                        $uploadDir, 
                                        $dstDir, 
                                        $fileName, 
                                        self::$files[$fileInd]['tmp_name'][$ind], 
                                        $mimeType, 
                                        $fieldOptions,
                                        !$thumbUploaded,
                                        false
                                );
                        }
                        
                        if ($results instanceof JException) {
                                $vInd = self::locateFile($fieldValues, $fileName);
                                $results->set('error_index', $vInd);
                                return $results;
                        }
                        
                        // save thumb file if available
                        if ($thumbUploaded) {
                                $thumbInd = $fileInd.'_thumb';
                                $thumbName = self::$files[$thumbInd]['name'][$ind];
                                $thumbMimetype = self::$files[$thumbInd]['type'][$ind];

                                $resultsThumb = self::saveUploadedFile(
                                        $item,
                                        $uploadDir,
                                        $dstDir, 
                                        $thumbName,
                                        self::$files[$thumbInd]['tmp_name'][$ind], 
                                        $thumbMimetype, 
                                        $fieldOptions,
                                        false,
                                        true
                                );
                                
                                if ($resultsThumb instanceOf JException) {
                                        $vInd = self::locateFile($fieldValues, $thumbName, true);
                                        $resultsThumb->set('error_index', $vInd);
                                        return $resultsThumb;
                                }

                                /**
                                 * @@todo better mapping between image and its actual thumbnail -
                                 * case where images with explicit thumbnails are mixed with one without
                                 */
                                // in case of remote files we require thumbnails
                                $num = $is == 'file' ? count($results) : 1;

                                // if one archive file with many files but only one thumbnail
                                if (count($resultsThumb) != $num) {     
                                        $resultsThumb = array_fill(0, $num, $resultsThumb[0]);
                                        $thumbFilled = true;
                                }

                                foreach ($resultsThumb as $r => $resultThumb) {
                                        $resultsThumb[$r] = $resultThumb['file'];
                                }

                                if ($is == 'thumb') {
                                        $results = array_fill(0, $num, 'PH');
                                }                                
                        } else {
                                $resultsThumb = 'default';
                        }
                        
                        $isArchive = self::isArchive($mimeType, $fileName);
                        
                        if ($isArchive) {
                                $vInd = self::locateFile($fieldValues, basename($fileName));
                                
                                if ($vInd == -1) {
                                        return self::error('Unknown error while saving.');
                                }
                                
                                $self = $fieldValues[$vInd];
                                $part = array_slice($fieldValues, 0, $vInd);
                                $part = array_merge($part, array_fill(0, count($results), $self));
                                
                                if (count($fieldValues) > $vInd + 1) {
                                        $fieldValues = array_slice($fieldValues, $vInd + 1, count($fieldValues) - $vInd - 1);
                                        $fieldValues = array_merge($part, $fieldValues);
                                } else {
                                        $fieldValues = $part;
                                }
                                
                                $vAInd = -1;
                                $_vInd = $vInd;
                        }
                        
                        foreach ($results as $r => $result) {
                                $file = $result['file'];
                                
                                if ($isArchive) {
                                        $vAInd++;
                                        $vInd = $_vInd + $vAInd;
                                        $file = self::reformatFileName($file);
                                        
                                        if ($resultsThumb == 'default') {
                                                $resultThumb = dirname($file) . '/' . $fieldOptions['thumbfolder'] . '/' . basename($file);
                                        }
                                } else {
                                        if ($is == 'file') {
                                                $file = self::reformatFileName($file);

                                                if ($resultsThumb == 'default') {
                                                        $resultThumb = dirname($file) . '/' . $fieldOptions['thumbfolder'] . '/' . basename($file);
                                                }
                                        } else {
                                                $file = $resultsThumb[$r];
                                        }

                                        $f = $result['originalfilename'];
                                        $vInd = self::locateFile($fieldValues, $f, $is != 'file');
                                }
                                
                                if ($vInd == -1) {
                                        return self::error('Unknown error while saving.');
                                }
                                
                                if ($is != 'file') $file = $fieldValues[$vInd][self::SRCPOS];
                                
                                $f = basename($file);
                                
                                if ($resultsThumb != 'default') {
                                        $resultThumb = $resultsThumb[$r];
                                        $f = preg_replace('#\.([^\.]+$)#', '', $f);
                                        $ext = JFile::getExt($resultThumb);
                                        $f = $f . '.' . $ext;
                                        $f = dirname($resultThumb) . '/' . $f;
                                        JFile::move($resultThumb, $f);
                                        $resultThumb = self::reformatFileName($f);
                                }
                                
                                $fieldValues[$vInd][self::SRCPOS] = $file;
                                $fieldValues[$vInd][self::THUMBSRCPOS] = $resultThumb;
                                $fieldValues[$vInd][self::MEDIATYPEPOS] = $result['mediatype'];
                                
                                if ($fieldValues[$vInd][self::CAPTIONNAMINGPOS] == 'filenameascaption') {
                                        $str = self::translateFileName($result['filenamecaption'], $fieldOptions);
                                        
                                        $fieldValues[$vInd][self::CAPTIONPOS] = $str;
                                }
                                
                                // TODO: what the?
                                if (false && $thumbFilled) {
                                        JFile::delete($resultsThumb[0]);
                                }                                
                        }
                }
                
                return $fieldValues;
        }
        
        protected static function locateFile($fieldValues, $file, $isThumb = false) {
                $vInd = null;
                $found = false;

                foreach ($fieldValues as $vInd => $fieldValue) {
                        if ($found = preg_match('#^.*'.$file.'$#', $fieldValue[$isThumb ? self::THUMBSRCPOS : self::SRCPOS])) {
                                break;
                        }
                }
                
                return $found ? $vInd : -1;
        }
        
        protected static function translateFileName($fileName, $fieldOptions) {
                $translations = $fieldOptions['filenameascaptiontranslation'];
                
                if (!$translations) return $fileName;
                
                $translations = explode("\n", $translations);
                $trans = array();
                
                foreach ($translations as $translation) {
                        $translation = explode(K2FieldsModelFields::VALUE_COMP_SEPARATOR, $translation);
                        $translation[1] = trim($translation[1]);
                        
                        if ($translation[1] == '{space}') {
                                $translation[1] = ' ';
                        } else if ($translation[1] == '{delete}') {
                                $translation[1] = '';
                        }
                        
                        $trans[$translation[0]] = $translation[1];
                }
                
                $fileName = strtr($fileName, $trans);
                
                return $fileName;
        }
        
        // TODO: implement
        // Remove widgets from here
        protected static function maintainItem(&$item, $fields) {
                $fields = JprovenUtility::indexBy($fields, 'isMedia');
                
                if (empty($fields) || !isset($fields['1'])) return;
                
                $fields = $fields['1'];
                
                if (empty($fields)) return;
                
                $fieldIds = (array) JprovenUtility::getColumn($fields, 'id');
                
                $db = JFactory::getDbo();
                $query = "
                        SELECT * 
                        FROM #__k2_extra_fields_values v
                        WHERE v.itemid = ".$item->id." AND v.fieldid IN (".implode(',', $fieldIds).") AND v.partindex = ".(self::SRCPOS-1)." AND v.listindex IN
                        (
                        SELECT DISTINCT li.listindex
                        FROM #__k2_extra_fields_values li 
                        WHERE li.itemid = v.itemid AND li.fieldid = v.fieldid AND li.value IN ('upload', 'remote')
                        )
                ";
                
                $db->setQuery($query);
                $fieldsValues = $db->loadObjectList();
                $fieldsValues = JprovenUtility::indexBy($fieldsValues, 'fieldid');
                
                foreach ($fields as $field) {
                        if (is_object($field)) $field = get_object_vars($field);
                        
                        $dir = self::getStorageDirectory($field, $item, true);
                        $files = $dir === false ? false : JFolder::files($dir, '.', false, true);
                        
                        if ($files) {
                                $_files = array();
                                
                                if (isset($fieldsValues[$field['id']])) {
                                        $_files = (array) JprovenUtility::getColumn($fieldsValues[$field['id']], 'value');
                                }
                                
                                foreach ($files as $i => $file) {
                                        $_file = JPath::clean(substr(str_replace(JPATH_SITE, '', $file), 1), '/');
                                        
                                        if (!in_array($_file, $_files)) {
                                                $thumb = JPath::clean($dir . '/' . $field['thumbfolder'] . '/'.basename($file), '/');
                                                if (JFile::exists($thumb)) JFile::delete ($thumb);
                                                JFile::delete($file);
                                        }
                                }
                        }
                }
        }
        
        /** Media saving 
         *
         * saveMedia : 
         * 1. collect files to be saved and related metadata
         * 2. check if upload number of files limit is respected
         * 3. create upload directory
         * 4. call saveMediaFile for each file
         * 5. delete upload directory
         * 
         * saveMediaFile :
         * 1. upload file
         * 2. check if upload size limit is respected
         * 3. if file is picture resize if applicable or check size
         * 4. move from upload to actual directory
         * 
         */
        public static function save(&$item, $fields) {
                self::maintainItem($item, $fields);
                
                self::collectFilesToSave($item, $fields);
                
                if (empty(self::$collectedFiles)) return true;
                
                // Define upload directory
                $uploadDir = JPath::clean(JPATH_SITE . '/tmp/k2media' . time());
                
                if (!JFolder::create($uploadDir)) {
                        $item->setError(self::error('Cannot upload file(s): directory is inaccessible.'));
                        return false;
                }
                
                $fieldsValues = json_decode($item->extra_fields);
                $n = count($fieldsValues);
                $result = '';
                
                $postProcess = array();
                
                for ($i = 0, $n = count($fieldsValues); $i < $n; $i++) {
                        $fieldValues = $fieldsValues[$i]->value;
                        $fieldId = $fieldsValues[$i]->id;
                        $fieldOptions = $fields[$fieldId];
                        
                        if (is_object($fieldOptions))
                                $fieldOptions = get_object_vars($fieldOptions);
                        
                        if ($fieldOptions['valid'] != 'media') continue;
                        
                        $fieldValues = K2FieldsModelFields::explodeValues($fieldValues, $fieldOptions);
                        $result = self::saveForField($item, $fieldOptions, $fieldValues, $uploadDir);
                        
                        if ($result instanceof JException) {
                                unset($fieldValues[$result->get('error_index')]);
                                $fieldsValues[$i]->value = K2FieldsModelFields::implodeValues($fieldValues, $fieldOptions);
                                if (empty($fieldsValues[$i]->value)) unset($fieldsValues[$i]);
                                $item->setError($result);
                                break;
                        } else if ($result) {
                                $postProcess[$fieldId] = $result;
                                $fieldsValues[$i]->value = K2FieldsModelFields::implodeValues($result, $fieldOptions);
                        }
                }
                
                self::postProcessGalleryOptions($item, $postProcess, $fields);
                
                $fieldsValues = array_values($fieldsValues);
                $fieldsValues = json_encode($fieldsValues);
                $item->extra_fields = $fieldsValues;
                
                JFolder::delete($uploadDir);
                
                return $result instanceof JException ? false : $item->store();
        }
        
        protected static function postProcessGalleryOptions($item, $medias, $fields) {
                if (empty($medias)) return;
                
                $errors = array();
                
                foreach ($medias as $fieldId => $_medias) {
                        $field = $fields[$fieldId];

                        // TODO: what to do with fixed media type of pic
                        if (self::getPlugin($field, 'pic')->name == 'widgetkit_k2') {
                                require_once JPATH_ADMINISTRATOR.'/components/com_k2fields/models/types/widgetkithelper.php';
                                K2fieldsWidgetkitHelper::save($item, $field, $_medias);
                        }
                }
        }
        
        
        
        // NOTE: to be removed, CAUSE: not used
        public static function processRemoteFile(&$item, $options, $fieldData) {
                $fieldId = K2FieldsModelFields::value($options, 'id');
                $request = $fieldData[self::SRCPOS];
                $prequest = parse_url($request);
                
                if ($prequest == false || $prequest['scheme'] != 'http') {
                        return false;
                }
                
                $dstDir = self::getStorageDirectory($options, $item, false);

                if (!is_string($dstDir)) {
                        if ($dstDir instanceof JException) $item->setError($dstDir);
                        
                        return false;
                }
                
                $fileNameAsCaption = $fieldData[self::CAPTIONNAMINGPOS];
                
                if (!$fileNameAsCaption) {
                        $fileNameAsCaption = $remoteDl = '';
                } else {
                        list($fileNameAsCaption, $remoteDl) = explode(',', $fileNameAsCaption ? $fileNameAsCaption : ',');
                }
                
                if ($remoteDl == 'remotedl') {
                        $file = self::saveRemoteFile($request, $options, $item, $dstDir);
                        
                        if ($file instanceof JException) {
                                $item->setError($file);
                                return false;
                        }
                        
                        $thumb = $fieldData[self::THUMBSRCPOS]; 
                        $thumb = self::saveThumb($item, $fieldId, $options, $file['file'], $dstDir);

                        if ($thumb === false && $file['mediatype'] == 'pic') {
                                $thumb = self::createThumb($file['file'], $options);
                        }
                        
                        $file['file'] = self::reformatFileName($file['file']);
                        
                        $fieldData[self::SRCPOS] = $file['file'];
                        $fieldData[self::MEDIATYPEPOS] = $file['mediatype'];
                        $fieldData[self::REMOTEURLDL] = true;
                } else {
                        $thumb = $fieldData[self::THUMBSRCPOS]; 
                        $thumb = self::saveThumb($item, $fieldId, $options, $request, $dstDir);
                }
                
                if ($thumb instanceof JException) {
                        $item->setError($thumb);
                        return false;
                }
                
                $thumb = self::reformatFileName($thumb);
                $fieldData[self::THUMBSRCPOS] = $thumb ? $thumb : '';
                
                return $fieldData;
        }
        
        protected static function saveThumb(&$item, $fieldId, $options, $thumbForFile, $dstDir) {
                static $counts = array();
                
                if (!isset($counts[$fieldId])) $counts[$fieldId] = 0;
                else $counts[$fieldId]++;
                
                $files = JRequest::get('files');
                $fielName = $loc = $type = '';
                
                foreach ($files as $fieldName => $fieldFiles) {
                        if ($fieldName == "k2fieldsmedia_" . $fieldId . '_thumb') {
                                foreach ($fieldFiles['name'] as $i => $fieldFile) {
                                        if ($i == $counts[$fieldId] && $fieldFiles['error'][$i] === 0) {
                                                $fileName = $fieldFiles['name'][$i];
                                                $loc = $fieldFiles['tmp_name'][$i];
                                                $type = $fieldFiles['type'][$i];
                                        }
                                }
                        }
                }
                
                if (empty($loc)) return false;
                
                // Upload/working directory
                $uploadDir = JPath::clean(JPATH_SITE . '/tmp/k2media' . time());
                
                if (!JFolder::create($uploadDir)) {
                        $item->setError(self::error('Cannot upload file(s): directory is inaccessible.'));
                        return false;
                }
                
                $saveFileName = basename($thumbForFile);
                
                // in case a url without extension or even a dynamic image
                if (!$saveFileName) $saveFileName = basename($fileName);
                
                $ext = JFile::getExt($saveFileName);
                
                $saveFileName = preg_replace('#\.[^\.]$#', $ext, $saveFileName);
                
                $results = self::saveUploadedFile(
                        $item,
                        $uploadDir,
                        $dstDir, 
                        $saveFileName,
                        $loc, 
                        $type, 
                        $options,
                        false,
                        true
                );  
                
                JFolder::delete($uploadDir);

                if ($results instanceOf JException) {
                        $item->setError($results);
                        return false;
                }
                
                return $results[0]['file'];
        }
        
        public static function getFileNameBasedCaption($file) {
                $ext = JFile::getExt($file);
                $caption = basename($file);
                $caption = str_replace('.'.$ext, '', $caption);
                return $caption;
        }
        
        protected static function checkMediaLimit($item, $options, $dir, $file) {
                static $accSize, $accQty;
                
                if (!isset($accQty)) {
                        $pat = self::mediaFilesPattern($options);
                        $files = JFolder::files($dir, $pat, false, true);
                        $accQty = count($files);
                        
                        foreach ($files as $f) {
                                $size = @ filesize($f);
                                
                                if ($size !== false) {
                                        $accSize += $size;
                                }            
                        }
                }
                
                if (array_key_exists($item->catid, $options['medialimit'])) {
                        $lim = $options['medialimit'][$item->catid];
                } else {
                        $lim = $options['medialimit']['_all_'];
                }
                
                if ($lim != 0 && $accQty + 1 > $lim) {
                        return self::error(
                                JText::sprintf(
                                        "Maximum number of media uploads of %d is exceeded.", 
                                        $lim
                                ), 
                                '', 
                                false
                        );
                }

                $size = @ filesize($file);
                                
                // If aggregated media size checking do it here against $accSize + $size
                
                if ($size !== false) {
                        $accSize += $size;
                        $accQty++;
                }                
                
                return true;
        }
        
        protected static function doSaveFile(
                $item, $file, $mt, $dstDir, $fileName, $options, $createThumb = true, $isThumb = false
        ) {
                if (($mt == 'pic' && (bool) $options['picresize']) || $isThumb) {
                        $file = self::resizePicNative($file, $options, $isThumb ? 'thumb' : '');
                        
                        if ($file instanceof JException) return $file;
                }
                
                $ext = strtolower(JFile::getExt($fileName));
                $originalFileName = $fileName;
                $fileName = preg_replace('#\.' . $ext . '$#i', '', $fileName);
                $dstFile = JPath::clean($dstDir . ($isThumb ? '/' . $options['thumbfolder'] : '' ), '/');
                
                if (!JFolder::exists($dstFile) && $isThumb) {   
                        if (!JFolder::create($dstFile)) {
                                return self::error(
                                        'Cannot upload file(s): upload directory is incorrect or inaccessible.'
                                );
                        }
                }
               
                $fileName = self::renameFile($fileName, $options);
                $dstFile .=  '/' . $fileName;
                  
                if (!(bool) $options['mediaoverwrite'] && JFile::exists($dstFile . '.' . $ext)) {
                        static $owKeys = array();
                        
                        $owKey = $isThumb ? $owKeys[$fileName . '.' . $ext] : time();
                        
                        $dstFile .= '_' . $owKey;
                        
                        if (!$isThumb) {
                                $owKeys[$fileName . '.' . $ext] = $owKey;
                        }
                }
                
                // compatibility adjustment where ex. JWSIG/PRO have issues in dealing with this extension,
                // where actual thumbnail created is jpg and it assumes to have jpeg when generating the html code                
                if ($ext == "jpeg") $ext = 'jpg';
                
                $dstFile .= '.' . $ext;
                
                if ((bool) $options['mediaoverwrite'] && JFile::exists($dstFile)) {
                        if (!JFile::delete($dstFile)) {
                                return self::error('File exists.', $fileName);
                        }
                }
                
                if (!JFile::move($file, $dstFile)) {
                        return self::error('Cannot move file to provided directory.');
                }  
                
                $thumb = false;
                
                self::watermark($item, $dstFile, $options);
                
                if ($createThumb && !$isThumb) {
                        $thumb = self::createThumb($dstFile, $options);
                        
                        if ($thumb instanceof JException) return $thumb;
                }
                
                $fileNameCaption = self::getFileNameBasedCaption($dstFile);
                
                return array(
                        'file' => $dstFile, 
                        'mediatype' => $mt, 
                        'filename' => $fileName, 
                        'originalfilename' => $originalFileName,
                        'filenamecaption' => $fileNameCaption, 
                        'thumbcreated' => $thumb !== false
                );
        }
        
        protected static function watermark($item, $file, $options) {
                $fields = K2FieldsModelFields::value($options, 'watermark_fields');
                $values = array();
                
                if ($fields) {
                        $model = JModel::getInstance('fields', 'K2FieldsModel');
                        $itemId = K2FieldsModelFields::value($item, 'id');
                        $fieldsValues = $model->itemValues($itemId, $fields);
                        
                        foreach ($fieldsValues as $fieldValues) {
                                foreach ($fieldValues as $fieldValue) {
                                        if (!empty($fieldValue->txt)) $values[] = $fieldValue->txt;
                                        else if (!empty($fieldValue->value)) $values[] = $fieldValue->value;
                                }
                        }
                        
                        $values = implode(' - ', $values);
                        $values = preg_replace("#\<span class=[\"\']lbl[\"\']>(.+)\<\/span\>#U", "$1 - ", $values);
                        $values = trim(html_entity_decode(htmlspecialchars_decode(strip_tags($values))));
                }
                
                $watermark = K2FieldsModelFields::value($options, 'watermark');
                
                if (!$watermark && !$values || !JFile::exists($file)) return;
                
                require_once JPATH_SITE."/media/k2fields/lib/wideimage-11.02.19-full/lib/WideImage.php";
                
                $img = WideImage::load($file);
                
                if ($watermark) $watermark = JPath::clean(JPATH_SITE . '/' . $watermark, '/');
                
                $watermarks = $lefts = $tops = array();
                
                if (JFile::exists($watermark)) {
                        $watermarks[] = WideImage::load($watermark);
                        $left = K2FieldsModelFields::value($options, 'watermark_left', 'left+10');
                        $top = K2FieldsModelFields::value($options, 'watermark_top', 'top+10');                        
                        $lefts[] = is_string($left) && strpos($left, ',') !== false ? explode(',', $left) : (array) $left;
                        $tops[] = is_string($top) && strpos($top, ',') !== false ? explode(',', $top) : (array) $top;
                }
                
                if ($values) {
                        $fontSize = 10;
                        $font = JPATH_SITE."/media/k2fields/fonts/Existence-Light.ttf";
                        
                        $oFont = K2FieldsModelFields::value($options, 'id');
                        
                        if ($oFont = JFolder::files(JPATH_SITE."/media/k2fields/fonts/", $oFont."\.[ttf|otf]", false, true)) {
                                $font = current($oFont);
                        }
                        
                        if (K2FieldsModelFields::value($options, 'watermark_copy')) $values = '(c) '.$values;
                        
                        $size = imagettfbbox($fontSize, 0, $font, $values);
                        $w = abs($size[2]) + abs($size[0]);
                        $h = abs($size[7]) + abs($size[1]);
                        $image = imagecreatetruecolor($w, $h);
                        imagesavealpha($image, true);
                        imagealphablending($image, false);
                        
                        $colors = K2FieldsModelFields::value($options, 'watermark_colors', '200,200,200');
                        $colors = explode(',', $colors);

                        $transparentColor = imagecolorallocatealpha($image, $colors[0], $colors[1], $colors[2], 127);
                        imagefill($image, 0, 0, $transparentColor);

                        $textColor = imagecolorallocate($image, $colors[0], $colors[1], $colors[2]);
                        imagettftext($image, $fontSize, 0, 0, abs($size[5]), $textColor, $font, $values);
                        
                        $watermarks[] = WideImage::load($image);
                        // 'right-'.($w+10), 'bottom-'.($h+10)
                        $left = K2FieldsModelFields::value($options, 'watermark_field_left', 'right-10');
                        $top = K2FieldsModelFields::value($options, 'watermark_field_top', 'bottom-10');
                        $lefts[] = is_string($left) && strpos($left, ',') !== false ? explode(',', $left) : (array) $left;
                        $tops[] = is_string($top) && strpos($top, ',') !== false ? explode(',', $top) : (array) $top;
                }
                
                foreach ($watermarks as $w => $watermark) {
                        foreach ($tops[$w] as $top) {
                                foreach ($lefts[$w] as $left) {
                                        $img = $img->merge($watermark, $left, $top, 100);
                                        $img->saveToFile($file);
                                }
                        }
                }
                
                if (isset($image) && is_resource($image)) imagedestroy($image);
                
                return true;

//                require_once JPATH_SITE."/media/k2fields/lib/class.rwatermark.php";
//                
//                $handle = new RWatermark(FILE_JPEG, "./original.jpg");
//
//                $handle->SetPosition("RND");
//                $handle->SetTransparentColor(255, 0, 255);
//                $handle->SetTransparency(60);
//                $handle->AddWatermark(FILE_PNG, "./watermark.png");
//
//                Header("Content-type: image/png");
//                $handle->GetMarkedImage(IMG_PNG);
//                $handle->Destroy();                
        }
        
        protected static function saveUploadedFile(
                $item, $uploadDir, $dstDir, $fileName, $tmpName, $mimeType, $options, $createThumb = true, $isThumb = false
        ) {
                $fileName = JFile::makeSafe($fileName);
                $file = JPath::clean($uploadDir . '/' . $fileName);
                $status = JFile::upload($tmpName, $file);
                
                if (!$status) return self::error("Upload failed.", $fileName);
                
                return self::saveFile($item, $file, $dstDir, $options, $createThumb, $isThumb, $mimeType);
        }
        
        protected static function saveFile(
                $item, $file, $dstDir, $options, $createThumb = true, $isThumb = false, $mimeType = ''
        ) {
                $mt = self::checkFile($item, $file, $options, $mimeType);
                
                if ($mt instanceof JException) return $mt;
                
                if ($mt == 'archive') {
                        jimport('joomla.filesystem.archive');
                        
                        $fileName = basename($file);
                        $udst = str_replace($fileName, '', $file) . 'unpacked';
                        
                        if (!JFolder::create($udst)) {
                                return self::error(
                                        'Cannot upload file(s): directory is inaccessible.'
                                );
                        }
                        
                        // 7z and rar not supported by JArchive::extract???
                        if (!JArchive::extract($file, $udst)) {
                                return self::error(
                                        'Cannot upload file(s): directory is inaccessible.'
                                );
                        }
                        
                        $files = JFolder::files($udst, '.', false, true);
                        
                        foreach ($files as $i => $file) {
                                $fileName = basename($file);
                                $mt = self::checkFile($item, $file, $options);
                                
                                if ($mt instanceof JException) return $mt;
                                
                                $files[$i] = array('file' => $file, 'mediatype' => $mt, 'filename' => $fileName);
                        }
                } else {
                        $files = array();
                        $fileName = basename($file);
                        $files[] = array('file' => $file, 'mediatype' => $mt, 'filename' => $fileName);                        
                }
                
                foreach ($files as $i => $file) {
                        if (!$isThumb) {
                                $status = self::checkMediaLimit($item, $options, $dstDir, $file['file']);
                                
                                if ($status instanceof JException) return $status;
                        }
                        
                        $file = self::doSaveFile($item, $file['file'], $file['mediatype'], $dstDir, $file['filename'], $options, $createThumb, $isThumb);
                        
                        if ($file instanceof JException) return $file;
                        
                        $files[$i] = $file;
                }
                
                return $files;
        }
        
        protected static function createThumb($file, $options) {
                if (strpos($options['piccreatethumb'], 'create') === false) return false;
                
                $thumb = self::resizePicNative($file, $options, 'thumb');
                $dst = JPath::clean(dirname($file) . '/' . $options['thumbfolder']);

                if (!JFolder::exists($dst)) {
                        JFolder::create($dst);
                }

                $dst .= '/' . basename($file);
                
                if (JFile::exists($dst) && !JFile::delete($dst) || !JFile::move($thumb, $dst)) {
                        return self::error('Cannot create thumbnail: directory is inaccessible.');
                }
                
                return $dst;
        }
        
        protected static function resizePicNative($file, $options, $type = '') {
                if (!JFile::exists($file)) return self::error('File missing.'); 
                
                $file = JPath::clean($file);
                $size = getimagesize($file);
                
                if ($size === false) return false;

                $maxWidth = K2FieldsModelFields::value($options, 'picwidth'.$type);
                $maxHeight = K2FieldsModelFields::value($options, 'picheight'.$type);

                $width = $size[0];
                $height = $size[1];
                $tmp = JPATH_SITE . '/tmp/' . time() . basename($file);
                
                if ($width <= $maxWidth && $height <= $maxHeight) {
                        JFile::copy($file, $tmp);
                        return $tmp;
                }
                
                if ($width > $maxWidth) {
                        $width = $maxWidth;
                        $height *= $maxWidth / $size[0];
                } else {
                        $height = $maxHeight;
                        $width *= $maxHeight / $size[1];
                }

                $width = round($width);
                $height = round($height);
                
                $ext = '';

                switch ($size['mime']) {
                        case "image/gif":
                                $img = imagecreatefromgif($file);
                                break;
                        case "image/png":
                                $img = imagecreatefrompng($file);
                                break;
                        case "image/pjpeg":
                        case "image/jpeg":
                                $img = imagecreatefromjpeg($file);
                                break;
                }

                if (!$img) return self::error('Cannot create image.');

                $mini = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($mini, 255, 255, 255);

                imagefilledrectangle($mini, 0, 0, $width, $height, $white);
                imagecopyresampled($mini, $img, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
                
                switch ($size['mime']) {
                        case "image/gif":
                                imagegif($mini, $tmp);
                                break;
                        case "image/png":
                                imagepng($mini, $tmp);
                                break;
                        case "image/pjpeg":
                        case "image/jpeg":
                                imagejpeg($mini, $tmp, K2FieldsModelFields::value($options, 'picquality'.$type));
                                break;
                }
                
                imagedestroy($mini);
                imagedestroy($img);

                if (empty($type)) JFile::delete($file);

                return $tmp;
        }        
        
        protected static function checkFile($item, $file, $options, $browserMimeType = false) {
                $mt = self::getMediaType($browserMimeType, $file);
                $fileName = basename($file);
                
                if ($mt == 'archive') {
                        if (array_key_exists('archiveallowed', $options)) {
                                $allowed = $options[$mt.'allowed'];
                                $user = JFactory::getUser();
                                
                                if (!in_array($allowed, $user->getAuthorisedViewLevels())) {
                                        return self::error('Archive files not allowed.');
                                }
                        }
                } else if (!in_array($mt, $options['mediatypes'])) {
                        return self::error('Provided media file type is not allowed.');
                }
                
                if ($mt == 'pic') {
                        if (($imgSize = getimagesize($file)) === FALSE) {
                                return self::error('Provided picture type is not allowed.');
                        }
                        
                        if (($imgSize[0] > $options['picwidth'] || $imgSize[1] > $options['picheight']) && !$options['picresize']) {
                                return self::error(
                                        JText::sprintf('Image larger than allowed size of width x height = %d x %d', $options['picwidth'], $options['picheight']),
                                        $fileName,
                                        false
                                );                        
                        }                        
                } else if ($options['checkmime']) {
                        $checkerAvailable = function_exists('finfo_open') || function_exists('mime_content_type');
                        
                        if (function_exists('finfo_open') && $options['checkmime']) {
                                $finfo = finfo_open(FILEINFO_MIME);
                                $type = finfo_file($finfo, $file);
                                
                                if (strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
                                        return self::error('Provided file type is not allowed.');
                                }
                                
                                finfo_close($finfo);
                        } else if (function_exists('mime_content_type') && $options['checkmime']) {
                                $type = mime_content_type($file);
                                $mimes = array_keys(self::$mimeType);
                                
                                if (strlen($type) && !in_array($type, $mimes)) {
                                        return self::error('Provided file type is not allowed.');
                                }
                        }
                        
                        if ($checkerAvailable && $browserMimeType && $browserMimeType != $type) {
                                return self::error('Incorrect file type.');
                        }
                } else {
                        $ext = strtolower(JFile::getExt($file));

                        if (!in_array($ext, $options['mediafileexts'])) {
                                return self::error('Provided file type is not allowed.');
                        }
                }
                
                $size = filesize($file);
                
                if (!$size) {
                        return self::error("No file found.");
                }
                
                $size /= 1024;
                
                $max = $options[$mt.'size'];
                
                if ($max > 0 && $size > $max) {
                        return self::error(
                                JText::sprintf("Maximum allowed file size of %d kb is exceeded.", $options[$mt.'size']),
                                $fileName,
                                false
                        );
                }     
                
                return $mt;
        }
        
        public static function getStorageDirectory($options, $item, $isUpdate = false) {
                $dir = JPath::clean(K2FieldsModelFields::setting('mediaroot', $options));
                $root = JPath::clean(JPATH_SITE);
                
                if (strpos($dir, $root) !== 0) {
                        $dir = JPath::clean($root . '/' . $dir);
                }
                
                if (!$isUpdate && !JFolder::exists($dir)) {
                        if (!JFolder::create($dir)) {
                                return self::error(
                                        'Cannot upload file(s): directory inaccessible.'
                                );
                        }
                }
                
                $dir .= '/' . (
                        K2FieldsModelFields::setting('mediafolder', $options) == 'user' ? 
                                JFactory::getUser()->get('id') : 
                                (is_object($item) ? $item->id : $item)
                );
                
                $dir = JPath::clean($dir);
                
                if (!$isUpdate && !JFolder::exists($dir)) {
                        if (!JFolder::create($dir)) {
                                return self::error(
                                        'Cannot upload file(s): directory inaccessible.'
                                );
                        }
                }
                
                if (isset($options['id'])) {
                        $dir .= '/' . $options['id'];
                        
                        if (!$isUpdate && !JFolder::exists($dir)) {
                                if (!JFolder::create($dir)) {
                                        return self::error(
                                                'Cannot upload file(s): directory inaccessible.'
                                        );
                                }
                        }
                }
                
                if ($isUpdate && !JFolder::exists($dir)) return false;
                
                $dir = JPath::clean($dir, '/');
                
                return $dir;
        }
        
        /**
         * removes media files for each orphan entry in the value table
         */
        public static function maintain() {
//                self::maintainItem($item, $fields);
                
                $db = JFactory::getDBO();
                
                $query = '
                        SELECT DISTINCT v.fieldid, v.itemid 
                        FROM #__k2_extra_fields_values AS v,
                        (
                                SELECT DISTINCT vv.itemid, vv.fieldid 
                                FROM #__k2_extra_fields_values AS vv, #__k2_extra_fields_definition vf
                                WHERE 
                                        vv.itemid NOT IN (SELECT id FROM #__k2_items) AND 
                                        vv.fieldid = vf.id AND 
                                        vf.definition LIKE "%valid=media%" AND
                                        vv.partindex = 0 AND 
                                        vv.value = "upload"
                        ) AS f
                        WHERE v.itemid = f.itemid AND v.fieldid = f.fieldid AND v.partindex = ' . (K2FieldsMedia::SRCPOS - 1);
                
                $db->setQuery($query);
                $entries = $db->loadObjectList();
                
                if (!empty($entries)) {
                        $_entries = array();
                        
                        foreach ($entries as $entry) {
                                if (!isset($_entries[$entry->fieldid])) $_entries[$entry->fieldid] = array();
                                
                                $_entries[$entry->fieldid][] = $entry->itemid;
                        }
                        
                        $model = JModel::getInstance('fields', 'K2FieldsModel');
                        $fields = $model->getFieldsById(array_keys($_entries));
                        
                        foreach ($fields as $fieldid => $field) {
                                $items = $_entries[$fieldid];
                                
                                foreach ($items as $item) {
                                        $dir = self::getStorageDirectory($field, $item);
                                        $thumbdir = K2FieldsModelFields::setting('thumbfolder', $field);
                                        
                                        if (JFolder::exists($dir.'/'.$thumbdir)) JFolder::delete($dir.'/'.$thumbdir);
                                        
                                        if (JFolder::exists($dir)) JFolder::delete($dir);
                                }
                        }
                }
        }
        
        protected static function handleError($item) {
                $link = 'index.php?option=com_k2&view=item&cid='.$item->id;
                $app = JFactory::getApplication();
                $app->redirect('index.php?option=com_k2&view=item&cid='.$item->id, $item->getError(), 'error');
        }
        
        /** Supporting methods **/
        public static function error($msg, $fileName = '', $process = true, $code = 403) {
                if (empty($code)) {
                        $code = 403;
                }
                
                return JError::raiseWarning(
                        $code, 
                        ($process ? JText::_($msg) : $msg) . 
                        ($fileName == '' ? '' : '<br />' . JText::sprintf("File: %s", $fileName))
                );
        }
        
        protected static function getMediaType($mimeType, $file) {
                if (!empty($mimeType) && isset(self::$mimeType[$mimeType])) {
                        return self::$mimeType[$mimeType];
                }
                
                $ext = JFile::getExt($file);
                $mimeType = self::$extMime[$ext]; // lets assume that the first one is the most applicable one
                
                if (is_array($mimeType)) {
                        $mimeType = $mimeType[0];
                }
                
                if (!empty($mimeType) && isset(self::$mimeType[$mimeType])) {
                        return self::$mimeType[$mimeType];
                }
                
                return -1;
        }
        
        protected static function isMediaType($what, $mimeType, $file) {
                return self::getMediaType($mimeType, $file) == $what;
        }
        
        protected static function isArchive($mimeType, $file) {
                return self::getMediaType($mimeType, $file) == 'archive';
        }
        
        public static function getParameters($field = null, $options = null) {
                if (empty($options)) $options = $field;
                
//                self::setAllowedSettings($options, array('mediatypes', 'mediafileexts', 'mediasources'));
                
                $options['mediatypes'] = (array) $options['mediatypes'];
                $options['mediafileexts'] = (array) K2FieldsModelFields::setting('mediafileexts', $options, true);
                $options['mediasources'] = (array) $options['mediasources'];
                $options['picresize'] = K2FieldsModelFields::setting('picresize', $options, true);
                $options['picquality'] = K2FieldsModelFields::setting('picquality', $options, 70);
                $options['picwidth'] = K2FieldsModelFields::setting('picwidth', $options);
                $options['picheight'] = K2FieldsModelFields::setting('picheight', $options);
                $options['picsize'] = K2FieldsModelFields::setting('picsize', $options);
                $options['piccreatethumb'] = K2FieldsModelFields::setting('piccreatethumb', $options);
                $options['picwidththumb'] = K2FieldsModelFields::setting('picwidththumb', $options);
                $options['picheightthumb'] = K2FieldsModelFields::setting('picheightthumb', $options);
                $options['picqualitythumb'] = K2FieldsModelFields::setting('picqualitythumb', $options, 80);
                $options['videosize'] = K2FieldsModelFields::setting('videosize', $options);
                $options['audiosize'] = K2FieldsModelFields::setting('audiosize', $options);
                $options['archivesize'] = K2FieldsModelFields::setting('archivesize', $options);
                $options['picplg'] = K2FieldsModelFields::setting('picplg', $options);
                $options['itemlistpicplg'] = K2FieldsModelFields::setting('itemlistpicplg', $options);
                $options['providerplg'] = K2FieldsModelFields::setting('providerplg', $options);
                $options['itemlistproviderplg'] = K2FieldsModelFields::setting('itemlistproviderplg', $options);
                $options['videoplg'] = K2FieldsModelFields::setting('videoplg', $options);
                $options['itemlistvideoplg'] = K2FieldsModelFields::setting('itemlistvideoplg', $options);
                $options['audioplg'] = K2FieldsModelFields::setting('audioplg', $options);
                $options['itemlistaudioplg'] = K2FieldsModelFields::setting('itemlistaudioplg', $options);

                $app = JFactory::getApplication();
                
                if ($app->isSite()) {
                        $input = $app->input;
                        $view = $input->get('view');

                        if ($view == 'itemlist') return $options;

                        $task = $input->get('task');

                        if (!in_array($task, array('edit', 'add', 'save'))) return $options;
                }                
                
                $options['thumbfolder'] = K2FieldsModelFields::setting('thumbfolder', $options);
                $options['mediaroot'] = K2FieldsModelFields::setting('mediaroot', $options, 'images/k2media/');
                $options['mediafolder'] = K2FieldsModelFields::setting('mediafolder', $options, 'item');
                
                $user = JFactory::getUser();
                
                //if (JRequest::getCmd('task') != 'edit') return $options;
                
                // TODO: remove following parameters as they are needed only when editing items
                
                $max = self::returnBytes(ini_get('upload_max_filesize')) / 1024;
                
                if ($max <= 0) $max = 1e6;
                
                $options['maxsize'] = min(
                        array($options['picsize'], $options['videosize'], $options['audiosize'], $max)
                );
                
                $lim = (int) K2FieldsModelFields::setting('medialimit', $options, 0);
                $limcat = K2FieldsModelFields::setting('medialimitspecific', $options, '');
                $limcat = empty($limcat) ? array() : explode('\r\n', $limcat);
                
                foreach ($limcat as $_lim) {
                        $_lim = explode(K2FieldsModelFields::VALUE_SEPARATOR, $_lim);
                        $limcat[$_lim[0]] = (int) $_lim[1];
                }
                
                $limcat['_all_'] = $lim;
                
                $options['medialimit'] = $limcat;
                $options['mediaoverwrite'] = K2FieldsModelFields::setting('mediaoverwrite', $options, false);
                $options['disablek2mediatabs'] = K2FieldsModelFields::setting('disablek2mediatabs', $options, false);
                $options['mediamergefields'] = K2FieldsModelFields::setting('mediamergefields', $options, false);
                $options['checkmime'] = K2FieldsModelFields::setting('checkmime', $options, true);
                
//                $options['mediatypes'] = K2FieldsModelFields::setting('mediatypes', $options, array());
//                if (!is_array($options['mediatypes'])) {
//                        $options['mediatypes'] = explode(K2FieldsModelFields::VALUE_SEPARATOR, $options['mediatypes']);
//                }
//                
//                $options['mediafileexts'] = K2FieldsModelFields::setting('mediafileexts', $options, array());
//                if (!is_array($options['mediafileexts'])) {
//                        $options['mediafileexts'] = explode(K2FieldsModelFields::VALUE_SEPARATOR, $options['mediafileexts']);
//                }
//                
//                $options['mediasources'] = K2FieldsModelFields::setting('mediasources', $options, array());
//                if (!is_array($options['mediasources'])) {
//                        $options['mediasources'] = explode(K2FieldsModelFields::VALUE_SEPARATOR, $options['mediasources']);
//                }
                
                if (
                        empty($options['avproviders']) &&
                        (in_array('video', $options['mediatypes']) || in_array('audio', $options['mediatypes'])) && 
                        !empty($options['videoplg'])
                ) {
                        $options['avproviders'] = self::getVideoProviders($options['videoplg']);
                }
                
                if (in_array('browse', $options['mediasources'])) {
                        $options['browsablefiles'] = self::getBrowsableFiles($options);
                }
                
                $noProvider = empty($options['avproviders']);
                $noBrowse = empty($options['browsablefiles']);
                
                if ($noProvider || $noBrowse) {
                        foreach ($options['mediasources'] as $i => $src) {
                                if ($src == 'provider' && $noProvider) {
                                        unset($options['mediasources'][$i]);
                                }
                                if ($src == 'browse' && $noBrowse) {
                                        unset($options['mediasources'][$i]);
                                }
                        }
                        $options['mediasources'] = array_values($options['mediasources']);
                }
                
                $options['archiveallowed'] = K2FieldsModelFields::setting('archiveallowed', $options, 24);
                $options['remotedlallowed'] = K2FieldsModelFields::setting('remotedlallowed', $options, false);
                
                if ($options['remotedlallowed']) {
                        $options['remotedlallowed'] = in_array($options['remotedlallowed'], $user->getAuthorisedViewLevels());
                }
                
                $options['filenameascaptiontranslation'] = K2FieldsModelFields::setting('filenameascaptiontranslation', $options, '');
                
                $options['renamefiles'] = K2FieldsModelFields::setting('renamefiles', $options, '');
                
                return $options;
        }
        
        protected static function setAllowedSettings(&$options, $paramNames, $limitToCore = true) {
                $paramNames = (array) $paramNames;
                
                foreach ($paramNames as $paramName) {
                        $fieldSettings = K2FieldsModelFields::setting($paramName, $options, array());

                        if (!is_array($fieldSettings))
                                $fieldSettings = explode(K2FieldsModelFields::VALUE_SEPARATOR, $fieldSettings);
                        
                        if ($limitToCore || empty($fieldSettings)) {
                                $coreSettings = K2FieldsModelFields::setting($paramName);
                                
                                if (!is_array($coreSettings)) 
                                        $coreSettings = explode(K2FieldsModelFields::VALUE_SEPARATOR, $coreSettings);
                                
                                if (empty($fieldSettings)) {
                                        $fieldSettings = $coreSettings;
                                } else if($limitToCore) {
                                        foreach ($fieldSettings as $i => $fieldSetting) {
                                                if (!in_array($fieldSetting, $coreSettings)) {
                                                        unset($fieldSettings[$i]);
                                                }
                                        }
                                }
                        }

                        $options[$paramName] = $fieldSettings;
                }
        }
        
        public static $fieldParameterFilters = array();
        
        protected static function getAllowedExtensions($options) {
                static $exts;
                
                if (isset($exts)) return $exts;
                
                $exts = $options['mediafileexts'];
                
                if (empty($exts)) {
                        $exts = array();
                        $mts = $options['mediatypes'];
                        
                        foreach ($mts as $mt) {
                                foreach (self::$mimeType as $mimeType => $_mt) {
                                        if ($_mt == $mt) {
                                                if (isset(self::$mimeExt[$mimeType])) {
                                                        $exts = array_merge($exts, self::$mimeExt[$mimeType]);
                                                }
                                        }
                                }
                        }
                        
                        $exts = array_unique($exts);
                }
                
                return $exts;
        }
        
        protected static function renderWidgetkit_k2($medias, $mediaType, $plugin, $item, $field) {
                require_once JPATH_ADMINISTRATOR.'/components/com_k2fields/models/types/widgetkithelper.php';
                return K2fieldsWidgetkitHelper::render($item, $field);
        }
        
        public static function render($item, $values, $field, $helper, $rule = null) {
                $rendered = array('embed'=>array(), 'other'=>array());
                $medias = array();
                
                foreach ($values as $value) {
                        $mediaType = $value[self::SRCTYPEPOS]->value == 'provider' ? 'provider' : $value[self::MEDIATYPEPOS]->value;
                        if (!isset($medias[$mediaType])) $medias[$mediaType] = array();
                        $medias[$mediaType][] = $value;
                }
                
                $postProcess = true;
                
                foreach ($medias as $mediaType => $_medias) {
                        if (empty($mediaType)) {
                                $rendered['other'][] = JText::_('Unknown media type');
                        }
                        
                        foreach ($_medias as $i => &$m) {
                                if ($m[self::SRCTYPEPOS]->value == 'embed') {
                                        // TODO utilize available methods for encapsulating values
                                        $rendered['embed'][] = 
                                                '<div class="media embed">'.
                                                $media[self::SRCPOS]->value.
                                                '<span class="caption">'.
                                                $media[self::CAPTIONPOS]->value.
                                                '</span></div>';
                                        unset($m);
                                }
                        }
                        
                        $plugin = self::getPlugin($field, $mediaType);
                        
                        if ($plugin instanceof JException) continue;
                        
                        $methodName = ucfirst(strtolower($plugin->name));
                        
                        if (method_exists('K2FieldsMedia', 'render'.$methodName)) {
                                $rendered['other'][] = call_user_func_array(
                                        array('K2FieldsMedia', 'render'.$methodName), 
                                        array($_medias, $mediaType, $plugin, $item, $field)
                                );
                        } else {                        
                                $rendered['other'][]  = self::renderPlugin($_medias, $mediaType, $plugin, $item, $field);
                        }
                }
                
                $_rendered = '';
                $dontProcess = array();
                $process = '';
                
                foreach ($rendered['other'] as $r) {
                        if (is_array($r)) $dontProcess[] = $r;
                        else $process .= $r;
                }
                
                $rendered = $process.implode($rendered['embed'], '');
                $rendered = $helper->renderFieldValues(array($rendered), $field, $rule);
                
                if (!empty($dontProcess)) {
                        return array($rendered, $dontProcess);
                }
                
                return $rendered;
        }
        
        protected static function getPlugin($field, $mediaType) {
                $nonePlugins = array('widgetkit_k2', 'img', 'source');
                
                $input = JFactory::getApplication()->input;
                $view = $input->get('view', '', 'cmd');
                        
                if ($view != 'itemlist') $view = '';
                
                $mediaTypes = K2FieldsModelFields::value($field, 'mediatypes');
                
                if (!in_array($mediaType, $mediaTypes)) return self::error('Media type not allowed.');
                
                $plugin = K2FieldsModelFields::value($field, $view.$mediaType.'plg');
                
                if (empty($plugin)) return self::error('Media plugin setting missing.');
                
                if (in_array($plugin, $nonePlugins)) {
                        $result = new stdClass();
                        $result->name = $plugin;
                        $result->dontPostprocess = $plugin == 'source';
                        return $result;
                }
                
                $pluginType = self::getPluginType($plugin);
                                
                if (!$pluginType) return self::error('The required media plugin is not available.');
                
                if (!JPluginHelper::importPlugin($pluginType, $plugin)) return self::error('The required media plugin is not available.');

                $plugin = JPluginHelper::getPlugin($pluginType, $plugin);  
                
                $params = new JRegistry;
                $params->loadString($plugin->params);
                $plugin->params = $params;
                $result->dontPostprocess = false;
                
                return $plugin;
        }
        
        protected static function renderSource($medias, $mediaType, $plugin, $item, $field) {
                $ui = array();
                
                $src = JRequest::getCmd('view', 'itemlist') == 'itemlist' ? self::THUMBSRCPOS : self::SRCPOS;
                
                foreach ($medias as $media) {
                        $ui[] = array('src'=>$media[self::SRCPOS]->value, 'thumb'=>$media[self::THUMBSRCPOS]->value, 'caption'=>$media[self::CAPTIONPOS]->value);
                }
                
                return $ui;
        }
        
        protected static function renderImg($medias, $mediaType, $plugin, $item, $field) {
                $ui = '';
                
                self::normalizeLocation($medias);
                
                $src = JRequest::getCmd('view', 'itemlist') == 'itemlist' ? self::THUMBSRCPOS : self::SRCPOS;
                
                foreach ($medias as $media) {
                        $ui .= "<img src=\"".$media[$src]->value."\" alt=\"".$media[self::CAPTIONPOS]->value."\" />";
                }
                
                return $ui;
        }
        
        protected static function normalizeLocation(&$medias) {
                foreach ($medias as $media) {
                        if ($media[self::SRCTYPEPOS]->value == 'upload') {
                                if ($media[self::SRCPOS]->value) {
                                        $media[self::SRCPOS]->source = $media[self::SRCPOS]->value;
                                        $media[self::SRCPOS]->value = JURI::root().$media[self::SRCPOS]->value;
                                }
                        }
                        
                        if ($media[self::SRCTYPEPOS]->value == 'upload' || $media[self::SRCTYPEPOS]->value == 'remote') {
                                if ($media[self::THUMBSRCPOS]->value) 
                                        $media[self::THUMBSRCPOS]->value = JURI::root().$media[self::THUMBSRCPOS]->value;
                        }
                }
        }
        
        // TODO: testa J2.5 kompatibel plugins o i plugin setting behåll endast de som är kompatibla alt. markera de som kompatibla
        protected static function renderPlugin($medias, $mediaType, $plugin, $item, $field) {
                $ui = '';
                if (is_object($field)) $field = get_object_vars($field);
                $DIR = self::getStorageDirectory($field, $item, false);
                $DIR = str_replace(JPath::clean(JPATH_SITE, '/') . '/', '', $DIR);
                $params =  K2HelperUtilities::getParams('com_k2');
                self::normalizeLocation($medias);
                $mediasId = $mediaType.'_'.$item->id;
                
                if (count($medias)) $mediasId .= '_'.$medias[0][0]->fieldid;
                
                $input = JFactory::getApplication()->input;
                $view = $input->get('view', '', 'cmd') == 'itemlist' ? 'list' : '';
                $mode = K2FieldsModelFields::value($field, $view.'mode');
                
                if (empty($mode) && $view == 'list') {
                        // $mode = K2FieldsModelFields::value($field, 'mode');
                        // if (empty($mode)) $mode = 'single';
                        $mode = 'single';
                }
                
                $isSingleMode = $mode == 'single';
                $singleMode = K2FieldsModelFields::value($field, 'singlemode');
                
                $layout = K2FieldsModelFields::value($field, $view.'layout');
                
                if (empty($layout) && $view == 'list') {
                        $layout = K2FieldsModelFields::value($field, 'layout');
                }
                
                switch ($plugin->name) {
                        // pic plugins
                        case "jw_sigpro":
                                $dirs = explode('/', $DIR);
                                $gallery = array_pop($dirs);
                                $root = implode('/', $dirs);
                                $ui = "{gallery}{$gallery}{/gallery}";
                                
                                $thw = K2FieldsModelFields::setting('picwidththumb', $field);
                                $thh = K2FieldsModelFields::setting('picheightthumb', $field);
                                $quality = K2FieldsModelFields::setting('picquality', $field);
                                $engine = K2FieldsModelFields::setting('engine', $field);

                                if ($thw) $plugin->params->set('thb_width', $thw);
                                if ($thh) $plugin->params->set('thb_height', $thw);
                                if ($quality) $plugin->params->set('jpg_quality', $quality);
                                if ($engine) $plugin->params->set('popup_engine', $engine);
                                
                                $plugin->params->set('galleries_rootfolder', $root);
                                $plugin->params->set('singlethumbmode', $isSingleMode ? '1' : '0');
                                
                                if ($singleMode == 'first') {
                                        $plugin->params->set('sortorder', 0);
                                } else if ($singleMode == 'random') {
                                        $plugin->params->set('sortorder', 4);
                                }
                                
                                if (!empty($layout)) $plugin->params->set('thb_template', $layout);
                                
                                if (self::createCaptionFile($DIR, 'en-GB.labels.txt', $medias)) 
                                        $plugin->params->set('showcaptions', 2);

                                break;
                        case "jw_simpleImageGallery":
                                /**
                                 * Correct setup: (since plugin doesn't respect the params values it has been passed)
                                 * 1. thumbnail size
                                 */
                                
                                $root = self::getMediaRoot($plugin, 'galleries_rootfolder', $DIR);
                                $ui = "{gallery}{$root}{/gallery}";
                                
                                break;
                        case "cdwebgallery":
                                $ui = "{webgallery}";
                                
                                foreach ($medias as $media) {
                                        $ui .= "<img src=\"".$media[self::SRCPOS]->source."\" alt=\"".$media[self::CAPTIONPOS]->value."\" title=\"".$media[self::CAPTIONPOS]->value."\" />";
                                }

                                $ui .= "{/webgallery}";
                                
                                break;
                        case "verysimpleimagegallery":
                        case "cssgallery":
                                static $galleryNo;
                                
                                if (!isset($galleryNo)) $galleryNo = -1;
                                
                                $galleryNo++;
                                
                                $root = self::getMediaRoot($plugin, 'imagepath', $DIR);
                                $plg = $plugin->name == 'verysimpleimagegallery' ? 'vsig' : 'becssg';
                                
                                $w = K2FieldsModelFields::setting('picwidth', $field);
                                $h = K2FieldsModelFields::setting('picheight', $field);
                                $thw = K2FieldsModelFields::setting('picwidththumb', $field);
                                $thh = K2FieldsModelFields::setting('picheightthumb', $field);
                                $q = K2FieldsModelFields::setting('picquality', $field);
                                $c = K2FieldsModelFields::setting('caps', $field);
                                $r = (int) K2FieldsModelFields::setting('throw', $field);
                                
                                $ui = array(
                                    $root, 
                                    'links=0|autolink=0',
                                    $w ? 'width='.$w : '',
                                    $h ? 'height='.$h : '',
                                    $c ? 'caps='.($c ? '1' : '0') : ''
                                );
                                
                                if ($plugin->name == 'verysimpleimagegallery') {
                                        $ui[] = $q ? 'imqual='.$q : '';
                                        $ui[] = $q ? 'qual='.$q : '';
                                } else {
                                        $ui[] = $q ? 'iqual='.$q : '';
                                        $ui[] = $q ? 'tqual='.$q : '';
                                }
                                
                                if ($plugin->name == 'verysimpleimagegallery') {
                                        $ui[] = $r ? 'cols='.$r : '';
                                } else {
                                        $ui[] = $r ? 'throw='.$r : '';
                                }
                                
                                $ui = array_filter($ui);
                                $ui = implode('|', $ui);
                                
                                $ui = "{{$plg}}{$ui}{/{$plg}}";

                                foreach ($medias as $media) {
                                        $ui .= "{{$plg}_c}$galleryNo|".basename($media[self::SRCPOS]->value)."|".$media[self::CAPTIONPOS]->value."|{/{$plg}_c}";
                                }
                                
                                break;
                        case "ppgallery":
                                $p = $plugin->params->get('plgstring', 'ppgallery');
                                $w = K2FieldsModelFields::setting('picwidth', $field);
                                $h = K2FieldsModelFields::setting('picheight', $field);
                                $thw = K2FieldsModelFields::setting('picwidththumb', $field);
                                $thh = K2FieldsModelFields::setting('picheightthumb', $field);
                                $q = K2FieldsModelFields::setting('picquality', $field);
                                $wm = K2FieldsModelFields::setting('watermark', $field);
                                $wmp = K2FieldsModelFields::setting('watermarkposition', $field);
                                $c = K2FieldsModelFields::setting('caps', $field);
                                $t = K2FieldsModelFields::setting('theme', $field);
                                
                                $ui = array(
                                    "{{$p} ",
                                    $w ? ' width="'.$w.'"' : '',
                                    $h ? ' height="'.$h.'"' : '',
                                    $q ? ' quality_j="'.$q.'"' : '',
                                    $wm ? ' logo="'.$wm.'"' : '',
                                    $wmp ? ' logo_pos="'.$wmp.'"' : '',
                                    $c ? ' caption="'.$c.'"' : '',
                                    $t ? ' theme="'.$t.'"' : '',
                                    '}'
                                );
                                
                                foreach ($medias as $media) {
                                        $ui[] = "<img src=\"".$media[self::SRCPOS]->source."\" alt=\"".$media[self::CAPTIONPOS]->value."\" title=\"".$media[self::CAPTIONPOS]->value."\" />";
                                }
                                
                                $ui[] = "{/{$p}}";
                                $ui = implode('', $ui);
                                
                                break;
                        case "slimbox":
                                $ui = '';

                                foreach ($medias as $media) {
                                        $ui .= ($ui != '' ? ';' : '') . $media[self::SRCPOS]->value . ',' . $media[self::THUMBSRCPOS]->value . ',' . htmlentities($media[$CAPTIONPOS]->value);
                                }

                                $ui = "{slimbox $ui}";
                                
                                break;
                        case "sige":
                                $c = K2FieldsModelFields::setting('caps', $field, 0);
                                
                                $ui = array(
                                    "{gallery}{$DIR}",
                                     "print=0",
                                     "root=1",
                                     "copyright=0",
                                     "download=0",
                                    $isSingleMode ? 'single_gallery=1' : '',
                                    'width_image='.K2FieldsModelFields::setting('picwidth', $field),
                                    'height_image='.K2FieldsModelFields::setting('picheight', $field),
                                    'width='.K2FieldsModelFields::setting('picwidththumb', $field),
                                    'height='.K2FieldsModelFields::setting('picheightthumb', $field),
                                    'quality='.K2FieldsModelFields::setting('picquality', $field),
                                    'watermark='.K2FieldsModelFields::setting('watermark', $field, 0),
                                    'caption='.$c,
                                    'fileinfo='.$c,
                                    'watermarkposition='.K2FieldsModelFields::setting('watermarkposition', $field)        
                                );
                                
                                $ui[] = "{/gallery}";
                                $ui = array_filter($ui);
                                $ui = implode(',', $ui);
                                
                                if ($c) self::createCaptionFile($DIR, 'captions.txt', $medias);
                                
                                break;
                        case "sigplus":
                                $root = self::getMediaRoot($plugin, 'base_folder', $DIR);
                                $c = K2FieldsModelFields::setting('caps', $field, 0);
                                
                                if ($c) $c = 'captions=boxplus.caption caption:alwaysOnTop=1';
                                
                                $w = K2FieldsModelFields::setting('watermark', $field, 0);
                                $wp = '';
                                
                                if ($w) {
                                        $w = 'watermark=1';
                                        $wp = K2FieldsModelFields::setting('watermarkposition', $field);
                                        if ($wp) $wp = 'watermark:position='.$wp;
                                }
                                
                                $e = K2FieldsModelFields::setting('engine', $field, 'boxplus');
                                
                                $ui = array(
                                    "{gallery",
                                    "layout=flow",
                                    "lightbox=".$e,
                                    "download=0",
                                    $isSingleMode ? 'rows=1 cols=1' : '',
                                    'width='.K2FieldsModelFields::setting('picwidththumb', $field),
                                    'height='.K2FieldsModelFields::setting('picheightthumb', $field),
                                    'quality='.K2FieldsModelFields::setting('picquality', $field),
                                    $w, $wp, $c
                                );
                                
                                $ui[] = "}{$root}{/gallery}";
                                $ui = array_filter($ui);
                                $ui = implode(' ', $ui);
                                
                                if ($c) self::createCaptionFile($DIR, 'labels.txt', $medias);
                                
                                break;
                        // multimedia plugins
                        case "jcemediabox":
                        case "shadowbox":
                        case "modalizer":
                        case "rokbox":
                                $ui = $end = '';
                                
                                if ($plugin->name == "jcemediabox") {
                                        $trigger = " class='jcebox noicon' rel='group[".$mediasId."]'";
                                        $end = '<script type="text/javascript">JCEMediaBox.Popup.init()</script>';
                                } else if ($plugin->name == "shadowbox") {
                                        // Mootools incompatibility issue
                                        $trigger = " rel='shadowbox[T{$item->id}]' ";
                                        $end = '<script type="text/javascript">Shadowbox.setup("span.'.$mediasId.' a");</script>';
                                } else if ($plugin->name == "modalizer") {
                                        $plg = JPluginHelper::getPlugin('system', 'modalizer');
                                        $plgParams = new JParameter($plg->params);
                                        $trigger = $plgParams->get('enable_classnames');
                                        if ((bool) $trigger) {
                                                $trigger = $plgParams->get('classnames');
                                                $trigger = explode(',', $trigger);
                                        } else {
                                                self::error('Media plugin incorrectly configured.');
                                                return false;
                                        }
                                        $trigger = ' class="'.$trigger[0].'" rel="[T'.$item->id.']"';
                                } else if ($plugin->name == "rokbox") {
                                        $trigger = ' rel="rokbox(T'.$item->id.')" ';
                                }
                                
                                $n = count($medias);
                                
                                if ($isSingleMode) {
                                        switch ($singleMode) {
                                                case 'first':
                                                        $aim = 0;
                                                        break;
                                                case 'last':
                                                        $aim = $n-1;
                                                        break;
                                                case 'random':
                                                        $aim = rand(0, $n-1);
                                                        break;
                                                default:
                                                        if (preg_match('#(\d+)#', $singleMode, $m)) {
                                                                $aim = (int) $m[1];
                                                        } else {
                                                                $aim = 0;
                                                        }
                                                        break;
                                        }
                                }
                                
                                for ($i = 0; $i < $n; $i++) {
                                        $media = $medias[$i];
                                        $caption = htmlentities($media[self::CAPTIONPOS]->value);
                                        
                                        if (!$isSingleMode || $i == $aim) {
                                                $img = "<img src='".$media[self::THUMBSRCPOS]->value."' alt='". $caption."' title='". $caption."' />";
                                                
                                                if (!$isSingleMode) 
                                                        $img .= "<span class='caption'>".$caption."</span>";
                                        } else {
                                                $img = '';
                                        }
                                        
                                        $ui .= "
                                                <span class='{$mediaType}{$plugin->name} {$mediaType} {$mediasId}'>
                                                        <a href='".$media[self::SRCPOS]->value."' $trigger title='".$caption."'>".$img."</a>
                                                 </span>
                                                 ";
                                }
                                
                                $ui .= $end;
                                
                                break;
                        // av plugins
                        case "jw_allvideos":
                                $dirs = explode('/', $DIR);
                                $gallery = array_pop($dirs);
                                $root = implode('/', $dirs);
                                $params->set('vfolder', $root);
                                
                                if ($singleMode == 'first') {
                                        $params->set('sortorder', 0);
                                } else if ($singleMode == 'random') {
                                        $params->set('sortorder', 4);
                                }
                                                                
                                $vfolder = JPath::clean($params->get('vfolder'), '/').'/';

                                foreach ($medias as $media) {
                                        $type = $media[self::SRCTYPEPOS]->value;
                                        $vfile = $media[self::SRCPOS]->value;
                                        
                                        switch ($type) {
                                                case 'provider':
                                                        $tag = $media[self::CAPTIONNAMINGPOS]->value;
                                                        break;
                                                case 'upload':
                                                case 'remote':
                                                default:
                                                        $tag = JFile::getExt($media[self::SRCPOS]->value);
                                                        
                                                        if ($type == 'upload') {
                                                                $vfile = str_replace($vfolder, '', $vfile);
                                                                $vfile = JFile::stripExt($vfile);
                                                        }
                                                        
                                                        break;
                                        }

                                        $ui .= "{{$tag}}{$vfile}{/{$tag}}";
                                }

                                break;
                        case "flowplayer":
                                foreach ($medias as $media) {
                                        $ui .= "{flowplayer}".$media[self::SRCPOS]->value."{/flowplayer}";
                                }

                                if ($isLoad !== true) {
                                        $evts = array('onAfterDisplayContent');
                                }
                                
                                break;
                        case "avreloaded":
                                $root = substr($rel_file, 0, strlen($rel_file) - 1);
                                $root = str_replace(DS, '/', $root);
                                $root = '/'.$root;
                                $tag = $vfile = $strparams = "";

                                $tag = JFile::getExt($media->videofile);
                                $vfile = $media->videofile;
                                $vfile = JFile::stripExt($vfile);

                                $strparams = ' width="'.$params->get('video_width').'" height="'.$params->get('video_height').'" autostart="'.($params->get('video_autoplay') ? 'true' : 'false').'"';
                                $pparams->set('vdir', $root);
                                $pparams->set('adir', $root);
                                $ui = "{{$tag}{$strparams}}{$vfile}{/{$tag}}";

                                break;
                        case "mp3skinned":
                        case "josdewplayer":
                                $vfile = str_replace(DS, "/", $rel_file.$media->videofile);
                                $ui = "{play}$vfile{/play}";
                                break;
                        case "denvideo":
                                $vfile = $pre.$rel_file.$media->videofile;
                                $_plugin =& JPluginHelper::getPlugin('content', 'denvideo');
                                $_plgParams = new JParameter( $_plugin->params );
                                $defdir = $_plgParams->get('defaultdir');
                                
                                if ($defdir) {
                                        $sep = stripos($defdir, '/') ? '/' : DS;
                                        $cnt = count(explode($sep, $defdir));
                                        $pre = implode(DS, array_fill(0, $cnt, '..')) . DS;
                                        $vfile = $pre.$vfile;
                                        $vfile = '/'.str_replace(DS, "/", $vfile);
                                } else {
                                        $vfile = '/'.str_replace(DS, "/", $vfile);
                                }

                                $strparams = $params->get('video_width')." ".$params->get('video_height')." ".($params->get('video_autoplay') == 1 ? "TRUE" : "FALSE");
                                $ui = "{denvideo {$vfile} {$strparams}}";
                                break;
                }
                
                $uiItem = new stdClass();
                $uiItem->text = $ui;
                $limitstart = $input->get('limitstart', 0, 'int');
                $dispatcher = JDispatcher::getInstance();
                $ctxt = 'com_k2.'.$input->get('view', 'item', 'cmd');
                
                $dispatcher->trigger('onContentPrepare', array($ctxt, &$uiItem, &$plugin->params, $limitstart));
                
                return $uiItem->text;        
        }
        
        protected static function getMediaRoot($plugin, $rootParamName, $accRoot) {
                $plgRoot = $plugin->params->get($rootParamName, '');

                if (!empty($plgRoot)) {
                        $plgRoot = str_replace(JPath::clean(JPATH_SITE, '/'), '', JPath::clean(JPATH_SITE . $plgRoot, '/'));
                        $plgRoot = explode('/', $plgRoot);
                        $plgRoot = array_filter($plgRoot);
                        $currentPos = count($plgRoot);
                        $currentPos = str_repeat('../', $currentPos);
                        $accRoot = $currentPos . $accRoot;
                }
                
                return $accRoot;
        }
        
        protected static function createCaptionFile($loc, $fileName, $medias) {
                $captionFile = JPATH_SITE . '/' . $loc . '/' . $fileName;
                
                jimport('joomla.filesystem.file');
                
                $existsCaptions = false;

                if (!($existsCaptions = JFile::exists($captionFile))) {
                        $captions = array();

                        foreach ($medias as $media) {
                                $file = basename($media[self::SRCPOS]->value);
                                $caption = $media[self::CAPTIONPOS]->value;
                                $captions[] = $file . "|" . $caption . "|" . $caption;
                        }
                        
                        $captions = implode($captions, "\n");
                        $captions = trim($captions);
                        $existsCaptions = !empty($captions);

                        if ($existsCaptions) JFile::write($captionFile, $captions);
                }
                
                return $existsCaptions;
        }

        protected static function mediaFilesPattern($options) {
                static $pat;
                
                if (empty($pat)) {
                        $pat = self::getAllowedExtensions($options);
                        $pat = '^(?i).+\.(' . implode('|', $pat) . ')$';
                }
                
                return $pat;
        }
        
        public static function getPluginType($plugin) {
                $db = JFactory::getDBO();
                $query = 'SELECT folder FROM #__extensions WHERE element = ' . $db->Quote($plugin) . ' AND enabled = 1 AND folder IN ("content", "system") ORDER BY folder LIMIT 1';
                $db->setQuery($query);
                $type = $db->loadResult();
                return $type ? $type : false;
        }
        
        public static function getBrowsableFiles($options) {
                $user = JFactory::getUser();
                $files = array();
                
                if ($user->guest) return $files; 
                
                $query = "
                SELECT 
                        it.id AS itemid, it.title, res.id, (SELECT value FROM #__k2_extra_fields_values AS c WHERE c.itemid = res.itemid AND c.fieldid = res.fieldid AND c.listindex = res.listindex AND c.partindex = -1 AND c.index = 0) as label, res.value
                FROM 
                        #__k2_extra_fields_values AS val, 
                        #__k2_items AS it,
                        #__k2_extra_fields AS flds,
                        #__k2_extra_fields_values AS res
                WHERE 
                        it.created_by = ".$user->get('id')." AND 
                        it.published = 1 AND
                        val.itemid = it.id AND
                        flds.name LIKE 'k2f---valid=media%' AND
                        flds.id = val.fieldid AND
                        val.value = 'upload' AND
                        res.itemid = val.itemid AND 
                        res.fieldid = val.fieldid AND 
                        res.listindex = val.listindex AND 
                        res.partindex = ".(self::SRCPOS - 1)."
                ORDER BY
                        it.id
                ";
                
                $db = JFactory::getDBO();
                $db->setQuery($query);
                $items = $db->loadObjectList();
                $files = array();
                $itemId = '';
                
                foreach ($items as $item) {
                        if ($itemId != $item->itemid) {
                                $itemId = $item->itemid;
                                $files[] = array('label' => $item->title);
                        }
                        
                        $files[] = array('value' => $item->id, 'text' => basename($item->value));
                }
                
                return $files;
                
//                /* actual file counting */
//                
//                $pat = self::mediaFilesPattern($options);
//                
//                $dir = JPATH_SITE . '/' . $options['mediaroot'] . '/';
//                $userFolder = $options['mediafolder'] == 'user';
//                
//                if ($userFolder) {
//                        $dir .= $user->get('id') . '/';
//                        
//                        if (!JFolder::exists($_dir)) {
//                                $_files = JFolder::files($dir, $pat, false, true);
//                                $files['all'] = $_files;
//                        }
//                } else {
//                        $db = JFactory::getDBO();
//                        $q = "SELECT id, title FROM #__k2_items WHERE published = 1";
//                        if ($user->gid < 23) $q .= " AND created_by = " . $user->get('id');
//                        
//                        $db->setQuery($q);
//                        $items = $db->loadObjectList();
//                        
//                        foreach ($items as $item) {
//                                $_dir = $dir . $item->id . '/';
//                                
//                                if (!JFolder::exists($_dir)) continue;
//                                
//                                $_files = JFolder::files($_dir, $pat, false, true);
//                                
//                                if (!empty($_files)) {
//                                        $files[$item->title] = array($item->id, $_files);
//                                }
//                        }
//                }
//                
//                $result = array();
//                
//                foreach ($files as $ind => $_files) {
//                        $result[] = array('label' => $ind);
//                        $itemId = $_files[0];
//                        $_files = $_files[1];
//                        
//                        foreach ($_files as $i => $_file) {
//                                $result[] = array(
//                                    'text' => basename($_file), 
//                                    'value' => $itemId . ':' . JPath::clean(substr(str_replace(JPATH_SITE, '', $_file), 1), '/')
//                                );
//                        }
//                }
//                
//                if ($userFolder && !empty($result)) {
//                        $result = array_shift($result);
//                }
                
//                return $result;
        }

        protected static function getVideoProviders($plg) {
                static $providers = array();
                
                if (!empty($providers[$plg])) return $providers[$plg];

                switch ($plg) {
                        case "avreloaded":
                                $db = JFactory::getDBO();
                                $q = "SELECT name FROM #__avr_tags WHERE local = 0 AND name NOT LIKE '%remote'";
                                $db->setQuery($q);
                                $providers[$plg] = $db->loadResultArray();
                                break;
                        case "jw_allvideos":
                                // Note: taken from k2: administrator/components/com_k2/models/item.php::getVideoProviders
                                // and with slight customization
                                
                                if(K2_JVERSION == '16') {
                                        $file = JPATH_PLUGINS.DS.'content'.DS.'jw_allvideos'.DS.'jw_allvideos'.DS.'includes'.DS.'sources.php';
                                }
                                else {
                                        $file = JPATH_PLUGINS.DS.'content'.DS.'jw_allvideos'.DS.'includes'.DS.'sources.php';
                                }

                                jimport('joomla.filesystem.file');
                                if (JFile::exists($file)) {
                                        require $file;
                                        $thirdPartyProviders = array_slice($tagReplace, 40);
                                        $providersTmp = array_keys($thirdPartyProviders);
                                        $providers = array();
                                        foreach ($providersTmp as $providerTmp) {

                                                if (stristr($providerTmp, 'google|google.co.uk|google.com.au|google.de|google.es|google.fr|google.it|google.nl|google.pl') !== false) {
                                                        $provider = 'google';
                                                } elseif (stristr($providerTmp, 'spike|ifilm') !== false) {
                                                        $provider = 'spike';
                                                } else {
                                                        $provider = $providerTmp;
                                                }
                                                $providers[] = $provider;
                                        }
                                        return $providers;
                                } else {
                                        return array();
                                }                                
                                break;
                          default:
                                  return '';
                }

                return $providers[$plg];
        }        
        
        protected static function returnBytes($val) {
                $val = trim($val);
                $last = strtolower($val[strlen($val) - 1]);
                
                switch($last) {
                        // The 'G' modifier is available since PHP 5.1.0
                        case 'g':
                                $val *= 1024;
                        case 'm':
                                $val *= 1024;
                        case 'k':
                                $val *= 1024;
                }

                return $val;
        }
        
//        /*** Wideimage based resizing ***/
//        protected static function resizePic($file, $options, $type = '') {
//                if (!JFile::exists($file)) {
//                        return self::error('File missing.'); 
//                }
//                
//                $file = JPath::clean($file);
//                $size = getimagesize($file);
//                
//                if ($size === false) return false;
//
//                $maxWidth = $options['picwidth'.$type];
//                $maxHeight = $options['picheight'.$type];
//
//                $width = $size[0];
//                $height = $size[1];
//                
//                if ($width <= $maxWidth && $height <= $maxHeight) {
//                        return $file;
//                }
//                
//                if ($width > $maxWidth) {
//                        $width = $maxWidth;
//                        $height = null;
//                } else {
//                        $height = $maxHeight;
//                        $width = null;
//                }
//
//                return self::doResizePic($file, $width, $height, $options['picquality'.$type], $type == '');
//        }
//        
//        protected static function doResizePic($file, $width, $height, $quality = 100, $deleteOriginal = true) {
//                require_once dirname(__FILE__).'/wideimage/WideImage.php';
//                
//                try {
//                        $img = WideImage::load($file);
//                } catch (Exception $e) {
//                        return self::error('Cannot create image'); 
//                }
//                
//                $img->resizeDown($width, $height);
//                $tmp = JPATH_SITE . '/tmp/' . time() . basename($file);
//                
//                if (strtolower(WideImage_MapperFactory::determineFormat($file)) == 'jpeg') {
//                        $img->saveToFile($tmp, $quality);
//                } else {
//                        $img->saveToFile($tmp);
//                }
//                
//                $img->destroy();
//                
//                if ($deleteOriginal) {
//                        JFile::delete($file);
//                }
//                
//                return $tmp;
//        }      
        
        protected static function saveRemoteFile($request, $options, $item, $dstDir) {
                if (!$options['remotedlallowed']) {
                        return false;
                }
                
                $content = K2fieldsUtility::makeHTTPRequest($request);
                
                if ($content && !($content instanceof JException)) {
                        $ext = JFile::getExt($request);
                        $toFile = JPath::clean(JPATH_SITE . '/tmp/k2media' . time() . '.' . $ext);
                        
                        if (JFile::write($toFile, $content) !== false) {
                                $mt = self::checkFile($item, $toFile, $options);
                                
                                if ($mt instanceof JException) {
                                        JFile::delete($toFile);
                                        return $mt;
                                }
                                
                                $dst = JPath::clean($dstDir . '/' . basename($request));
                                
                                if (JFile::exists($dst) && !JFile::delete($dst) || !JFile::move($toFile, $dst)) {
                                        return self::error('Cannot move file to provided directory.');
                                }
                                
                                $dst = str_replace(JPATH_SITE . '/', '', $dst);
                                
                                return array('file' => $dst, 'mediatype' => $mt);
                        } else {
                                return self::error('Cannot download file(s): directory is inaccessible.');
                        }
                }

                return self::error('Provided remote resource is inaccessible.');
        }
}