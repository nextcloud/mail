<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMapper;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reduce;

class PreviewEnhancer {
	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var ImapMapper */
	private $imapMapper;

	/** @var DbMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IMAPClientFactory $clientFactory,
		ImapMapper $imapMapper,
		DbMapper $dbMapper,
		LoggerInterface $logger) {
		$this->clientFactory = $clientFactory;
		$this->imapMapper = $imapMapper;
		$this->mapper = $dbMapper;
		$this->logger = $logger;
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Message[]
	 */
	public function process(Account $account, Mailbox $mailbox, array $messages): array {
		$needAnalyze = array_reduce($messages, static function (array $carry, Message $message) {
			if ($message->getStructureAnalyzed()) {
				// Nothing to do
				return $carry;
			}

			return array_merge($carry, [$message->getUid()]);
		}, []);

		if ($needAnalyze === []) {
			// Nothing to enhance
			return $messages;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$data = $this->imapMapper->getBodyStructureData(
				$client,
				$mailbox->getName(),
				$needAnalyze,
				$account->getEMailAddress()
			);
		} catch (Horde_Imap_Client_Exception $e) {
			// Ignore for now, but log
			$this->logger->warning('Could not fetch structure detail data to enhance message previews: ' . $e->getMessage(), [
				'exception' => $e,
			]);

			return $messages;
		} finally {
			$client->logout();
		}

		return $this->mapper->updatePreviewDataBulk(...array_map(static function (Message $message) use ($data) {
			if (!array_key_exists($message->getUid(), $data)) {
				// Nothing to do
				return $message;
			}

			$structureData = $data[$message->getUid()];
			$message->setFlagAttachments($structureData->hasAttachments());
			$message->setPreviewText($structureData->getPreviewText());
			$message->setStructureAnalyzed(true);
			$message->setImipMessage($structureData->isImipMessage());
			$message->setEncrypted($structureData->isEncrypted());
			$message->setMentionsMe($structureData->getMentionsMe());

			return $message;
		}, $messages));
	}
}
