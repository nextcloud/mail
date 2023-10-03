<?php

declare(strict_types=1);

/*
 * @copyright 2023 Micke Nordin <kano@sunet.se>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Micke Nordin <kano@sunet.se>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Integration\MasterPassword;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;




class MasterPasswordController extends Controller {
	private MasterPassword $masterPassword;

	public function __construct(IRequest $request,
	MasterPassword $masterPassword
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->masterPassword = $masterPassword;
	}

	/**
	 * @param string $masterPassword
	 *
	 * @return JsonResponse
	 */
	public function configure(string $masterPassword): JsonResponse {
		if (empty($masterPassword)) {
			return JsonResponse::fail(null, Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$this->masterPassword->configure(
			$masterPassword,
		);

		return JsonResponse::success([]);
	}

	/*
	 * @return JsonResponse
	 */
	public function remove(): JsonResponse {
		$this->masterPassword->remove();

		return JsonResponse::success([]);
	}

}
