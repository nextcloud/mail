<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCP\EventDispatcher\IEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SaveSentMessageListenerTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventListener */
	private $listener;
	private MockObject|LocalMessageMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mapper = $this->createMock(LocalMessageMapper::class);
	}
}
