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
			$imapMessages = $this->mailManager->getImapMessagesForScheduleProcessing($account, $mailbox, $messageUids);

			$this->submitMessages($imapMessages, $account->getUserId());
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
		// Get mailbox ID from message UID
		$messages = $this->messageMapper->findByUid((int)$id);
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
					$imapMessages = $this->mailManager->getImapMessagesForScheduleProcessing($account, $mailbox, $messageUids);

					$this->submitMessages($imapMessages, $userId);
				}
			}
		});
	}

	public function submitMessages(array $imapMessages, string $userId): void {
		$items = [];
		foreach ($imapMessages as $message) {
			$uid = $message->getUid();

			$items[] = new ContentItem(
				(string)$uid,
				$this->getId(),
				$message->getSubject(),
				$message->getFullMessage($uid),
				'E-Mail',
				$message->getSentDate(),
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
