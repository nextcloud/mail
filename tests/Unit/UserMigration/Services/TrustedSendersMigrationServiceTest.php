<?php

namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TrustedSender;
use OCA\Mail\Service\TrustedSenderService;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCA\UserMigration\ExportDestination;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class TrustedSendersMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private TrustedSenderService $trustedSendersService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->trustedSendersService = $this->createMock(TrustedSenderService::class);
	}

	public function testExportsMultipleTrustedSender(): void {
		$trustedSendersList = [$this->getTrustedIndividual(), $this->getTrustedDomain()];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE, json_encode($trustedSendersList));

		$this->trustedSendersService->method('getTrusted')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new TrustedSendersMigrationService($this->trustedSendersService, $this->l);
		$service->exportTrustedSenders($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneTrustedSenders(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE, json_encode($trustedSendersList));

		$this->trustedSendersService->method('getTrusted')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new TrustedSendersMigrationService($this->trustedSendersService, $this->l);
		$service->exportTrustedSenders($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTrustedSender(): void {
		$trustedIndividual = $this->getTrustedIndividual();
		$trustedDomain = $this->getTrustedDomain();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn(json_encode($trustedSendersList));

		$this->trustedSendersService->expects(self::exactly(2))->method('trust')->with(self::USER_ID, self::callback(function ($email) use ($trustedIndividual, $trustedDomain) {
			return $email === $trustedIndividual->getEmail() || $email === $trustedDomain->getEmail();
}), self::callback(function ($type) use ($trustedIndividual, $trustedDomain) {
	return $type === $trustedIndividual->getType() || $type === $trustedDomain->getType();
		}));

		$service = new TrustedSendersMigrationService($this->trustedSendersService, $this->l);
		$service->importTrustedSenders($this->user, $this->importSource);
	}

	public function testImportNoneTrustedSenders(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn(json_encode($trustedSendersList));
		$this->trustedSendersService->expects(self::never())->method('trust');
		$service = new TrustedSendersMigrationService($this->trustedSendersService, $this->l);
		$service->importTrustedSenders($this->user, $this->importSource);
	}

	private function getTrustedIndividual(): TrustedSender {
		$individualSender = new TrustedSender;

		$individualSender->setId(1);
		$individualSender->setUserId(self::USER_ID);
		$individualSender->setEmail("max@mustermann.com");
		$individualSender->setType("individual");

		return $individualSender;
	}

	private function getTrustedDomain(): TrustedSender {
		$domainSender = new TrustedSender();

		$domainSender->setId(2);
		$domainSender->setUserId(self::USER_ID);
		$domainSender->setEmail('nextcloud.com');
		$domainSender->setType('domain');

		return $domainSender;
	}
}
