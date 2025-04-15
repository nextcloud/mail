<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Dashboard;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Dashboard\ImportantMailWidget;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Search\MailSearch;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

class ImportantMailWidgetTest extends TestCase {

	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private IUserManager&MockObject $userManager;
	private AccountService&MockObject $accountService;
	private MailSearch&MockObject $mailSearch;
	private IInitialState&MockObject $initialState;
	private ImportantMailWidget $widget;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailSearch = $this->createMock(MailSearch::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->widget = new ImportantMailWidget(
			$this->l10n,
			$this->urlGenerator,
			$this->userManager,
			$this->accountService,
			$this->mailSearch,
			$this->initialState,
			'bob'
		);
	}

	public function testGetItems(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('bob');
		$this->userManager->expects($this->once())
			->method('get')
			->willReturn($user);

		$message1 = new Message();
		$message1->setSubject('Important');
		$message1->setMailboxId(1);

		$message2 = new Message();
		$message2->setSubject('Important, but deleted');
		$message2->setMailboxId(2);

		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->willReturn([$message1, $message2]);

		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->willReturn([$account]);

		$items = $this->widget->getItems('bob', null, 7);

		$this->assertCount(2, $items);
	}

	public function testGetItemsSkipTrash(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('bob');
		$this->userManager->expects($this->once())
			->method('get')
			->willReturn($user);

		$message1 = new Message();
		$message1->setSubject('Important');
		$message1->setMailboxId(1);

		$message2 = new Message();
		$message2->setSubject('Important, but deleted');
		$message2->setMailboxId(2);

		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->willReturn([$message1, $message2]);

		$mailAccount = new MailAccount();
		$mailAccount->setTrashMailboxId(2);
		$account = new Account($mailAccount);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->willReturn([$account]);

		$items = $this->widget->getItems('bob', null, 7);

		$this->assertCount(1, $items);
	}
}
