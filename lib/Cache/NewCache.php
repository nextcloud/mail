<?php

declare(strict_types=1);

namespace OCA\Mail\Cache;

use Horde_Imap_Client_Cache_Backend;
use InvalidArgumentException;
use JsonException;
use OCP\ICache;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * This class is inspired by Horde_Imap_Client_Cache_Backend_Cache of the Horde Project
 */
class NewCache extends Horde_Imap_Client_Cache_Backend {
	/** Cache structure version. */
	public const VERSION = 1;

	private ICache $cache;
	private LoggerInterface $logger;

	/**
	 * @param array $params Configuration parameters:
	 */
	public function __construct(array $params = []) {
		// Default parameters.
		$params = array_merge([
			'lifetime' => 604800,
		], array_filter($params));

		if (!isset($params['cacheob'])) {
			throw new InvalidArgumentException('Missing cacheob parameter');
		}
		$this->cache = $params['cacheob'];
		$this->logger = $params['logger'];

		foreach (['lifetime'] as $val) {
			$params[$val] = (int)$params[$val];
		}

		parent::__construct($params);
	}

	public function get($mailbox, $uids, $fields, $uidvalid) {
		$ret = [];
		foreach ($uids as $uid) {
			$data = $this->loadCachedData($mailbox, $uid, $uidvalid);
			foreach ($fields as $field) {
				if (!isset($data[$field])) {
					continue;
				}

				$ret[$uid][$field] = @unserialize($data[$field]);
			}
		}

		return $ret;
	}

	private function getCacheKeyPrefix(string $mailbox): string {
		return implode('/', [
			'NewCache',
			self::VERSION,
			$mailbox,
		]);
	}

	private function getCacheKey(string $mailbox, int $uid): string {
		return $this->getCacheKeyPrefix($mailbox) . "/$uid";
	}

	private function getMetaCacheKey(string $mailbox): string {
		return $this->getCacheKeyPrefix($mailbox) . '/meta';
	}

	private function loadCachedMetaData(string $mailbox, ?int $uidvalid): array {
		$key = $this->getMetaCacheKey($mailbox);
		$rawData = $this->cache->get($key);
		if ($rawData === null || $rawData === '') {
			return [];
		}

		try {
			$data = json_decode($rawData, true, 1, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			return [];
		}

		// TODO: can we even cache without uidvalid?
		$existingUidValid = $data['uidvalid'] ?? null;
		if ($uidvalid !== null && $existingUidValid !== null && $uidvalid !== $existingUidValid) {
			//$this->cache->remove($key);
			$this->deleteMailbox($mailbox);
			return [];
		}

		return $data;
	}

	private function loadCachedData(string $mailbox, int $uid, ?int $uidvalid): array {
		$key = $this->getCacheKey($mailbox, $uid);
		$rawData = $this->cache->get($key);
		if ($rawData === null || $rawData = '') {
			return [];
		}

		try {
			$data = json_decode($rawData, true, 1, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			return [];
		}

		// TODO: can we even cache without uidavalid?
		if (!isset($data['uidvalid']) || $uidvalid !== $data['uidvalid']) {
			$this->cache->remove($key);
			return [];
		}

		return $data;
	}

	/**
	 * @param array<string, string> $data
	 */
	private function saveCachedData(string $mailbox, int $uid, ?int $uidvalid, array $data): void {
		$key = $this->getCacheKey($mailbox, $uid);
		if ($uidvalid === null) {
			// TODO: just for testing
			throw new RuntimeException('Refusing to save without uidvalid');
		}

		// TODO: use a DTO for cache lines (JsonSerializable)
		$data['uidvalid'] = $uidvalid;
		$rawData = json_encode($data);
		$this->cache->set($key, $rawData);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function saveCachedMetaData(string $mailbox, ?int $uidvalid, array $data): void {
		$key = $this->getMetaCacheKey($mailbox);

		// TODO: use a DTO for cache lines (JsonSerializable)
		$existingUidvalid = $data['uidvalid'] ?? null;
		if ($uidvalid !== null && $existingUidvalid !== null && $uidvalid !== $existingUidvalid) {
			$this->deleteMailbox($mailbox);
			unset($data['uidvalid']);
		}

		if ($uidvalid !== null) {
			$data['uidvalid'] = $uidvalid;
		}

		if ($uidvalid === null) {
			// TODO: just for testing
			$this->logger->error('Refusing to save', [
				'mailbox' => $mailbox,
				'uidvalid' => $uidvalid,
				'data' => json_encode($data, JSON_PRETTY_PRINT),
			]);
			//throw new RuntimeException('Refusing to save without uidvalid');
		}

		$rawData = json_encode($data, JSON_THROW_ON_ERROR);
		$this->cache->set($key, $rawData);
	}

	public function getCachedUids($mailbox, $uidvalid) {
		$metaData = $this->loadCachedMetaData($mailbox, $uidvalid);
		// TODO: convert meta data cache line to a DTO
		return $metaData['uids'] ?? [];
	}

	public function set($mailbox, $data, $uidvalid) {
		$metaData = $this->loadCachedMetaData($mailbox, $uidvalid);
		// TODO: convert meta data cache line to a DTO
		$uids = $metaData['uids'] ?? [];

		foreach ($data as $uid => $uidData) {
			$uids[] = $uid;
			$existingData = $this->loadCachedData($mailbox, $uid, $uidvalid);

			foreach ($uidData as $field => $value) {
				$existingData[$field] = @serialize($value);
			}

			$this->saveCachedData($mailbox, $uid, $uidvalid, $existingData);
		}

		$metaData['uids'] = array_unique($uids, SORT_NUMERIC);
		$this->saveCachedMetaData($mailbox, $uidvalid, $metaData);
	}

	public function getMetaData($mailbox, $uidvalid, $entries) {
		$ret = [];
		$data = $this->loadCachedMetaData($mailbox, $uidvalid);
		foreach ($entries as $entry) {
			if (!isset($data[$entry])) {
				continue;
			}

			// Don't need to unserialize here as the array is hopefully only containing PODs
			//$ret[$entry] = @unserialize($data[$entry]);
			$ret[$entry] = $data[$entry];
		}

		return $ret;
	}

	public function setMetaData($mailbox, $data) {
		// TODO: uidvalid is optional here
		$uidvalid = $data['uidvalid'] ?? null;
		$existingData = $this->loadCachedMetaData($mailbox, $uidvalid);
		foreach ($data as $entry => $value) {

			// Don't need to serialize here as the array is hopefully only containing PODs
			//$existingData[$field] = @serialize($value);
			$existingData[$entry] = $value;
		}

		$this->saveCachedMetaData($mailbox, $uidvalid, $existingData);
	}

	public function deleteMsgs($mailbox, $uids) {
		$metaData = $this->loadCachedMetaData($mailbox, null);
		// TODO: convert meta data cache line to a DTO
		$cachedUids = $metaData['uids'] ?? [];
		$metaData['uids'] = array_diff($cachedUids, $uids);
		// TODO: differentiate between no uidvalid needed and none available
		$this->saveCachedMetaData($mailbox, $metaData['uidvalid'], $metaData);

		foreach ($uids as $uid) {
			$key = $this->getCacheKey($mailbox, $uid);
			$this->cache->remove($key);
		}
	}

	public function deleteMailbox($mailbox) {
		$prefix = $this->getCacheKeyPrefix($mailbox);
		$this->cache->clear($prefix);
	}

	public function clear($lifetime) {
		$this->cache->clear();
	}

	/* Serializable methods. */

	/**
	 */
	public function serialize() {
		$this->save();
		return parent::serialize();
	}

	public function __serialize(): array {
		$this->save();
		return parent::__serialize();
	}
}
