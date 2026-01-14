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
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\AvatarService;
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

	/** @var AvatarService */
	private $avatarService;

	public function __construct(
		IMAPClientFactory $clientFactory,
		ImapMapper $imapMapper,
		DbMapper $dbMapper,
		LoggerInterface $logger,
		AvatarService $avatarService,
		private AttachmentService $attachmentService,
	) {
		$this->clientFactory = $clientFactory;
		$this->imapMapper = $imapMapper;
		$this->mapper = $dbMapper;
		$this->logger = $logger;
		$this->avatarService = $avatarService;
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Message[]
	 */
	public function process(Account $account, Mailbox $mailbox, array $messages, bool $preLoadAvatars = false, ?string $userId = null): array {
		$needAnalyze = array_reduce($messages, static function (array $carry, Message $message) {
			if ($message->getStructureAnalyzed()) {
				// Nothing to do
				return $carry;
			}

			return array_merge($carry, [$message->getUid()]);
		}, []);
		$client = $this->clientFactory->getClient($account);

		foreach ($messages as $message) {
			$attachments = $this->attachmentService->getAttachmentNames($account, $mailbox, $message, $client);
			$message->setAttachments($attachments);
		}

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
