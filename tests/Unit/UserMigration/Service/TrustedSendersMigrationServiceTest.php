<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TrustedSender;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TrustedSendersMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private TrustedSendersMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(TrustedSendersMigrationService::class);
		$this->migrationService = $this->serviceMock->getService();
	}

	public function testExportsMultipleTrustedSenders(): void {
		$trustedSendersList = [$this->getTrustedIndividual(), $this->getTrustedDomain()];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('trustedSenderService')->method('getTrusted')->with(self::USER_ID)->willReturn($trustedSendersList);
		$this->migrationService->exportTrustedSenders($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneTrustedSenders(): void {
		$trustedSendersList = [];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE, json_encode($trustedSendersList));

		$this->serviceMock->getParameter('trustedSenderService')->method('getTrusted')->with(self::USER_ID)->willReturn($trustedSendersList);
		$this->migrationService->exportTrustedSenders($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTrustedSenders(): void {
		$trustedIndividual = $this->getTrustedIndividual();
		$trustedDomain = $this->getTrustedDomain();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn(json_encode($trustedSendersList));

		$this->serviceMock->getParameter('trustedSenderService')->expects(self::exactly(2))->method('trust')->with(self::USER_ID, self::callback(function ($email) use ($trustedIndividual, $trustedDomain) {
			return $email === $trustedIndividual->getEmail() || $email === $trustedDomain->getEmail();
		}), self::callback(function ($type) use ($trustedIndividual, $trustedDomain) {
			return $type === $trustedIndividual->getType() || $type === $trustedDomain->getType();
		}));

		$this->migrationService->importTrustedSenders($this->user, $this->importSource, $this->output);
	}

	public function testImportNoneTrustedSenders(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn(json_encode($trustedSendersList));
		$this->serviceMock->getParameter('trustedSenderService')->expects(self::never())->method('trust');
		$this->migrationService->importTrustedSenders($this->user, $this->importSource, $this->output);
	}

	public function testImportInvalidJsonThrowsException(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn('this is not valid json {[}');

		$this->serviceMock->getParameter('trustedSenderService')->expects(self::never())->method('trust');
		$this->expectException(UserMigrationException::class);

		$this->migrationService->importTrustedSenders($this->user, $this->importSource, $this->output);
	}

	public function testImportEmptyStringThrowsException(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn('');

		$this->serviceMock->getParameter('trustedSenderService')->expects(self::never())->method('trust');
		$this->expectException(UserMigrationException::class);

		$this->migrationService->importTrustedSenders($this->user, $this->importSource, $this->output);
	}

	public function testImportJsonWithNonArrayRootThrowsException(): void {
		$this->importSource->expects(self::once())->method('getFileContents')->with(TrustedSendersMigrationService::TRUSTED_SENDERS_FILE)->willReturn('"just a string"');

		$this->serviceMock->getParameter('trustedSenderService')->expects(self::never())->method('trust');
		$this->expectException(UserMigrationException::class);

		$this->migrationService->importTrustedSenders($this->user, $this->importSource, $this->output);
	}

	private function getTrustedIndividual(): TrustedSender {
		$individualSender = new TrustedSender;

		$individualSender->setId(1);
		$individualSender->setUserId(self::USER_ID);
		$individualSender->setEmail('max@mustermann.com');
		$individualSender->setType('individual');

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
