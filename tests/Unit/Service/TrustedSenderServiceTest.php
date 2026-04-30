<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\TrustedSenderMapper;
use OCA\Mail\Service\TrustedSenderService;
use PHPUnit\Framework\MockObject\MockObject;

class TrustedSenderServiceTest extends TestCase {
	/** @var TrustedSenderMapper|MockObject */
	private $mapper;

	/** @var TrustedSenderService */
	private $service;

	protected function setUp(): void {
		$this->mapper = $this->createMock(TrustedSenderMapper::class);

		$this->service = new TrustedSenderService(
			$this->mapper
		);
	}

	public function testIsTrusted(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(true);

		$trusted = $this->service->isTrusted(
			$uid,
			$email
		);

		$this->assertTrue($trusted);
	}

	public function testIsNotTrusted(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(false);

		$trusted = $this->service->isTrusted(
			$uid,
			$email
		);

		$this->assertFalse($trusted);
	}

	public function testIsTrustedByMessage(): void {
		$message = new Message();
		$message->setFrom(new AddressList([Address::fromRaw('Christoph', 'christoph@next.cloud')]));

		$this->mapper->expects($this->once())
			->method('exists')
			->with('greta', 'christoph@next.cloud')
			->willReturn(true);

		$this->assertTrue($this->service->isTrustedByMessage('greta', $message));
	}

	public function testIsTrustedByMessageReturnsFalseOnInvalidEmailEncoding(): void {
		$invalidAddress = new Horde_Mail_Rfc822_Address();
		$invalidAddress->mailbox = "\xC3";
		$invalidAddress->host = 'example.com';

		$message = new Message();
		$message->setFrom(new AddressList([Address::fromHorde($invalidAddress)]));

		$this->mapper->expects($this->never())
			->method('exists');

		$this->assertFalse($this->service->isTrustedByMessage('greta', $message));
	}

	public function testTrustAlreadyTrusted(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(true);
		$this->mapper->expects($this->never())
			->method('create');
		$this->mapper->expects($this->never())
			->method('remove');

		$this->service->trust(
			$uid,
			$email,
			'individual'
		);
	}

	public function testTrustNew(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(false);
		$this->mapper->expects($this->once())
			->method('create')
			->with($uid, $email);

		$this->service->trust(
			$uid,
			$email,
			'individual'
		);
	}

	public function testRemoveTrust(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->never())
			->method('exists');
		$this->mapper->expects($this->never())
			->method('create');
		$this->mapper->expects($this->once())
			->method('remove')
			->with($uid, $email);

		$this->service->trust(
			$uid,
			$email,
			'individual',
			false
		);
	}
}
