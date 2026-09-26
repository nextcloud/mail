<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeCertificateParserException;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCA\Mail\UserMigration\Service\SmimeMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class SmimeMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private SmimeMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(SmimeMigrationService::class);

		$this->serviceMock->getParameter('crypto')
			->method('encrypt')
			->willReturnCallback(function (string $value) {
				return $value . '_encrypted';
			});

		$this->serviceMock->getParameter('crypto')
			->method('decrypt')
			->willReturnCallback(function (string $encryptedValue) {
				if (!str_ends_with($encryptedValue, '_encrypted')) {
					throw new Exception('Invalid encrypted value');
				}
				return substr($encryptedValue, 0, strlen($encryptedValue) - strlen('_encrypted'));
			});

		$this->migrationService = $this->serviceMock->getService();
	}

	public function testImportWithoutCertificateFolderImportsNothing(): void {
		$this->importSource->expects(self::once())
			->method('getFolderListing')
			->with(SmimeMigrationService::SMIME_CERTIFICATE_FOLDER)
			->willReturn([]);
		$this->serviceMock->getParameter('smimeService')->expects(self::never())->method('createCertificate');

		$mappedCertificates = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);

		self::assertSame([], $mappedCertificates);
	}

	public function testExportWithoutCertificates(): void {
		$this->serviceMock->getParameter('smimeService')
			->method('findAllCertificates')
			->with(self::USER_ID)
			->willReturn([]);

		$this->exportDestination->expects(self::never())->method('addFileContents');

		$this->migrationService->exportCertificates($this->user, $this->exportDestination, $this->output);
	}

	public function testEstimatedExportSizeMeasuresKeyMaterial(): void {
		$certificate = new SmimeCertificate();
		$certificate->setCertificate(str_repeat('c', 3000));
		$certificate->setPrivateKey(str_repeat('k', 2000));

		$this->serviceMock->getParameter('smimeService')
			->method('findAllCertificates')
			->with(self::USER_ID)
			->willReturn([$certificate]);

		$size = $this->migrationService->getEstimatedExportSize($this->user);

		self::assertSame(SmimeMigrationService::ESTIMATED_CERTIFICATE_SIZE + 3000 + 2000, $size);
	}

	public function testEstimatedExportSizeWithoutPrivateKey(): void {
		$certificate = new SmimeCertificate();
		$certificate->setCertificate(str_repeat('c', 3000));

		$this->serviceMock->getParameter('smimeService')
			->method('findAllCertificates')
			->willReturn([$certificate]);

		self::assertSame(SmimeMigrationService::ESTIMATED_CERTIFICATE_SIZE + 3000, $this->migrationService->getEstimatedExportSize($this->user));
	}

	public function testExportsMultipleCertificates(): void {
		$userCertificate = $this->getUserCertificate();
		$chainCertificate = $this->getChainCertificate();
		$callCount = 0;
		$expectedCertificates = [$userCertificate, $chainCertificate];
		$this->exportDestination->expects(self::exactly(2))->method('addFileContents')
			->willReturnCallback(function (string $filename, string $fileContent) use (&$callCount, $expectedCertificates): void {
				$expected = $expectedCertificates[$callCount];
				self::assertSame(
					str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$expected->getId(), SmimeMigrationService::SMIME_CERTIFICATE_FILES),
					$filename
				);
				self::assertSame(
					json_encode(['id' => $expected->getId(), 'certificate' => $expected->getCertificate(), 'privateKey' => $expected->getPrivateKey()]),
					$fileContent
				);
				$callCount++;
			});
		$this->serviceMock->getParameter('crypto')->expects(self::exactly(3))->method('decrypt');

		$allEncryptedCertificates = [$this->getEncryptedSmimeCertificate($userCertificate), $this->getEncryptedSmimeCertificate($chainCertificate)];
		$this->serviceMock->getParameter('smimeService')->method('findAllCertificates')->with(self::USER_ID)->willReturn($allEncryptedCertificates);
		$this->migrationService->exportCertificates($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoCertificates(): void {
		$certificateFiles = [];

		$this->serviceMock->getParameter('smimeService')->method('findAllCertificates')->with(self::USER_ID)->willReturn($certificateFiles);
		$this->exportDestination->expects(self::never())->method('addFileContents');

		$this->migrationService->exportCertificates($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleCertificates(): void {
		$userCertificate = $this->getUserCertificate();
		$chainCertificate = $this->getChainCertificate();

		// getFolderListing() returns names relative to the folder
		$certificateFileNames = [$userCertificate->getId() . '.json', $chainCertificate->getId() . '.json'];
		$certificateFiles = array_map(
			static fn (string $name) => SmimeMigrationService::SMIME_CERTIFICATE_FOLDER . $name,
			$certificateFileNames,
		);

		$this->importSource->expects(self::once())->method('getFolderListing')->willReturn($certificateFileNames);

		$expectedCertificates = [$userCertificate, $chainCertificate];

		$getFileContentsCallCount = 0;
		$this->importSource->expects(self::exactly(2))->method('getFileContents')
			->willReturnCallback(function (string $filename) use (&$getFileContentsCallCount, $certificateFiles, $expectedCertificates): string {
				$expected = $expectedCertificates[$getFileContentsCallCount];
				self::assertSame($certificateFiles[$getFileContentsCallCount], $filename);
				$result = json_encode(['id' => $expected->getId(), 'certificate' => $expected->getCertificate(), 'privateKey' => $expected->getPrivateKey()]);
				$getFileContentsCallCount++;
				return $result;
			});

		$createCertificateCallCount = 0;
		$this->serviceMock->getParameter('smimeService')->expects(self::exactly(2))->method('createCertificate')
			->willReturnCallback(function (string $userId, string $certificate, ?string $privateKey) use (&$createCertificateCallCount, $expectedCertificates): SmimeCertificate {
				$expected = $expectedCertificates[$createCertificateCallCount];
				self::assertSame(self::USER_ID, $userId);
				self::assertSame($expected->getCertificate(), $certificate);
				self::assertSame($expected->getPrivateKey(), $privateKey);
				$newCertificate = new SmimeCertificate();
				$newCertificate->setId(random_int(10, 999));
				$createCertificateCallCount++;
				return $newCertificate;
			});

		$mappedCertificates = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);

		$this->assertCount(2, $mappedCertificates);
		$this->assertArrayHasKey($userCertificate->getId(), $mappedCertificates);
		$this->assertIsInt($mappedCertificates[$userCertificate->getId()]);
		$this->assertArrayHasKey($chainCertificate->getId(), $mappedCertificates);
		$this->assertIsInt($mappedCertificates[$chainCertificate->getId()]);
	}

	public function testImportNoCertificatesFolder(): void {
		$this->importSource->expects(self::once())
			->method('getFolderListing')
			->with(SmimeMigrationService::SMIME_CERTIFICATE_FOLDER)
			->willReturn([]);
		$this->importSource->expects(self::never())->method('getFileContents');
		$this->serviceMock->getParameter('smimeService')->expects(self::never())->method('createCertificate');
		$mappedTags = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);
		$this->assertCount(0, $mappedTags);
	}

	public static function provideCreateCertificateFailures(): array {
		return [
			'private key mismatch' => [new ServiceException('Private key does not match certificate')],
			'unparsable certificate' => [new SmimeCertificateParserException('Could not parse certificate')],
		];
	}

	/**
	 * @dataProvider provideCreateCertificateFailures
	 */
	public function testImportSkipsCertificatesThatCannotBeCreated(Exception $failure): void {
		$userCertificate = $this->getUserCertificate();
		$chainCertificate = $this->getChainCertificate();

		$this->importSource->expects(self::once())->method('getFolderListing')
			->willReturn(['1.json', '2.json']);
		$this->importSource->method('getFileContents')
			->willReturnCallback(function (string $path) use ($userCertificate, $chainCertificate): string {
				$certificate = str_ends_with($path, '1.json') ? $userCertificate : $chainCertificate;
				return json_encode(['id' => $certificate->getId(),
					'certificate' => $certificate->getCertificate(),
					'privateKey' => $certificate->getPrivateKey()]);
			});

		$this->serviceMock->getParameter('smimeService')
			->expects(self::exactly(2))
			->method('createCertificate')
			->willReturnCallback(function (string $userId, string $certificate) use ($userCertificate, $failure): SmimeCertificate {
				if ($certificate === $userCertificate->getCertificate()) {
					throw $failure;
				}
				$created = new SmimeCertificate();
				$created->setId(500);
				return $created;
			});

		$mappedCertificates = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);

		self::assertSame([$chainCertificate->getId() => 500], $mappedCertificates);
	}

	public static function provideFileContentsWithNoCertificatesImported(): array {
		return [
			'empty list' => [json_encode([])],
			'invalid JSON' => ['this is not valid json {{{'],
			'missing required fields' => [json_encode(['unexpected' => 'field'])],
		];
	}

	/**
	 * @dataProvider provideFileContentsWithNoCertificatesImported
	 */
	public function testImportEmptyOrInvalidCertificates(string $fileContents): void {
		$certificateFile = SmimeMigrationService::SMIME_CERTIFICATE_FOLDER . '1.json';
		$this->importSource->expects(self::once())->method('getFolderListing')->willReturn(['1.json']);
		$this->importSource->expects(self::once())->method('getFileContents')->with($certificateFile)->willReturn($fileContents);
		$this->serviceMock->getParameter('smimeService')->expects(self::never())->method('createCertificate');
		$mappedCertificates = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);
		$this->assertCount(0, $mappedCertificates);
	}

	private function getUserCertificate(): SmimeCertificate {
		$certificate = new SmimeCertificate();

		$certificate->setId(1);
		$certificate->setUserId(self::USER_ID);
		$certificate->setEmailAddress('user@domain.tld');
		$publicKey = file_get_contents(__DIR__ . '/../../../data/smime-certs/user@domain.tld.crt');
		$privateKey = file_get_contents(__DIR__ . '/../../../data/smime-certs/user@domain.tld.key');
		$certificate->setCertificate($publicKey);
		$certificate->setPrivateKey($privateKey);

		return $certificate;
	}

	private function getChainCertificate(): SmimeCertificate {
		$certificate = new SmimeCertificate();

		$certificate->setId(2);
		$certificate->setUserId(self::USER_ID);
		$certificate->setEmailAddress('chain@imap.localhost');
		$publicKey = file_get_contents(__DIR__ . '/../../../data/smime-certs/chain@imap.localhost.crt');
		$certificate->setCertificate($publicKey);
		$certificate->setPrivateKey(null);

		return $certificate;
	}

	private function getEncryptedSmimeCertificate(SmimeCertificate $certificateToEncrypt): SmimeCertificate {
		$encryptedCertificate = new SmimeCertificate();

		$encryptedCertificate->setId($certificateToEncrypt->getId());
		$encryptedCertificate->setUserId($certificateToEncrypt->getUserId());
		$encryptedCertificate->setEmailAddress($certificateToEncrypt->getEmailAddress());
		$encryptedCertificate->setCertificate("{$certificateToEncrypt->getCertificate()}_encrypted");
		$encryptedCertificate->setPrivateKey($certificateToEncrypt->getPrivateKey() !== null ? "{$certificateToEncrypt->getPrivateKey()}_encrypted" : null);

		return $encryptedCertificate;
	}
}
