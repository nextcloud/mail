<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Listener;

use Horde_Imap_Client;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|NewMessagesSynchronized>
 */
class NewMessageClassificationListener implements IEventListener {
	private const EXEMPT_FROM_CLASSIFICATION = [
		Horde_Imap_Client::SPECIALUSE_ARCHIVE,
		Horde_Imap_Client::SPECIALUSE_DRAFTS,
		Horde_Imap_Client::SPECIALUSE_JUNK,
		Horde_Imap_Client::SPECIALUSE_SENT,
		Horde_Imap_Client::SPECIALUSE_TRASH,
	];

	/** @var ImportanceClassifier */
	private $classifier;

	/** @var TagMapper */
	private $tagMapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var IMailManager */
	private $mailManager;

	/** @var IUserPreferences */
	private $preferences;

	public function __construct(ImportanceClassifier $classifier,
								TagMapper $tagMapper,
								LoggerInterface $logger,
								IMailManager $mailManager,
								IUserPreferences $preferences) {
		$this->classifier = $classifier;
		$this->logger = $logger;
		$this->tagMapper = $tagMapper;
		$this->mailManager = $mailManager;
		$this->preferences = $preferences;
	}

	public function handle(Event $event): void {
		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}

		$allowTagging = $this->preferences->getPreference($event->getAccount()->getUserId(), 'tag-classified-messages');
		if ($allowTagging === 'false') {
			return;
		}

		foreach (self::EXEMPT_FROM_CLASSIFICATION as $specialUse) {
			if ($event->getMailbox()->isSpecialUse($specialUse)) {
				// Nothing to do then
				return;
			}
		}

		$messages = $event->getMessages();

		// if this is a message that's been flagged / tagged as important before, we don't want to reclassify it again.
		$doNotReclassify = $this->tagMapper->getTaggedMessageIdsForMessages(
			$event->getMessages(),
			$event->getAccount()->getUserId(),
			Tag::LABEL_IMPORTANT
		);
		$messages = array_filter($messages, static function ($message) use ($doNotReclassify) {
			return ($message->getFlagImportant() === false || in_array($message->getMessageId(), $doNotReclassify, true));
		});

		try {
			$important = $this->tagMapper->getTagByImapLabel(Tag::LABEL_IMPORTANT, $event->getAccount()->getUserId());
		} catch (DoesNotExistException $e) {
			// just in case - if we get here, the tag is missing
			$this->logger->error('Could not find important tag for ' . $event->getAccount()->getUserId(). ' ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return;
		}

		try {
			$predictions = $this->classifier->classifyImportance(
				$event->getAccount(),
				$messages
			);

			foreach ($event->getMessages() as $message) {
				if ($predictions[$message->getUid()] ?? false) {
					$this->mailManager->flagMessage($event->getAccount(), $event->getMailbox()->getName(), $message->getUid(), Tag::LABEL_IMPORTANT, true);
					$this->mailManager->tagMessage($event->getAccount(), $event->getMailbox()->getName(), $message, $important, true);
				}
			}
		} catch (ServiceException $e) {
			$this->logger->error('Could not classify incoming message importance: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
