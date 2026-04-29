<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\JMAP;

use Exception;
use JmapClient\Client;
use JmapClient\Requests\Mail\MailboxGet;
use JmapClient\Requests\Mail\MailboxQuery;
use JmapClient\Requests\Mail\MailboxSet;
use JmapClient\Requests\Mail\MailChanges;
use JmapClient\Requests\Mail\MailGet;
use JmapClient\Requests\Mail\MailIdentityGet;
use JmapClient\Requests\Mail\MailParameters as MailParametersRequest;
use JmapClient\Requests\Mail\MailQuery;
use JmapClient\Requests\Mail\MailQueryChanges;
use JmapClient\Requests\Mail\MailSet;
use JmapClient\Requests\Mail\MailSubmissionSet;
use JmapClient\Responses\Mail\MailboxParameters as MailboxParametersResponse;
use JmapClient\Responses\Mail\MailParameters as MailParametersResponse;
use JmapClient\Responses\ResponseException;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\JMAP\JmapClientFactory;
use OCA\Mail\JMAP\JmapMailboxAdapter;
use OCA\Mail\JMAP\JmapMessageAdapter;

class JmapOperationsService {
	protected Client $dataStore;
	protected ?string $dataAccount = null;

	protected array $entityPropertiesBasic = [
		'id', 'blobId', 'threadId', 'mailboxIds', 'messageId', 'size',
	];

	protected array $entityPropertiesDefault = [
		'id', 'blobId', 'threadId', 'mailboxIds', 'messageId', 'size',
		'receivedAt', 'inReplyTo', 'references', 'sender', 'from', 'keywords',
		'to', 'cc', 'bcc', 'replyTo', 'subject', 'sentAt', 'hasAttachment',
		'attachments', 'preview', 'bodyStructure', 'bodyValues',
		'header:Disposition-Notification-To:asText',
		'header:DKIM-Signature:asText',
		'header:List-Unsubscribe:asText',
		'header:List-Unsubscribe-Post:asText',
	];

	public function __construct(
		private readonly JmapClientFactory $jmapClientFactory,
		private readonly JmapMailboxAdapter $jmapMailboxAdapter,
		private readonly JmapMessageAdapter $jmapMessageAdapter,
	) {
	}

	/**
	 * Establish connection to remote storage for given account
	 *
	 * @return bool true if connection was successful, false otherwise
	 */
	public function connect(Account $account): bool {
		$this->dataStore = $this->jmapClientFactory->getClient($account);
		// evaluate if client was already connected
		if (!$this->dataStore->sessionStatus()) {
			$this->dataStore->connect();
		}
		// determine account
		if ($this->dataAccount === null) {
			$this->dataAccount = $this->dataStore->sessionAccountDefault('mail')->id();
		}

		return true;
	}

	/**
	 * List of collections in remote storage
	 *
	 * @param string|null $location optional location constraint (e.g. parent collection id)
	 * @param array<array{attribute:string,value:mixed}>|null $filter optional filter conditions
	 * @param array<array{attribute:string,direction:string}>|null $sort optional sort conditions
	 *
	 * @return Mailbox[]
	 */
	public function collectionList(?string $location = null, ?array $filter = null, ?array $sort = null): array {
		// construct request
		$r0 = new MailboxQuery($this->dataAccount);
		// define location
		if (!empty($location)) {
			$r0->filter()->in($location);
		}
		// define filter
		if ($filter !== null) {
			foreach ($filter as $condition) {
				$value = $condition['value'];
				match($condition['attribute']) {
					'in' => $r0->filter()->in($value),
					'name' => $r0->filter()->name($value),
					'role' => $r0->filter()->role($value),
					'hasRoles' => $r0->filter()->hasRoles($value),
					'subscribed' => $r0->filter()->isSubscribed($value),
					default => null
				};
			}
		}
		// define order
		if ($sort !== null) {
			foreach ($sort as $condition) {
				$direction = $condition['direction'];
				match($condition['attribute']) {
					'name' => $r0->sort()->name($direction),
					'order' => $r0->sort()->order($direction),
					default => null
				};
			}
		}
		// construct request
		$r1 = new MailboxGet($this->dataAccount);
		$r1->targetFromRequest($r0, '/ids');
		// transceive
		$bundle = $this->dataStore->perform([$r0, $r1]);
		// extract response
		$response = $bundle->response(1);
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// convert collection objects
		$list = [];
		foreach ($response->objects() as $so) {
			if (!$so instanceof MailboxParametersResponse) {
				continue;
			}
			$list[] = $this->jmapMailboxAdapter->convertToMailbox($so);
		}
		// return collection of collections
		return $list;
	}

	/**
	 * Check existence of collections in remote storage
	 *
	 * @param string ...$identifiers remote identifiers
	 *
	 * @return array<string, bool> map of remote identifiers to existence status
	 */
	public function collectionExtant(string ...$identifiers): array {
		$extant = [];
		// construct request
		$r0 = new MailboxGet($this->dataAccount);
		$r0->target(...$identifiers);
		$r0->property('id');
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// construct map of extant collection identifiers
		foreach ($response->objects() as $so) {
			if (!$so instanceof MailboxParametersResponse) {
				continue;
			}
			$extant[$so->id()] = true;
		}
		return $extant;
	}

	/**
	 * Retrieve details for a specific collection in remote storage
	 *
	 * @param string $identifier remote identifier
	 *
	 * @return Mailbox|null collection object if retrieval was successful, null otherwise
	 */
	public function collectionFetch(string $identifier): ?Mailbox {
		// construct request
		$r0 = new MailboxGet($this->dataAccount);
		$r0->target($identifier);
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// convert collection objects
		$collection = $response->object(0);
		if ($collection instanceof MailboxParametersResponse) {
			return $this->jmapMailboxAdapter->convertToMailbox($collection);
		}
		return null;
	}

	/**
	 * Create collection in remote storage
	 *
	 * @param Mailbox|null $location optional parent collection
	 * @param Mailbox $mailbox collection to create
	 *
	 * @return Mailbox|null created collection or null if creation failed
	 */
	public function collectionCreate(?Mailbox $location, Mailbox $mailbox): ?Mailbox {
		// convert entity
		$to = $this->jmapMailboxAdapter->convertFromMailbox($mailbox);
		// define location
		if (!empty($location)) {
			$to->in($location->getRemoteId());
		}
		$id = uniqid();
		// construct request
		$r0 = new MailboxSet($this->dataAccount);
		$r0->create($id, $to);
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// check for success
		$result = $response->createSuccess($id);
		if ($result !== null) {
			$mailbox->setRemoteId($result['id']);
			$mailbox->setNameHash(md5($result['id']));
			return $mailbox;
		}
		// check for failure
		$result = $response->createFailure($id);
		if ($result !== null) {
			$type = $result['type'] ?? 'unknownError';
			$description = $result['description'] ?? 'An unknown error occurred during collection creation.';
			throw new Exception("$type: $description", 1);
		}
		// return null if creation failed without failure reason
		return null;
	}

	/**
	 * Modify collection in remote storage
	 *
	 * @param string $identifier remote identifier
	 * @param Mailbox $mailbox collection with modifications to apply
	 *
	 * @return Mailbox|null modified collection or null if modification failed
	 */
	public function collectionModify(string $identifier, Mailbox $mailbox): ?Mailbox {
		// convert entity
		$to = $this->jmapMailboxAdapter->convertFromMailbox($mailbox);
		// construct request
		$r0 = new MailboxSet($this->dataAccount);
		$r0->update($identifier, $to);
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// check for success
		$result = $response->updateSuccess($identifier);
		if ($result !== null) {
			return $mailbox;
		}
		// check for failure
		$result = $response->updateFailure($identifier);
		if ($result !== null) {
			$type = $result['type'] ?? 'unknownError';
			$description = $result['description'] ?? 'An unknown error occurred during collection modification.';
			throw new Exception("$type: $description", 1);
		}
		// return null if modification failed without failure reason
		return null;
	}

	/**
	 * Delete collection in remote storage
	 *
	 * @param string $identifier remote identifier
	 * @param bool $force whether to force deletion even if collection is not empty
	 *
	 * @return string|null deleted collection identifier or null if deletion failed
	 */
	public function collectionDestroy(string $identifier, bool $force = false): ?string {
		// construct request
		$r0 = new MailboxSet($this->dataAccount);
		$r0->delete($identifier);
		if ($force) {
			$r0->destroyContents(true);
		}
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// check for success
		$result = $response->deleteSuccess($identifier);
		if ($result !== null) {
			return (string)$result['id'];
		}
		// check for failure
		$result = $response->deleteFailure($identifier);
		if ($result !== null) {
			$type = $result['type'] ?? 'unknownError';
			$description = $result['description'] ?? 'An unknown error occurred during collection deletion.';
			throw new Exception("$type: $description", 1);
		}
		// return null if deletion failed without failure reason
		return null;
	}

	/**
	 * Retrieve entities from remote storage
	 *
	 * @param string|null $location optional location constraint
	 * @param array|null $filter optional filter conditions
	 * @param array|null $sort optional sort conditions
	 * @param array|null $range optional range conditions
	 * @param string|null $granularity optional granularity level
	 *
	 * @return array{state:string, list:array<string, Message>}
	 */
	public function entityList(?string $location = null, ?array $filter = null, ?array $sort = null, ?array $range = null, ?string $granularity = null): array {
		// construct first request
		$r0 = new MailQuery($this->dataAccount);
		// define location
		if (!empty($location)) {
			$r0->filter()->in($location);
		}
		// define filter
		if ($filter !== null) {
			foreach ($filter as $condition) {
				$value = $condition['value'];
				match($condition['attribute']) {
					'*' => $r0->filter()->text($value),
					'in' => $r0->filter()->in($value),
					'inOmit' => $r0->filter()->inOmit($value),
					'from' => $r0->filter()->from($value),
					'to' => $r0->filter()->to($value),
					'cc' => $r0->filter()->cc($value),
					'bcc' => $r0->filter()->bcc($value),
					'subject' => $r0->filter()->subject($value),
					'body' => $r0->filter()->body($value),
					'attachmentPresent' => $r0->filter()->hasAttachment($value),
					'tagPresent' => $r0->filter()->keywordPresent($value),
					'tagAbsent' => $r0->filter()->keywordAbsent($value),
					'before' => $r0->filter()->receivedBefore($value),
					'after' => $r0->filter()->receivedAfter($value),
					'min' => $r0->filter()->sizeMin((int)$value),
					'max' => $r0->filter()->sizeMax((int)$value),
					default => null
				};
			}
		}
		// define order
		if ($sort !== null) {
			foreach ($sort as $condition) {
				$direction = $condition['direction'];
				match($condition['attribute']) {
					'from' => $r0->sort()->from($direction),
					'to' => $r0->sort()->to($direction),
					'subject' => $r0->sort()->subject($direction),
					'received' => $r0->sort()->received($direction),
					'sent' => $r0->sort()->sent($direction),
					'size' => $r0->sort()->size($direction),
					'tag' => $r0->sort()->keyword($direction),
					default => null
				};
			}
		}
		// define range
		if ($range !== null) {
			$anchor = $range['anchor'] ?? null;
			$position = $range['position'] ?? null;
			$tally = $range['tally'] ?? null;
			if ($anchor === 'absolute' && $position !== null && $tally !== null) {
				$r0->limitAbsolute((int)$position, (int)$tally);
			}
			if ($anchor === 'relative' && $position !== null && $tally !== null) {
				$r0->limitRelative((int)$position, (int)$tally);
			}
		}
		// construct second request
		$r1 = new MailGet($this->dataAccount);
		$r1->targetFromRequest($r0, '/ids');
		// select properties to return
		if ($granularity === 'basic') {
			$r1->property(...$this->entityPropertiesBasic);
		} else {
			$r1->property(...$this->entityPropertiesDefault);
			$r1->bodyAll(true);
		}
		// transceive
		$bundle = $this->dataStore->perform([$r0, $r1]);
		// extract response
		$response = $bundle->response(1);
		// convert json objects to message objects
		$state = $response->state();
		$list = $response->objects();
		foreach ($list as $id => $entry) {
			if (!$entry instanceof MailParametersResponse) {
				continue;
			}
			$list[$id] = $this->jmapMessageAdapter->convertToDatabaseMessage($entry);
		}
		// return message collection
		return ['list' => $list, 'state' => $state];
	}

	/**
	 * Check existence of entities in remote storage
	 *
	 * @param string ...$identifiers remote identifiers
	 *
	 * @return array<string, bool> array of remote identifiers and their existence status
	 */
	public function entityExtant(string ...$identifiers): array {
		$extant = [];
		// construct request
		$r0 = new MailGet($this->dataAccount);
		$r0->target(...$identifiers);
		$r0->property('id');
		// transmit request and receive response
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// construct map of extant collection identifiers
		foreach ($response->objects() as $so) {
			if (!$so instanceof MailParametersResponse) {
				continue;
			}
			$extant[$so->id()] = true;
		}
		return $extant;
	}

	/**
	 * Delta for entities in remote storage
	 *
	 * @param string|null $location optional remote location constraint (e.g. remote collection identifier)
	 * @param string $state state identifier to compare against
	 *
	 * @return array{state:string, additions:array<int, string>, modifications:array<int, string>, deletions:array<int, string>}
	 */
	public function entityDelta(?string $location, string $state): array {
		// if no state is given, return all entities as additions
		if (empty($state)) {
			$results = $this->entityList($location, null, null, null, 'B');
			$delta = [
				'state' => $results['state'],
				'additions' => [],
				'modifications' => [],
				'deletions' => [],
			];
			foreach ($results['list'] as $entry) {
				$delta['additions'][] = $entry->getRemoteId();
			}
			return $delta;
		}
		// if location is given, perform delta for specific collection, otherwise perform delta for all collections
		if (empty($location)) {
			return $this->entityDeltaDefault($state);
		} else {
			return $this->entityDeltaSpecific($location, $state);
		}
	}

	/**
	 * Delta of changes for specific collection in remote storage
	 *
	 * @param string|null $location optional remote location constraint (e.g. remote collection identifier)
	 * @param string $state state identifier to compare against
	 *
	 * @return array{state:string, additions:array<int, string>, modifications:array<int, string>, deletions:array<int, string>}
	 */
	public function entityDeltaSpecific(?string $location, string $state): array {
		// construct set request
		$r0 = new MailQueryChanges($this->dataAccount);
		// set location constraint
		if (!empty($location)) {
			$r0->filter()->in($location);
		}
		// set state constraint
		if (!empty($state)) {
			$r0->state($state);
		} else {
			$r0->state('0');
		}
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		return $this->constructDeltaResult(
			$response->stateNew(),
			$response->added(),
			$response->removed(),
		);
	}

	/**
	 * Delta of changes for all collections in remote storage
	 *
	 * @param string $state state identifier to compare against
	 *
	 * @return array{state:string, additions:array<int, string>, modifications:array<int, string>, deletions:array<int, string>}
	 */
	public function entityDeltaDefault(string $state): array {
		// construct set request
		$r0 = new MailChanges($this->dataAccount);
		if (!empty($state)) {
			$r0->state($state);
		} else {
			$r0->state('');
		}
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}

		return $this->constructDeltaResult(
			$response->stateNew(),
			$response->added(),
			$response->removed(),
		);
	}

	/**
	 * Construct delta result from added and removed entries
	 *
	 * @param string $state state identifier to return in result
	 * @param array<int, mixed> $added entries that were added
	 * @param array<int, mixed> $removed entries that were removed
	 *
	 * @return array{state:string, additions:array<int, string>, modifications:array<int, string>, deletions:array<int, string>}
	 */
	private function constructDeltaResult(string $state, array $added, array $removed): array {
		// extract/flatten ids from added and removed entries
		$extractIds = static function (array $entries): array {
			$ids = [];
			foreach ($entries as $entry) {
				if (is_string($entry) && $entry !== '') {
					$ids[] = $entry;
					continue;
				}

				$id = is_array($entry) ? ($entry['id'] ?? null) : null;
				if (is_string($id) && $id !== '') {
					$ids[] = $id;
				}
			}

			return array_values(array_unique($ids));
		};
		$addedIds = $extractIds($added);
		$removedIds = $extractIds($removed);
		// entries that are both in added and removed are considered modified
		$modifiedIds = array_values(array_intersect($addedIds, $removedIds));
		$modifiedIdMap = array_fill_keys($modifiedIds, true);
		// entries that are only in added are considered additions, entries that are only in removed are considered deletions
		$additionIds = array_values(array_filter(
			$addedIds,
			static fn (string $id): bool => !isset($modifiedIdMap[$id]),
		));
		$deletionIds = array_values(array_filter(
			$removedIds,
			static fn (string $id): bool => !isset($modifiedIdMap[$id]),
		));

		return [
			'state' => $state,
			'additions' => $additionIds,
			'modifications' => $modifiedIds,
			'deletions' => $deletionIds,
		];
	}


	/**
	 * Retrieve entities from remote storage
	 *
	 * @param string ...$identifiers remote identifiers
	 *
	 * @return MailParametersResponse[]
	 */
	public function entityFetchModelMessage(string ...$identifiers): array {
		$responses = $this->entityFetchNative(...$identifiers);
		$list = [];
		foreach ($responses as $id => $entry) {
			$list[$id] = $this->jmapMessageAdapter->convertToModelMessage($entry);
		}
		return $list;
	}

	/**
	 * Retrieve entities from remote storage
	 *
	 * @param string ...$identifiers remote identifiers
	 *
	 * @return MailParametersResponse[]
	 */
	public function entityFetchDatabaseMessage(string ...$identifiers): array {
		$responses = $this->entityFetchNative(...$identifiers);
		$list = [];
		foreach ($responses as $id => $entry) {
			$list[$id] = $this->jmapMessageAdapter->convertToDatabaseMessage($entry);
		}
		return $list;
	}

	/**
	 * Retrieve entities from remote storage
	 *
	 * @param string ...$identifiers remote identifiers
	 *
	 * @return MailParametersResponse[]
	 */
	public function entityFetchNative(string ...$identifiers): array {
		// construct request
		$r0 = new MailGet($this->dataAccount);
		$r0->target(...$identifiers);
		// select properties to return
		$r0->property(...$this->entityPropertiesDefault);
		$r0->bodyAll(true);
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// convert json objects to message objects
		$list = [];
		foreach ($response->objects() as $so) {
			if (!$so instanceof MailParametersResponse) {
				continue;
			}
			$id = $so->id();
			$list[$id] = $so;
		}
		// return message collection
		return $list;
	}

	public function entityFetchRaw(string $identifier): ?string {
		$entities = $this->entityFetchNative($identifier);
		$entity = $entities[$identifier] ?? null;
		if (!$entity instanceof MailParametersResponse) {
			return null;
		}

		$blobId = $entity->blob();
		if ($blobId === null || $blobId === '') {
			return null;
		}

		$rawMessage = null;
		$this->dataStore->download($this->dataAccount, $blobId, $rawMessage, 'message/rfc822', 'message.eml');

		return is_string($rawMessage) ? $rawMessage : null;
	}

	/**
	 * create entity in remote storage
	 *
	 * TODO: is this needed?
	 */
	public function entityCreate(string $location, array $so): ?array {
		// convert entity
		$to = new MailParametersRequest();
		$to->parametersRaw($so);
		$to->in($location);
		$id = uniqid();
		// construct request
		$r0 = new MailSet($this->dataAccount);
		$r0->create($id, $to);
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		// check for success
		$result = $response->createSuccess($id);
		if ($result !== null) {
			return array_merge($so, $result);
		}
		// check for failure
		$result = $response->createFailure($id);
		if ($result !== null) {
			$type = $result['type'] ?? 'unknownError';
			$description = $result['description'] ?? 'An unknown error occurred during collection creation.';
			throw new Exception("$type: $description", 1);
		}
		// return null if creation failed without failure reason
		return null;
	}

	/**
	 * update entity in remote storage
	 *
	 * TODO: Is this needed?
	 */
	public function entityModify(array $so): ?array {
		// extract entity id
		$id = $so['id'];
		// convert entity
		$to = new MailParametersRequest();
		$to->parametersRaw($so);
		// construct request
		$r0 = new MailSet($this->dataAccount);
		$r0->update($id, $to);
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		$results = [];
		// check for success
		foreach ($response->updateSuccesses() as $id) {
			$results[$id] = true;
		}
		// check for failure
		foreach ($response->updateFailures() as $id => $data) {
			$results[$id] = $data['type'] ?? 'unknownError';
		}

		return $results;
	}

	/**
	 * Partially update entity in remote storage
	 *
	 * @param array $patch patch request containing modifications to apply
	 * @param string ...$identifiers remote identifiers to apply patch to
	 *
	 * @return array<string, bool|string> map of remote identifiers to modification result (true for success, error type for failure)
	 */
	public function entityModifyPatch(array $patch, string ...$identifiers): ?array {
	//public function entityModifyPatch(MailParametersRequest $patch, string ...$identifiers): ?array {
		// construct request
		$r0 = new MailSet($this->dataAccount);
		foreach ($identifiers as $id) {
			$r0->update($id, $patch);
		}
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}
		$results = [];
		// check for success
		foreach ($response->updateSuccesses() as $id => $data) {
			$results[$id] = true;
		}
		// check for failure
		foreach ($response->updateFailures() as $id => $data) {
			$results[$id] = $data['type'] ?? 'unknownError';
		}

		return $results;
	}

	/**
	 * Modify entity flags in remote storage
	 *
	 * @param array $flags list of flags to set on entity (e.g. ['seen' => true, 'flagged' => false])
	 * @param string ...$identifiers remote identifiers to apply flag modifications to
	 *
	 * @return array<string, bool|string> map of remote identifiers to modification result (true for success, error type for failure)
	 */
	public function entityModifyFlags(array $flags, string ...$identifiers): ?array {
		// construct patch request with flag modifications
		//$patch = new MailParametersRequest();
		// TODO: add keywords() method to jmap client library to avoid using parametersRaw
		//$patch->parametersRaw(['keywords' => $flags]);
		$patch = [];
		foreach ($flags as $flag => $value) {
			$patch['keywords/' . $flag] = $value;
		}

		// execute patch modification
		$result = $this->entityModifyPatch($patch, ...$identifiers);
		return $result;
	}

	/**
	 * Delete entity in remote storage
	 *
	 * @param string ...$identifiers remote identifiers to delete
	 *
	 * @return array<string, bool|string> map of remote identifiers to deletion result (true for success, error type for failure)
	 */
	public function entityDelete(string ...$identifiers): array {
		// construct set request
		$r0 = new MailSet($this->dataAccount);
		foreach ($identifiers as $id) {
			$r0->delete($id);
		}
		// transceive
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// check for command error
		if ($response instanceof ResponseException) {
			if ($response->type() === 'unknownMethod') {
				throw new JmapUnknownMethod($response->description(), 1);
			} else {
				throw new Exception($response->type() . ': ' . $response->description(), 1);
			}
		}

		$results = [];
		// map successful and failed deletions to their identifiers
		foreach ($response->deleteSuccesses() as $id) {
			$results[$id] = true;
		}
		foreach ($response->deleteFailures() as $id => $data) {
			$results[$id] = $data['type'] ?? 'unknownError';
		}

		return $results;
	}

	/**
	 * Move entity to another collection in remote storage
	 *
	 * @param string $target remote identifier of target collection to move entities to
	 * @param string ...$identifiers remote identifiers of entities to move
	 *
	 * @return array<string, bool|string> map of remote identifiers to move result (true for success, error type for failure)
	 */
	public function entityMove(string $target, string ...$identifiers): array {
		// construct patch request with flag modifications
		$patch = new MailParametersRequest();
		$patch->in($target);
		// execute patch modification
		$result = $this->entityModifyPatch($patch, ...$identifiers);
		return $result;
	}

	/**
	 * send entity
	 *
	 *
	 */
	public function entitySend(string $identity, MailMessageObject $message, ?string $presendLocation = null, ?string $postsendLocation = null): string {
		// determine if pre-send location is present
		if ($presendLocation === null || empty($presendLocation)) {
			throw new Exception('Pre-Send Location is missing', 1);
		}
		// determine if post-send location is present
		if ($postsendLocation === null || empty($postsendLocation)) {
			throw new Exception('Post-Send Location is missing', 1);
		}
		// determine if we have the basic required data and fail otherwise
		if (empty($message->getFrom())) {
			throw new Exception('Missing Requirements: Message MUST have a From address', 1);
		}
		if (empty($message->getTo())) {
			throw new Exception('Missing Requirements: Message MUST have a To address(es)', 1);
		}
		// determine if message has attachments
		if (count($message->getAttachments()) > 0) {
			// process attachments first
			$message = $this->depositAttachmentsFromMessage($message);
		}
		// convert from address object to string
		$from = $message->getFrom()->getAddress();
		// convert to, cc and bcc address object arrays to single strings array
		$to = array_map(
			function ($entry) { return $entry->getAddress(); },
			array_merge($message->getTo(), $message->getCc(), $message->getBcc())
		);
		unset($cc, $bcc);
		// construct set request
		$r0 = new MailSet($this->dataAccount);
		$r0->create('1', $message)->in($presendLocation);
		// construct set request
		$r1 = new MailSubmissionSet($this->dataAccount);
		// construct envelope
		$e1 = $r1->create('2');
		$e1->identity($identity);
		$e1->message('#1');
		$e1->from($from);
		$e1->to($to);
		// transceive
		$bundle = $this->dataStore->perform([$r0, $r1]);
		// extract response
		$response = $bundle->response(1);
		// return collection information
		return (string)$response->created()['2']['id'];
	}

	/**
	 * retrieve collection entity attachment from remote storage
	 *
	 *
	 */
	public function depositAttachmentsFromMessage(MailMessageObject $message): MailMessageObject {

		$parameters = $message->toJmap();
		$attachments = $message->getAttachments();
		$matches = [];

		$this->findAttachmentParts($parameters['bodyStructure'], $matches);

		foreach ($attachments as $attachment) {
			$part = $attachment->toJmap();
			if (isset($matches[$part->getId()])) {
				// deposit attachment in data store
				$response = $this->blobDeposit($account, $part->getType(), $attachment->getContents());
				// transfer blobId and size to mail part
				$matches[$part->getId()]->blobId = $response['blobId'];
				$matches[$part->getId()]->size = $response['size'];
				unset($matches[$part->getId()]->partId);
			}
		}

		return (new MailMessageObject())->fromJmap($parameters);

	}

	protected function findAttachmentParts(object &$part, array &$matches) {

		if ($part->disposition === 'attachment' || $part->disposition === 'inline') {
			$matches[$part->partId] = $part;
		}

		foreach ($part->subParts as $entry) {
			$this->findAttachmentParts($entry, $matches);
		}

	}

	/**
	 * retrieve identity from remote storage
	 *
	 *
	 */
	public function identityFetch(?string $account = null): array {
		if ($account === null) {
			$account = $this->dataAccount;
		}
		// construct set request
		$r0 = new MailIdentityGet($this->dataAccount);
		// transmit request and receive response
		$bundle = $this->dataStore->perform([$r0]);
		// extract response
		$response = $bundle->first();
		// convert json object to message object and return
		return $response->objects();
	}

}
