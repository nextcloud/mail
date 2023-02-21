<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Controller\PageController;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\OutboxService;
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
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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

	/** @var AliasesService|MockObject */
	private $aliasesService;

	/** @var IUserSession|MockObject */
	private $userSession;

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

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->userId = 'george';
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->accountService = $this->createMock(AccountService::class);
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
		);
	}

	public function testIndex(): void {
		$account1 = $this->createMock(Account::class);
		$account2 = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$this->preferences->expects($this->exactly(6))
			->method('getPreference')
			->willReturnMap([
				[$this->userId, 'account-settings', '[]', json_encode([])],
				[$this->userId, 'external-avatars', 'true', 'true'],
				[$this->userId, 'reply-mode', 'top', 'bottom'],
				[$this->userId, 'collect-data', 'true', 'true'],
				[$this->userId, 'start-mailbox-id', null, '123'],
				[$this->userId, 'tag-classified-messages', 'true', 'true'],
			]);
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
		$this->config->expects($this->exactly(6))
			->method('getAppValue')
			->withConsecutive(
				[ 'mail', 'installed_version' ],
				['mail', 'google_oauth_client_id' ],
				['mail', 'microsoft_oauth_client_id' ],
				['mail', 'microsoft_oauth_tenant_id' ],
				['core', 'backgroundjobs_mode', 'ajax' ],
				['mail', 'allow_new_mail_accounts', 'yes']
			)->willReturnOnConsecutiveCalls(
				$this->returnValue('1.2.3'),
				$this->returnValue(''),
				$this->returnValue(''),
				$this->returnValue(''),
				$this->returnValue('cron'),
				$this->returnValue('yes')
			);
		$user->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Jane Doe'));
		$user->method('getUID')
			->will($this->returnValue('jane'));
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

		$this->initialState->expects($this->exactly(12))
			->method('provideInitialState')
			->withConsecutive(
				['debug', true],
				['ncVersion', '26.0.0'],
				['accounts', $accountsJson],
				['account-settings', []],
				['tags', []],
				['password-is-unavailable', true],
				['prefill_displayName', 'Jane Doe'],
				['prefill_email', 'jane@doe.cz'],
				['outbox-messages', []],
				['disable-scheduled-send', false],
				['allow-new-accounts', true],
				['smime-certificates', []],
			);

		$expected = new TemplateResponse($this->appName, 'index', [
			'attachment-size-limit' => 123,
			'external-avatars' => 'true',
			'reply-mode' => 'bottom',
			'app-version' => '1.2.3',
			'collect-data' => 'true',
			'start-mailbox-id' => '123',
			'tag-classified-messages' => 'true',
		]);
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
