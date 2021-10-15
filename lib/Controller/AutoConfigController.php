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

use OCA\Mail\Service\AutoConfig\IspDb;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class AutoConfigController extends Controller {

	/** @var IspDb */
	private $ispDbService;

	public function __construct(string   $appName,
								IRequest $request,
								IspDb    $ispDb
	) {
		parent::__construct($appName, $request);
		$this->ispDbService = $ispDb;
	}

	public function ispDb(string $email): array {
		$address = new \Horde_Mail_Rfc822_Address($email);

		return $this->ispDbService->query(
			$address->host,
			$address->writeAddress()
		);
	}
}
