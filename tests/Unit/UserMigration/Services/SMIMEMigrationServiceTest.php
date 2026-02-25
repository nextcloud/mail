<?php

namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\SmimeCertificate;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Model\EnrichedSmimeCertificate;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCA\Mail\UserMigration\Service\SMIMEMigrationService;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class SMIMEMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;

	private ICrypto $crypto;
	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private SmimeService $smimeService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->crypto->method('encrypt')->willReturnCallback(function (string $data): string {
			return "Encrypted: {$data}";
		});
		$this->crypto->method('decrypt')->willReturnCallback(function (string $data): string {
			return "Decrypted: {$data}";
		});

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->smimeService = $this->createMock(SmimeService::class);
	}

	public function testExportsMultipleCertificates(): void {
		$userCertificate = $this->getUserCertificate();
		$chainCertificate = $this->getUnencryptedChainCertificate();
		$trustedSendersList = [$userCertificate, $chainCertificate];
		$this->exportDestination->expects(self::exactly(2))->method('addFileContents')->with(self::callback(function ($filename) use ($userCertificate, $chainCertificate): bool {
			return $filename === str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, $userCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES) ||
				$filename === str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, $chainCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES);

		}), self::callback(function ($fileContent) use ($userCertificate, $chainCertificate): bool {
			if(str_contains($fileContent, '"id":1')){
				$expectedString['id'] = $userCertificate->getId();
				$expectedString['certificate'] = "Decrypted: {$userCertificate->getCertificate()}";
				$expectedString['privateKey'] = "Decrypted: {$userCertificate->getPrivateKey()}";
			} else {
				$expectedString['id'] = $chainCertificate->getId();
				$expectedString['certificate'] = "Decrypted: {$chainCertificate->getCertificate()}";
				$expectedString['privateKey'] = null;
			}

			return $fileContent === json_encode($expectedString);
		}));
		$this->crypto->expects(self::exactly(3))->method('decrypt');

		$this->smimeService->method('findAllCertificates')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new SMIMEMigrationService($this->smimeService, $this->crypto, $this->l);
		$service->exportCertificates($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoCertificates(): void {
		$certificateFiles = [];

		$this->smimeService->method('findAllCertificates')->with(self::USER_ID)->willReturn($certificateFiles);
		$this->exportDestination->expects(self::never())->method('addFileContents');

		$service = new SMIMEMigrationService($this->smimeService, $this->crypto, $this->l);
		$service->exportCertificates($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleCertificates(): void {
		$userCertificate = $this->getUserCertificate();
		$chainCertificate = $this->getUnencryptedChainCertificate();

		$certificateFiles = [str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, $userCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES),
			str_replace(MailAccountMigrator::FILENAME_PLACEHOLDER, $chainCertificate->getId(), SMIMEMigrationService::SMIME_CERTIFICATE_FILES)];

		$this->importSource->expects(self::once())->method('pathExists')->willReturn(true);
		$this->importSource->expects(self::once())->method('getFolderListing')->willReturn($certificateFiles);

		$this->importSource->expects(self::exactly(2))->method('getFileContents')->with(self::callback(function ($filename) use ($certificateFiles): bool {
			return array_find($certificateFiles, function ($certificateFile) use ($filename): bool {
				return $filename === $certificateFile;
			});
		}))->willReturnCallback(function ($filename) use ($userCertificate, $chainCertificate): string {
			$expectedString = [];

			if(str_contains($filename, "{$userCertificate->getId()}.json")) {
				$expectedString['id'] = $userCertificate->getId();
				$expectedString['certificate'] = "Decrypted: {$userCertificate->getCertificate()}";
				$expectedString['privateKey'] = "Decrypted: {$userCertificate->getPrivateKey()}";
			} elseif (str_contains($filename, "{$chainCertificate->getId()}.json")) {
				$expectedString['id'] = $chainCertificate->getId();
				$expectedString['certificate'] = "Decrypted: {$chainCertificate->getCertificate()}";
				$expectedString['privateKey'] = null;
			}

			return json_encode($expectedString);
		});

		$this->smimeService->expects(self::exactly(2))->method('createCertificate')->with(self::USER_ID, self::callback(function (string $certificate) use ($userCertificate, $chainCertificate): bool {
			return $certificate === "Decrypted: {$userCertificate->getCertificate()}" || $certificate === "Decrypted: {$chainCertificate->getCertificate()}";
		}), self::callback(function (?string $privateKey) use ($userCertificate, $chainCertificate): bool {
			return $privateKey === null || $privateKey === "Decrypted: {$userCertificate->getPrivateKey()}";
		}))->willReturnCallback(function () {
			$newCertificate = new SmimeCertificate();
			$newCertificate->setId(random_int(10, 999));
			return $newCertificate;
		});

		$service = new SMIMEMigrationService($this->smimeService, $this->crypto, $this->l);
		$mappedCertificates = $service->importCertificates($this->user, $this->importSource);

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
		$this->smimeService->expects(self::never())->method('createCertificate');
		$service = new SMIMEMigrationService($this->smimeService, $this->crypto, $this->l);
		$mappedTags = $service->importCertificates($this->user, $this->importSource);
		$this->assertCount(0, $mappedTags);
	}

	private function getUserCertificate(): SmimeCertificate {
		$individualSender = new SmimeCertificate();;

		$individualSender->setId(1);
		$individualSender->setUserId(self::USER_ID);
		$individualSender->setEmailAddress('user@domain.tld');
		$publicKey = file_get_contents(__DIR__ . '/../../../data/smime-certs/user@domain.tld.crt');
		$privateKey = file_get_contents(__DIR__ . '/../../../data/smime-certs/user@domain.tld.key');
		$individualSender->setCertificate($publicKey);
		$individualSender->setPrivateKey($privateKey);

		return $individualSender;
	}

	public function getUserCertificateString(): SmimeCertificate {
		$certificate = $this->getUserCertificate();
		$expectedString = $certificate->jsonSerialize();
		$expectedString['certificate'] = $certificate->getCertificate();
		$expectedString['privateKey'] = $certificate->getPrivateKey();
		return $expectedString;
	}

	private function getUnencryptedChainCertificate(): SmimeCertificate {
		$domainSender = new SmimeCertificate();

		$domainSender->setId(2);
		$domainSender->setUserId(self::USER_ID);
		$domainSender->setEmailAddress('chain@imap.localhost');
		$publicKey = file_get_contents(__DIR__ . '/../../../data/smime-certs/chain@imap.localhost.crt');
		$domainSender->setCertificate($publicKey);
		$domainSender->setPrivateKey(null);

		return $domainSender;
	}

	private function userIdMatches(Tag $tag): bool {
		return $tag->getUserId() === self::USER_ID;
	}

	private function displayNameMatches(Tag $tag): bool {
		$testing = $this->getUserCertificate(true);
		$successful = $this->getUnencryptedChainCertificate(true);

		return $tag->getDisplayName() === $testing->getDisplayName() || $tag->getDisplayName() === $successful->getDisplayName();
	}

	private function imapLabelMatches(Tag $tag): bool {
		$testing = $this->getUserCertificate(true);
		$successful = $this->getUnencryptedChainCertificate(true);

		return $tag->getImapLabel() === $testing->getImapLabel() || $tag->getImapLabel() === $successful->getImapLabel();
	}

	private function colorMatches(Tag $tag): bool {
		$testing = $this->getUserCertificate(true);
		$successful = $this->getUnencryptedChainCertificate(true);

		return $tag->getColor() === $testing->getColor() || $tag->getColor() === $successful->getColor();
	}

	private function isDefaultTagMatches(Tag $tag): bool {
		$testing = $this->getUserCertificate(true);
		$successful = $this->getUnencryptedChainCertificate(true);

		return $tag->getIsDefaultTag() === $testing->getIsDefaultTag() || $tag->getIsDefaultTag() === $successful->getIsDefaultTag();
	}
}
