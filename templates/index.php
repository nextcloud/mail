<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
script('mail', 'mail');
?>

<input type="hidden" id="attachment-size-limit" value="<?php p($_['attachment-size-limit']); ?>">
<input type="hidden" id="config-installed-version" value="<?php p($_['app-version']); ?>">
<input type="hidden" id="external-avatars" value="<?php p($_['external-avatars']); ?>">
<input type="hidden" id="collect-data" value="<?php p($_['collect-data']); ?>">
<input type="hidden" id="start-mailbox-id" value="<?php p($_['start-mailbox-id']); ?>">
<input type="hidden" id="tag-classified-messages" value="<?php p($_['tag-classified-messages']); ?>">
<input type="hidden" id="search-priority-body" value="<?php p($_['search-priority-body']); ?>">
<input type="hidden" id="layout-mode" value="<?php p($_['layout-mode']); ?>">

