<?php


namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\Db\TrustedSender;
use OCA\Mail\Service\InternalAddressService;
use OCA\Mail\Service\TrustedSenderService;
use OCA\Mail\UserMigration\Service\InternalAddressesMigrationService;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCA\UserMigration\ExportDestination;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class InternalAddressesMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private InternalAddressService $internalAddressService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->internalAddressService = $this->createMock(InternalAddressService::class);
	}

	public function testExportsMultipleInternalAddresses(): void {
		$trustedSendersList = [$this->getTrustedIndividual(), $this->getTrustedDomain()];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE, json_encode($trustedSendersList));

		$this->internalAddressService->method('getInternalAddresses')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new InternalAddressesMigrationService($this->internalAddressService, $this->l);
		$service->exportInternalAddresses($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneInternalAddress(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE, json_encode($trustedSendersList));

		$this->internalAddressService->method('getInternalAddresses')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new InternalAddressesMigrationService($this->internalAddressService, $this->l);
		$service->exportInternalAddresses($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleInternalAddresses(): void {
		$trustedIndividual = $this->getTrustedIndividual();
		$trustedDomain = $this->getTrustedDomain();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())->method('getFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)->willReturn(json_encode($trustedSendersList));

		$this->internalAddressService->expects(self::exactly(2))->method('add')->with(self::USER_ID, self::callback(function ($email) use ($trustedIndividual, $trustedDomain) {
			return $email === $trustedIndividual->getAddress() || $email === $trustedDomain->getAddress();
		}), self::callback(function ($type) use ($trustedIndividual, $trustedDomain) {
			return $type === $trustedIndividual->getType() || $type === $trustedDomain->getType();
		}));

		$service = new InternalAddressesMigrationService($this->internalAddressService, $this->l);
		$service->importInternalAddresses($this->user, $this->importSource);
	}

	public function testImportNoneInternalAddress(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)->willReturn(json_encode($trustedSendersList));
		$this->internalAddressService->expects(self::never())->method('add');
		$service = new InternalAddressesMigrationService($this->internalAddressService, $this->l);
		$service->importInternalAddresses($this->user, $this->importSource);
	}

	private function getTrustedIndividual(): InternalAddress {
		$individualSender = new InternalAddress;

		$individualSender->setId(1);
		$individualSender->setUserId(self::USER_ID);
		$individualSender->setAddress("max@mustermann.com");
		$individualSender->setType("individual");

		return $individualSender;
	}

	private function getTrustedDomain(): InternalAddress {
		$domainSender = new InternalAddress();

		$domainSender->setId(2);
		$domainSender->setUserId(self::USER_ID);
		$domainSender->setAddress('nextcloud.com');
		$domainSender->setType('domain');

		return $domainSender;
	}
}
