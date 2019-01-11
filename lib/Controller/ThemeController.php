<?php

declare(strict_types=1);

/**
 * @author Pierre Gordon <pierregordon@protonmail.com>
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

use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCA\Mail\Http\JSONResponse;
use OCP\AppFramework\Controller;

class ThemeController extends Controller
{
	/** @var IConfig */
	private $config;

	/** @var IUserSession */
	private $userSession;

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IUserSession $userSession)
	{
		parent::__construct($appName, $request);

		$this->config = $config;
		$this->userSession = $userSession;
	}

	public function value()
	{
		$uid = $this->userSession->getUser()->getUID();
		$theme = $this->config->getUserValue($uid, 'accessibility', 'theme', false);

		return new JSONResponse(compact('theme'));
	}

}
