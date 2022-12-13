<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Listener;

use OCA\Mail\Events\BeforeMessageSentEvent;
use OCA\Mail\Service\AntiAbuseService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|BeforeMessageSentEvent>
 */
class AntiAbuseListener implements IEventListener {
	/** @var IUserManager */
	private $userManager;

	/** @var AntiAbuseService */
	private $service;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IUserManager $userManager,
								AntiAbuseService $service,
								LoggerInterface $logger) {
		$this->service = $service;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeMessageSentEvent)) {
			return;
		}

		$user = $this->userManager->get($event->getAccount()->getUserId());
		if ($user === null) {
			$this->logger->error('User {user} for mail account {id} does not exist', [
				'user' => $event->getAccount()->getUserId(),
				'id' => $event->getAccount()->getId(),
			]);
			return;
		}

		$this->service->onBeforeMessageSent(
			$user,
			$event->getNewMessageData(),
		);
	}
}
