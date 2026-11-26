<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\IMAP;

use Horde_Mime_Part;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\SmimeCertificateMapper;
use OCA\Mail\IMAP\ImapMessageFetcherFactory;
use OCA\Mail\Service\SmimeService;
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
	private SmimeCertificate $signingCertificate;

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
		$this->signingCertificate = $this->importCertificate('debug@imap.localhost');
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

		$this->assertEquals(self::LOREM, $message->getPlainBody());
		$this->assertCount(1, $message->attachments);
		$this->assertTrue($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
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

		$this->assertEquals(self::LOREM, $message->getPlainBody());
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

		$this->assertEquals(self::LOREM . "\n", $message->getPlainBody());
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

		$this->assertEquals(self::LOREM, $message->getPlainBody());
		$this->assertFalse($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
	}

	public function testFetchMessageWithSignedMessageWithAttachment(): void {
		$uid = $this->saveMimeMessage('INBOX', $this->createSignedMessageWithAttachment('attachment'));
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM, $message->getPlainBody());
		$this->assertSame([['1.2', 'attachment.bin', 'application/octet-stream']], array_map(
			static fn (array $attachment): array => [$attachment['id'], $attachment['fileName'], $attachment['mime']],
			$message->getAttachments(),
		));
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
	}

	/**
	 * @requires function memory_reset_peak_usage
	 */
	public function testFetchMessageWithLargeSignedMessageDoesNotParseItInMemory(): void {
		$signedMessage = $this->createSignedMessageWithAttachment(random_bytes(2 * 1024 * 1024));
		$uid = $this->saveMimeMessage('INBOX', $signedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);
		gc_collect_cycles();
		memory_reset_peak_usage();
		$memoryBefore = memory_get_usage();

		$message = $fetcher->fetchMessage();

		$this->assertLessThan(3 * strlen($signedMessage), memory_get_peak_usage() - $memoryBefore);
		$this->assertEquals(self::LOREM, $message->getPlainBody());
		$this->assertTrue($message->isSignatureValid());
	}

	public function testFetchMessageWithUnverifiedSignedMessage(): void {
		$signedMessage = $this->createSignedMessageWithAttachment('attachment');
		// Force verification to fail
		$this->certificateManager->removeCertificate('imap.localhost.ca.crt');
		$uid = $this->saveMimeMessage('INBOX', $signedMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertEquals(self::LOREM, $message->getPlainBody());
		$this->assertSame(['attachment.bin'], array_column($message->getAttachments(), 'fileName'));
		$this->assertTrue($message->isSigned());
		$this->assertFalse($message->isSignatureValid());
	}

	public function testFetchMessageWithSignedHtmlMessageWithInlineImage(): void {
		$html = new Horde_Mime_Part();
		$html->setType('text/html');
		$html->setCharset('utf-8');
		$html->setContents('<p>' . self::LOREM . '</p><img src="cid:inline-image">');
		$image = new Horde_Mime_Part();
		$image->setType('image/png');
		$image->setDisposition('inline');
		$image->setContentId('inline-image');
		$image->setContents(file_get_contents(__DIR__ . '/../../data/nextcloud.png'));
		$related = new Horde_Mime_Part();
		$related->setType('multipart/related');
		$related[] = $html;
		$related[] = $image;
		$uid = $this->saveMimeMessage('INBOX', $this->createSignedMessage($related));
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertTrue($message->hasHtmlMessage());
		$this->assertCount(0, $message->attachments);
		$this->assertSame([['1.2', 'inline-image', 'image/png']], array_map(
			static fn (array $attachment): array => [$attachment['id'], $attachment['cid'], $attachment['mime']],
			$message->inlineAttachments,
		));
		$this->assertTrue($message->isSignatureValid());
	}

	private function createSignedMessageWithAttachment(string $attachmentContents): string {
		$text = new Horde_Mime_Part();
		$text->setType('text/plain');
		$text->setCharset('utf-8');
		$text->setContents(self::LOREM);
		$attachment = new Horde_Mime_Part();
		$attachment->setType('application/octet-stream');
		$attachment->setDisposition('attachment');
		$attachment->setName('attachment.bin');
		$attachment->setContents($attachmentContents);
		$content = new Horde_Mime_Part();
		$content->setType('multipart/mixed');
		$content[] = $text;
		$content[] = $attachment;

		return $this->createSignedMessage($content);
	}

	private function createSignedMessage(Horde_Mime_Part $content): string {
		return Server::get(SmimeService::class)
			->signMimePart($content, $this->signingCertificate)
			->toString(['headers' => true]);
	}

	public function aiGeneratedHeaderProvider(): array {
		return [
			[LocalMessage::HEADER_AI_GENERATED . ": 1\r\n", true],
			[LocalMessage::HEADER_AI_GENERATED . ": 0\r\n", false],
			['', false],
		];
	}

	/**
	 * @dataProvider aiGeneratedHeaderProvider
	 */
	public function testFetchMessageWithAiGeneratedHeader(string $header, bool $expected): void {
		$mimeMessage = "From: debug@imap.localhost\r\n"
			. "To: user@imap.localhost\r\n"
			. "Subject: AI generated\r\n"
			. $header
			. "\r\n"
			. "Hello\r\n";
		$uid = $this->saveMimeMessage('INBOX', $mimeMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertSame($expected, $message->jsonSerialize()['hasAiGeneratedHeader']);
	}

	public function testFetchMessageWithInlineImageWithoutFilename(): void {
		$mimeMessage = file_get_contents(__DIR__ . '/../../data/mime-html-cid-no-filename.txt');
		$uid = $this->saveMimeMessage('INBOX', $mimeMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertCount(0, $message->attachments);
		$this->assertCount(1, $message->inlineAttachments);
		$inlineAttachment = $message->inlineAttachments[0];
		$this->assertSame('_1_0FAD84280FACDD0C0038DE9FC1258E02', $inlineAttachment['cid']);
		$this->assertNull($inlineAttachment['fileName']);
		$this->assertSame('image/gif', $inlineAttachment['mime']);
		$this->assertTrue($message->hasHtmlMessage());
	}

	public function testFetchMessageDoesNotTreatHtmlBodyWithCidAsInlineAttachment(): void {
		$mimeMessage = file_get_contents(__DIR__ . '/../../data/mime-html-body-with-cid.txt');
		$uid = $this->saveMimeMessage('INBOX', $mimeMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertCount(0, $message->attachments);
		$this->assertCount(1, $message->inlineAttachments);
		$this->assertSame('image/gif', $message->inlineAttachments[0]['mime']);
		$this->assertTrue($message->hasHtmlMessage());
		$this->assertStringContainsString('Hello', $message->getHtmlBody($uid));
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

		$this->assertEquals(self::LOREM . "\n", $message->getPlainBody());
		$this->assertFalse($message->isEncrypted());
		$this->assertTrue($message->isSigned());
		$this->assertTrue($message->isSignatureValid());
	}

	public function testFetchMessageWithoutBody(): void {
		$mimeMessage = file_get_contents(__DIR__ . '/../../data/nobody-message.txt');
		$uid = $this->saveMimeMessage('INBOX', $mimeMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertFalse($message->isEncrypted());
		$this->assertCount(1, $message->attachments);
		$attachment = $message->attachments[0];
		$this->assertEquals('attach.zip', $attachment['fileName']);
		$this->assertSame('application/zip', $attachment['mime']);
	}

	/**
	 * Proton Mail Bridge strips the `method=` parameter from the
	 * text/calendar Content-Type during E2E re-assembly. The fetcher must
	 * fall back to parsing the METHOD: line out of the ICS body, and
	 * still emit a scheduling entry so downstream rendering picks it up.
	 */
	public function testFetchMessageWithImipRequestMissingMethodParam(): void {
		$rawMessage = file_get_contents(__DIR__ . '/../../data/imip/request_proton_bridge.txt');
		$uid = $this->saveMimeMessage('INBOX', $rawMessage);
		$fetcher = $this->fetcherFactory
			->build(
				$uid,
				'INBOX',
				$this->getTestClient(),
				$this->account->getUserId()
			)
			->withBody(true);

		$message = $fetcher->fetchMessage();

		$this->assertCount(1, $message->scheduling);
		$this->assertSame('REQUEST', $message->scheduling[0]['method']);
		$this->assertStringContainsString('METHOD:REQUEST', $message->scheduling[0]['contents']);
	}
}
