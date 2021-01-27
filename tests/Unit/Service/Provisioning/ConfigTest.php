<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Unit\Service\Provisioning;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Provisioning\Config;
use OCP\IUser;

class ConfigTest extends TestCase {
	public function testBuildEmailWithUserId() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'email' => '%USERID%@domain.se',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('test');
		$user->expects($this->exactly(2))
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('test@domain.se', $config->buildEmail($user));
	}

	public function testBuildEmailWithEmailPlaceholder() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'email' => '%EMAIL%',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user');
		$user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildEmail($user));
	}

	public function testBuildImapUserWithUserId() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'imapUser' => '%USERID%@domain.se',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('test');
		$user->expects($this->once())
			->method('getEMailAddress')
			->willReturn(null);

		$this->assertEquals('test@domain.se', $config->buildImapUser($user));
	}

	public function testBuildImapUserWithEmailPlaceholder() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'imapUser' => '%EMAIL%',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user');
		$user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildImapUser($user));
	}

	public function testBuildImapUserFromDefaultEmail() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'email' => '%EMAIL%',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user');
		$user->expects($this->exactly(2))
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildImapUser($user));
	}

	public function testBuildSmtpUserWithUserId() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'smtpUser' => '%USERID%@domain.se',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('test');
		$user->expects($this->once())
			->method('getEMailAddress')
			->willReturn(null);

		$this->assertEquals('test@domain.se', $config->buildSmtpUser($user));
	}

	public function testBuilldSmtpUserWithEmailPlaceholder() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'smtpUser' => '%EMAIL%',
		]);
		$user->expects($this->any())
			->method('getUID')
			->willReturn(null);
		$user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildSmtpUser($user));
	}

	public function testBuildSmtpUserFromDefaultEmail() {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'email' => '%EMAIL%',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user');
		$user->expects($this->exactly(2))
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildImapUser($user));
	}

	public function testBuildSieveUserWithUserId(): void {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'sieveUser' => '%USERID%@domain.se',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('test');
		$user->expects($this->once())
			->method('getEMailAddress')
			->willReturn(null);

		$this->assertEquals('test@domain.se', $config->buildSieveUser($user));
	}

	public function testBuilldSieveUserWithEmailPlaceholder(): void {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'sieveUser' => '%EMAIL%',
		]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn(null);
		$user->expects($this->exactly(2))
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildSieveUser($user));
	}

	public function testBuildSieveUserFromDefaultEmail(): void {
		$user = $this->createMock(IUser::class);
		$config = new Config([
			'email' => '%EMAIL%',
		]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user');
		$user->expects($this->exactly(2))
			->method('getEMailAddress')
			->willReturn('user@domain.se');

		$this->assertEquals('user@domain.se', $config->buildSieveUser($user));
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @dataProvider providerTestGetter
	 */
	public function testGetter(string $key, $value): void {
		$config = new Config([
			$key => $value
		]);
		$this->assertEquals($value, $config->{'get' . ucfirst($key)}());
	}

	public function providerTestGetter(): array {
		return [
			'smtpHost' => ['smtpHost', 'smtp.domain.com'],
			'smtpPort' => ['smtpPort', 465],
			'smtpSslMode' => ['smtpSslMode', 'tls'],
			'imapHost' => ['imapHost', 'imap.domain.com'],
			'imapPort' => ['imapPort', 993],
			'imapSslMode' => ['imapSslMode', 'tls'],
			'sieveEnabled' => ['sieveEnabled', true],
			'sieveHost' => ['sieveHost', 'imap.domain.com'],
			'sievePort' => ['sieveHost', 4190],
			'sieveSslMode' => ['sieveSslMode', 'tls'],
		];
	}
}
