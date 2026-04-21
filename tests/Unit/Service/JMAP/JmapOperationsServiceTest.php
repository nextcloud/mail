<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use JmapClient\Client;
use JmapClient\Requests\Mail\MailParameters as MailParametersRequest;
use JmapClient\Responses\ResponseBundle;
use OCA\Mail\JMAP\JmapMailboxAdapter;
use OCA\Mail\JMAP\JmapMessageAdapter;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

class JmapOperationsServiceTest extends TestCase {

	private ProtocolFactory|MockObject $protocolFactory;
	private JmapMailboxAdapter|MockObject $jmapMailboxAdapter;
	private JmapMessageAdapter|MockObject $jmapMessageAdapter;
	private Client|MockObject $dataStore;
	private JmapOperationsService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->jmapMailboxAdapter = $this->createMock(JmapMailboxAdapter::class);
		$this->jmapMessageAdapter = $this->createMock(JmapMessageAdapter::class);
		$this->dataStore = $this->createMock(Client::class);

		$this->service = new JmapOperationsService(
			$this->protocolFactory,
			$this->jmapMailboxAdapter,
			$this->jmapMessageAdapter,
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

		$this->expectException(Exception::class);
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

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Blob upload did not return a blob id');

		$this->service->entitySave(new MailParametersRequest(), $attachments);
	}
}
