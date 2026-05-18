<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration\Service;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\UserMigration\Service\TextBlocksMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TextBlocksMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private TextBlocksMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(TextBlocksMigrationService::class);
		$this->migrationService = $this->serviceMock->getService();
	}

	public function testExportsMultipleTextBlocks(): void {
		$textBlocksList = [$this->getLoremIpsum1(), $this->getIpsumLorem2()];
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE, json_encode($textBlocksList));

		$this->serviceMock->getParameter('textBlockService')
			->method('findAll')
			->with(self::USER_ID)
			->willReturn($textBlocksList);
		$this->migrationService->exportTextBlocks($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneTextBlocks(): void {
		$textBlocksList = [];

		$this->serviceMock->getParameter('textBlockService')
			->method('findAll')
			->with(self::USER_ID)
			->willReturn($textBlocksList);
		$this->exportDestination->expects(self::once())
			->method('addFileContents')
			->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE, json_encode($textBlocksList));

		$this->migrationService->exportTextBlocks($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTextBlocks(): void {
		$textBlock1 = $this->getLoremIpsum1();
		$textBlock2 = $this->getIpsumLorem2();
		$textBlocksList = [$textBlock1, $textBlock2];
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE)
			->willReturn(json_encode($textBlocksList));

		$callCount = 0;
		$expectedBlocks = [$textBlock1, $textBlock2];
		$this->serviceMock->getParameter('textBlockService')
			->expects(self::exactly(2))
			->method('create')
			->willReturnCallback(function (string $uid, string $title, string $content) use (&$callCount, $expectedBlocks): TextBlock {
				$expectedBlock = $expectedBlocks[$callCount];
				self::assertSame(self::USER_ID, $uid);
				self::assertEquals([$expectedBlock->getTitle(), $expectedBlock->getContent()], [$title, $content]);
				$callCount++;
				return $expectedBlock;
			});

		$this->migrationService->importTextBlocks($this->user, $this->importSource, $this->output);
	}

	public function testImportNoneTextBlocks(): void {
		$textBlocksList = [];
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE)
			->willReturn(json_encode($textBlocksList));

		$this->serviceMock->getParameter('textBlockService')
			->expects(self::never())
			->method('create');

		$this->migrationService->importTextBlocks($this->user, $this->importSource, $this->output);
	}

	public function testImportNoFileIsBeingIgnored(): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE)
			->willThrowException(new UserMigrationException());

		$this->serviceMock->getParameter('textBlockService')
			->expects(self::never())
			->method('create');

		$this->migrationService->importTextBlocks($this->user, $this->importSource, $this->output);
	}

	public static function provideFileContentsWithNoTextBlocksImported(): array {
		return [
			'empty list' => [json_encode([])],
			'invalid JSON' => ['this is not valid json {{{'],
			'JSON object instead of list' => [json_encode(['unexpected' => 'object'])],
		];
	}

	/**
	 * @dataProvider provideFileContentsWithNoTextBlocksImported
	 */
	public function testImportEmptyOrInvalidTextBlocks(string $fileContents): void {
		$this->importSource->expects(self::once())
			->method('getFileContents')
			->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE)
			->willReturn($fileContents);

		$this->serviceMock->getParameter('textBlockService')
			->expects(self::never())
			->method('create');

		$this->migrationService->importTextBlocks($this->user, $this->importSource, $this->output);
	}

	private function getLoremIpsum1(): TextBlock {
		$textBlock = new TextBlock();

		$textBlock->setId(1);
		$textBlock->setOwner(self::USER_ID);
		$textBlock->setTitle('Lorem ipsum 1');
		$textBlock->setPreview('Lorem ipsum dolor sit amet');
		$textBlock->setContent('<p style="margin:0;">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>');

		return $textBlock;
	}

	private function getIpsumLorem2(): TextBlock {
		$textBlock = new TextBlock();

		$textBlock->setId(2);
		$textBlock->setOwner(self::USER_ID);
		$textBlock->setTitle('Ipsum lorem 2');
		$textBlock->setPreview('Ipsum lorem amet sit dolor');
		$textBlock->setContent('<p style="margin:0;">At vero eos et accusam et justo duo dolores et ea rebum.</p>');

		return $textBlock;
	}
}
