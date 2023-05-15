<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\Classification;

use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IUserPreferences;
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
		private IUserPreferences $preferences) {
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
		$allowTagging = $this->preferences->getPreference($account->getUserId(), 'tag-classified-messages');
		if ($allowTagging === 'false') {
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
				$this->logger->info("Message {$message->getUid()} ({$message->getPreviewText()}) is " . ($predictions[$message->getUid()] ? 'important' : 'not important'));
				if ($predictions[$message->getUid()] ?? false) {
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
