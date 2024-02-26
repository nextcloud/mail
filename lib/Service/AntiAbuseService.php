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

namespace OCA\Mail\Service;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Events\OutboxMessageStatusChangeEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IMemcache;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use function implode;

class AntiAbuseService {
	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IConfig $config,
		ICacheFactory $cacheFactory,
		ITimeFactory $timeFactory,
		LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher) {
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
	}

	public function onBeforeMessageSent(IUser $user, LocalMessage $localMessage): void {
		$abuseDetection = $this->config->getAppValue(
			Application::APP_ID,
			'abuse_detection',
			'off'
		);
		if ($abuseDetection !== 'on') {
			$this->logger->debug('Anti abuse detection is off');
			return;
		}

		$this->checkNumberOfRecipients($user, $localMessage);
		$this->checkRateLimits($user, $localMessage);
	}

	private function checkNumberOfRecipients(IUser $user, LocalMessage $message): void {
		$numberOfRecipientsThreshold = (int)$this->config->getAppValue(
			Application::APP_ID,
			'abuse_number_of_recipients_per_message_threshold',
			'0',
		);
		if ($numberOfRecipientsThreshold <= 1) {
			return;
		}

		$actualNumberOfRecipients = count($message->getRecipients());

		if ($actualNumberOfRecipients >= $numberOfRecipientsThreshold) {
			$message->setStatus(LocalMessage::STATUS_TOO_MANY_RECIPIENTS);
			$this->eventDispatcher->dispatchTyped(new OutboxMessageStatusChangeEvent($message));
			$this->logger->alert('User {user} sends to a suspicious number of recipients. {expected} are allowed. {actual} are used', [
				'user' => $user->getUID(),
				'expected' => $numberOfRecipientsThreshold,
				'actual' => $actualNumberOfRecipients,
			]);
		}
	}

	private function checkRateLimits(IUser $user, LocalMessage $message): void {
		if (!$this->cacheFactory->isAvailable()) {
			// No cache, no rate limits
			return;
		}
		$cache = $this->cacheFactory->createDistributed('mail_anti_abuse');
		if (!($cache instanceof IMemcache)) {
			// This integration only works with caches that support inc and dec
			return;
		}

		$ratelimited = (
			$this->checkRateLimitsForPeriod($user, $cache, '15m', 15 * 60, $message) ||
			$this->checkRateLimitsForPeriod($user, $cache, '1h', 60 * 60, $message) ||
			$this->checkRateLimitsForPeriod($user, $cache, '1d', 24 * 60 * 60, $message)
		);
		if($ratelimited) {
			$message->setStatus(LocalMessage::STATUS_RATELIMIT);
			$this->eventDispatcher->dispatchTyped(new OutboxMessageStatusChangeEvent($message));
		}
	}

	private function checkRateLimitsForPeriod(IUser $user,
		IMemcache $cache,
		string $id,
		int $period,
		LocalMessage $message): bool {
		$maxNumberOfMessages = (int)$this->config->getAppValue(
			Application::APP_ID,
			'abuse_number_of_messages_per_' . $id,
			'0',
		);
		if ($maxNumberOfMessages === 0) {
			// No limit set
			return false;
		}

		$now = $this->timeFactory->getTime();

		// Build blocks of periods per period size
		$periodStart = ((int)($now / $period)) * $period;
		$cacheKey = implode('_', ['counter', $id, $periodStart]);
		$cache->add($cacheKey, 0);
		$counter = $cache->inc($cacheKey, count($message->getRecipients()));

		if ($counter >= $maxNumberOfMessages) {
			$this->logger->alert('User {user} sends a supcious number of messages within {period}. {expected} are allowed. {actual} have been sent', [
				'user' => $user->getUID(),
				'period' => $id,
				'expected' => $maxNumberOfMessages,
				'actual' => $counter,
			]);
			return true;
		}
		return false;
	}
}
