<?php

namespace Unit\UserMigration\Services;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TagsMigrationServiceTest extends TestCase {
	private const USER_ID = '123';
	private OutputInterface $output;
	private IUser $user;
	private IL10N $l;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private TagMapper $tagMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);
		$this->l = $this->createStub(IL10N::class);

		$this->user = $this->createMock(IUser::CLASS);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->tagMapper = $this->createMock(TagMapper::class);
	}

	public function testExportsMultipleTags(): void {
		$trustedSendersList = [$this->getTestingTag(), $this->getSuccessfulTag()];
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TagsMigrationService::TAGS_FILE, json_encode($trustedSendersList));

		$this->tagMapper->method('getAllTagsForUser')->with(self::USER_ID)->willReturn($trustedSendersList);
		$service = new TagsMigrationService($this->tagMapper, $this->l);
		$service->exportTags($this->user, $this->exportDestination, $this->output);
	}

	public function testExportsNoTags(): void {
		$trustedSendersList = [];

		$this->tagMapper->method('getAllTagsForUser')->with(self::USER_ID)->willReturn($trustedSendersList);
		$this->exportDestination->expects(self::once())->method('addFileContents')->with(TagsMigrationService::TAGS_FILE, json_encode($trustedSendersList));

		$service = new TagsMigrationService($this->tagMapper, $this->l);
		$service->exportTags($this->user, $this->exportDestination, $this->output);
	}

	public function testImportMultipleTags(): void {
		$trustedIndividual = $this->getTestingTag();
		$trustedDomain = $this->getSuccessfulTag();
		$trustedSendersList = [$trustedIndividual, $trustedDomain];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TagsMigrationService::TAGS_FILE)->willReturn(json_encode($trustedSendersList));

		$this->tagMapper->expects(self::exactly(2))->method('insert')->with(self::callback(function (Tag $writtenTag) use ($trustedIndividual, $trustedDomain): bool {
			if ($this->userIdMatches($writtenTag)
				&& $this->displayNameMatches($writtenTag)
				&& $this->imapLabelMatches($writtenTag)
				&& $this->colorMatches($writtenTag)
				&& $this->isDefaultTagMatches($writtenTag)
			) {
				return true;
			} else {
				return false;
			}
		}))->willReturnCallback(function ($test) {
			$test->setId(random_int(10, 999));
			return $test;
		});

		$service = new TagsMigrationService($this->tagMapper, $this->l);
		$mappedTags = $service->importTags($this->user, $this->importSource);

		$this->assertCount(2, $mappedTags);
		$this->assertArrayHasKey($trustedIndividual->getId(), $mappedTags);
		$this->assertIsInt($mappedTags[$trustedIndividual->getId()]);
		$this->assertArrayHasKey($trustedDomain->getId(), $mappedTags);
		$this->assertIsInt($mappedTags[$trustedDomain->getId()]);
	}

	public function testImportNoTags(): void {
		$trustedSendersList = [];
		$this->importSource->expects(self::once())->method('getFileContents')->with(TagsMigrationService::TAGS_FILE)->willReturn(json_encode($trustedSendersList));
		$this->tagMapper->expects(self::never())->method('insert');
		$service = new TagsMigrationService($this->tagMapper, $this->l);
		$mappedTags = $service->importTags($this->user, $this->importSource);
		$this->assertCount(0, $mappedTags);
	}

	private function getTestingTag(): Tag {
		$testingTag = new Tag();

		$testingTag->setId(1);
		$testingTag->setUserId(self::USER_ID);
		$testingTag->setImapLabel('testing');
		$testingTag->setDisplayName('Testing');
		$testingTag->setColor('#fff');
		$testingTag->setIsDefaultTag(false);

		return $testingTag;
	}

	private function getSuccessfulTag(): Tag {
		$successfulTag = new Tag();

		$successfulTag->setId(2);
		$successfulTag->setUserId(self::USER_ID);
		$successfulTag->setImapLabel('successful');
		$successfulTag->setDisplayName('Successful');
		$successfulTag->setColor('#fff');
		$successfulTag->setIsDefaultTag(false);

		return $successfulTag;
	}

	private function userIdMatches(Tag $tag): bool {
		return $tag->getUserId() === self::USER_ID;
	}

	private function displayNameMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getDisplayName() === $testing->getDisplayName() || $tag->getDisplayName() === $successful->getDisplayName();
	}

	private function imapLabelMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getImapLabel() === $testing->getImapLabel() || $tag->getImapLabel() === $successful->getImapLabel();
	}

	private function colorMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getColor() === $testing->getColor() || $tag->getColor() === $successful->getColor();
	}

	private function isDefaultTagMatches(Tag $tag): bool {
		$testing = $this->getTestingTag();
		$successful = $this->getSuccessfulTag();

		return $tag->getIsDefaultTag() === $testing->getIsDefaultTag() || $tag->getIsDefaultTag() === $successful->getIsDefaultTag();
	}
}
