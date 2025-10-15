<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Dashboard;

use OCA\Mail\Service\Search\Flag;
use OCA\Mail\Service\Search\GlobalSearchQuery;
use OCA\Mail\Service\Search\SearchQuery;

class UnreadMailWidget extends MailWidget {
	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getId(): string {
		return 'mail-unread';
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('Unread mail');
	}

	#[\Override]
	public function getSearchQuery(string $userId): SearchQuery {
		$query = new GlobalSearchQuery();
		$query->addFlag(Flag::not(Flag::SEEN));
		$query->setExcludeMailboxIds($this->getMailboxIdsToExclude($userId));
		return $query;
	}
}
