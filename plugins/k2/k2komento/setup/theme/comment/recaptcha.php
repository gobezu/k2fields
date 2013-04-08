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
<script type="text/javascript" src="<?php echo $server; ?>/js/recaptcha_ajax.js"></script>
<script type="text/javascript">
	Komento(function($) {
		$(document).ready(function(){
			Recaptcha.create("<?php echo $publicKey ?>", "recaptcha-image", {
				lang: "<?php echo $language ?>",
				theme: "<?php echo $theme ?>",
				tabindex: 0
			});
		});
	});
</script>
<div id="recaptcha-image"></div>
