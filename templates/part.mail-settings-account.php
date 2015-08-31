<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

?>

<script id="mail-settings-account" type="text/x-handlebars-template">
	<span class="mail-account-name">{{ emailAddress }}</span>
	<span class="actions">
		<a class="icon-delete delete action"
		   title="<?php p($l->t('Delete')); ?>"></a>
	</span>
</script>