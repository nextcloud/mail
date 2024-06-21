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
class ImportantMailWidgetV2 extends MailWidgetV2 {
	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mail';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Important mail');
	}

	/**
	 * @inheritDoc
	 */
	public function getSearchFilter(): string {
		return 'is:important';
	}
}
