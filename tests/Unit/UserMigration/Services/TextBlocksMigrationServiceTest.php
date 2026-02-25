<?php

namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TextBlock;
use OCA\Mail\Service\TextBlockService;
use OCA\Mail\UserMigration\Service\TextBlocksMigrationService;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class TextBlocksMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private TextBlockService $textBlockService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->textBlockService = $this->createMock(TextBlockService::class);
	}

	public function testExportsMultipleTextBlocks(): void {
		$trustedSendersList = [$this->getLoremIpsum(), $this->getIpsumLorem()];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE, json_encode($trustedSendersList));

		$this->textBlockService->method('findAll')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new TextBlocksMigrationService($this->textBlockService, $this->l);
		$service->exportTextBlocks($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoneTrustedSenders(): void {
		$trustedSendersList = [];

		$this->textBlockService->method('findAll')->with(self::USER_ID)->willReturn($trustedSendersList);
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE, json_encode($trustedSendersList));

		$service = new TextBlocksMigrationService($this->textBlockService, $this->l);
		$service->exportTextBlocks($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTrustedSender(): void {
		$trustedIndividual = $this->getLoremIpsum();
		$trustedDomain = $this->getIpsumLorem();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE)->willReturn(json_encode($trustedSendersList));

		$this->textBlockService->expects(self::exactly(2))->method('create')->with(self::USER_ID, self::callback(function ($title) use ($trustedIndividual, $trustedDomain) {
			return $title === $trustedIndividual->getTitle() || $title === $trustedDomain->getTitle();
		}), self::callback(function ($content) use ($trustedIndividual, $trustedDomain) {
			return $content === $trustedIndividual->getContent() || $content === $trustedDomain->getContent();
		}));

		$service = new TextBlocksMigrationService($this->textBlockService, $this->l);
		$service->importTextBlocks($this->user, $this->importSource);
	}

	public function testImportNoneTrustedSenders(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TextBlocksMigrationService::TEXT_BLOCKS_FILE)->willReturn(json_encode($trustedSendersList));
		$this->textBlockService->expects(self::never())->method('create');
		$service = new TextBlocksMigrationService($this->textBlockService, $this->l);
		$service->importTextBlocks($this->user, $this->importSource);
	}

	private function getLoremIpsum(): TextBlock {
		$individualSender = new TextBlock();

		$individualSender->setId(1);
		$individualSender->setOwner(self::USER_ID);
		$individualSender->setTitle("Lorem ipsum");
		$individualSender->setPreview("Lorem ipsum dolor sit amet");
		$individualSender->setContent('<p style="margin:0;">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>');

		return $individualSender;
	}

	private function getIpsumLorem(): TextBlock {
		$domainSender = new TextBlock();

		$domainSender->setId(2);
		$domainSender->setOwner(self::USER_ID);
		$domainSender->setTitle("Ipsum lorem");
		$domainSender->setPreview("Ipsum lorem amet sit dolor");
		$domainSender->setContent('<p style="margin:0;">At vero eos et accusam et justo duo dolores et ea rebum.</p>');

		return $domainSender;
	}
}
