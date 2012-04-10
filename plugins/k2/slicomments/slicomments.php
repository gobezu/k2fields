<?php
// $copyright$
// original copyright of plguin on which this is based follows
/**
 * @package		sliComments
 * @subpackage	Content Plugin
 * @license		GNU General Public License version 3; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * sliComments Content Plugin
 *
 * @package		sliComments
 * @subpackage	Content Plugin
 * @since		1.0
 */
class plgK2Slicomments extends JPlugin {
        const NOCOMMENT = '{nocomment}';
        
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_content_slicomments.sys');
	}

        // &$item, &$params, $limitstart
	public function onK2CommentsBlock(&$item, &$params, $limitstart = 0) {
		if (!$params->get('itemComments') || strtolower(JRequest::getWord('format', 'html')) !== 'html') return;
		
		$view = strtolower(JRequest::getCmd('view'));
                
		if ($view == 'itemlist') {
                        return $this->onK2CommentsCounter($item, $params, $limitstart);
		} elseif ($view == 'item') {
                        if (strpos($item->introtext, self::NOCOMMENT) !== false) {
                                $item->introtext = str_replace(self::NOCOMMENT, '', $item->introtext);
                                return '<span class="nocomment"></span>';
                        }
                        
			jimport('application.component.controller');
                        
                        $input = JFactory::getApplication()->input;

			$old_view = $input->get('view', '', 'cmd');
			$old_task = $input->get('task', '', 'cmd');
                        $input->set('view', 'comments');
                        $input->set('task', 'comments.display');
			$config = array('base_path'=> JPATH_SITE.'/components/com_slicomments');
			require_once $config['base_path'].'/controllers/comments.php';
			JFactory::getLanguage()->load('com_slicomments', JPATH_BASE, null, false, false);
			$controller = new sliCommentsControllerComments($config);
			$model = $controller->getModel('comments');
                        // @TODO: k2 category från plugin
//			if (!$model->isCategoryEnabled($item->catid)) {
//				return;
//			}
                        
			$model->setState('article.id', $item->id);
			$model->setState('article.link', $item->link);
			$model->setState('article.catid', $item->catid);
			$model->setState('article.params', $item->params);
                        $model->setState('article.extension_name', 'com_k2');
			ob_start();
			$controller->execute('display');
                        $cmnts = ob_get_clean();
                        $input->set('view', $old_view);
                        $input->set('task', $old_task);
                        return $cmnts;
		}
	}
        
	function onK2CommentsCounter( &$item, &$params, $limitstart ) {
                if ($params->get('itemComments') && $this->params->get('comments_count', false)) {
                        $model = $this->getModel();
                        $model->setState('article.id', $item->id);
                        $total = $model->getTotal();
                        // @TODO: k2 category från plugin
                        //if (($total > 0 || $item->params->get('slicomments.enabled', true)) && $model->isCategoryEnabled($item->catid)) {

                        if ($total > 0 || $item->params->get('slicomments.enabled', true)) {
                                return '<a href="'.  K2HelperRoute::getItemRoute($item->id, $item->catid).'#itemCommentsAnchor">'.JText::sprintf('PLG_CONTENT_SLICOMMENTS_COMMENTS_COUNT', $total).'</a>';
                        }                
                }
                
		return '';
	}
        
        // @TODO: Need to detect url params and invoke this manually from plgSystemK2fields
	public function onK2ItemDelete($item) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete()
			->from('#__slicomments')
			->where('article_id = '.(int)$item->id);
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500, 'Error deleting comments from article "'.$item->id.'-'.$item->title.'". '.$db->getErrorMsg());
		}
	}

        // K2 not yet fully compatible
	public function onContentPrepareForm($form, $data)
	{
//		if ($form->getName() != 'com_content.article') return;
//
//		// Load the custom form
//		$this->loadLanguage();
//		$form->loadFile(dirname(__FILE__).'/article.xml');
	}
        
        public function onCommentsProcess($comments) {
                
        }

	protected function getModel()
	{
		static $model;
		if ($model === null)
		{
			JModel::addIncludePath(JPATH_SITE.'/components/com_slicomments/models', 'sliCommentsModel');
			$model = JModel::getInstance('Comments', 'sliCommentsModel', array('ignore_request' => true));
		}
		return $model;
	}
}
