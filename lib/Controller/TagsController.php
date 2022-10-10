<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class TagsController extends Controller {
	private string $currentUserId;
	private IMailManager $mailManager;

	public function __construct(IRequest $request,
								string $UserId,
								IMailManager $mailManager
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->currentUserId = $UserId;
		$this->mailManager = $mailManager;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $displayName
	 * @param string $color
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function create(string $displayName, string $color): JSONResponse {
		$this->validateDisplayName($displayName);
		$this->validateColor($color);

		$tag = $this->mailManager->createTag($displayName, $color, $this->currentUserId);
		return new JSONResponse($tag);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string $displayName
	 * @param string $color
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function update(int $id, string $displayName, string $color): JSONResponse {
		$this->validateDisplayName($displayName);
		$this->validateColor($color);

		$tag = $this->mailManager->updateTag($id, $displayName, $color, $this->currentUserId);
		return new JSONResponse($tag);
	}

	/**
	 * @throws ClientException
	 */
	private function validateDisplayName(string $displayName): void {
		if (mb_strlen($displayName) > 128) {
			throw new ClientException('The maximum length for displayName is 128');
		}
	}

	/**
	 * @throws ClientException
	 */
	private function validateColor(string $color): void {
		if (mb_strlen($color) > 9) {
			throw new ClientException('The maximum length for color is 9');
		}
	}
}
