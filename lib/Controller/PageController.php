<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Timo Witte <timo.witte@gmail.com>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;
use function json_decode;

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

	/** @var TagMapper */
	private $tagMapper;

	/** @var IInitialState */
	private $initialStateService;

	/** @var LoggerInterface */
	private $logger;

	/** @var OutboxService */
	private $outboxService;

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
								TagMapper $tagMapper,
								IInitialState $initialStateService,
								LoggerInterface $logger,
								OutboxService $outboxService) {
		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->accountService = $accountService;
		$this->aliasesService = $aliasesService;
		$this->currentUserId = $UserId;
		$this->userSession = $userSession;
		$this->preferences = $preferences;
		$this->mailManager = $mailManager;
		$this->tagMapper = $tagMapper;
		$this->initialStateService = $initialStateService;
		$this->logger = $logger;
		$this->outboxService = $outboxService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse renders the index page
	 */
	public function index(): TemplateResponse {
		$this->initialStateService->provideInitialState(
			'debug',
			$this->config->getSystemValue('debug', false)
		);

		$mailAccounts = $this->accountService->findByUserId($this->currentUserId);
		$accountsJson = [];
		foreach ($mailAccounts as $mailAccount) {
			$json = $mailAccount->jsonSerialize();
			$json['aliases'] = $this->aliasesService->findAll($mailAccount->getId(),
				$this->currentUserId);
			try {
				$mailboxes = $this->mailManager->getMailboxes($mailAccount);
				$json['mailboxes'] = $mailboxes;
			} catch (Throwable $ex) {
				$this->logger->critical('Could not load account mailboxes: ' . $ex->getMessage(), [
					'exception' => $ex,
				]);
				$json['mailboxes'] = [];
				$json['error'] = true;
			}
			$accountsJson[] = $json;
		}
		$this->initialStateService->provideInitialState(
			'accounts',
			$accountsJson
		);
		$this->initialStateService->provideInitialState(
			'account-settings',
			json_decode($this->preferences->getPreference($this->currentUserId, 'account-settings', '[]'), true, 512, JSON_THROW_ON_ERROR) ?? []
		);
		$this->initialStateService->provideInitialState(
			'tags',
			$this->tagMapper->getAllTagsForUser($this->currentUserId)
		);

		$user = $this->userSession->getUser();
		$response = new TemplateResponse($this->appName, 'index',
			[
				'attachment-size-limit' => $this->config->getSystemValue('app.mail.attachment-size-limit', 0),
				'app-version' => $this->config->getAppValue('mail', 'installed_version'),
				'external-avatars' => $this->preferences->getPreference($this->currentUserId, 'external-avatars', 'true'),
				'reply-mode' => $this->preferences->getPreference($this->currentUserId, 'reply-mode', 'top'),
				'collect-data' => $this->preferences->getPreference($this->currentUserId, 'collect-data', 'true'),
				'tag-classified-messages' => $this->preferences->getPreference($this->currentUserId, 'tag-classified-messages', 'true'),
			]);
		$this->initialStateService->provideInitialState(
			'prefill_displayName',
			$user->getDisplayName()
		);
		$this->initialStateService->provideInitialState(
			'prefill_email',
			$this->config->getUserValue($user->getUID(), 'settings', 'email', '')
		);

		$this->initialStateService->provideInitialState(
			'outbox-messages',
			$this->outboxService->getMessages($user->getUID())
		);

		// Disable scheduled send in frontend if ajax cron is used because it is unreliable
		$cronMode = $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax');
		$this->initialStateService->provideInitialState(
			'disable-scheduled-send',
			$cronMode === 'ajax',
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
	 * @return TemplateResponse
	 */
	public function filteredThread(string $filter, int $mailboxId, int $id): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function outbox(): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function outboxMessage(int $messageId): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function draft(int $mailboxId, int $draftId): TemplateResponse {
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function filteredDraft(string $filter, int $mailboxId, int $draftId): TemplateResponse {
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
