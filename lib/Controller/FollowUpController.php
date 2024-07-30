<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class FollowUpController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private ?string $userId,
		private ThreadMapper $threadMapper,
		private MessageMapper $messageMapper,
		private MailboxMapper $mailboxMapper,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param int[] $messageIds
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function checkMessageIds(array $messageIds): JsonResponse {
		$userId = $this->userId;
		if ($userId === null) {
			return JsonResponse::fail([], Http::STATUS_FORBIDDEN);
		}

		$mailboxes = [];

		$wasFollowedUp = [];
		$messages = $this->messageMapper->findByIds($userId, $messageIds, 'ASC');
		foreach ($messages as $message) {
			$mailboxId = $message->getMailboxId();
			if (!isset($mailboxes[$mailboxId])) {
				try {
					$mailboxes[$mailboxId] = $this->mailboxMapper->findByUid($mailboxId, $userId);
				} catch (DoesNotExistException $e) {
					continue;
				}
			}

			$newerMessageIds = $this->threadMapper->findNewerMessageIdsInThread(
				$mailboxes[$mailboxId]->getAccountId(),
				$message,
			);
			if (!empty($newerMessageIds)) {
				$wasFollowedUp[] = $message->getId();
			}
		}

		return JsonResponse::success([
			'wasFollowedUp' => $wasFollowedUp,
		]);
	}

}
