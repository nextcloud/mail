<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\MailAccount;

class AllowedRecipientsService {

	public function __construct(
		private AliasesService $aliasesService,
	) {
	}

	/**
	 * Return a list of allowed recipients for a given mail account
	 *
	 * @return string[] email addresses
	 */
	public function get(MailAccount $mailAccount): array {
		$aliases = array_map(
			static fn ($alias) => $alias->getAlias(),
			$this->aliasesService->findAll($mailAccount->getId(), $mailAccount->getUserId())
		);

		return array_merge([$mailAccount->getEmail()], $aliases);
	}
}
