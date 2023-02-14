<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Service;

use ChristophWurst\KItinerary\Itinerary;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Integration\KItinerary\ItineraryExtractor;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;
use function array_reduce;
use function count;
use function json_encode;

class ItineraryService {
	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var ItineraryExtractor */
	private $extractor;

	/** @var ICache */
	private $cache;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IMAPClientFactory $clientFactory,
								MessageMapper $messageMapper,
								ItineraryExtractor $extractor,
								ICacheFactory $cacheFactory,
								LoggerInterface $logger) {
		$this->clientFactory = $clientFactory;
		$this->messageMapper = $messageMapper;
		$this->extractor = $extractor;
		$this->cache = $cacheFactory->createLocal();
		$this->logger = $logger;
	}

	private function buildCacheKey(Account $account, Mailbox $mailbox, int $id): string {
		return 'mail_itinerary_' . $account->getId() . '_' . $mailbox->getName() . '_' . $id;
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
		$itinerary = array_reduce($attachments, function (Itinerary $combined, string $attachment) {
			$extracted = $this->extractor->extract($attachment);
			$this->logger->debug('Extracted ' . count($extracted) . ' itinerary entries from an attachment');
			return $combined->merge($extracted);
		}, $itinerary);

		// Lastly, we put the extracted data through the tool again, so it can combine
		// and pick the most relevant information
		$final = $this->extractor->extract(json_encode($itinerary));
		$this->logger->debug('Reduced ' . count($itinerary) . ' itinerary entries to ' . count($final) . ' entries');

		$cache_key = $this->buildCacheKey($account, $mailbox, $id);
		$this->cache->set($cache_key, json_encode($final));

		return $final;
	}
}
