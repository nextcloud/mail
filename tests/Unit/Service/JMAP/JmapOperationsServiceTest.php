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
use JmapClient\Responses\ResponseBundle;
use JmapClient\Responses\ResponseException;
use JmapClient\Session\Account as JmapSessionAccount;
use JmapClient\Session\Session;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\Exception\JmapException;
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

	private function account(int $id): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId($id);
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);
		return new Account($mailAccount);
	}

	/**
	 * @return Client&MockObject
	 */
	private function client(bool $connected, ?string $sessionAccountId = 'u1'): Client {
		$client = $this->createMock(Client::class);
		$client->method('sessionStatus')->willReturn($connected);
		$client->method('sessionAccountDefault')
			->with('mail')
			->willReturn($sessionAccountId === null ? null : new JmapSessionAccount($sessionAccountId, []));
		return $client;
	}

	/**
	 * Returns the JMAP account id the service sends with its next request.
	 */
	private function requestedSessionAccountId(Client&MockObject $client): string {
		$accountId = null;
		$client->method('perform')
			->willReturnCallback(function (array $commands) use (&$accountId) {
				$accountId = $commands[0]->jsonSerialize()[1]['accountId'];
				throw new \RuntimeException('stop');
			});
		try {
			$this->service->collectionList();
		} catch (ServiceException) {
		}
		return $accountId;
	}

	public function testConnectReusesConnectedClient(): void {
		$account = $this->account(1);
		$client = $this->client(true);
		$client->expects(self::never())
			->method('connect');
		$this->protocolFactory->method('jmapClient')
			->with($account)
			->willReturn($client);

		$result = $this->service->connect($account);

		self::assertTrue($result);
	}

	public function testConnectEstablishesSessionWhenNotConnected(): void {
		$account = $this->account(1);
		$client = $this->client(false);
		$client->expects(self::once())
			->method('connect')
			->willReturn(new Session([]));
		$this->protocolFactory->method('jmapClient')
			->willReturn($client);

		$result = $this->service->connect($account);

		self::assertTrue($result);
	}

	public function testConnectWrapsConnectionFailure(): void {
		$account = $this->account(1);
		$client = $this->client(false);
		$client->method('connect')
			->willThrowException(new \RuntimeException('Connection refused'));
		$this->protocolFactory->method('jmapClient')
			->willReturn($client);
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Could not connect to JMAP server: Connection refused');

		$this->service->connect($account);
	}

	public function testConnectFailsWithoutDefaultMailAccount(): void {
		$account = $this->account(1);
		$client = $this->client(true, null);
		$this->protocolFactory->method('jmapClient')
			->willReturn($client);
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('JMAP session does not provide a default mail account');

		$this->service->connect($account);
	}

	public function testConnectSwitchesBetweenAccounts(): void {
		$accountA = $this->account(1);
		$accountB = $this->account(2);
		$clientA = $this->client(true, 'jmap-a');
		$clientB = $this->client(true, 'jmap-b');
		$this->protocolFactory->method('jmapClient')
			->willReturnCallback(fn (Account $account) => match ($account) {
				$accountA => $clientA,
				$accountB => $clientB,
			});

		$this->service->connect($accountA);
		$this->service->connect($accountB);
		$this->service->connect($accountA);

		self::assertSame('jmap-a', $this->requestedSessionAccountId($clientA));
	}
}
