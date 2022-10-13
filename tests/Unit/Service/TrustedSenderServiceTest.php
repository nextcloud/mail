<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
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
