<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IDkimService;
use OCA\Mail\Contracts\IDkimValidator;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCP\ICache;
use OCP\ICacheFactory;

class DkimService implements IDkimService {
	private const CACHE_PREFIX = 'mail_dkim';
	private const CACHE_TTL = 7 * 24 * 3600;

	/** @var ICache */
	private $cache;

	public function __construct(
		private readonly \OCA\Mail\IMAP\IMAPClientFactory $clientFactory,
		private readonly \OCA\Mail\IMAP\MessageMapper $messageMapper,
		ICacheFactory $cacheFactory,
		private readonly IDkimValidator $dkimValidator,
	) {
		$this->cache = $cacheFactory->createLocal(self::CACHE_PREFIX);
	}

	#[\Override]
	public function validate(Account $account, Mailbox $mailbox, int $id): bool {
		$cached = $this->getCached($account, $mailbox, $id);
		if (is_bool($cached)) {
			return $cached;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$fullText = $this->messageMapper->getFullText(
				$client,
				$mailbox->getName(),
				$id,
				$account->getUserId(),
				false,
			);

			if ($fullText === null) {
				throw new ServiceException('Could not fetch message source for uid ' . $id);
			}
		} finally {
			$client->logout();
		}

		$result = $this->dkimValidator->validate($fullText);

		$cache_key = $this->buildCacheKey($account, $mailbox, $id);
		$this->cache->set($cache_key, $result, self::CACHE_TTL);

		return $result;
	}

	#[\Override]
	public function getCached(Account $account, Mailbox $mailbox, int $id): ?bool {
		return $this->cache->get($this->buildCacheKey($account, $mailbox, $id));
	}

	private function buildCacheKey(Account $account, Mailbox $mailbox, int $id): string {
		return $account->getId() . '_' . $mailbox->getName() . '_' . $id;
	}
}
