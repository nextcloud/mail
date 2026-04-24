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
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCA\Mail\UserMigration\Service\SMIMEMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class SMIMEMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private SMIMEMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(SMIMEMigrationService::class);

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

	public function testExportsMultipleCertificates(): void {
		$userCertificate = $this->getUserCertificate();
		$chainCertificate = $this->getChainCertificate();
		$this->exportDestination->expects(self::exactly(2))->method('addFileContents')->with(self::callback(function ($filename) use ($userCertificate, $chainCertificate): bool {
			return $filename === str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$userCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES)
				|| $filename === str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$chainCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES);

		}), self::callback(function ($fileContent) use ($userCertificate, $chainCertificate): bool {
			if (str_contains($fileContent, '"id":1')) {
				$expectedString['id'] = $userCertificate->getId();
				$expectedString['certificate'] = $userCertificate->getCertificate();
				$expectedString['privateKey'] = $userCertificate->getPrivateKey();
			} else {
				$expectedString['id'] = $chainCertificate->getId();
				$expectedString['certificate'] = $chainCertificate->getCertificate();
				$expectedString['privateKey'] = null;
			}

			return $fileContent === json_encode($expectedString);
		}));
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

		$certificateFiles = [str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$userCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES),
			str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, (string)$chainCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES)];

		$this->importSource->expects(self::once())->method('pathExists')->willReturn(true);
		$this->importSource->expects(self::once())->method('getFolderListing')->willReturn($certificateFiles);

		$this->importSource->expects(self::exactly(2))->method('getFileContents')->with(self::callback(function ($filename) use ($certificateFiles): bool {
			return in_array($filename, $certificateFiles, true);
		}))->willReturnCallback(function ($filename) use ($userCertificate, $chainCertificate): string {
			$expectedString = [];

			if (str_contains($filename, "{$userCertificate->getId()}.json")) {
				$expectedString['id'] = $userCertificate->getId();
				$expectedString['certificate'] = $userCertificate->getCertificate();
				$expectedString['privateKey'] = $userCertificate->getPrivateKey();
			} elseif (str_contains($filename, "{$chainCertificate->getId()}.json")) {
				$expectedString['id'] = $chainCertificate->getId();
				$expectedString['certificate'] = $chainCertificate->getCertificate();
				$expectedString['privateKey'] = null;
			}

			return json_encode($expectedString);
		});

		$this->serviceMock->getParameter('smimeService')->expects(self::exactly(2))->method('createCertificate')->with(self::USER_ID, self::callback(function (string $certificate) use ($userCertificate, $chainCertificate): bool {
			return $certificate === $userCertificate->getCertificate() || $certificate === $chainCertificate->getCertificate();
		}), self::callback(function (?string $privateKey) use ($userCertificate, $chainCertificate): bool {
			return $privateKey === null || $privateKey === $userCertificate->getPrivateKey();
		}))->willReturnCallback(function () {
			$newCertificate = new SmimeCertificate();
			$newCertificate->setId(random_int(10, 999));
			return $newCertificate;
		});

		$mappedCertificates = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);

		$this->assertCount(2, $mappedCertificates);
		$this->assertArrayHasKey($userCertificate->getId(), $mappedCertificates);
		$this->assertIsInt($mappedCertificates[$userCertificate->getId()]);
		$this->assertArrayHasKey($chainCertificate->getId(), $mappedCertificates);
		$this->assertIsInt($mappedCertificates[$chainCertificate->getId()]);
	}

	public function testImportNoCertificates(): void {
		$this->importSource->expects(self::once())->method('pathExists')->willReturn(false);
		$this->importSource->expects(self::never())->method('getFolderListing');
		$this->importSource->expects(self::never())->method('getFileContents');
		$this->serviceMock->getParameter('smimeService')->expects(self::never())->method('createCertificate');
		$mappedTags = $this->migrationService->importCertificates($this->user, $this->importSource, $this->output);
		$this->assertCount(0, $mappedTags);
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
