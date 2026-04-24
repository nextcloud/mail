<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\UserMigration\Service\InternalAddressesMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class InternalAddressesMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private InternalAddressesMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(InternalAddressesMigrationService::class);
		$this->migrationService = $this->serviceMock->getService();

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
	}

	public function testExportsMultipleInternalAddresses(): void {
		$trustedSendersList = [$this->getTrustedIndividual(), $this->getTrustedDomain()];
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('internalAddressService')
			->method('getInternalAddresses')
			->with(self::USER_ID)
			->willReturn($trustedSendersList);

		$this->migrationService->exportInternalAddresses($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneInternalAddress(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('internalAddressService')
			->method('getInternalAddresses')
			->with(self::USER_ID)
			->willReturn($trustedSendersList);

		$this->migrationService->exportInternalAddresses($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleInternalAddresses(): void {
		$trustedIndividual = $this->getTrustedIndividual();
		$trustedDomain = $this->getTrustedDomain();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)
			->willReturn(json_encode($trustedSendersList));

		$callCount = 0;
		$expectedAddresses = [$trustedIndividual, $trustedDomain];
		$this->serviceMock
			->getParameter('internalAddressService')->expects(self::exactly(2))->method('add')
			->willReturnCallback(function (string $uid, string $address, string $type) use (
				&$callCount,
				$expectedAddresses
			): void {
				$expected = $expectedAddresses[$callCount];
				self::assertSame(self::USER_ID, $uid);
				self::assertSame($expected->getAddress(), $address);
				self::assertSame($expected->getType(), $type);
				$callCount++;
			});

		$this->migrationService->importInternalAddresses($this->user, $this->importSource, $this->output);
	}

	public static function provideFileContentsWithNoInternalAddressesImported(): array {
		return [
			'empty list' => [json_encode([])],
			'invalid JSON' => ['this is not valid json {{{'],
			'JSON object instead of list' => [json_encode(['unexpected' => 'object'])],
		];
	}

	/**
	 * @dataProvider provideFileContentsWithNoInternalAddressesImported
	 */
	public function testImportEmptyOrInvalidInternalAddresses(string $fileContents): void {
		$this->importSource
			->expects(self::once())->method('getFileContents')
			->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)
			->willReturn($fileContents);
		$this->serviceMock->getParameter('internalAddressService')->expects(self::never())->method('add');
		$this->migrationService->importInternalAddresses($this->user, $this->importSource, $this->output);
	}

	public function testImportNoFileIsBeingIgnored(): void {
		$this->importSource
			->expects(self::once())
			->method('getFileContents')
			->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)
			->willThrowException(new UserMigrationException());
		$this->serviceMock->getParameter('internalAddressService')->expects(self::never())->method('add');

		$this->migrationService->importInternalAddresses($this->user, $this->importSource, $this->output);
	}

	private function getTrustedIndividual(): InternalAddress {
		$individualSender = new InternalAddress;

		$individualSender->setId(1);
		$individualSender->setUserId(self::USER_ID);
		$individualSender->setAddress('max@mustermann.com');
		$individualSender->setType('individual');

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
