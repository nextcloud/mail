<?php

declare(strict_types=1);

/**
 *
 * @copyright Copyright (c) 2023, Gerke Frölje (gerke@audriga.com)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use SML\Html2JsonLd\Util\MarkupUtil;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;
use stdClass;

class SchemaService {
	private const CACHE_PREFIX = 'mail_schema';
	private const CACHE_TTL = 7 * 24 * 3600;

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
		ICacheFactory $cacheFactory,
		LoggerInterface $logger) {
		$this->clientFactory = $clientFactory;
		$this->messageMapper = $messageMapper;
		$this->cache = $cacheFactory->createLocal(self::CACHE_PREFIX);
		$this->logger = $logger;
	}

	private function buildCacheKey(Account $account, Mailbox $mailbox, int $id): string {
		return $account->getId() . '_' . $mailbox->getName() . '_' . $id;
	}

	public function getCached(Account $account, Mailbox $mailbox, int $id) {
		if ($cached = ($this->cache->get($this->buildCacheKey($account, $mailbox, $id)))) {
			return json_decode($cached);
		}

		return null;
	}

    public function extract(Account $account, Mailbox $mailbox, int $id)
    {
		if ($cached = ($this->getCached($account, $mailbox, $id))) {
			return $cached;
		}

        $client = $this->clientFactory->getClient($account);
        $schema = null;

        try {
			$htmlBody = $this->messageMapper->getHtmlBody($client, $mailbox->getName(), $id, $account->getUserId());
			if ($htmlBody !== null) {
                $schema = json_decode(MarkupUtil::getJsonLdFromHtmlString($htmlBody, "www.example.com"));
				
				if (is_null($schema)) {
					$this->logger->debug('Found no schema entries in the message HTML body');
				} else {
                	$this->logger->debug('Extracted ' . (is_array($schema) ? count($schema) : '1') . ' schema entries from the message HTML body');
				}
			} else {
				$this->logger->debug('Message does not have an HTML body, can\'t extract schema info');
			}
		} finally {
			$client->logout();
		}

		$cache_key = $this->buildCacheKey($account, $mailbox, $id);
		$this->cache->set($cache_key, json_encode($schema), self::CACHE_TTL);

        return $schema;
    }
}