<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Tests\Integration\IMAP;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\SmimeCertificateMapper;
use OCA\Mail\IMAP\ImapMessageFetcherFactory;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\ICertificateManager;
use OCP\Security\ICrypto;
use OCP\Server;

class ImapMessageFetcherIntegrationTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	private const LOREM = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.';

	private MailAccount $account;
	private ImapMessageFetcherFactory $fetcherFactory;
	private SmimeCertificateMapper $certificateMapper;
	private ICrypto $crypto;
	private ICertificateManager $certificateManager;

	protected function setUp(): void {
		parent::setUp();


		$this->account = $this->createTestAccount();
		$this->fetcherFactory = Server::get(ImapMessageFetcherFactory::class);
		$this->certificateMapper = Server::get(SmimeCertificateMapper::class);
		$this->crypto = Server::get(ICrypto::class);
		$this->certificateManager = Server::get(ICertificateManager::class);

		$this->certificateManager->addCertificate(
			file_get_contents(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt'),
			'imap.localhost.ca.crt'
		);

		$this->importCertificate('user@imap.localhost');
		$this->importCertificate('debug@imap.localhost');
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->certificateManager->removeCertificate('imap.localhost.ca.crt');
		$this->clearCertificates();
	}

	private function importCertificate(string $emailAddress): SmimeCertificate {
		// TODO: convert to a trait?!

		$certificateData = file_get_contents(__DIR__ . "/../../data/smime-certs/$emailAddress.crt");
		$privateKeyData = file_get_contents(__DIR__ . "/../../data/smime-certs/$emailAddress.key");

		$certificate = new SmimeCertificate();
		$certificate->setUserId($this->account->getUserId());
		$certificate->setEmailAddress($emailAddress);
		$certificate->setCertificate($this->crypto->encrypt($certificateData));
		$certificate->setPrivateKey($this->crypto->encrypt($privateKeyData));
		$this->certificateMapper->insert($certificate);

		return $certificate;
	}

	private function clearCertificates(): void {
		// TODO: convert to a trait?!

		$certificates = $this->certificateMapper->findAll($this->account->getUserId());
		foreach ($certificates as $certificate) {
			$this->certificateMapper->delete($certificate);
		}
	}

	public function testFetchMessageWithEncryptedMessage(): void {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/encrypted-message.txt');
		$uid = $this->saveMimeMessage('INBOX', $encryptedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM . "\n\n", $message->getPlainBody());
		$this->assertCount(1, $message->attachments);
		$this->assertTrue($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		// TODO: https://github.com/nextcloud/mail/issues/9286
		// $this->assertTrue($message->isSignatureValid());
	}

	public function testFetchMessageWithEncryptedUnverifiedMessage(): void {
		// Force verification to fail
		$this->certificateManager->removeCertificate('imap.localhost.ca.crt');

		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/encrypted-message.txt');
		$uid = $this->saveMimeMessage('INBOX', $encryptedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM . "\n\n", $message->getPlainBody());
		$this->assertCount(1, $message->attachments);
		$this->assertTrue($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertFalse($message->isSignatureValid());
	}

	public function testFetchMessageWithEncryptedSignedOpaqueMessage(): void {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/encrypted-signed-opaque-message.txt');
		$uid = $this->saveMimeMessage('INBOX', $encryptedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM . "\n\n", $message->getPlainBody());
		$this->assertTrue($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
	}

	public function testFetchMessageWithSignedMessage(): void {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/signed-message.txt');
		$uid = $this->saveMimeMessage('INBOX', $encryptedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM . "\n\n", $message->getPlainBody());
		$this->assertFalse($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		// TODO: https://github.com/nextcloud/mail/issues/9286
		// $this->assertTrue($message->isSignatureValid());
	}

	public function testFetchMessageWithOpaqueSignedMessage(): void {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/signed-opaque-message.txt');
		$uid = $this->saveMimeMessage('INBOX', $encryptedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM . "\n\n", $message->getPlainBody());
		$this->assertFalse($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
	}
}
