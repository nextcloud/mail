<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use Exception;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\Ime\ImeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ImeController extends Controller {

	public function __construct(
		IRequest $request,
		private ImeService $ImeService
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @UserRateThrottle(limit=60, period=60)
	 */
	public function index(array $events): JsonResponse {
		
		if (!$this->ImeService->getEnabled() || !$this->authorize()) {
			return JsonResponse::fail();
		}

		$this->ImeService->handle($events);

		return JsonResponse::success();
		
	}

	/**
	 * authorize ime request
	 * 
	 * @return bool
	 */
	public function authorize(): bool {

		$restriction = $this->ImeService->getRestrictions();
		$source = $this->request->__get('server')['REMOTE_ADDR'];

		// evaluate, if id address restriction is set
		if (!empty($restriction)) {
			$addresses = explode(' ', $restriction);
			foreach ($addresses as $entry) {
				// evaluate, if ip address matches
				if ($this->ipInCidr($source, $entry)) {
					return true;
				}
			}
		}

		return false;

	}

	protected function ipInCidr(string $ip, string $cidr): bool {

        if (str_contains($cidr, '/')) {
            // split cidr and convert to parameters
            list($cidr_net, $cidr_mask) = explode('/', $cidr);
            // convert ip address and cidr network to binary
            $ip = inet_pton($ip);
            $cidr_net = inet_pton($cidr_net);
            // evaluate, if ip is valid
            if ($ip === false) {
                throw new InvalidArgumentException('Invalid IP Address');
            }
            // evaluate, if cidr network is valid
            if ($cidr_net === false) {
                throw new InvalidArgumentException('Invalid CIDR Network');
            }
            // evaluate, if ip and network are the same version
            if (strlen($ip) != strlen($cidr_net)) {
                throw new InvalidArgumentException('IP Address and CIDR Network version do not match');
            }
            
            // determain the amount of full bit bytes and add them
            $mask = str_repeat(chr(255), (int) floor($cidr_mask / 8));
            // determain, if any bits are remaing
            if ((strlen($mask) * 8) < $cidr_mask) {
                $mask .= chr(1 << (8 - ($cidr_mask - (strlen($mask) * 8))));
            }
            // determain, the amount of empty bit bytes and add them
            $mask = str_pad($mask, strlen($cidr_net), chr(0));

            // Compare the mask
            return ($ip & $mask) === ($cidr_net & $mask);
            
        }
        else {
            // return comparison
            return inet_pton($ip) === inet_pton($cidr);
        }
        
    }

}
