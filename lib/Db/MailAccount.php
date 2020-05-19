<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class MailAccount
 *
 * @package OCA\Mail\Db
 *
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getInboundHost()
 * @method void setInboundHost(string $inboundHost)
 * @method integer getInboundPort()
 * @method void setInboundPort(integer $inboundPort)
 * @method string getInboundSslMode()
 * @method void setInboundSslMode(string $inboundSslMode)
 * @method string getInboundUser()
 * @method void setInboundUser(string $inboundUser)
 * @method string|null getInboundPassword()
 * @method void setInboundPassword(string $inboundPassword)
 * @method string getOutboundHost()
 * @method void setOutboundHost(string $outboundHost)
 * @method integer getOutboundPort()
 * @method void setOutboundPort(integer $outboundPort)
 * @method string getOutboundSslMode()
 * @method void setOutboundSslMode(string $outboundSslMode)
 * @method string getOutboundUser()
 * @method void setOutboundUser(string $outboundUser)
 * @method string|null getOutboundPassword()
 * @method void setOutboundPassword(string $outboundPassword)
 * @method string|null getSignature()
 * @method void setSignature(string|null $signature)
 * @method int getLastMailboxSync()
 * @method void setLastMailboxSync(int $time)
 * @method string getEditorMode()
 * @method void setEditorMode(string $editorMode)
 * @method bool getProvisioned()
 * @method void setProvisioned(bool $provisioned)
 * @method int getOrder()
 * @method void setOrder(int $order)
 * @method bool getShowSubscribedOnly()
 * @method void setShowSubscribedOnly(bool $showSubscribedOnly)
 */
class MailAccount extends Entity {
	protected $userId;
	protected $name;
	protected $email;
	protected $inboundHost;
	protected $inboundPort;
	protected $inboundSslMode;
	protected $inboundUser;
	protected $inboundPassword;
	protected $outboundHost;
	protected $outboundPort;
	protected $outboundSslMode;
	protected $outboundUser;
	protected $outboundPassword;
	protected $signature;
	protected $lastMailboxSync;
	protected $editorMode;
	protected $provisioned;
	protected $order;
	protected $showSubscribedOnly;

	/**
	 * @param array $params
	 */
	public function __construct(array $params=[]) {
		if (isset($params['accountId'])) {
			$this->setId($params['accountId']);
		}
		if (isset($params['accountName'])) {
			$this->setName($params['accountName']);
		}
		if (isset($params['emailAddress'])) {
			$this->setEmail($params['emailAddress']);
		}

		if (isset($params['imapHost'])) {
			$this->setInboundHost($params['imapHost']);
		}
		if (isset($params['imapPort'])) {
			$this->setInboundPort($params['imapPort']);
		}
		if (isset($params['imapSslMode'])) {
			$this->setInboundSslMode($params['imapSslMode']);
		}
		if (isset($params['imapUser'])) {
			$this->setInboundUser($params['imapUser']);
		}
		if (isset($params['imapPassword'])) {
			$this->setInboundPassword($params['imapPassword']);
		}

		if (isset($params['smtpHost'])) {
			$this->setOutboundHost($params['smtpHost']);
		}
		if (isset($params['smtpPort'])) {
			$this->setOutboundPort($params['smtpPort']);
		}
		if (isset($params['smtpSslMode'])) {
			$this->setOutboundSslMode($params['smtpSslMode']);
		}
		if (isset($params['smtpUser'])) {
			$this->setOutboundUser($params['smtpUser']);
		}
		if (isset($params['smtpPassword'])) {
			$this->setOutboundPassword($params['smtpPassword']);
		}
		if (isset($params['showSubscribedOnly'])) {
			$this->setShowSubscribedOnly($params['showSubscribedOnly']);
		}

		$this->addType('inboundPort', 'integer');
		$this->addType('outboundPort', 'integer');
		$this->addType('lastMailboxSync', 'integer');
		$this->addType('provisioned', 'bool');
		$this->addType('order', 'integer');
		$this->addType('showSubscribedOnly', 'boolean');
	}

	/**
	 * @return array
	 */
	public function toJson() {
		$result = [
			'accountId' => $this->getId(),
			'name' => $this->getName(),
			'order' => $this->getOrder(),
			'emailAddress' => $this->getEmail(),
			'imapHost' => $this->getInboundHost(),
			'imapPort' => $this->getInboundPort(),
			'imapUser' => $this->getInboundUser(),
			'imapSslMode' => $this->getInboundSslMode(),
			'signature' => $this->getSignature(),
			'editorMode' => $this->getEditorMode(),
			'provisioned' => $this->getProvisioned(),
			'showSubscribedOnly' => $this->getShowSubscribedOnly(),
		];

		if (!is_null($this->getOutboundHost())) {
			$result['smtpHost'] = $this->getOutboundHost();
			$result['smtpPort'] = $this->getOutboundPort();
			$result['smtpUser'] = $this->getOutboundUser();
			$result['smtpSslMode'] = $this->getOutboundSslMode();
		}

		return $result;
	}
}
