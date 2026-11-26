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
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\AvatarService;
use Psr\Log\LoggerInterface;
use function array_chunk;
use function array_filter;
use function array_key_exists;
use function array_map;

class PreviewEnhancer {
	public const CHUNK_SIZE = 500;

	public function __construct(
		private readonly ProtocolFactory $protocolFactory,
		private ImapMapper $imapMapper,
		private DbMapper $mapper,
		private LoggerInterface $logger,
		private AvatarService $avatarService,
		private AttachmentService $attachmentService,
	) {
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Message[]
	 */
	public function process(Account $account, Mailbox $mailbox, array $messages, bool $preLoadAvatars = false, ?string $userId = null): array {
		$needAnalyze = array_filter($messages, static fn (Message $message): bool => !$message->getStructureAnalyzed());
		if ($needAnalyze !== []) {
			$this->analyzeStructure($account, $mailbox, $needAnalyze);
		}

		// After the analysis, messages without attachments skip loading the full message
		foreach ($messages as $message) {
			$attachments = $this->attachmentService->getAttachmentNames($account, $mailbox, $message);
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

		return $messages;
	}

	/**
	 * @param Message[] $messages
	 */
	private function analyzeStructure(Account $account, Mailbox $mailbox, array $messages): void {
		if ($account->getMailAccount()->isJmap()) {
			foreach ($messages as $message) {
				$message->setStructureAnalyzed(true);
			}

			$this->mapper->updatePreviewDataBulk(...$messages);
			return;
		}

		$client = $this->protocolFactory->imapClient($account);
		foreach (array_chunk($messages, self::CHUNK_SIZE) as $chunk) {
			try {
				$data = $this->imapMapper->getBodyStructureData(
					$client,
					$mailbox->getName(),
					array_map(static fn (Message $message): int => $message->getUid(), $chunk),
					$account->getEMailAddress()
				);
			} catch (Horde_Imap_Client_Exception $e) {
				// Ignore for now, but log
				$this->logger->info('Could not fetch structure detail data to enhance message previews: ' . $e->getMessage(), [
					'exception' => $e,
				]);

				return;
			}

			foreach ($chunk as $message) {
				if (!array_key_exists($message->getUid(), $data)) {
					continue;
				}

				$structureData = $data[$message->getUid()];
				$message->setFlagAttachments($structureData->hasAttachments());
				$message->setPreviewText($structureData->getPreviewText());
				$message->setStructureAnalyzed(true);
				$message->setImipMessage($structureData->isImipMessage());
				$message->setEncrypted($structureData->isEncrypted());
				$message->setMentionsMe($structureData->getMentionsMe());
			}

			$this->mapper->updatePreviewDataBulk(...$chunk);
		}
	}
}
