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

use OC;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\SmimeCertificateMapper;
use OCA\Mail\IMAP\ImapMessageFetcherFactory;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\ICertificateManager;
use OCP\Security\ICrypto;

class ImapMessageFetcherIntegrationTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	private MailAccount $account;
	private ImapMessageFetcherFactory $fetcherFactory;
	private SmimeCertificateMapper $certificateMapper;
	private ICrypto $crypto;
	private ICertificateManager $certificateManager;

	protected function setUp(): void {
		parent::setUp();


		$this->account = $this->createTestAccount();
		$this->fetcherFactory = OC::$server->get(ImapMessageFetcherFactory::class);
		$this->certificateMapper = OC::$server->get(SmimeCertificateMapper::class);
		$this->crypto = OC::$server->get(ICrypto::class);
		$this->certificateManager = OC::$server->get(ICertificateManager::class);

		$this->certificateManager->addCertificate(
			file_get_contents(__DIR__ . '/../../data/smime-certs/domain.tld.ca.crt'),
			'domain.tld.ca.crt'
		);
		$this->certificateManager->addCertificate(
			file_get_contents(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt'),
			'imap.localhost.ca.crt'
		);

		$this->importCertificate('user@imap.localhost');
		$this->importCertificate('user@domain.tld');
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->certificateManager->removeCertificate('domain.tld.ca.crt');
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

		$this->assertEquals("Just some encrypted test images.\n\n", $message->getPlainBody());
		$this->assertCount(3, $message->attachments);
		$this->assertTrue($message->isEncrypted());
		// TODO: $this->assertTrue($message->isSigned());
		// TODO: $this->assertTrue($message->isSignatureValid());
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

		$this->assertEquals("hoi\n\n", $message->getPlainBody());
		$this->assertTrue($message->isEncrypted());
		// TODO: $this->assertTrue($message->isSigned());
		// TODO: $this->assertTrue($message->isSignatureValid());
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

		$this->assertEquals("This is a signed message.\n\n", $message->getPlainBody());
		$this->assertFalse($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
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

		$this->assertEquals("hoi\n\n", $message->getPlainBody());
		$this->assertFalse($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
	}
}
