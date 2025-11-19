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
use OCA\Mail\Service\Avatar\Avatar;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reduce;

class PreviewEnhancer {
	public function __construct(
		private readonly \OCA\Mail\IMAP\IMAPClientFactory $clientFactory,
		private readonly \OCA\Mail\IMAP\MessageMapper $imapMapper,
		private readonly \OCA\Mail\Db\MessageMapper $mapper,
		private readonly \Psr\Log\LoggerInterface $logger,
		private readonly \OCA\Mail\Service\AvatarService $avatarService
	) {
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Message[]
	 */
	public function process(Account $account, Mailbox $mailbox, array $messages, bool $preLoadAvatars = false, ?string $userId = null): array {
		$needAnalyze = array_reduce($messages, static function (array $carry, Message $message): array {
			if ($message->getStructureAnalyzed()) {
				// Nothing to do
				return $carry;
			}

			return array_merge($carry, [$message->getUid()]);
		}, []);

		if ($preLoadAvatars) {
			foreach ($messages as $message) {
				$from = $message->getFrom()->first();
				if ($message->getAvatar() === null && $from !== null && $from->getEmail() !== null && $userId !== null) {
					$avatar = $this->avatarService->getCachedAvatar($from->getEmail(), $userId);
					if ($avatar === null) {
						$message->setFetchAvatarFromClient(true);
					}
					if ($avatar instanceof Avatar) {
						$message->setAvatar($avatar);
					}

				}
			}
		}

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

		return $this->mapper->updatePreviewDataBulk(...array_map(static function (Message $message) use ($data): \OCA\Mail\Db\Message {
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
