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
	public function process(Account $account, Mailbox $mailbox, array $messages, bool $preLoadAvatars = false): array {
		$needAnalyze = [];

		$client = $this->clientFactory->getClient($account);

		foreach ($messages as $message) {
			if ($message->getStructureAnalyzed() === false || $message->getStructureAnalyzed() === null) {
				$needAnalyze[] = $message->getUid();
			}

			$attachments = $this->attachmentService->getAttachmentNames($account, $mailbox, $message, $client);
			$message->setAttachments($attachments);

			if ($preLoadAvatars) {
				$this->preLoadAvatar($message, $account->getUserId());
			}
		}

		if ($needAnalyze === []) {
			// Nothing to enhance
			$client->logout();
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

	private function preLoadAvatar(Message $message, string $userId): void {
		if ($message->getAvatar() !== null) {
			return;
		}

		// Only allow client-side fetching once we confirm a cache miss.
		$message->setFetchAvatarFromClient(false);

		$from = $message->getFrom()->first();
		if ($from === null) {
			return;
		}

		try {
			$email = $from->getEmail();
		} catch (\Exception) {
			return;
		}

		$avatar = $this->avatarService->getCachedAvatar($email, $userId);
		if ($avatar instanceof Avatar) {
			$message->setAvatar($avatar);
			return;
		}

		$message->setFetchAvatarFromClient(true);
	}
}
