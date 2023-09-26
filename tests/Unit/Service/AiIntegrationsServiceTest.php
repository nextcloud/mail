<?php

declare(strict_types=1);

/**
 * @copyright 2023 Hamza Mahjoubi <hamzamahjoubi22@proton.me>
 *
 * @author 2023 Hamza Mahjoubi <hamzamahjoubi22@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\AiIntegrations\Cache;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\TopicsTaskType;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use Psr\Container\ContainerInterface;

class AiIntegrationsServiceTest extends TestCase {

	/** @var ContainerInterface|MockObject */
	private $container;

	/** @var IManager|MockObject */
	private $manager;

	/** @var AiIntegrationsService */
	private $aiIntegrationsService;

	/** @var Cache */
	private $cache;

	/** @var IMAPClientFactory|MockObject */
	private $clientFactory;

	/** @var IMailManager|MockObject */
	private $mailManager;


	protected function setUp(): void {
		parent::setUp();
		$this->container = $this->createMock(ContainerInterface::class);
		try {
			$this->manager = $this->createMock(IManager::class);
		} catch (UnknownTypeException $e) {
			$this->manager = null;
		}

		$this->cache = $this->createMock(Cache::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->aiIntegrationsService = new AiIntegrationsService(
			$this->container,
			$this->cache,
			$this->clientFactory,
			$this->mailManager
		);
	}

	public function testSummarizeThreadNoBackend() {
		$account = new Account(new MailAccount());
		$mailbox = new Mailbox();
		if($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([]);
			$this->expectException(ServiceException::class);
			$this->expectExceptionMessage('No language model available for summary');
			$this->aiIntegrationsService->summarizeThread($account, $mailbox, '', [], '');
		}
		$this->container->method('get')->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Text processing is not available in your current Nextcloud version');
		$this->aiIntegrationsService->summarizeThread($account, $mailbox, '', [], '');

	}

	public function testLlmAvailable() {
		if($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
			->method('getAvailableTaskTypes')
			->willReturn([SummaryTaskType::class, TopicsTaskType::class, FreePromptTaskType::class]);
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable();
			$this->assertTrue($isAvailable);
		} else {
			$this->container->method('get')->willThrowException(new Exception());
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable();
			$this->assertFalse($isAvailable);
		}

	}

	public function testLlmUnavailable() {
		if($this->manager !== null) {
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([TopicsTaskType::class, FreePromptTaskType::class]);
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable();
			$this->assertFalse($isAvailable);
		} else {
			$this->container->method('get')->willThrowException(new Exception());
			$isAvailable = $this->aiIntegrationsService->isLlmAvailable();
			$this->assertFalse($isAvailable);
		}

	}

	public function testCached() {
		if($this->manager !== null) {
			$account = new Account(new MailAccount());
			$mailbox = new Mailbox();
			$this->container->method('get')->willReturn($this->manager);
			$this->manager
				->method('getAvailableTaskTypes')
				->willReturn([SummaryTaskType::class]);

			$message1 = new Message();
			$message1->setMessageId('300');
			$message1->setPreviewText('message1');
			$message1->setThreadRootId('some-thread-root-id-1');

			$message2 = new Message();
			$message2->setMessageId('301');
			$message2->setPreviewText('message2');
			$message2->setThreadRootId('some-thread-root-id-1');

			$message3 = new Message();
			$message3->setMessageId('302');
			$message3->setPreviewText('message3');
			$message3->setThreadRootId('some-thread-root-id-1');

			$messages = [ $message1,$message2,$message3];
			$messageIds = [ $message1->getMessageId(),$message2->getMessageId(),$message3->getMessageId()];

			$this->cache
				->method('getSummary')
				->with($messageIds)
				->willReturn('this is a cached summary');

			$this->assertEquals('this is a cached summary', $this->aiIntegrationsService->summarizeThread($account, $mailbox, 'some-thread-root-id-1', $messages, 'admin'));
		}
	}
}
