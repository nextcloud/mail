<?php
/**
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
?>
<?php /** @var array $_ */?>
<?php /** @var \OCP\IL10N $l */?>
<div class="error">
	<legend><strong><?php p($l->t('Error loading message'));?></strong></legend>
	<p><?php p($_['message']); ?></p>
</div>
