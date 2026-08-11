<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Framework;

use OC\Memcache\Factory;
use OCA\Mail\Cache\HordeCacheFactory;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\Profiler\IProfiler;
use OCP\Security\ICrypto;
use OCP\Server;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;
use Throwable;

class Caching {
	/**
	 * Force usage of a real cache as configured in system config. The original ICacheFactory
	 * service closure is hard-coded to always return an instance of ArrayCache when the global
	 * PHPUNIT_RUN is defined.
	 */
	public static function getImapClientFactoryAndConfiguredCacheFactory(?ICrypto $crypto = null): array {
		$config = Server::get(IConfig::class);
		try {
			// 32+ constructor
			$cacheFactory = new Factory(
				Server::get(LoggerInterface::class),
				Server::get(IProfiler::class),
				Server::get(ServerVersion::class),
				$config->getSystemValue('memcache.local', null),
				$config->getSystemValue('memcache.distributed', null),
				$config->getSystemValue('memcache.locking', null),
				$config->getSystemValueString('redis_log_file')
			);
		} catch (Throwable) {
			// 31 and older constructor
			$cacheFactory = new Factory(
				static fn () => 'mail-integration-tests',
				Server::get(LoggerInterface::class),
				Server::get(IProfiler::class),
				$config->getSystemValue('memcache.local', null),
				$config->getSystemValue('memcache.distributed', null),
				$config->getSystemValue('memcache.locking', null),
				$config->getSystemValueString('redis_log_file')
			);
		}
		$imapClient = new IMAPClientFactory(
			$crypto ?? Server::get(ICrypto::class),
			$config,
			$cacheFactory,
			Server::get(IEventDispatcher::class),
			Server::get(ITimeFactory::class),
			Server::get(HordeCacheFactory::class),
		);
		return [$imapClient, $cacheFactory];
	}
}
