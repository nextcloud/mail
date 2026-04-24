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
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('internalAddressService')->method('getInternalAddresses')->with(self::USER_ID)->willReturn($trustedSendersList);

		$this->migrationService->exportInternalAddresses($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneInternalAddress(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('internalAddressService')->method('getInternalAddresses')->with(self::USER_ID)->willReturn($trustedSendersList);

		$this->migrationService->exportInternalAddresses($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleInternalAddresses(): void {
		$trustedIndividual = $this->getTrustedIndividual();
		$trustedDomain = $this->getTrustedDomain();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())->method('getFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)->willReturn(json_encode($trustedSendersList));

		$this->serviceMock->getParameter('internalAddressService')->expects(self::exactly(2))->method('add')->with(self::USER_ID, self::callback(function ($email) use ($trustedIndividual, $trustedDomain) {
			return $email === $trustedIndividual->getAddress() || $email === $trustedDomain->getAddress();
		}), self::callback(function ($type) use ($trustedIndividual, $trustedDomain) {
			return $type === $trustedIndividual->getType() || $type === $trustedDomain->getType();
		}));

		$this->migrationService->importInternalAddresses($this->user, $this->importSource, $this->output);
	}

	public function testImportNoneInternalAddress(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(InternalAddressesMigrationService::INTERNAL_ADDRESSES_FILE)->willReturn(json_encode($trustedSendersList));
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
