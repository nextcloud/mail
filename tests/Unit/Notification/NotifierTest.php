<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Notification;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Notification\Notifier;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\MockObject\MockObject;

class NotifierTest extends TestCase {
	private IFactory&MockObject $factory;
	private IURLGenerator&MockObject $url;
	private IL10N&MockObject $l10n;
	private Notifier $notifier;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = $this->createMock(IFactory::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);
		$this->factory->method('get')->willReturn($this->l10n);

		$this->notifier = new Notifier($this->factory, $this->url);
	}

	public function testGetID(): void {
		$this->assertEquals('mail', $this->notifier->getID());
	}

	public function testGetName(): void {
		$this->assertEquals('Mail', $this->notifier->getName());
	}

	public function testPrepareForeignAppThrows(): void {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('other');

		$this->expectException(UnknownNotificationException::class);

		$this->notifier->prepare($notification, 'en');
	}

	public function testPrepareUnknownSubjectThrows(): void {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('mail');
		$notification->method('getSubject')->willReturn('something_unknown');

		$this->expectException(UnknownNotificationException::class);

		$this->notifier->prepare($notification, 'en');
	}

	public function testPrepareAccountDelegationDelegated(): void {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('mail');
		$notification->method('getSubject')->willReturn('account_delegation');
		$notification->method('getSubjectParameters')->willReturn([
			'id' => 1,
			'account_email' => 'owner@example.com',
		]);
		$notification->method('getMessageParameters')->willReturn([
			'id' => 1,
			'delegated' => true,
			'current_user_id' => 'owner',
			'current_user_display_name' => 'Owner User',
			'account_email' => 'owner@example.com',
		]);

		$this->url->method('linkTo')->with('mail', 'img/delegation.svg')->willReturn('/apps/mail/img/delegation.svg');
		$this->url->method('getAbsoluteURL')->with('/apps/mail/img/delegation.svg')->willReturn('https://example.com/apps/mail/img/delegation.svg');

		$notification->expects($this->once())
			->method('setIcon')
			->with('https://example.com/apps/mail/img/delegation.svg')
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setRichSubject')
			->with(
				'{account_email} has been delegated to you',
				[
					'account_email' => [
						'type' => 'highlight',
						'id' => '1',
						'name' => 'owner@example.com',
					],
				]
			)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setRichMessage')
			->with(
				'{user} delegated {account} to you',
				[
					'user' => [
						'type' => 'user',
						'id' => 'owner',
						'name' => 'Owner User',
					],
					'account' => [
						'type' => 'highlight',
						'id' => '1',
						'name' => 'owner@example.com',
					],
				]
			)
			->willReturnSelf();

		$result = $this->notifier->prepare($notification, 'en');

		$this->assertSame($notification, $result);
	}

	public function testPrepareAccountDelegationRevoked(): void {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('mail');
		$notification->method('getSubject')->willReturn('account_delegation');
		$notification->method('getSubjectParameters')->willReturn([
			'id' => 1,
			'account_email' => 'owner@example.com',
		]);
		$notification->method('getMessageParameters')->willReturn([
			'id' => 1,
			'delegated' => false,
			'current_user_id' => 'owner',
			'current_user_display_name' => 'Owner User',
			'account_email' => 'owner@example.com',
		]);

		$this->url->method('linkTo')->with('mail', 'img/delegation.svg')->willReturn('/apps/mail/img/delegation.svg');
		$this->url->method('getAbsoluteURL')->with('/apps/mail/img/delegation.svg')->willReturn('https://example.com/apps/mail/img/delegation.svg');

		$notification->expects($this->once())
			->method('setIcon')
			->with('https://example.com/apps/mail/img/delegation.svg')
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setRichSubject')
			->with(
				'{account_email} is no longer delegated to you',
				[
					'account_email' => [
						'type' => 'highlight',
						'id' => '1',
						'name' => 'owner@example.com',
					],
				]
			)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setRichMessage')
			->with(
				'{user} revoked delegation for {account}',
				[
					'user' => [
						'type' => 'user',
						'id' => 'owner',
						'name' => 'Owner User',
					],
					'account' => [
						'type' => 'highlight',
						'id' => '1',
						'name' => 'owner@example.com',
					],
				]
			)
			->willReturnSelf();

		$result = $this->notifier->prepare($notification, 'en');

		$this->assertSame($notification, $result);
	}
}
