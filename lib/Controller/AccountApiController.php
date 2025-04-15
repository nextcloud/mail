<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Account;
use OCA\Mail\Db\Alias;
use OCA\Mail\ResponseDefinitions;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
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
class AccountApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ?string $userId,
		private readonly AccountService $accountService,
		private readonly AliasesService $aliasesService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all email accounts and their aliases of the user which is currently logged-in
	 *
	 * @return DataResponse<Http::STATUS_OK, list<MailAccountListResponse>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 200: Account list
	 * 404: User was not logged in
	 */
	#[ApiRoute(verb: 'GET', url: '/account/list')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function list(): DataResponse {
		$userId = $this->userId;
		if ($userId === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$accounts = $this->accountService->findByUserId($userId);
		return new DataResponse(array_map(function (Account $account) use ($userId) {
			$aliases = $this->aliasesService->findAll($account->getId(), $userId);
			return [
				'id' => $account->getId(),
				'email' => $account->getEmail(),
				'aliases' => array_map(static fn (Alias $alias) => [
					'id' => $alias->getId(),
					'email' => $alias->getAlias(),
					'name' => $alias->getName(),
				], $aliases),
			];
		}, $accounts));
	}
}
