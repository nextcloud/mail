<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Timo Witte <timo.witte@gmail.com>
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

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class PageController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IConfig */
	private $config;

	/** @var AccountService */
	private $accountService;

	/** @var AliasesService */
	private $aliasesService;

	/** @var string */
	private $currentUserId;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserPreferences */
	private $preferences;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param IConfig $config
	 * @param AccountService $accountService
	 * @param AliasesService $aliasesService
	 * @param string $UserId
	 * @param IUserPreferences
	 */
	public function __construct(string $appName, IRequest $request,
								IURLGenerator $urlGenerator, IConfig $config, AccountService $accountService,
								AliasesService $aliasesService, string $UserId, IUserSession $userSession,
								IUserPreferences $preferences) {
		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->accountService = $accountService;
		$this->aliasesService = $aliasesService;
		$this->currentUserId = $UserId;
		$this->userSession = $userSession;
		$this->preferences = $preferences;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse renders the index page
	 */
	public function index(): TemplateResponse {
		$mailAccounts = $this->accountService->findByUserId($this->currentUserId);

		$accountsJson = [];
		foreach ($mailAccounts as $mailAccount) {
			$json = $mailAccount->jsonSerialize();
			$json['aliases'] = $this->aliasesService->findAll($mailAccount->getId(),
				$this->currentUserId);
			$accountsJson[] = $json;
		}

		$user = $this->userSession->getUser();
		$response = new TemplateResponse($this->appName, 'index',
			[
				'debug' => $this->config->getSystemValue('debug', false),
				'app-version' => $this->config->getAppValue('mail', 'installed_version'),
				'accounts' => base64_encode(json_encode($accountsJson)),
				'external-avatars' => $this->preferences->getPreference('external-avatars', 'true'),
				'prefill_displayName' => $user->getDisplayName(),
				'prefill_email' => $this->config->getUserValue($user->getUID(), 'settings',
					'email', ''),
			]);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $uri
	 * @return RedirectResponse
	 */
	public function compose(string $uri): RedirectResponse {
		$parts = parse_url($uri);
		$params = ['to' => $parts['path']];
		if (isset($parts['query'])) {
			$parts = explode('&', $parts['query']);
			foreach ($parts as $part) {
				$pair = explode('=', $part, 2);
				$params[strtolower($pair[0])] = urldecode($pair[1]);
			}
		}

		array_walk($params,
			function (&$value, $key) {
				$value = "$key=" . urlencode($value);
			});

		$hashParams = '#mailto?' . implode('&', $params);

		$baseUrl = $this->urlGenerator->linkToRoute("mail.page.index");
		return new RedirectResponse($baseUrl . $hashParams);
	}

}
