<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Dashboard;

/**
 * Requires Nextcloud >= 27.1.0
 */
class UnreadMailWidgetV2 extends MailWidgetV2 {
	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mail-unread';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Unread mail');
	}

	/**
	 * @inheritDoc
	 */
	public function getSearchFilter(): string {
		return 'is:unread';
	}
}
