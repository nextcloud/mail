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

namespace OCA\Mail\Tests\Service\DefaultAccount;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\DefaultAccount\Config;
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

	public function testGetImapHost() {
		$config = new Config([
			'imapHost' => 'imap.domain.com',
		]);

		$this->assertEquals('imap.domain.com', $config->getImapHost());
	}

	public function testGetImapPort() {
		$config = new Config([
			'imapPort' => 993,
		]);

		$this->assertEquals(993, $config->getImapPort());
	}

	public function testGetImapSslMode() {
		$config = new Config([
			'imapSslMode' => 'ssl',
		]);

		$this->assertEquals('ssl', $config->getImapSslMode());
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

	public function testGetSmtpSslMode() {
		$config = new Config([
			'smtpSslMode' => 'tls',
		]);

		$this->assertEquals('tls', $config->getSmtpSslMode());
	}

	public function testGetSmtpHost() {
		$config = new Config([
			'smtpHost' => 'smtp.domain.com',
		]);

		$this->assertEquals('smtp.domain.com', $config->getSmtpHost());
	}

	public function testGetSmtpPort() {
		$config = new Config([
			'smtpPort' => 465,
		]);

		$this->assertEquals(465, $config->getSmtpPort());
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

}
