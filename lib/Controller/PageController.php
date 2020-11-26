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

use Exception;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

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

	/** @var IMailManager */
	private $mailManager;

	/** @var IInitialStateService */
	private $initialStateService;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(string $appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IConfig $config,
								AccountService $accountService,
								AliasesService $aliasesService,
								?string $UserId,
								IUserSession $userSession,
								IUserPreferences $preferences,
								IMailManager $mailManager,
								IInitialStateService $initialStateService,
								LoggerInterface $logger) {
		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->accountService = $accountService;
		$this->aliasesService = $aliasesService;
		$this->currentUserId = $UserId;
		$this->userSession = $userSession;
		$this->preferences = $preferences;
		$this->mailManager = $mailManager;
		$this->initialStateService = $initialStateService;
		$this->logger = $logger;
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
			try {
				$mailboxes = $this->mailManager->getMailboxes($mailAccount);
				$json['mailboxes'] = $mailboxes;
			} catch (Exception $ex) {
				$this->logger->critical('Could not load account mailboxes: ' . $ex->getMessage(), [
					'exception' => $ex,
				]);
				$json['mailboxes'] = [];
				$json['error'] = true;
			}
			$accountsJson[] = $json;
		}

		$accountSettings = $this->preferences->getPreference('account-settings', json_encode([]));

		$user = $this->userSession->getUser();
		$response = new TemplateResponse($this->appName, 'index',
			[
				'debug' => $this->config->getSystemValue('debug', false),
				'app-version' => $this->config->getAppValue('mail', 'installed_version'),
				'accounts' => base64_encode(json_encode($accountsJson)),
				'external-avatars' => $this->preferences->getPreference('external-avatars', 'true'),
				'collect-data' => $this->preferences->getPreference('collect-data', 'true'),
				'account-settings' => base64_encode($accountSettings),
			]);
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'prefill_displayName',
			$user->getDisplayName()
		);
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'prefill_email',
			$this->config->getUserValue($user->getUID(), 'settings', 'email', '')
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function setup(): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function accountSettings(int $id): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function mailbox(int $id): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function thread(int $mailboxId, int $id): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $uri
	 *
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
		$name = '?' . implode('&', $params);
		$baseUrl = $this->urlGenerator->linkToRoute('mail.page.mailto');
		return new RedirectResponse($baseUrl . $name);
	}
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function mailto(): TemplateResponse {
		return $this->index();
	}
}
