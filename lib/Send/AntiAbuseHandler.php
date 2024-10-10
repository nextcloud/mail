<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Service\AntiAbuseService;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class AntiAbuseHandler extends AHandler {

	public function __construct(
		private IUserManager $userManager,
		private AntiAbuseService $service,
		private LoggerInterface $logger,
	) {
	}
	public function process(Account $account, LocalMessage $localMessage): LocalMessage {
		if ($localMessage->getStatus() === LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL
			|| $localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return $this->processNext($account, $localMessage);
		}

		$user = $this->userManager->get($account->getUserId());
		if ($user === null) {
			$this->logger->error('User {user} for mail account {id} does not exist', [
				'user' => $account->getUserId(),
				'id' => $account->getId(),
			]);
			// What to do here?
			return $localMessage;
		}

		$this->service->onBeforeMessageSent(
			$user,
			$localMessage,
		);
		// We don't react to a ratelimited message / a message that has too many recipients
		// at this point.
		// Any future improvement from https://github.com/nextcloud/mail/issues/6461
		// should refactor the chain to stop at this point unless the force send option is true
		return $this->processNext($account, $localMessage);
	}
}
