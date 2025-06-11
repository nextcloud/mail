<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification;

use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use Psr\Log\LoggerInterface;

class NewMessagesClassifier {
	private const EXEMPT_FROM_CLASSIFICATION = [
		Horde_Imap_Client::SPECIALUSE_ARCHIVE,
		Horde_Imap_Client::SPECIALUSE_DRAFTS,
		Horde_Imap_Client::SPECIALUSE_JUNK,
		Horde_Imap_Client::SPECIALUSE_SENT,
		Horde_Imap_Client::SPECIALUSE_TRASH,
	];

	public function __construct(
		private ImportanceClassifier $classifier,
		private TagMapper $tagMapper,
		private LoggerInterface $logger,
		private IMailManager $mailManager,
		private ClassificationSettingsService $classificationSettingsService,
	) {
	}

	/**
	 * Classify a batch on freshly synced messages.
	 * Objects in the incoming $messages array are mutated in place.
	 *
	 * The importance tag will be propagated to IMAP and its mapping will be persisted to the db.
	 * However, changes to db message objects themselves won't be persisted.
	 * This is up to the caller (e.g. MessageMapper->insertBulk()).
	 *
	 * @param Message[] $messages
	 * @param Mailbox $mailbox
	 * @param Account $account
	 * @param Tag $importantTag
	 * @return void
	 */
	public function classifyNewMessages(
		array $messages,
		Mailbox $mailbox,
		Account $account,
		Tag $importantTag,
	): void {
		if (!$this->classificationSettingsService->isClassificationEnabled($account->getUserId())) {
			return;
		}

		foreach (self::EXEMPT_FROM_CLASSIFICATION as $specialUse) {
			if ($mailbox->isSpecialUse($specialUse)) {
				// Nothing to do then
				return;
			}
		}

		// if this is a message that's been flagged / tagged as important before, we don't want to reclassify it again.
		$doNotReclassify = $this->tagMapper->getTaggedMessageIdsForMessages(
			$messages,
			$account->getUserId(),
			Tag::LABEL_IMPORTANT
		);
		$messages = array_filter($messages, static function ($message) use ($doNotReclassify) {
			return ($message->getFlagImportant() === false || in_array($message->getMessageId(), $doNotReclassify, true));
		});

		try {
			$predictions = $this->classifier->classifyImportance(
				$account,
				$messages,
				$this->logger
			);

			foreach ($messages as $message) {
				$prediction = $predictions[$message->getUid()] ?? false;
				$this->logger->info("Message {$message->getUid()} ({$message->getPreviewText()}) is " . ($prediction ? 'important' : 'not important'));
				if ($prediction) {
					$message->setFlagImportant(true);
					$this->mailManager->flagMessage($account, $mailbox->getName(), $message->getUid(), Tag::LABEL_IMPORTANT, true);
					$this->mailManager->tagMessage($account, $mailbox->getName(), $message, $importantTag, true);
				}
			}
		} catch (ServiceException $e) {
			$this->logger->error('Could not classify incoming message importance: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		} catch (ClientException $e) {
			$this->logger->error('Could not persist incoming message importance to IMAP: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
