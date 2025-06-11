<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Data_Envelope;
use Horde_Imap_Client_Data_Fetch;
use Horde_Mime_Headers;
use Horde_Mime_Headers_ContentParam_ContentType;
use Horde_Mime_Part;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\SmimeCertificateMapper;
use OCA\Mail\Model\SmimeCertificateInfo;
use OCA\Mail\Model\SmimeCertificatePurposes;
use OCA\Mail\Service\SmimeService;
use OCP\ICertificateManager;
use OCP\ITempManager;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

class SmimeServiceTest extends TestCase {
	private $tempFiles = [];

	/** @var ITempManager|MockObject */
	private $tempManager;

	/** @var ICertificateManager|MockObject */
	private $certificateManager;

	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var SmimeCertificateMapper|MockObject */
	private $certificateMapper;

	/** @var SmimeService&MockObject */
	private $smimeService;

	protected function setUp(): void {
		parent::setUp();

		$this->tempManager = $this->createMock(ITempManager::class);
		$this->certificateManager = $this->createMock(ICertificateManager::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->certificateMapper = $this->createMock(SmimeCertificateMapper::class);

		$this->smimeService = new SmimeService(
			$this->tempManager,
			$this->certificateManager,
			$this->crypto,
			$this->certificateMapper,
		);
	}

	protected function tearDown(): void {
		parent::tearDown();

		foreach ($this->tempFiles as $tempFile) {
			unlink($tempFile);
		}
		$this->tempFiles = [];
	}

	private function getTestCertificate(string $emailAddress, string $userId = 'user'): SmimeCertificate {
		$rawCert = file_get_contents(__DIR__ . "/../../data/smime-certs/{$emailAddress}.crt");
		$rawKey = file_get_contents(__DIR__ . "/../../data/smime-certs/{$emailAddress}.key");

		$certificate = new SmimeCertificate();
		$certificate->setId(42);
		$certificate->setUserId($userId);
		$certificate->setEmailAddress($emailAddress);
		$certificate->setCertificate($rawCert);
		$certificate->setPrivateKey($rawKey);
		return $certificate;
	}

	private function createTempFile(): string {
		$n = count($this->tempFiles);
		$tempFile = "/tmp/mail-smime-service-temp-{$n}";
		touch($tempFile);
		$this->tempFiles[] = $tempFile;
		return $tempFile;
	}

	public function testDecryptMimePartText() {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/encrypted-message.txt');
		$decryptedBody = file_get_contents(__DIR__ . '/../../data/decrypted-message-body.txt');

		$certificate = $this->getTestCertificate('user@imap.localhost');
		$this->crypto->expects(self::exactly(2))
			->method('decrypt')
			->willReturnMap([
				[$certificate->getCertificate(), '', $certificate->getCertificate()],
				[$certificate->getPrivateKey(), '', $certificate->getPrivateKey()],
			]);
		$this->tempManager->expects(self::exactly(3))
			->method('getTemporaryFile')
			->willReturnCallback(function () {
				return $this->createTempFile();
			});
		$this->certificateManager->expects(self::once())
			->method('getAbsoluteBundlePath')
			->willReturn(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt');

		$this->assertEquals(
			$decryptedBody,
			$this->smimeService
				->decryptMimePartText($encryptedMessage, $certificate)
				->getDecryptedMessage(),
		);
	}

	public function testDecryptDataFetch(): void {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/encrypted-message.txt');
		$decryptedBody = file_get_contents(__DIR__ . '/../../data/decrypted-message-body.txt');

		$message = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$message->expects(self::once())
			->method('getFullMsg')
			->willReturn($encryptedMessage);
		$headers = new Horde_Mime_Headers();
		$contentType = new Horde_Mime_Headers_ContentParam_ContentType('', 'application/pkcs7-mime');
		$contentType['smime-type'] = 'enveloped-data';
		$headers['content-type'] = $contentType;
		$message->expects(self::once())
			->method('getHeaderText')
			->with('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
			->willReturn($headers);
		$envelope = new Horde_Imap_Client_Data_Envelope();
		$envelope->to = AddressList::parse('user@imap.localhost')->toHorde();
		$message->expects(self::once())
			->method('getEnvelope')
			->willReturn($envelope);
		$certificate = $this->getTestCertificate('user@imap.localhost');
		$this->certificateMapper->expects(self::once())
			->method('findAllByEmailAddress')
			->with('user', 'user@imap.localhost')
			->willReturn([$certificate]);
		$this->crypto->expects(self::exactly(2))
			->method('decrypt')
			->willReturnMap([
				[$certificate->getCertificate(), '', $certificate->getCertificate()],
				[$certificate->getPrivateKey(), '', $certificate->getPrivateKey()],
			]);
		$this->tempManager->expects(self::exactly(3))
			->method('getTemporaryFile')
			->willReturnCallback(function () {
				return $this->createTempFile();
			});
		$this->certificateManager->expects(self::once())
			->method('getAbsoluteBundlePath')
			->willReturn(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt');

		$this->assertEquals(
			$decryptedBody,
			$this->smimeService->decryptDataFetch($message, 'user')->getDecryptedMessage(),
		);
	}

	public function testDecryptDataFetchWithOpaqueSignedData(): void {
		$encryptedMessage = file_get_contents(__DIR__ . '/../../data/encrypted-signed-opaque-message.txt');
		$decryptedBody = file_get_contents(__DIR__ . '/../../data/decrypted-signed-opaque-message-body.txt');

		$message = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$message->expects(self::once())
			->method('getFullMsg')
			->willReturn($encryptedMessage);
		$headers = new Horde_Mime_Headers();
		$contentType = new Horde_Mime_Headers_ContentParam_ContentType('', 'application/pkcs7-mime');
		$contentType['smime-type'] = 'enveloped-data';
		$headers['content-type'] = $contentType;
		$message->expects(self::once())
			->method('getHeaderText')
			->with('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
			->willReturn($headers);
		$envelope = new Horde_Imap_Client_Data_Envelope();
		$envelope->to = AddressList::parse('user@imap.localhost')->toHorde();
		$message->expects(self::once())
			->method('getEnvelope')
			->willReturn($envelope);
		$certificate = $this->getTestCertificate('user@imap.localhost');
		$this->certificateMapper->expects(self::once())
			->method('findAllByEmailAddress')
			->with('user', 'user@imap.localhost')
			->willReturn([$certificate]);
		$this->crypto->expects(self::exactly(2))
			->method('decrypt')
			->willReturnMap([
				[$certificate->getCertificate(), '', $certificate->getCertificate()],
				[$certificate->getPrivateKey(), '', $certificate->getPrivateKey()],
			]);
		$this->tempManager->expects(self::exactly(5))
			->method('getTemporaryFile')
			->willReturnCallback(function () {
				return $this->createTempFile();
			});
		$this->certificateManager->expects(self::exactly(2))
			->method('getAbsoluteBundlePath')
			->willReturn(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt');

		$this->assertEquals(
			$decryptedBody,
			$this->smimeService->decryptDataFetch($message, 'user')->getDecryptedMessage(),
		);
	}

	public function testDecryptDataFetchWithRegularMessage(): void {
		$messageText = file_get_contents(__DIR__ . '/../../data/mail-message-123.txt');

		$message = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$message->expects(self::once())
			->method('getFullMsg')
			->willReturn($messageText);
		$headers = new Horde_Mime_Headers();
		$headers['content-type'] = new Horde_Mime_Headers_ContentParam_ContentType('', 'multipart/alternative');
		$message->expects(self::once())
			->method('getHeaderText')
			->with('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
			->willReturn($headers);

		$this->assertEquals(
			$messageText,
			$this->smimeService->decryptDataFetch($message, 'user')->getDecryptedMessage(),
		);
	}

	public function provideIsEncryptedData(): array {
		return [
			['application/pkcs7-mime', ['smime-type' => 'enveloped-data'], true],
			['application/pkcs7-mime', ['smime-type' => 'signed-data'], false],
			['application/pkcs7-mime', [], false], // Should not happen in real life but who knows
			['multipart/alternative', [], false],
			['', [], false],
		];
	}

	/**
	 * @dataProvider provideIsEncryptedData
	 */
	public function testIsEncrypted(?string $contentType,
		array $contentTypeParams,
		bool $expectedResult): void {
		$message = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$headers = new Horde_Mime_Headers();
		$contentType = new Horde_Mime_Headers_ContentParam_ContentType('', $contentType);
		foreach ($contentTypeParams as $key => $value) {
			$contentType[$key] = $value;
		}
		$headers['content-type'] = $contentType;
		$message->expects(self::once())
			->method('getHeaderText')
			->with('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
			->willReturn($headers);

		$this->assertEquals($expectedResult, $this->smimeService->isEncrypted($message));
	}

	public function testIsEncryptedWhenHeaderIsMissing(): void {
		$message = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$headers = new Horde_Mime_Headers();
		$message->expects(self::once())
			->method('getHeaderText')
			->with('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
			->willReturn($headers);
		$this->assertFalse($this->smimeService->isEncrypted($message));
	}

	public function testExtractSignedContent(): void {
		$signedMessage = file_get_contents(__DIR__ . '/../../data/signed-opaque-message.txt');
		$verifiedContent = file_get_contents(__DIR__ . '/../../data/decrypted-signed-opaque-message-body.txt');

		$this->tempManager->expects(self::exactly(2))
			->method('getTemporaryFile')
			->willReturnOnConsecutiveCalls(
				$this->createTempFile(),
				$this->createTempFile(),
			);
		$this->certificateManager->expects(self::once())
			->method('getAbsoluteBundlePath')
			->willReturn(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt');

		$this->assertEquals(
			$verifiedContent,
			$this->smimeService->extractSignedContent($signedMessage),
		);
	}
	public function testFindCertificatesByAddressList(): void {
		$addressJohn = Address::fromRaw('John', 'john@foo.bar');
		$addressJane = Address::fromRaw('Jane', 'jane@foo.bar');

		$addressList = new AddressList([
			$addressJohn,
			$addressJane
		]);

		$certificateJohn = new SmimeCertificate();
		$certificateJohn->setId(1);
		$certificateJohn->setUserId('100');
		$certificateJohn->setCertificate('10101010');

		$certificateJane = new SmimeCertificate();
		$certificateJane->setId(2);
		$certificateJane->setUserId('100');
		$certificateJane->setCertificate('10101010');

		$this->certificateMapper
			->method('findAllByEmailAddresses')
			->with(100, ['john@foo.bar', 'jane@foo.bar'])
			->willReturn([$certificateJohn, $certificateJane]);

		$certificates = $this->smimeService->findCertificatesByAddressList($addressList, '100');
		$this->assertCount(2, $certificates);
	}

	public function testEncryptMimePartText() {
		$certificateDomainTld = $this->getTestCertificate('user@domain.tld');
		$certificateImapLocalhost = $this->getTestCertificate('user@imap.localhost');

		$certificates = [
			$certificateDomainTld,
			$certificateImapLocalhost
		];

		$mailBody = file_get_contents(__DIR__ . '/../../../tests/data/mime-html-image.txt');

		$mimePart = new \Horde_Mime_Part();
		$mimePart->setContents($mailBody);

		$this->crypto
			->method('decrypt')
			->will($this->returnArgument(0));
		$this->tempManager
			->method('getTemporaryFile')
			->willReturnCallback(function () {
				return $this->createTempFile();
			});

		$encryptedMimePart = $this->smimeService->encryptMimePart($mimePart, $certificates);
		$encryptedText = $encryptedMimePart->toString([
			'canonical' => true,
			'headers' => true,
			'encode' => Horde_Mime_Part::ENCODE_8BIT,
		]);

		$decryptedTextImapLocalhost = $this->smimeService
			->decryptMimePartText($encryptedText, $certificateImapLocalhost)
			->getDecryptedMessage();
		$decryptedMimePartImapLocalhost = Horde_Mime_Part::parseMessage($decryptedTextImapLocalhost, [
			'forcemime' => true,
		]);

		$decryptionResult = $this->smimeService
			->decryptMimePartText($encryptedText, $certificateDomainTld);
		$decryptedTextDomainTld = $decryptionResult->getDecryptedMessage();
		$decryptedMimePartDomainTld = Horde_Mime_Part::parseMessage($decryptedTextDomainTld, [
			'forcemime' => true,
		]);

		$this->assertEquals($mimePart->getContents(), $decryptedMimePartImapLocalhost->getContents());
		$this->assertEquals($mimePart->getContents(), $decryptedMimePartDomainTld->getContents());
		$this->assertFalse($decryptionResult->isSigned());
	}

	public function provideParseCertificateData(): array {
		return [
			[
				$this->getTestCertificate('user@imap.localhost'),
				new SmimeCertificateInfo(
					'user',
					'user@imap.localhost',
					4862017735,
					new SmimeCertificatePurposes(true, true),
					true,
				),
			],
			[
				$this->getTestCertificate('cn-only@imap.localhost'),
				new SmimeCertificateInfo(
					'cn-only',
					'cn-only@imap.localhost',
					4862017727,
					new SmimeCertificatePurposes(true, true),
					true,
				),
			],
			[
				$this->getTestCertificate('user@domain.tld'),
				new SmimeCertificateInfo(
					'user',
					'user@domain.tld',
					4862017705,
					new SmimeCertificatePurposes(true, true),
					false,
				),
			],
			[ // Full chain: Leaf -> Intermediate CA -> CA
				$this->getTestCertificate('chain@imap.localhost'),
				new SmimeCertificateInfo(
					'chain',
					'chain@imap.localhost',
					4870519697,
					new SmimeCertificatePurposes(true, true),
					true,
				),
			],
			[ // Partial chain: Leaf -> Intermediate CA
				$this->getTestCertificate('chain-partial@imap.localhost'),
				new SmimeCertificateInfo(
					'chain',
					'chain@imap.localhost',
					4870519697,
					new SmimeCertificatePurposes(true, true),
					true,
				),
			],
			[ // Leaf only
				$this->getTestCertificate('chain-leaf-only@imap.localhost'),
				new SmimeCertificateInfo(
					'chain',
					'chain@imap.localhost',
					4870519697,
					new SmimeCertificatePurposes(true, true),
					false,
				),
			],
		];
	}

	/**
	 * @dataProvider provideParseCertificateData
	 */
	public function testParseCertificate(SmimeCertificate $certificate,
		SmimeCertificateInfo $expected): void {
		$this->certificateManager->expects(self::once())
			->method('getAbsoluteBundlePath')
			->willReturn(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt');

		$this->tempManager->expects(self::once())
			->method('getTemporaryFile')
			->willReturnCallback(fn () => $this->createTempFile());

		$this->assertEquals(
			$expected,
			$this->smimeService->parseCertificate($certificate->getCertificate()),
		);
	}

	public function testSignMimePart(): void {
		$plainPart = Horde_Mime_Part::parseMessage(file_get_contents(__DIR__ . '/../../data/html-with-signature.txt'));
		$certificate = $this->getTestCertificate('debug@imap.localhost');

		$this->crypto
			->method('decrypt')
			->will($this->returnArgument(0));
		$this->tempManager
			->method('getTemporaryFile')
			->willReturnCallback(function () {
				return $this->createTempFile();
			});

		// Can't compare to serialized part as the boundaries inside the signed MIME message are
		// generated randomly each time. => Verify the signed part instead.
		$actualPart = $this->smimeService->signMimePart($plainPart, $certificate);
		$messageTemp = $this->createTempFile();
		file_put_contents($messageTemp, $actualPart->toString([
			'canonical' => true,
			'headers' => true,
			'encode' => Horde_Mime_Part::ENCODE_8BIT,
		]));
		$this->assertTrue(openssl_pkcs7_verify(
			$messageTemp,
			0,
			null,
			[__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt'],
		));
	}

	public function testSignAndEncryptMimePart() {
		$certificateDomainTld = $this->getTestCertificate('user@domain.tld');
		$certificateImapLocalhost = $this->getTestCertificate('user@imap.localhost');

		$mailBody = file_get_contents(__DIR__ . '/../../../tests/data/mime-html-unicode.txt');
		$mimePart = Horde_Mime_Part::parseMessage($mailBody);

		$this->crypto
			->method('decrypt')
			->will($this->returnArgument(0));
		$this->tempManager
			->method('getTemporaryFile')
			->willReturnCallback(function () {
				return $this->createTempFile();
			});
		$this->certificateManager->expects(self::once())
			->method('getAbsoluteBundlePath')
			->willReturn(__DIR__ . '/../../data/smime-certs/imap.localhost.ca.crt');

		$signedMimePart = $this->smimeService->signMimePart($mimePart, $certificateImapLocalhost);
		$encryptedMimePart = $this->smimeService->encryptMimePart(
			$signedMimePart,
			[$certificateDomainTld],
		);
		$encryptedText = $encryptedMimePart->toString([
			'canonical' => true,
			'headers' => true,
			'encode' => Horde_Mime_Part::ENCODE_8BIT,
		]);

		$decryptionResult = $this->smimeService
			->decryptMimePartText($encryptedText, $certificateDomainTld);
		$decryptedMimePart = Horde_Mime_Part::parseMessage($decryptionResult->getDecryptedMessage());

		$this->assertEquals(
			$signedMimePart->toString([
				'canonical' => true,
				'headers' => false,
				'encode' => Horde_Mime_Part::ENCODE_8BIT,
			]),
			$decryptedMimePart->toString([
				'canonical' => true,
				'headers' => false,
				'encode' => Horde_Mime_Part::ENCODE_8BIT,
			]),
		);
		$this->assertTrue($decryptionResult->isSigned());
		$this->assertTrue($decryptionResult->isSignatureValid());
	}
}
