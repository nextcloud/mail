<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\ContextChat;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\BackgroundJob\ContextChat\SubmitContentJob;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Service\AccountService;
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

	public function __construct(
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
			$this->jobList->add(SubmitContentJob::class, [
				'userId' => $event->getAccount()->getUserId(),
				'account' => $event->getAccount(),
				'mailbox' => $event->getMailbox(),
				'messages' => $event->getMessages(),
				'startTime' => time() - Application::CONTEXT_CHAT_MESSAGE_MAX_AGE,
			]);

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
		// Get mailbox ID from message ID
		$messages = $this->messageMapper->findByIds('', [(int)$id], '');
		if (!$messages) {
			return '';
		}
		$mailboxId = $messages[0]->getMailboxId();

		return $this->urlGenerator->linkToRouteAbsolute('mail.page.thread', [ 'mailboxId' => $mailboxId, 'id' => $id ]);
	}

	/**
	 * Starts the initial import of content items into context chat
	 *
	 * @return void
	 * @since 5.2.0
	 */
	public function triggerInitialImport(): void {
		$startTime = time() - Application::CONTEXT_CHAT_MESSAGE_MAX_AGE;

		$this->userManager->callForSeenUsers(function (IUser $user) use ($startTime): void {
			$userId = $user->getUID();
			$userAccounts = $this->accountService->findByUserId($userId);

			foreach ($userAccounts as $account) {
				$mailboxes = $this->mailManager->getMailboxes($account);

				foreach ($mailboxes as $mailbox) {
					$messageUids = $this->messageMapper->findAllUids($mailbox);
					$messages = $this->messageMapper->findByUids($mailbox, $messageUids);

					$this->jobList->add(SubmitContentJob::class, [
						'userId' => $userId,
						'account' => $account,
						'mailbox' => $mailbox,
						'messages' => $messages,
						'startTime' => $startTime,
					]);
				}
			}
		});
	}
}
