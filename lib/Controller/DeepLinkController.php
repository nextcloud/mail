<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use Horde_Mail_Rfc822_Identification;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class DeepLinkController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private MailAccountMapper $mailAccountMapper,
		private AccountService $accountService,
		private MessageMapper $messageMapper,
		private IURLGenerator $urlGenerator,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $messageId
	 * @return RedirectResponse
	 */
	public function open(string $messageId): RedirectResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.page.login'));
		}

		$userId = $user->getUID();

		try {
			$id = '<' . trim(trim($messageId), '<>') . '>';
			$parsed = new Horde_Mail_Rfc822_Identification($id);
			$cleanedId = $parsed->ids[0] ?? null;

			if ($cleanedId === null) {
				return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('mail.page.index', []));
			}

			$lightAccounts = $this->mailAccountMapper->findByUserId($userId);

			foreach ($lightAccounts as $lightAccount) {
				$accountId = $lightAccount->getId();
				$account = $this->accountService->find($userId, $accountId);
				$messages = $this->messageMapper->findByMessageId($account, $cleanedId);

				if (!empty($messages)) {
					$message = $messages[0];
					$targetId = $message->getId();

					// IMPORTANT FIX: Use 'mail.page.thread' instead of 'mail.page#thread'
					$url = $this->urlGenerator->linkToRouteAbsolute(
						'mail.page.thread',
						['mailboxId' => $message->getMailboxId(), 'id' => $targetId]
					);

					return new RedirectResponse($url);
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('DeepLinkController: An unexpected error occurred.', [
				'exception' => $e,
				'messageId' => $messageId,
			]);
		}

		// Fallback
		return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('mail.page.index', []));
	}
}
