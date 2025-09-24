<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Contacts\Event\LoadContactsOcaApiEvent;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCA\Mail\Service\InternalAddressService;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\Service\SmimeService;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\PasswordUnavailableException;
use OCP\Authentication\LoginCredentials\IStore as ICredentialStore;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\SummaryTaskType;
use OCP\User\IAvailabilityCoordinator;
use Psr\Log\LoggerInterface;
use Throwable;
use function class_exists;
use function http_build_query;
use function json_decode;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class PageController extends Controller {
	private IURLGenerator $urlGenerator;
	private IConfig $config;
	private AccountService $accountService;
	private AliasesService $aliasesService;
	private ?string $currentUserId;
	private IUserSession $userSession;
	private IUserPreferences $preferences;
	private IMailManager $mailManager;
	private TagMapper $tagMapper;
	private IInitialState $initialStateService;
	private LoggerInterface $logger;
	private OutboxService $outboxService;
	private IEventDispatcher $dispatcher;
	private ICredentialstore $credentialStore;
	private SmimeService $smimeService;
	private AiIntegrationsService $aiIntegrationsService;
	private IUserManager $userManager;
	private IAvailabilityCoordinator $availabilityCoordinator;
	private ClassificationSettingsService $classificationSettingsService;
	private InternalAddressService $internalAddressService;
	private QuickActionsService $quickActionsService;

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
		OutboxService $outboxService,
		IEventDispatcher $dispatcher,
		ICredentialStore $credentialStore,
		SmimeService $smimeService,
		AiIntegrationsService $aiIntegrationsService,
		IUserManager $userManager,
		ClassificationSettingsService $classificationSettingsService,
		InternalAddressService $internalAddressService,
		IAvailabilityCoordinator $availabilityCoordinator,
		QuickActionsService $quickActionsService,
	) {
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
		$this->dispatcher = $dispatcher;
		$this->credentialStore = $credentialStore;
		$this->smimeService = $smimeService;
		$this->aiIntegrationsService = $aiIntegrationsService;
		$this->userManager = $userManager;
		$this->classificationSettingsService = $classificationSettingsService;
		$this->internalAddressService = $internalAddressService;
		$this->availabilityCoordinator = $availabilityCoordinator;
		$this->quickActionsService = $quickActionsService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse renders the index page
	 */
	public function index(): TemplateResponse {
		if (class_exists(LoadViewer::class)) {
			$this->dispatcher->dispatchTyped(new LoadViewer());
		}

		$this->initialStateService->provideInitialState(
			'debug',
			$this->config->getSystemValue('debug', false)
		);

		$this->initialStateService->provideInitialState(
			'ncVersion',
			$this->config->getSystemValue('version', '0.0.0')
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

		$this->initialStateService->provideInitialState(
			'internal-addresses-list',
			$this->internalAddressService->getInternalAddresses($this->currentUserId)
		);

		$this->initialStateService->provideInitialState(
			'internal-addresses',
			$this->preferences->getPreference($this->currentUserId, 'internal-addresses', false)
		);

		$this->initialStateService->provideInitialState(
			'smime-sign-aliases',
			json_decode($this->preferences->getPreference($this->currentUserId, 'smime-sign-aliases', '[]'), true, 512, JSON_THROW_ON_ERROR) ?? []
		);

		$this->initialStateService->provideInitialState(
			'sort-order',
			$this->preferences->getPreference($this->currentUserId, 'sort-order', 'newest')
		);

		try {
			$password = $this->credentialStore->getLoginCredentials()->getPassword();
			$passwordIsUnavailable = $password === null || $password === '';
		} catch (CredentialsUnavailableException|PasswordUnavailableException $e) {
			$passwordIsUnavailable = true;
		}
		$this->initialStateService->provideInitialState(
			'password-is-unavailable',
			$passwordIsUnavailable,
		);

		$user = $this->userSession->getUser();
		$response = new TemplateResponse($this->appName, 'index');
		$this->initialStateService->provideInitialState('preferences', [
			'attachment-size-limit' => $this->config->getSystemValue('app.mail.attachment-size-limit', 0),
			'app-version' => $this->config->getAppValue('mail', 'installed_version'),
			'external-avatars' => $this->preferences->getPreference($this->currentUserId, 'external-avatars', 'true'),
			'layout-mode' => $this->preferences->getPreference($this->currentUserId, 'layout-mode', 'vertical-split'),
			'layout-message-view' => $this->preferences->getPreference($this->currentUserId, 'layout-message-view', $this->config->getAppValue('mail', 'layout_message_view', 'threaded')),
			'reply-mode' => $this->preferences->getPreference($this->currentUserId, 'reply-mode', 'top'),
			'collect-data' => $this->preferences->getPreference($this->currentUserId, 'collect-data', 'true'),
			'search-priority-body' => $this->preferences->getPreference($this->currentUserId, 'search-priority-body', 'false'),
			'start-mailbox-id' => $this->preferences->getPreference($this->currentUserId, 'start-mailbox-id'),
			'tag-classified-messages' => $this->classificationSettingsService->isClassificationEnabled($this->currentUserId) ? 'true' : 'false',
			'follow-up-reminders' => $this->preferences->getPreference($this->currentUserId, 'follow-up-reminders', 'true'),
		]);
		$this->initialStateService->provideInitialState(
			'prefill_displayName',
			$this->userManager->getDisplayName($this->currentUserId),
		);
		$this->initialStateService->provideInitialState(
			'prefill_email',
			$this->config->getUserValue($user->getUID(), 'settings', 'email', '')
		);

		$this->initialStateService->provideInitialState(
			'outbox-messages',
			$this->outboxService->getMessages($user->getUID())
		);
		$this->initialStateService->provideInitialState(
			'quick-actions',
			$this->quickActionsService->findAll($this->currentUserId),
		);
		$googleOauthclientId = $this->config->getAppValue(Application::APP_ID, 'google_oauth_client_id');
		if (!empty($googleOauthclientId)) {
			$this->initialStateService->provideInitialState(
				'google-oauth-url',
				'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
					'client_id' => $googleOauthclientId,
					'redirect_uri' => $this->urlGenerator->linkToRouteAbsolute('mail.googleIntegration.oauthRedirect'),
					'response_type' => 'code',
					'prompt' => 'consent',
					'state' => '_accountId_', // Replaced by frontend
					'scope' => 'https://mail.google.com/',
					'access_type' => 'offline',
					'login_hint' => '_email_', // Replaced by frontend
				]),
			);
		}
		$microsoftOauthClientId = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_client_id');
		$microsoftOauthTenantId = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_tenant_id', 'common');
		if (!empty($microsoftOauthClientId) && !empty($microsoftOauthTenantId)) {
			$this->initialStateService->provideInitialState(
				'microsoft-oauth-url',
				"https://login.microsoftonline.com/$microsoftOauthTenantId/oauth2/v2.0/authorize?" . http_build_query([
					'client_id' => $microsoftOauthClientId,
					'redirect_uri' => $this->urlGenerator->linkToRouteAbsolute('mail.microsoftIntegration.oauthRedirect'),
					'response_type' => 'code',
					'response_mode' => 'query',
					'prompt' => 'consent',
					'state' => '_accountId_', // Replaced by frontend
					'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send',
					'access_type' => 'offline',
					'login_hint' => '_email_', // Replaced by frontend
				]),
			);
		}

		// Disable snooze and scheduled send in frontend if ajax cron is used because it is unreliable
		$cronMode = $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax');
		$this->initialStateService->provideInitialState(
			'disable-scheduled-send',
			$cronMode === 'ajax',
		);
		$this->initialStateService->provideInitialState(
			'disable-snooze',
			$cronMode === 'ajax',
		);

		$this->initialStateService->provideInitialState(
			'allow-new-accounts',
			$this->config->getAppValue('mail', 'allow_new_mail_accounts', 'yes') === 'yes'
		);

		$this->initialStateService->provideInitialState(
			'llm_summaries_available',
			$this->aiIntegrationsService->isLlmProcessingEnabled() && $this->aiIntegrationsService->isLlmAvailable(SummaryTaskType::class)
		);

		$this->initialStateService->provideInitialState(
			'llm_translation_enabled',
			$this->aiIntegrationsService->isLlmProcessingEnabled() && $this->aiIntegrationsService->isTaskAvailable('core:text2text:translate')
		);

		$this->initialStateService->provideInitialState(
			'llm_freeprompt_available',
			$this->aiIntegrationsService->isLlmProcessingEnabled() && $this->aiIntegrationsService->isLlmAvailable(FreePromptTaskType::class)
		);

		$this->initialStateService->provideInitialState(
			'llm_followup_available',
			$this->aiIntegrationsService->isLlmProcessingEnabled()
			&& $this->aiIntegrationsService->isLlmAvailable(FreePromptTaskType::class)
		);

		$this->initialStateService->provideInitialState(
			'smime-certificates',
			array_map(
				function (SmimeCertificate $certificate) {
					return $this->smimeService->enrichCertificate($certificate);
				},
				$this->smimeService->findAllCertificates($user->getUID()),
			),
		);

		$this->initialStateService->provideInitialState(
			'enable-system-out-of-office',
			$this->availabilityCoordinator->isEnabled(),
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($csp);
		$this->dispatcher->dispatchTyped(new RenderReferenceEvent());

		if (class_exists(LoadContactsOcaApiEvent::class)) {
			$this->dispatcher->dispatchTyped(new LoadContactsOcaApiEvent());
		}

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
	public function mailboxStarred(int $id): TemplateResponse {
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
		$params = [];
		if (isset($parts['path'])) {
			$params['to'] = $parts['path'];
		}
		if (isset($parts['query'])) {
			$parts = explode('&', $parts['query']);
			foreach ($parts as $part) {
				$pair = explode('=', $part, 2);
				$params[strtolower($pair[0])] = urldecode($pair[1]);
			}
		}

		array_walk($params,
			static function (&$value, $key) {
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
