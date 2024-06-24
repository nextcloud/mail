<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Dashboard;

class UnreadMailWidget extends MailWidget {
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
