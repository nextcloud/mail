<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Http\JsonResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;

class OutboxController extends Controller {
	public function __construct(string $appName,
								IRequest $request) {
		parent::__construct($appName, $request);
	}

	private function stubbedMessage(int $id): array {
		return [
			'id' => $id,
			'type' => 0,
			'accountId' => $id,
			'aliasId' => null,
			'sendAt' => null,
			'subject' => 'I am a stub',
			'text' => 'bonjour',
			'html' => false,
			'inReplyToId' => null,
			'draftId' => null,
			'attachments' => [],
			'to' => [
				[
					'id' => 1001,
					'messageId' => $id,
					'type' => 1,
					'label' => 'Reci Pient One',
					'email' => 'rep1@domain.tld',
				],
				[
					'id' => 1002,
					'messageId' => $id,
					'type' => 1,
					'label' => 'Recipient Two',
					'email' => 'rep2@domain.tld',
				],
			],
			'cc' => [],
			'bcc' => [],
		];
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 */
	public function index(): JSONResponse {
		return JsonResponse::success([
			'messages' => [
				$this->stubbedMessage(101),
				$this->stubbedMessage(102),
			],
		]);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 */
	public function show(int $id): JSONResponse {
		if ($id === 101) {
			return JsonResponse::success($this->stubbedMessage(101));
		}

		return JsonResponse::fail(null, Http::STATUS_NOT_FOUND);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $subject
	 * @param string $body
	 * @param bool $isHtml
	 * @param array $to i. e. [['label' => 'Lewis', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array $cc
	 * @param array $bcc
	 * @param array $attachmentIds
	 * @param int|null $aliasId
	 * @param int|null $inReplyToId
	 * @param int|null $draftId
	 */
	public function create(
		int $accountId,
		string $subject,
		string $body,
		bool $isHtml,
		array $to = [],
		array $cc = [],
		array $bcc = [],
		array $attachmentIds = [],
		?int $aliasId = null,
		?int $inReplyToId = null,
		?int $draftId = null
	): JSONResponse {
		if ($subject === 'error') {
			return JsonResponse::error('the server errored');
		}

		if ($subject === 'invalid') {
			return JsonResponse::fail('invalid message', Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		return JsonResponse::success($this->stubbedMessage(103), Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 */
	public function update(int $id,
							int $accountId,
							string $subject,
							string $body,
							bool $isHtml,
							array $to = [],
							array $cc = [],
							array $bcc = [],
							array $attachmentIds = [],
							?int $aliasId = null,
							?int $inReplyToId = null,
							?int $draftId = null): JSONResponse {
		if ($id === 101) {
			return JsonResponse::success($this->stubbedMessage($id));
		}

		return JsonResponse::fail('message not found', Http::STATUS_NOT_FOUND);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 */
	public function send(int $id): JSONResponse {
		if ($id === 102) {
			return JsonResponse::error('could not send message');
		}

		return JsonResponse::success($this->stubbedMessage($id));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 */
	public function destroy(int $id): JSONResponse {
		if ($id === 101) {
			return JsonResponse::success($this->stubbedMessage($id));
		}

		return JsonResponse::fail('message not found', Http::STATUS_NOT_FOUND);
	}
}
