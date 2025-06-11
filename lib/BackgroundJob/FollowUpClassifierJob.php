<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class FollowUpClassifierJob extends QueuedJob {

	public const PARAM_MESSAGE_ID = 'messageId';
	public const PARAM_MAILBOX_ID = 'mailboxId';
	public const PARAM_USER_ID = 'userId';

	public function __construct(
		ITimeFactory $time,
		private LoggerInterface $logger,
		private AccountService $accountService,
		private IMailManager $mailManager,
		private AiIntegrationsService $aiService,
		private ThreadMapper $threadMapper,
	) {
		parent::__construct($time);
	}

	#[\Override]
	public function run($argument): void {
		$messageId = $argument[self::PARAM_MESSAGE_ID];
		$mailboxId = $argument[self::PARAM_MAILBOX_ID];
		$userId = $argument[self::PARAM_USER_ID];

		if (!$this->aiService->isLlmProcessingEnabled()) {
			return;
		}

		try {
			$mailbox = $this->mailManager->getMailbox($userId, $mailboxId);
			$account = $this->accountService->find($userId, $mailbox->getAccountId());
		} catch (ClientException $e) {
			return;
		}

		$messages = $this->mailManager->getByMessageId($account, $messageId);
		$messages = array_filter(
			$messages,
			static fn (Message $message) => $message->getMailboxId() === $mailboxId,
		);
		if (count($messages) === 0) {
			return;
		}

		if (count($messages) > 1) {
			$this->logger->warning('Trying to analyze multiple messages with the same message id for follow-ups');
		}
		$message = $messages[0];

		try {
			$newerMessages = $this->threadMapper->findNewerMessageIdsInThread(
				$mailbox->getAccountId(),
				$message,
			);
		} catch (Exception $e) {
			$this->logger->error(
				'Failed to check if a message needs a follow-up: ' . $e->getMessage(),
				[ 'exception' => $e ],
			);
			return;
		}
		if (count($newerMessages) > 0) {
			return;
		}

		$requiresFollowup = $this->aiService->requiresFollowUp(
			$account,
			$mailbox,
			$message,
			$userId,
		);
		if (!$requiresFollowup) {
			return;
		}

		$this->logger->debug('Message requires follow-up: ' . $message->getId());
		$tag = $this->mailManager->createTag('Follow up', '#d77000', $userId);
		$this->mailManager->tagMessage(
			$account,
			$mailbox->getName(),
			$message,
			$tag,
			true,
		);
	}
}
