<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Nextcloud\KItinerary\Itinerary;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCP\ICache;
use OCP\ICacheFactory;
use function array_reduce;
use function count;
use function json_encode;

class ItineraryService {
	private const CACHE_PREFIX = 'mail_itinerary';
	private const CACHE_TTL = 7 * 24 * 3600;

	/** @var ICache */
	private $cache;

	public function __construct(
		private readonly \OCA\Mail\IMAP\IMAPClientFactory $clientFactory,
		private readonly \OCA\Mail\IMAP\MessageMapper $messageMapper,
		private readonly \OCA\Mail\Integration\KItinerary\ItineraryExtractor $extractor,
		ICacheFactory $cacheFactory,
		private readonly \Psr\Log\LoggerInterface $logger
	) {
		$this->cache = $cacheFactory->createLocal(self::CACHE_PREFIX);
	}

	private function buildCacheKey(Account $account, Mailbox $mailbox, int $id): string {
		return $account->getId() . '_' . $mailbox->getName() . '_' . $id;
	}

	public function getCached(Account $account, Mailbox $mailbox, int $id): ?Itinerary {
		if ($cached = ($this->cache->get($this->buildCacheKey($account, $mailbox, $id)))) {
			return Itinerary::fromJson($cached);
		}

		return null;
	}

	public function extract(Account $account, Mailbox $mailbox, int $id): Itinerary {
		if ($cached = ($this->getCached($account, $mailbox, $id))) {
			return $cached;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$itinerary = new Itinerary();
			$htmlBody = $this->messageMapper->getHtmlBody($client, $mailbox->getName(), $id, $account->getUserId());
			if ($htmlBody !== null) {
				$itinerary = $itinerary->merge(
					$this->extractor->extract($htmlBody)
				);
				$this->logger->debug('Extracted ' . count($itinerary) . ' itinerary entries from the message HTML body');
			} else {
				$this->logger->debug('Message does not have an HTML body, can\'t extract itinerary info');
			}
			$attachments = $this->messageMapper->getRawAttachments($client, $mailbox->getName(), $id, $account->getUserId());
		} finally {
			$client->logout();
		}
		$itinerary = array_reduce($attachments, function (Itinerary $combined, string $attachment): \Nextcloud\KItinerary\Itinerary {
			$extracted = $this->extractor->extract($attachment);
			$this->logger->debug('Extracted ' . count($extracted) . ' itinerary entries from an attachment');
			return $combined->merge($extracted);
		}, $itinerary);

		// Lastly, we put the extracted data through the tool again, so it can combine
		// and pick the most relevant information
		$final = $this->extractor->extract(json_encode($itinerary));
		$this->logger->debug('Reduced ' . count($itinerary) . ' itinerary entries to ' . count($final) . ' entries');

		$cache_key = $this->buildCacheKey($account, $mailbox, $id);
		$this->cache->set($cache_key, json_encode($final), self::CACHE_TTL);

		return $final;
	}
}
