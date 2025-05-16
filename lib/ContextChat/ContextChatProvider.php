<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\ContextChat;

use OCA\ContextChat\Event\ContentProviderRegisterEvent;
use OCA\ContextChat\Public\ContentItem;
use OCA\ContextChat\Public\ContentManager;
use OCA\ContextChat\Public\IContentProvider;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\MailManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

/**
 * @implements IEventListener<Event>
 */
class ContextChatProvider implements IContentProvider, IEventListener {

	public function __construct(
		private AccountService $accountService,
		private MailManager $mailManager,
		private MessageMapper $messageMapper,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private ContentManager $contentManager,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof ContentProviderRegisterEvent) {
			$event->registerContentProvider($this->getAppId(), $this->getId(), self::class);
			return;
		}

		if ($event instanceof NewMessagesSynchronized) {
			$account = $event->getAccount();
			$mailbox = $event->getMailbox();
			$messages = $event->getMessages();
			$messageUids = array_map(static fn (Message $m): int => $m->getUid(), $messages);
			$messageIds = array_map(static fn (Message $m): int => $m->getId(), $messages);
			$imapMessages = $this->mailManager->getImapMessagesForScheduleProcessing($account, $mailbox, $messageUids);

			// Assume that each message has a 1-to-1 equivalent IMAP message, otherwise skip this mailbox for now
			// TODO: handle situations when this is not the case
			if (count($imapMessages) === count($messageIds)) {
				$this->submitMessages($imapMessages, $messageIds, $account->getUserId());
			}
			return;
		}

		if ($event instanceof MessageDeletedEvent) {
			$this->contentManager->deleteContent($this->getAppId(), $this->getId(), [$event->getMessageId()]);
			return;
		}
	}

	/**
	 * The ID of the provider
	 *
	 * @return string
	 * @since 4.4.0
	 */
	public function getId(): string {
		return 'mail';
	}

	/**
	 * The ID of the app making the provider avaialble
	 *
	 * @return string
	 * @since 4.4.0
	 */
	public function getAppId(): string {
		return Application::APP_ID;
	}

	/**
	 * The absolute URL to the content item
	 *
	 * @param string $id
	 * @return string
	 * @since 4.4.0
	 */
	public function getItemUrl(string $id): string {
		// Get mailbox ID from message ID
		$messages = $this->messageMapper->findByIds('', [(int)$id], '');
		if (!$messages) {
			return '';
		}
		$mailboxId = $messages[0]->getMailboxId();

		return $this->urlGenerator->linkToRouteAbsolute('mail.page.thread', [ 'mailboxId' => $mailboxId, 'id' => $id ]);
	}

	/**
	 * Starts the initial import of content items into content chat
	 *
	 * @return void
	 * @since 4.4.0
	 */
	public function triggerInitialImport(): void {
		$this->userManager->callForAllUsers(function (IUser $user): void {
			$userId = $user->getUID();
			$userAccounts = $this->accountService->findByUserId($userId);

			foreach ($userAccounts as $account) {
				$mailboxes = $this->mailManager->getMailboxes($account);

				foreach ($mailboxes as $mailbox) {
					$messageUids = $this->messageMapper->findAllUids($mailbox);
					$messageIds = $this->messageMapper->findAllIds($mailbox);
					$messages = $this->messageMapper->findByUids($mailbox, $messageUids);
					$imapMessages = $this->mailManager->getImapMessagesForScheduleProcessing($account, $mailbox, $messageUids);

					// Assume that each message has a 1-to-1 equivalent IMAP message, otherwise skip this mailbox for now
					// TODO: handle situations when this is not the case
					if (count($imapMessages) === count($messageIds)) {
						$this->submitMessages($imapMessages, $messageIds, $userId);
					}
				}
			}
		});
	}

	public function submitMessages(array $imapMessages, array $messageIds, string $userId): void {
		$items = [];

		for ($i = 0; $i < count($imapMessages); $i++) {
			$uid = $imapMessage[$i]->getUid();
			$messageContents = $imapMessage[$i]->getFullMessage($uid);

			$items[] = new ContentItem(
				(string)$messageIds[$i],
				$this->getId(),
				$imapMessage[$i]->getSubject(),
				$messageContents['body'],
				'E-Mail',
				$imapMessage[$i]->getSentDate(),
				[$userId],
			);

			// Submit 100 items at a time
			if (count($items) < 100) {
				continue;
			}
			$this->contentManager->submitContent($this->getAppId(), $items);
			$items = [];
		}

		// Submit remaining items
		if ($items) {
			$this->contentManager->submitContent($this->getAppId(), $items);
		}
	}
}
