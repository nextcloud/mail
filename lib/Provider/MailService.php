<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
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
namespace OCA\Mail\Provider;

use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IServiceIdentity;
use OCP\Mail\Provider\IServiceLocation;
use OCP\Mail\Provider\IMessage;

use OCA\Mail\AppInfo\Application;

class MailService implements IService, IMessageSend {

	private string $userId;
	private string $serviceId;
	private string $serviceLabel;
	private string $servicePrimaryAddress;
	private array $serviceSecondaryAddress;
	private MailServiceIdentity $serviceIdentity;
	private MailServiceLocation $serviceLocation;
	private $accountService;

	public function __construct(
		string $uid,
		string $sid,
		string $label,
		string $primaryAddress,
		MailServiceIdentity $identity,
		MailServiceLocation $location
	) {

		$this->userId = $uid;
		$this->serviceId = $sid;
		$this->serviceLabel = $label;
		$this->servicePrimaryAddress = $primaryAddress;
		$this->serviceIdentity = $identity;
		$this->serviceLocation = $location;

	}

	/**
	 * An arbitrary unique text string identifying this service
	 * @since 1.0.0
	 */
	public function id(): string {

		return $this->userId;

	}

	/**
	 * The localized human frendly name of this provider
	 * @since 1.0.0
	 */
	public function getLabel(): string {

		return $this->serviceLabel;

	}

	/**
	 * The localized human frendly name of this provider
	 * @since 1.0.0
	 */
	public function setLabel(string $value) {

		$this->serviceLabel = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getIdentity(): IServiceIdentity {

		return $this->serviceIdentity;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setIdentity(IServiceIdentity $value) {

		$this->serviceIdentity = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getLocation(): IServiceLocation {

		return $this->serviceLocation;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setLocation(IServiceLocation $value) {

		$this->serviceLocation = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setPrimaryAddress(string $value) {

		$this->servicePrimaryAddress = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getPrimaryAddress(): string {

		return $this->servicePrimaryAddress;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setSecondaryAddress(string $array) {

		$this->serviceSecondaryAddress = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getSecondaryAddress(): array | null {

		return $this->serviceSecondaryAddress;

	}

	public function messageSend(IMessage $message, array $option = []): void {

		// evaluate if account service is loaded
		if ($this->accountService === null) {
			$this->accountService = \OC::$server->get(\OCA\Mail\Service\AccountService::class);
		}

		$account = $this->accountService->findById((int) $this->serviceId);

		$lm = new \OCA\Mail\Db\LocalMessage();
		$lm->setType($lm::TYPE_OUTGOING);
		$lm->setAccountId($account->getId());
		$lm->setSubject($message->getSubject());
		$lm->setBody($message->getBody());
		//$lm->setEditorBody($editorBody);
		$lm->setHtml(true);
		//$lm->setInReplyToMessageId($inReplyToMessageId);
		$lm->setSendAt(time());
		//$lm->setSmimeSign($smimeSign);
		//$lm->setSmimeEncrypt($smimeEncrypt);

		/*
		if (!empty($smimeCertificateId)) {
			$smimeCertificate = $this->smimeService->findCertificate($smimeCertificateId, $this->userId);
			$lm->setSmimeCertificateId($smimeCertificate->getId());
		}
		*/
		$attachments = [];
		if (count($message->getAttachments()) > 0) {
			// load attachment service
			$AttachmentService = \OC::$server->get(\OCA\Mail\Service\Attachment\AttachmentService::class);
			// iterate attachments and save them
			foreach ($message->getAttachments() as $entry) {
				$attachments[] = $AttachmentService->addFileFromString(
					$this->userId,
					$entry->getName(),
					$entry->getType(),
					$entry->getContents()
				);
			}
		}

		// load outbound mail service
		$OutboxService = \OC::$server->get(\OCA\Mail\Service\OutboxService::class);

		$to = $this->convertAddressArray($message->getTo());
		$cc = $this->convertAddressArray($message->getCc());
		$bcc = $this->convertAddressArray($message->getBcc());

		$OutboxService->saveMessage(
			$account,
			$lm,
			$to,
			$cc,
			$bcc,
			$attachments
		);

	}

	protected function convertAddressArray(array|null $in) {
		// construct place holder
		$out = [];
		// convert format
		foreach ($in as $entry) {
			$out[] = (!empty($entry->getName())) ? ['email' => $entry->getAddress(), 'label' => $entry->getName()] : ['email' => $entry->getAddress()];
		}
		// return converted addressess
		return $out;
	}

}
