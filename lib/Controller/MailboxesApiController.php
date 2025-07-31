<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\ResponseDefinitions;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type MailAccountListResponse from ResponseDefinitions
 */
class MailboxesApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ?string $userId,
		private IMailManager $mailManager,
		private readonly AccountService $accountService,
		private IMailSearch $mailSearch,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all mailboxes of an account of the user which is currently logged-in
	 *
	 * @param int $accountId the mail account id
	 * @return DataResponse<Http::STATUS_OK, array<string, mixed>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 200: Mailbox list
	 * 404: User was not logged in or account doesn't exist
	 */
	#[ApiRoute(verb: 'GET', url: 'ocs/mailboxes')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function list(int $accountId): DataResponse {
		$userId = $this->userId;
		if ($userId === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		try {
			$account = $this->accountService->find($userId, $accountId);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$mailboxes = $this->mailManager->getMailboxes($account);
		return new DataResponse($mailboxes, Http::STATUS_OK);
	}



	/**
	 * List the newest messages in a mailbox of the user which is currently logged-in
	 *
	 * @param int $mailboxId the mailbox id
	 * @param int $cursor the query cursor
	 * @param string $filter the query filter
	 * @param int|null $limit the number of messages to be returned, can be left ampty to get all messages
	 * @param string $view returns messages in requested view ('singleton' or 'threaded')
	 * @param string|null $v Cache buster version to guarantee unique urls (will trigger HTTP caching if set)
	 * @return DataResponse<Http::STATUS_OK, array<string, mixed>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{}, array{}>
	 *
	 * 200: Message list
	 * 403: User cannot access this mailbox
	 * 404: User was not logged in
	 */
	#[ApiRoute(verb: 'GET', url: 'ocs/mailboxes/{mailboxId}/messages')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function listMessages(int $mailboxId,
		?int $cursor = null,
		?string $filter = null,
		?int $limit = null,
		?string $view = null): DataResponse {
		$userId = $this->userId;
		if ($userId === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		try {
			$mailbox = $this->mailManager->getMailbox($userId, $mailboxId);
			$account = $this->accountService->find($userId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$sort = IMailSearch::ORDER_NEWEST_FIRST;

		$view = $view === 'singleton' ? IMailSearch::VIEW_SINGLETON : IMailSearch::VIEW_THREADED;

		$messages = $this->mailSearch->findMessages(
			$account,
			$mailbox,
			$sort,
			$filter === '' ? null : $filter,
			$cursor,
			$limit,
			$userId,
			$view
		);
		return new DataResponse($messages, Http::STATUS_OK);
	}
}
