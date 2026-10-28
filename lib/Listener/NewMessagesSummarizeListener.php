<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use DateInterval;
use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use function array_filter;
use function array_values;
use function in_array;

/**
 * @template-implements IEventListener<Event>
 */
class NewMessagesSummarizeListener implements IEventListener {

	private const SKIPPED_SPECIAL_USES = [
		Horde_Imap_Client::SPECIALUSE_ALL,
		Horde_Imap_Client::SPECIALUSE_ARCHIVE,
		Horde_Imap_Client::SPECIALUSE_DRAFTS,
		Horde_Imap_Client::SPECIALUSE_FLAGGED,
		Horde_Imap_Client::SPECIALUSE_JUNK,
		Horde_Imap_Client::SPECIALUSE_SENT,
		Horde_Imap_Client::SPECIALUSE_TRASH,
	];

	public function __construct(
		private LoggerInterface $logger,
		private AiIntegrationsService $aiService,
		private IAppConfig $appConfig,
		private ITimeFactory $timeFactory,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($this->appConfig->getAppValueBool(ConfigLexicon::LLM_PROCESSING, false) === false) {
			return;
		}
		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}
		if ($this->isSkippedMailbox($event->getAccount(), $event->getMailbox())) {
			return;
		}

		$notBefore = $this->timeFactory->getDateTime()
			->sub(new DateInterval(AiIntegrationsService::RECENT_MESSAGE_MAX_AGE))
			->getTimestamp();
		$messages = array_values(array_filter(
			$event->getMessages(),
			static fn (Message $message) => $message->getSentAt() >= $notBefore,
		));
		if ($messages === []) {
			return;
		}

		try {
			$this->aiService->summarizeMessages(
				$event->getAccount(),
				$messages,
			);
		} catch (ServiceException $e) {
			$this->logger->error('Could not initiate a message summarize task(s): ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}

	private function isSkippedMailbox(Account $account, Mailbox $mailbox): bool {
		foreach (self::SKIPPED_SPECIAL_USES as $specialUse) {
			if ($mailbox->isSpecialUse($specialUse)) {
				return true;
			}
		}

		$mailAccount = $account->getMailAccount();
		return in_array($mailbox->getId(), [
			$mailAccount->getSentMailboxId(),
			$mailAccount->getTrashMailboxId(),
			$mailAccount->getJunkMailboxId(),
			$mailAccount->getArchiveMailboxId(),
			$mailAccount->getDraftsMailboxId(),
			$mailAccount->getSnoozeMailboxId(),
		], true);
	}
}
