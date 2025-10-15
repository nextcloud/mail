<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Controller\PageController;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCA\Mail\Service\InternalAddressService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\Service\SmimeService;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore as ICredentialStore;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\IAvailabilityCoordinator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function urlencode;

class PageControllerTest extends TestCase {
	/** @var string */
	private $appName;

	/** @var IRequest|MockObject */
	private $request;

	/** @var string */
	private $userId;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IConfig|MockObject */
	private $config;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var AiIntegrationsService|MockObject */
	private $aiIntegrationsService;

	/** @var AliasesService|MockObject */
	private $aliasesService;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IUserPreferences|MockObject */
	private $preferences;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var TagMapper|MockObject */
	private $tagMapper;

	/** @var IInitialState|MockObject */
	private $initialState;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var OutboxService|MockObject */
	private $outboxService;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var ICredentialStore|MockObject */
	private $credentialStore;

	/** @var PageController */
	private $controller;

	private SmimeService $smimeService;

	/** @var ClassificationSettingsService|MockObject */
	private $classificationSettingsService;

	/** @var InternalAddressService|MockObject */
	private $internalAddressService;

	private QuickActionsService|MockObject $quickActionsService;

	private IAvailabilityCoordinator&MockObject $availabilityCoordinator;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->userId = 'jane';
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->aiIntegrationsService = $this->createMock(AiIntegrationsService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->preferences = $this->createMock(IUserPreferences::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->tagMapper = $this->createMock(TagMapper::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->credentialStore = $this->createMock(ICredentialStore::class);
		$this->smimeService = $this->createMock(SmimeService::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->classificationSettingsService = $this->createMock(ClassificationSettingsService::class);
		$this->internalAddressService = $this->createMock(InternalAddressService::class);
		$this->availabilityCoordinator = $this->createMock(IAvailabilityCoordinator::class);
		$this->quickActionsService = $this->createMock(QuickActionsService::class);

		$this->controller = new PageController(
			$this->appName,
			$this->request,
			$this->urlGenerator,
			$this->config,
			$this->accountService,
			$this->aliasesService,
			$this->userId,
			$this->userSession,
			$this->preferences,
			$this->mailManager,
			$this->tagMapper,
			$this->initialState,
			$this->logger,
			$this->outboxService,
			$this->eventDispatcher,
			$this->credentialStore,
			$this->smimeService,
			$this->aiIntegrationsService,
			$this->userManager,
			$this->classificationSettingsService,
			$this->internalAddressService,
			$this->availabilityCoordinator,
			$this->quickActionsService,
		);
	}

	public function testIndex(): void {
		$account1 = $this->createMock(Account::class);
		$account2 = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$this->preferences->expects($this->exactly(12))
			->method('getPreference')
			->willReturnMap([
				[$this->userId, 'account-settings', '[]', json_encode([])],
				[$this->userId, 'sort-order', 'newest', 'newest'],
				[$this->userId, 'external-avatars', 'true', 'true'],
				[$this->userId, 'reply-mode', 'top', 'bottom'],
				[$this->userId, 'collect-data', 'true', 'true'],
				[$this->userId, 'search-priority-body', 'false', 'false'],
				[$this->userId, 'start-mailbox-id', null, '123'],
				[$this->userId, 'layout-mode', 'vertical-split', 'vertical-split'],
				[$this->userId, 'layout-message-view', 'threaded', 'threaded'],
				[$this->userId, 'follow-up-reminders', 'true', 'true'],
				[$this->userId, 'internal-addresses', 'false', 'false'],
				[$this->userId, 'smime-sign-aliases', '[]', '[]'],
			]);
		$this->classificationSettingsService->expects(self::once())
			->method('isClassificationEnabled')
			->with($this->userId)
			->willReturn(false);
		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with($this->userId)
			->will($this->returnValue([
				$account1,
				$account2,
			]));
		$this->mailManager->expects($this->exactly(2))
			->method('getMailboxes')
			->withConsecutive(
				[$account1],
				[$account2]
			)
			->willReturnOnConsecutiveCalls(
				[$mailbox],
				[]
			);
		$account1->expects($this->once())
			->method('jsonSerialize')
			->will($this->returnValue([
				'accountId' => 1,
			]));
		$account1->expects($this->once())
			->method('getId')
			->will($this->returnValue(1));
		$account2->expects($this->once())
			->method('jsonSerialize')
			->will($this->returnValue([
				'accountId' => 2,
			]));
		$account2->expects($this->once())
			->method('getId')
			->will($this->returnValue(2));
		$this->aliasesService->expects($this->exactly(2))
			->method('findAll')
			->will($this->returnValueMap([
				[1, $this->userId, ['a11', 'a12']],
				[2, $this->userId, ['a21', 'a22']],
			]));
		$accountsJson = [
			[
				'accountId' => 1,
				'aliases' => [
					'a11',
					'a12',
				],
				'mailboxes' => [
					$mailbox,
				],
			],
			[
				'accountId' => 2,
				'aliases' => [
					'a21',
					'a22',
				],
				'mailboxes' => [],
			],
		];

		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['debug', false, true],
				['version', '0.0.0', '26.0.0'],
				['app.mail.attachment-size-limit', 0, 123],
			]);
		$this->config->expects($this->exactly(7))
			->method('getAppValue')
			->withConsecutive(
				[ 'mail', 'installed_version' ],
				['mail', 'layout_message_view' ],
				['mail', 'google_oauth_client_id' ],
				['mail', 'microsoft_oauth_client_id' ],
				['mail', 'microsoft_oauth_tenant_id' ],
				['core', 'backgroundjobs_mode', 'ajax' ],
				['mail', 'allow_new_mail_accounts', 'yes'],
			)->willReturnOnConsecutiveCalls(
				$this->returnValue('1.2.3'),
				$this->returnValue('threaded'),
				$this->returnValue(''),
				$this->returnValue(''),
				$this->returnValue(''),
				$this->returnValue('cron'),
				$this->returnValue('yes'),
				$this->returnValue('no')
			);
		$this->aiIntegrationsService->expects(self::exactly(4))
			->method('isLlmProcessingEnabled')
			->willReturn(false);

		$user->method('getUID')
			->will($this->returnValue('jane'));
		$this->userManager->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('jane'))
			->will($this->returnValue('Jane Doe'));
		$this->config->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo('jane'), $this->equalTo('settings'),
				$this->equalTo('email'), $this->equalTo(''))
			->will($this->returnValue('jane@doe.cz'));

		$loginCredentials = $this->createMock(ICredentials::class);
		$loginCredentials->expects($this->once())
			->method('getPassword')
			->willReturn(null);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willReturn($loginCredentials);

		$this->availabilityCoordinator->expects(self::once())
			->method('isEnabled')
			->willReturn(true);

		$this->quickActionsService->expects(self::once())
			->method('findAll')
			->with($this->userId)
			->willReturn([]);
		$this->initialState->expects($this->exactly(24))
			->method('provideInitialState')
			->withConsecutive(
				['debug', true],
				['ncVersion', '26.0.0'],
				['accounts', $accountsJson],
				['account-settings', []],
				['tags', []],
				['internal-addresses-list', []],
				['internal-addresses', false],
				['smime-sign-aliases',[]],
				['sort-order', 'newest'],
				['password-is-unavailable', true],
				['preferences', [
					'attachment-size-limit' => 123,
					'external-avatars' => 'true',
					'reply-mode' => 'bottom',
					'app-version' => '1.2.3',
					'collect-data' => 'true',
					'start-mailbox-id' => '123',
					'tag-classified-messages' => 'false',
					'search-priority-body' => 'false',
					'layout-mode' => 'vertical-split',
					'layout-message-view' => 'threaded',
					'follow-up-reminders' => 'true',
				]],
				['prefill_displayName', 'Jane Doe'],
				['prefill_email', 'jane@doe.cz'],
				['outbox-messages', []],
				['quick-actions', []],
				['disable-scheduled-send', false],
				['disable-snooze', false],
				['allow-new-accounts', true],
				['llm_summaries_available', false],
				['llm_translation_enabled', false],
				['llm_freeprompt_available', false],
				['llm_followup_available', false],
				['smime-certificates', []],
				['enable-system-out-of-office', true],
			);

		$expected = new TemplateResponse($this->appName, 'index');
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');
		$expected->setContentSecurityPolicy($csp);

		$response = $this->controller->index();

		$this->assertEquals($expected, $response);
	}

	public function testComposeSimple() {
		$address = 'user@example.com';
		$uri = "mailto:$address";

		$expected = new RedirectResponse('?to=' . urlencode($address));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithSubject() {
		$address = 'user@example.com';
		$subject = 'hello there';
		$uri = "mailto:$address?subject=$subject";

		$expected = new RedirectResponse('?to=' . urlencode($address)
			. '&subject=' . urlencode($subject));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithCc() {
		$address = 'user@example.com';
		$cc = 'other@example.com';
		$uri = "mailto:$address?cc=$cc";

		$expected = new RedirectResponse('?to=' . urlencode($address)
			. '&cc=' . urlencode($cc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeBcc() {
		$bcc = 'blind@example.com';
		$uri = "mailto:?bcc=$bcc";

		$expected = new RedirectResponse('?bcc=' . urlencode($bcc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithBcc() {
		$address = 'user@example.com';
		$bcc = 'blind@example.com';
		$uri = "mailto:$address?bcc=$bcc";

		$expected = new RedirectResponse('?to=' . urlencode($address)
			. '&bcc=' . urlencode($bcc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithMultilineBody() {
		$address = 'user@example.com';
		$body = 'Hi!\nWhat\'s up?\nAnother line';
		$uri = "mailto:$address?body=$body";

		$expected = new RedirectResponse('?to=' . urlencode($address)
			. '&body=' . urlencode($body));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}
}
