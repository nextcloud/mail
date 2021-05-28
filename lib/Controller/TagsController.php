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
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class TagsController extends Controller {

	/** @var string */
	private $currentUserId;

	/** @var TagMapper */
	private $tagMapper;

	/**
	 * TagsController constructor.
	 *
	 * @param IRequest $request
	 * @param $UserId
	 * @param TagMapper $tagMapper
	 */
	public function __construct(IRequest $request,
								$UserId,
								TagMapper $tagMapper
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->currentUserId = $UserId;
		$this->tagMapper = $tagMapper;
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
		$imapLabel = str_replace(' ', '_', $displayName);
		$imapLabel = mb_convert_encoding($imapLabel, 'UTF7-IMAP', 'UTF-8');
		if ($imapLabel === false) {
			throw new ClientException('Error converting display name to UTF7-IMAP ', 0);
		}

		try {
			return new JSONResponse($this->tagMapper->getTagByImapLabel($imapLabel, $this->currentUserId));
		} catch (DoesNotExistException $e) {
			// it's valid that a tag does not exist.
		}

		$tag = new Tag();
		$tag->setUserId($this->currentUserId);
		$tag->setDisplayName($displayName);
		$tag->setImapLabel($imapLabel);
		$tag->setColor($color);
		$tag->setIsDefaultTag(false);

		try {
			$tag = $this->tagMapper->insert($tag);
		} catch (\OCP\DB\Exception $e) {
			throw new ClientException($e->getMessage(), 0);
		}

		return new JSONResponse($tag);
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
	 * @throws DoesNotExistException
	 */
	public function update(int $id, string $displayName, string $color): JSONResponse {
		$tag = $this->tagMapper->getTagForUser($id, $this->currentUserId);

		$tag->setDisplayName($displayName);
		$tag->setColor($color);

		try {
			$tag = $this->tagMapper->update($tag);
		} catch (\OCP\DB\Exception $e) {
			throw new ClientException($e->getMessage(), 0);
		}

		return new JSONResponse($tag);
	}
}
