<?php
//$Copyright$

// No direct access
defined('_JEXEC') or die;

class Com_K2fieldsInstallerScript {
	public function __construct($installer) {
		$this->installer = $installer;
	}
        
        public function update($adapter) { $this->install($adapter, true); }

	public function install($adapter, $isUpdate = false) {
                if (!$isUpdate) {
                        // Create stored procedures if any available
                        jimport('joomla.filesystem.file');
                        if (JFile::exists(dirname(__FILE__).'/setup/sp.install.mysqli.sql')) {
                                if (class_exists('PDO')) {
                                        $sql = JFile::read(dirname(__FILE__).'/sp.install.mysql.sql');
                                        $sql = str_replace('DELIMITER //', '', $sql);
                                        $config = JFactory::getConfig();
                                        $dbh = new PDO('mysql:host='.$config->getValue('config.host').';dbname='.$config->getValue('config.db'), $config->getValue('config.user'), $config->getValue('config.password'));
                                        $sql = $db->replacePrefix($sql);
                                        $dbh->exec($sql);
                                        $status = $dbh->errorInfo();
                                        $sperror = isset($status[1]);
                                } else {
                                        $sperror = true;
                                }
                        } else {
                                $sperror = false;
                        }
                }

                $extensions = $this->getExtensions($adapter);
                $aerror = array();
                $error = false;
                $db = JFactory::getDbo();

                for ($i = 0; $i < count($extensions); $i++) {
                        $extension =& $extensions[$i];

                        if ($extension['installer']->install($extension['folder'])) {
                                $extension['status'] = true;
                                if (!$isUpdate && $extension['type'] == 'plugin' && $extension['enable']) {
                                        // Publish installed plugins if required
                                        $query = "UPDATE `#__extensions` SET enabled = 1 WHERE type = 'plugin' AND folder = ".$db->Quote($extension['group'])." AND element = ".$db->Quote($extension['ename']);

                                        $db->setQuery($query);
                                        $status = $db->query();

                                        if (!$status) $aerror[] = $extension['name'] . ' in ' . $extension['group'];
                                }
                        } else {
                                $error = true;
                                break;
                        }
                }

                // rollback on installation errors
                if ($error) {
                        $adapter->parent->abort(JText::_('Component').' '.JText::_('Install').': '.JText::_('Error'), 'component');

                        for ($i = 0; $i < count($extensions); $i++) {
                                if ($extensions[$i]['status']) {
                                        $extensions[$i]['installer']->abort(JText::_($extensions[$i]['type']).' '.JText::_('Install').': '.JText::_('Error'), $extensions[$i]['type']);
                                        $extensions[$i]['status'] = false;
                                }
                        }
                } else {
                        // ev. specific tasks post installation
                }
                
                $action = $isUpdate ? 'Updated' : 'Installed';
?>
<h3><?php echo JText::_('Extensions'); ?></h3>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title"><?php echo JText::_('Extension'); ?></th>
			<th width="60%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
	</tfoot>
	<tbody>
                <?php if (isset($sperror) && $sperror) : ?>
			<tr>
                                <td colspan="2">
                                        <div class="error"><?php echo JText::_('Stored procedure creation failed. Please create manually from the file sp.install.mysql.sql.'); ?></div>
                                </td>
			</tr>
		<?php endif; ?>
                <?php if (!empty($aerror)) : ?>
			<tr>
                                <td colspan="2">
                                        <div class="error"><?php 
                                                echo JText::_('Was unable to publish some required plugins. Please make sure to publish the following plugins:'); 
                                                echo '<ul><li>'.implode('</li><li>', $aerror).'</li></ul>';
                                        ?></div>
                                </td>
			</tr>
		<?php endif; ?>
		<?php foreach ($extensions as $i => $ext) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="key"><?php echo $ext['name']; ?> (<?php echo JText::_($ext['type']); ?>)</td>
				<td>
					<?php $style = $ext['status'] ? 'font-weight: bold; color: green;' : 'font-weight: bold; color: red;'; ?>
					<span style="<?php echo $style; ?>"><?php echo $ext['status'] ? JText::_($action.' successfully') : JText::_('NOT '.$action); ?></span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
        }
        
        function uninstall($adapter) {
                $extensions = array();
                $exts = $this->getExtensions($adapter);
                
                foreach ($exts as $ext) 
                        $extensions[] = $this->uninstallExt($ext);
?>
<h3><?php echo JText::_('Additional Extensions'); ?></h3>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title"><?php echo JText::_('Extension'); ?></th>
			<th width="60%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
	</tfoot>
	<tbody>
		<?php foreach ($extensions as $i => $ext) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="key"><?php echo $ext['name']; ?> (<?php echo JText::_($ext['type']); ?>)</td>
				<td>
					<?php $style = $ext['status'] ? 'font-weight: bold; color: green;' : 'font-weight: bold; color: red;'; ?>
					<span style="<?php echo $style; ?>"><?php echo $ext['status'] ? JText::_('Uninstalled successfully') : JText::_('Uninstall FAILED'); ?></span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
        }
        
        function getExtensions($adapter) {
                $installer  = $adapter->getParent();
                $add = $installer->manifest->xpath('additional');
                
                if ($add) $add = $add[0];

                $extensions = array();

                if ((is_a($add, 'JSimpleXMLElement') || is_a($add, 'JXMLElement')) && count($add->children())) {
                        $exts =& $add->children();
                        foreach ($exts as $ext) {
                                $extensions[] = array(
                                        'ename' => (string) $ext->attributes()->name,
                                        'name' => (string) $ext->data(),
                                        'type' => $ext->getName(),
                                        'folder' => $installer->getPath('source').'/'.(string) $ext->attributes()->folder,
                                        'installer' => new JInstaller(),
                                        'status' => false,
                                        'enable' => $ext->getName() == 'plugin' && (string) $ext->attributes()->enable == '1',
                                        'group' => (string) $ext->attributes()->group
                                );
                        }
                }
                
                return $extensions;
        }
        
        function uninstallExt($ext) {
                $type = $ext['type'];
                $name = $ext['ename'];
                $n = $type == 'module' ? 'mod_'.$name : $name;
                $db = JFactory::getDbo();

                $query = 'SELECT extension_id FROM #__extensions WHERE type='.$db->Quote($type).' AND element='.$db->Quote($n);

                if ($type == 'plugin') {
                        $folder = $ext['group'];
                        $query .= ' AND folder='.$db->Quote($folder);
                }

                $db->setQuery($query);
                $extId = $db->loadResult();
                $inst = $ext['installer']->uninstall($type, $extId, 1);

                return array('name' => $name.' - '.$ext['name'], 'type' => $type, 'status' => $inst ? true : false);
        }        
}
?>
