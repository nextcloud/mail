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
use OCP\Mail\IMessage;

use OCA\Mail\AppInfo\Application;

class MailService implements IService, IMessageSend {

	private string $_id;
	private string $_label;
	private string $primaryAddress;
	private array $secondaryAddress;
	private MailServiceIdentity $_identity;
	private MailServiceLocation $_location;
	private $accountService;

	public function __construct(
		string $id,
		string $label,
		string $primaryAddress,
		MailServiceIdentity $identity,
		MailServiceLocation $location
	) {

		$this->_id = $id;
		$this->_label = $label;
		$this->primaryAddress = $primaryAddress;
		$this->_identity = $identity;
		$this->_location = $location;

	}

	/**
	 * An arbitrary unique text string identifying this service
	 * @since 1.0.0
	 */
	public function id(): string {

		return $this->_id;

	}

	/**
	 * The localized human frendly name of this provider
	 * @since 1.0.0
	 */
	public function getLabel(): string {

		return $this->_label;

	}

	/**
	 * The localized human frendly name of this provider
	 * @since 1.0.0
	 */
	public function setLabel(string $value) {

		$this->_label = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getIdentity(): IServiceIdentity {

		return $this->_identity;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setIdentity(IServiceIdentity $value) {

		$this->_identity = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getLocation(): IServiceLocation {

		return $this->_location;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setLocation(IServiceLocation $value) {

		$this->_location = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setPrimaryAddress(string $value) {

		$this->primaryAddress = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getPrimaryAddress(): string {

		return $this->primaryAddress;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setSecondaryAddress(string $array) {

		$this->secondaryAddress = $value;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getSecondaryAddress(): array | null {

		return $this->secondaryAddress;

	}

	public function messageSend(IMessage $message, array $option = []): void {

		// evaluate if account service is loaded
		if ($this->accountService === null) {
			$this->accountService = \OC::$server->get(\OCA\Mail\Service\AccountService::class);
		}

		$account = $this->accountService->findById((int) $this->_id);

		$lm = new \OCA\Mail\Db\LocalMessage();
		$lm->setType($lm::TYPE_OUTGOING);
		$lm->setAccountId($account->getId());
		$lm->setSubject($message->getSubject());
		$lm->setBody($message->getPlainBody());
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

		$service = \OC::$server->get(\OCA\Mail\Service\OutboxService::class);

		$to = $this->convertAddressArray($message->getTo());
		$cc = $this->convertAddressArray($message->getCc());
		$bcc = $this->convertAddressArray($message->getBcc());

		$service->saveMessage(
			$account,
			$lm,
			$to,
			$cc,
			$bcc,
			[]
		);

	}

	protected function convertAddressArray(array $in) {
		// construct place holder
		$out = [];
		// convert format
		foreach ($in as $key => $value) {
			$out[] = ($value) ? ['email' => $key, 'label' => $value] : ['email' => $key];
		}
		// return converted addressess
		return $out;
	}

}
