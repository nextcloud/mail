<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Client;
use JmapClient\Requests\Mail\MailParameters as MailParametersRequest;
use JmapClient\Requests\Request;
use JmapClient\Responses\Mail\MailParameters as MailParametersResponse;
use JmapClient\Responses\ResponseBundle;
use JmapClient\Responses\ResponseException;
use JmapClient\Session\Session;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\Exception\JmapException;
use OCA\Mail\JMAP\Exception\JmapUnknownMethod;
use OCA\Mail\JMAP\JmapMailboxAdapter;
use OCA\Mail\JMAP\JmapMessageAdapter;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use RuntimeException;

class JmapOperationsServiceTest extends TestCase {

	private ProtocolFactory|MockObject $protocolFactory;
	private JmapMailboxAdapter|MockObject $jmapMailboxAdapter;
	private JmapMessageAdapter|MockObject $jmapMessageAdapter;
	private LoggerInterface|MockObject $logger;
	private Client|MockObject $dataStore;
	private JmapOperationsService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->jmapMailboxAdapter = $this->createMock(JmapMailboxAdapter::class);
		$this->jmapMessageAdapter = $this->createMock(JmapMessageAdapter::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dataStore = $this->createMock(Client::class);

		$this->service = new JmapOperationsService(
			$this->protocolFactory,
			$this->jmapMailboxAdapter,
			$this->jmapMessageAdapter,
			$this->logger,
		);

		// Bypass connect()'s session handshake and inject an already-connected data store,
		// since attachmentUpload() only needs the data store and account id to be set.
		$dataStoreProperty = new ReflectionProperty(JmapOperationsService::class, 'dataStore');
		$dataStoreProperty->setAccessible(true);
		$dataStoreProperty->setValue($this->service, $this->dataStore);

		$dataAccountProperty = new ReflectionProperty(JmapOperationsService::class, 'dataAccount');
		$dataAccountProperty->setAccessible(true);
		$dataAccountProperty->setValue($this->service, 'account1');
	}

	public function testAttachmentUpload(): void {
		$content = 'hello world';

		$this->dataStore->expects(self::once())
			->method('upload')
			->with('account1', 'text/plain', $content)
			->willReturn(json_encode(['accountId' => 'account1', 'blobId' => 'blob123', 'type' => 'text/plain', 'size' => strlen($content)]));

		$blobId = $this->service->attachmentUpload('text/plain', $content);

		$this->assertEquals('blob123', $blobId);
	}

	public function testAttachmentUploadMissingBlobIdThrows(): void {
		$this->dataStore->expects(self::once())
			->method('upload')
			->willReturn(json_encode(['accountId' => 'account1']));

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Blob upload did not return a blob id');

		$this->service->attachmentUpload('text/plain', 'hello world');
	}

	/**
	 * Finds the sub-part carrying a blobId in a bound bodyStructure (attachments are
	 * appended as bodyStructure sibling parts, not via the separate "attachments"
	 * property, since a server rejects a create that sets both at once).
	 */
	private function findAttachmentPart(object $bodyStructure): ?object {
		foreach ($bodyStructure->subParts ?? [] as $subPart) {
			if (isset($subPart->blobId)) {
				return $subPart;
			}
		}
		return null;
	}

	public function testEntitySaveWithAttachmentsUploadsAndWiresBlobId(): void {
		$attachments = [
			[
				'content' => 'binary-jpeg-bytes',
				'type' => 'image/jpeg',
				'name' => 'photo.jpg',
				'disposition' => 'attachment',
				'contentId' => null,
			],
		];

		$this->dataStore->expects(self::once())
			->method('upload')
			->with('account1', 'image/jpeg', 'binary-jpeg-bytes')
			->willReturn(json_encode(['accountId' => 'account1', 'blobId' => 'real-uploaded-blob']));

		$captured = [];
		$this->dataStore->expects(self::once())
			->method('perform')
			->willReturnCallback(function (array $requests) use (&$captured) {
				$captured = $requests;
				$mailSetWire = $requests[0]->jsonSerialize();
				$emailId = array_key_first($mailSetWire[1]['create']);
				return new ResponseBundle([
					'methodResponses' => [
						['Email/set', ['created' => [$emailId => ['id' => 'remote-email-1']]], 'c0'],
					],
				]);
			});

		// Mimics JmapTransmissionConnector::buildEmailParams(), which always builds an
		// explicit multipart/mixed bodyStructure before entitySave() wires attachments in.
		$email = new MailParametersRequest();
		$body = $email->bodyPartStructure();
		$body->type('multipart/mixed');
		$body->addPart()->id('text-plain')->type('text/plain')->charset('utf-8');
		$email->bodyPartValue('text-plain', 'body');

		$remoteId = $this->service->entitySave($email, $attachments);

		$this->assertEquals('remote-email-1', $remoteId);
		$this->assertCount(1, $captured);

		$mailSetWire = $captured[0]->jsonSerialize();
		$emailId = array_key_first($mailSetWire[1]['create']);
		$emailCreateObj = $mailSetWire[1]['create'][$emailId];
		$this->assertFalse(isset($emailCreateObj->attachments), 'must not set the separate "attachments" property alongside bodyStructure');
		$attachmentPart = $this->findAttachmentPart($emailCreateObj->bodyStructure);
		$this->assertNotNull($attachmentPart);
		$this->assertEquals('real-uploaded-blob', $attachmentPart->blobId);
		$this->assertEquals('photo.jpg', $attachmentPart->name);
		$this->assertFalse(isset($attachmentPart->partId), 'must not set partId alongside blobId');
	}

	public function testEntitySaveWithoutDispositionOmitsNameAndDisposition(): void {
		$attachments = [
			[
				'content' => 'BEGIN:VCALENDAR',
				'type' => 'text/calendar; method=REQUEST',
				'name' => '',
				'disposition' => null,
				'contentId' => null,
			],
		];

		$this->dataStore->method('upload')
			->willReturn(json_encode(['accountId' => 'account1', 'blobId' => 'calendar-blob']));
		$captured = [];
		$this->dataStore->expects(self::once())
			->method('perform')
			->willReturnCallback(function (array $requests) use (&$captured) {
				$captured = $requests;
				$emailId = array_key_first($requests[0]->jsonSerialize()[1]['create']);
				return new ResponseBundle([
					'methodResponses' => [
						['Email/set', ['created' => [$emailId => ['id' => 'remote-email-2']]], 'c0'],
					],
				]);
			});

		$email = new MailParametersRequest();
		$email->bodyPartStructure()->type('multipart/mixed');

		$this->service->entitySave($email, $attachments);

		$mailSetWire = $captured[0]->jsonSerialize();
		$emailCreateObj = $mailSetWire[1]['create'][array_key_first($mailSetWire[1]['create'])];
		$attachmentPart = $this->findAttachmentPart($emailCreateObj->bodyStructure);
		$this->assertNotNull($attachmentPart);
		$this->assertFalse(isset($attachmentPart->name));
		$this->assertFalse(isset($attachmentPart->disposition));
	}

	public function testEntitySaveWithoutAttachmentsDoesNotTouchUpload(): void {
		$this->dataStore->expects(self::never())->method('upload');
		$this->dataStore->expects(self::once())
			->method('perform')
			->willReturnCallback(function (array $requests) {
				$mailSetWire = $requests[0]->jsonSerialize();
				$emailId = array_key_first($mailSetWire[1]['create']);
				return new ResponseBundle([
					'methodResponses' => [
						['Email/set', ['created' => [$emailId => ['id' => 'remote-email-3']]], 'c0'],
					],
				]);
			});

		$email = new MailParametersRequest();
		$remoteId = $this->service->entitySave($email, []);

		$this->assertEquals('remote-email-3', $remoteId);
	}

	public function testEntitySaveThrowsWhenAttachmentUploadFails(): void {
		$attachments = [
			[
				'content' => 'binary-jpeg-bytes',
				'type' => 'image/jpeg',
				'name' => 'photo.jpg',
				'disposition' => 'attachment',
				'contentId' => null,
			],
		];

		$this->dataStore->expects(self::once())
			->method('upload')
			->willReturn(json_encode(['accountId' => 'account1']));
		$this->dataStore->expects(self::never())->method('perform');

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Blob upload did not return a blob id');

		$this->service->entitySave(new MailParametersRequest(), $attachments);
	}

	private function sendEmail(): void {
		$email = new MailParametersRequest();
		$email->subject('Test');
		$this->service->entitySend('identity-1', $email, 'drafts-remote', 'sent-remote', 'bob@example.com', ['alice@example.com']);
	}

	public function testEntitySend(): void {
		$this->dataStore->expects(self::once())
			->method('perform')
			->willReturn(new ResponseBundle([
				'methodResponses' => [
					['Email/set', ['created' => ['1' => ['id' => 'staged-1']]], 'c0'],
					['EmailSubmission/set', ['created' => ['2' => ['id' => 'submission-1']]], 'c1'],
				],
			]));

		$this->sendEmail();
	}

	public function testEntitySendWithoutRecipients(): void {
		$this->dataStore->expects(self::never())->method('perform');

		try {
			$this->service->entitySend('identity-1', new MailParametersRequest(), 'drafts-remote', 'sent-remote', 'bob@example.com', []);
			$this->fail('Expected JmapException');
		} catch (JmapException $e) {
			$this->assertSame(ResponseException::INVALID_ARGUMENTS, $e->getType());
			$this->assertFalse($e->isOutcomeUncertain());
		}
	}

	public function testEntitySendRequestFailureIsUncertain(): void {
		$this->dataStore->expects(self::once())
			->method('perform')
			->willThrowException(new RuntimeException('connection reset'));

		try {
			$this->sendEmail();
			$this->fail('Expected JmapException');
		} catch (JmapException $e) {
			$this->assertTrue($e->isOutcomeUncertain());
		}
	}

	public function testEntitySendSaveFailure(): void {
		$this->dataStore->expects(self::once())
			->method('perform')
			->willReturn(new ResponseBundle([
				'methodResponses' => [
					['Email/set', ['notCreated' => ['1' => ['type' => 'overQuota']]], 'c0'],
					['error', ['type' => 'invalidResultReference'], 'c1'],
				],
			]));

		try {
			$this->sendEmail();
			$this->fail('Expected JmapException');
		} catch (JmapException $e) {
			$this->assertSame('overQuota', $e->getType());
			$this->assertFalse($e->isOutcomeUncertain());
		}
	}

	public function testEntitySendSubmissionFailureDiscardsStagedEmail(): void {
		$requests = [];
		$this->dataStore->expects(self::exactly(2))
			->method('perform')
			->willReturnCallback(function (array $commands) use (&$requests) {
				$requests[] = $commands;
				if (count($requests) === 1) {
					return new ResponseBundle([
						'methodResponses' => [
							['Email/set', ['created' => ['1' => ['id' => 'staged-1']]], 'c0'],
							['EmailSubmission/set', ['notCreated' => ['2' => ['type' => 'forbiddenToSend']]], 'c1'],
						],
					]);
				}
				return new ResponseBundle([
					'methodResponses' => [
						['Email/set', ['destroyed' => ['staged-1']], 'c0'],
					],
				]);
			});
		$this->logger->expects(self::never())->method('warning');

		try {
			$this->sendEmail();
			$this->fail('Expected JmapException');
		} catch (JmapException $e) {
			$this->assertSame('forbiddenToSend', $e->getType());
			$this->assertFalse($e->isOutcomeUncertain());
		}
		$this->assertSame(['staged-1'], $requests[1][0]->jsonSerialize()[1]['destroy']);
	}

	public function testEntitySendSubmissionFailureLogsWhenDiscardFails(): void {
		$this->dataStore->expects(self::exactly(2))
			->method('perform')
			->willReturnOnConsecutiveCalls(
				new ResponseBundle([
					'methodResponses' => [
						['Email/set', ['created' => ['1' => ['id' => 'staged-1']]], 'c0'],
						['error', ['type' => ResponseException::SERVER_UNAVAILABLE, 'description' => 'try later'], 'c1'],
					],
				]),
				$this->throwException(new RuntimeException('connection reset')),
			);
		$this->logger->expects(self::once())->method('warning');

		try {
			$this->sendEmail();
			$this->fail('Expected JmapException');
		} catch (JmapException $e) {
			$this->assertSame(ResponseException::SERVER_UNAVAILABLE, $e->getType());
		}
	}

	public function testEntityIdentifiersReturnsEveryPage(): void {
		$ids = $this->remoteIds(1204);
		$requests = [];
		$this->dataStore->method('sessionStatus')->willReturn(true);
		$this->dataStore->method('perform')
			->willReturnCallback(function (array $commands) use ($ids, &$requests) {
				$requests[] = $commands[0]->jsonSerialize()[1];
				return $this->queryResponse($ids, $commands[0]);
			});

		$result = $this->service->entityIdentifiers('mailbox-1');

		$this->assertSame(['state' => 'query-state', 'list' => $ids], $result);
		$this->assertCount(3, $requests);
		$this->assertSame('mailbox-1', $requests[0]['filter']->inMailbox);
		$this->assertSame(1000, $requests[2]['position']);
	}

	public function testEntityDeltaWithoutStateReturnsEveryIdentifierAsAddition(): void {
		$ids = $this->remoteIds(600);
		$this->dataStore->method('sessionStatus')->willReturn(true);
		$this->dataStore->method('perform')
			->willReturnCallback(fn (array $commands) => $this->queryResponse($ids, $commands[0]));

		$delta = $this->service->entityDelta('mailbox-1', '');

		$this->assertSame([
			'state' => 'query-state',
			'additions' => $ids,
			'modifications' => [],
			'deletions' => [],
		], $delta);
	}

	public function testEntityIdentifiersThrowsUnknownMethod(): void {
		$this->dataStore->method('sessionStatus')->willReturn(true);
		$this->dataStore->method('perform')->willReturn(new ResponseBundle([
			'methodResponses' => [
				['error', ['type' => ResponseException::UNKNOWN_METHOD, 'description' => 'no query'], 'c0'],
			],
		]));

		$this->expectException(JmapUnknownMethod::class);
		$this->expectExceptionMessage('no query');

		$this->service->entityIdentifiers('mailbox-1');
	}

	public function testEntityFetchMessagePagesFetchesWithinTheServerLimit(): void {
		$this->dataStore->method('sessionStatus')->willReturn(true);
		$this->dataStore->method('sessionData')->willReturn(new Session([
			'capabilities' => ['urn:ietf:params:jmap:core' => ['maxObjectsInGet' => 2, 'maxCallsInRequest' => 1]],
		]));
		$requested = [];
		$this->dataStore->expects(self::exactly(3))
			->method('perform')
			->willReturnCallback(function (array $commands) use (&$requested) {
				$ids = $commands[0]->jsonSerialize()[1]['ids'];
				$requested[] = $ids;
				return $this->getResponse($ids);
			});
		$this->jmapMessageAdapter->method('convertToDatabaseMessage')
			->willReturnCallback(static function (MailParametersResponse $entry): Message {
				$message = new Message();
				$message->setRemoteId($entry->id());
				return $message;
			});

		$pages = iterator_to_array($this->service->entityFetchMessagePages('m1', 'm2', 'm3', 'm4', 'm5'), false);

		$this->assertSame([['m1', 'm2'], ['m3', 'm4'], ['m5']], $requested);
		$this->assertSame([['m1', 'm2'], ['m3', 'm4'], ['m5']], array_map(array_keys(...), $pages));
		$this->assertSame('m3', $pages[1]['m3']->getRemoteId());
	}

	public function testEntityFetchMessagePagesThrowsServiceExceptionOnMethodError(): void {
		$this->dataStore->method('sessionStatus')->willReturn(true);
		$this->dataStore->method('perform')->willReturn(new ResponseBundle([
			'methodResponses' => [
				['error', ['type' => ResponseException::SERVER_FAIL, 'description' => 'broken'], 'c0'],
			],
		]));

		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('serverFail: broken');

		iterator_to_array($this->service->entityFetchMessagePages('m1'), false);
	}

	public function testEntityFetchNativeCollectsEveryPage(): void {
		$this->dataStore->method('sessionStatus')->willReturn(true);
		$this->dataStore->method('sessionData')->willReturn(new Session([
			'capabilities' => ['urn:ietf:params:jmap:core' => ['maxObjectsInGet' => 2]],
		]));
		$this->dataStore->method('perform')
			->willReturnCallback(fn (array $commands) => $this->getResponse(...array_map(
				static fn ($command) => $command->jsonSerialize()[1]['ids'],
				$commands,
			)));

		$result = $this->service->entityFetchNative('m1', 'm2', 'm3');

		$this->assertSame(['m1', 'm2', 'm3'], array_keys($result));
		$this->assertContainsOnlyInstancesOf(MailParametersResponse::class, $result);
	}

	public function testEntityFetchNativeWithoutIdentifiersSendsNothing(): void {
		$this->dataStore->expects(self::never())->method('perform');

		$this->assertSame([], $this->service->entityFetchNative());
	}

	/**
	 * @return list<string>
	 */
	private function remoteIds(int $count): array {
		return array_map(static fn (int $n): string => 'm' . $n, range(1, $count));
	}

	/**
	 * @param list<string> $ids
	 */
	private function queryResponse(array $ids, Request $query): ResponseBundle {
		$arguments = $query->jsonSerialize()[1];
		$position = $arguments['position'] ?? 0;
		return new ResponseBundle([
			'methodResponses' => [
				['Email/query', [
					'queryState' => 'query-state',
					'position' => $position,
					'total' => count($ids),
					'limit' => 500,
					'ids' => array_slice($ids, $position, 500),
				], $query->getIdentifier()],
			],
		]);
	}

	/**
	 * @param list<string> ...$calls ids requested by each get call
	 */
	private function getResponse(array ...$calls): ResponseBundle {
		$responses = [];
		foreach ($calls as $index => $ids) {
			$list = array_map(static fn (string $id): array => ['id' => $id], $ids);
			$responses[] = ['Email/get', ['state' => 'state-1', 'list' => $list, 'notFound' => []], 'c' . $index];
		}
		return new ResponseBundle(['methodResponses' => $responses]);
	}
}
