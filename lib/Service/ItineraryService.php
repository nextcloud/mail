<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Integration\KItinerary\ItineraryExtractor;
use OCP\ICacheFactory;
use OCP\ILogger;
use function array_reduce;
use function count;
use function json_encode;

class ItineraryService {

	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var ItineraryExtractor */
	private $extractor;

	/** @var ILogger */
	private $logger;

	public function __construct(IMAPClientFactory $clientFactory,
								MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								ItineraryExtractor $extractor,
								ICacheFactory $cacheFactory,
								ILogger $logger) {
		$this->clientFactory = $clientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->extractor = $extractor;
		$this->cache = $cacheFactory->createLocal();
		$this->logger = $logger;
	}

	public function extract(Account $account, string $mailbox, int $id): Itinerary {
		$mailbox = $this->mailboxMapper->find($account, $mailbox);

		$cacheKey = 'mail_itinerary_' . $account->getId() . '_' . $mailbox->getName() . '_' . $id;
		if ($cached = ($this->cache->get($cacheKey))) {
			return Itinerary::fromJson($cached);
		}

		$client = $this->clientFactory->getClient($account);

		$itinerary = new Itinerary();
		$htmlBody = $this->messageMapper->getHtmlBody($client, $mailbox->getName(), $id);
		if ($htmlBody !== null) {
			$itinerary = $itinerary->merge(
				$this->extractor->extract($htmlBody)
			);
			$this->logger->debug('Extracted ' . count($itinerary) . ' itinerary entries from the message HTML body');
		} else {
			$this->logger->debug('Message does not have an HTML body, can\'t extract itinerary info');
		}
		$attachments = $this->messageMapper->getRawAttachments($client, $mailbox->getName(), $id);
		$itinerary = array_reduce($attachments, function (Itinerary $combined, string $attachment) {
			$extracted = $this->extractor->extract($attachment);
			$this->logger->debug('Extracted ' . count($extracted) . ' itinerary entries from an attachment');
			return $combined->merge($extracted);
		}, $itinerary);

		// Lastly, we put the extracted data through the tool again, so it can combine
		// and pick the most relevant information
		$final = $this->extractor->extract(json_encode($itinerary));
		$this->logger->debug('Reduced ' . count($itinerary) . ' itinerary entries to ' . count($final) . ' entries');

		$this->cache->set($cacheKey, json_encode($final));

		return $final;
	}
}
