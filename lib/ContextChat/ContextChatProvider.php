<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\ContextChat;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\BackgroundJob\IJobList;
use OCP\ContextChat\Events\ContentProviderRegisterEvent;
use OCP\ContextChat\IContentManager;
use OCP\ContextChat\IContentProvider;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

/**
 * @implements IEventListener<Event>
 */
class ContextChatProvider implements IContentProvider, IEventListener {

	public const CONTEXT_CHAT_MESSAGE_MAX_AGE = 60 * 60 * 24 * 365.25;
	public const CONTEXT_CHAT_IMPORT_MAX_ITEMS = 1000;
	public const CONTEXT_CHAT_JOB_INTERVAL = 60 * 5;

	public function __construct(
		private TaskService $taskService,
		private AccountService $accountService,
		private MailManager $mailManager,
		private MessageMapper $messageMapper,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private IContentManager $contentManager,
		private IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if (!$this->contentManager->isContextChatAvailable()) {
			return;
		}

		if ($event instanceof ContentProviderRegisterEvent) {
			$event->registerContentProvider($this->getAppId(), $this->getId(), self::class);
			return;
		}

		if ($event instanceof NewMessagesSynchronized) {
			$messageIds = array_map(static fn (Message $m): int => $m->getId(), $event->getMessages());

			// Ensure that there are messages to sync
			if (count($messageIds) === 0) {
				return;
			}

			$mailboxId = $event->getMailbox()->getId();

			$this->taskService->updateOrCreate($mailboxId, min($messageIds));
			return;
		}

		if ($event instanceof MessageDeletedEvent) {
			$this->contentManager->deleteContent($this->getAppId(), $this->getId(), [strval($event->getMessageId())]);
			return;
		}
	}

	/**
	 * The ID of the provider
	 *
	 * @return string
	 * @since 5.2.0
	 */
	public function getId(): string {
		return 'mail';
	}

	/**
	 * The ID of the app making the provider avaialble
	 *
	 * @return string
	 * @since 5.2.0
	 */
	public function getAppId(): string {
		return Application::APP_ID;
	}

	/**
	 * The absolute URL to the content item
	 *
	 * @param string $id
	 * @return string
	 * @since 5.2.0
	 */
	public function getItemUrl(string $id): string {
		[$mailboxId, $messageId] = explode(':', $id);
		return $this->urlGenerator->linkToRouteAbsolute('mail.page.thread', [ 'mailboxId' => $mailboxId, 'id' => $messageId ]);
	}

	/**
	 * Starts the initial import of content items into context chat
	 *
	 * @return void
	 * @since 5.2.0
	 */
	public function triggerInitialImport(): void {
		$this->userManager->callForSeenUsers(function (IUser $user): void {
			$userId = $user->getUID();
			$userAccounts = $this->accountService->findByUserId($userId);

			foreach ($userAccounts as $account) {
				$mailboxes = $this->mailManager->getMailboxes($account);

				foreach ($mailboxes as $mailbox) {
					$this->taskService->updateOrCreate($mailbox->getId(), 0);
				}
			}
		});
	}
}
