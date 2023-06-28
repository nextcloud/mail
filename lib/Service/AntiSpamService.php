<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author Anna Larch <anna@nextcloud.com>
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

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\SentMailboxNotSetException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\NewMessageData;
use OCP\IConfig;

class AntiSpamService {
	private const NAME = 'antispam_reporting';
	private const MESSAGE_TYPE = 'message/rfc822';

	/** @var IConfig */
	private $config;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var IMailTransmission */
	private $transmission;

	public function __construct(IConfig $config,
		MessageMapper $messageMapper,
		IMailTransmission $transmission) {
		$this->config = $config;
		$this->messageMapper = $messageMapper;
		$this->transmission = $transmission;
	}

	public function getSpamEmail(): string {
		return $this->config->getAppValue('mail', self::NAME . '_spam');
	}

	public function getHamEmail(): string {
		return $this->config->getAppValue('mail', self::NAME. '_ham');
	}

	public function getSpamSubject(): string {
		return 'Learn as Junk';
	}

	public function getHamSubject(): string {
		return 'Learn as Not Junk';
	}

	public function setSpamEmail(string $email): void {
		$this->config->setAppValue('mail', self::NAME . '_spam', $email);
	}

	public function setHamEmail(string $email): void {
		$this->config->setAppValue('mail', self::NAME. '_ham', $email);
	}

	public function deleteConfig(): void {
		$this->config->deleteAppValue('mail', self::NAME . '_spam');
		$this->config->deleteAppValue('mail', self::NAME . '_ham');
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $uid
	 * @param string $flag
	 * @throws ServiceException
	 */
	public function sendReportEmail(Account $account, Mailbox $mailbox, int $uid, string $flag): void {
		$reportEmail = ($flag === '$junk') ? $this->getSpamEmail() : $this->getHamEmail();
		if ($reportEmail === '') {
			return;
		}
		$subject = ($flag === '$junk') ? $this->getSpamSubject() : $this->getHamSubject();

		// Message to attach not found
		$messageId = $this->messageMapper->getIdForUid($mailbox, $uid);
		if ($messageId === null) {
			throw new ServiceException('Could not find reported message');
		}

		$messageData = NewMessageData::fromRequest(
			$account,
			$reportEmail,
			null,
			null,
			$subject,
			$subject, // add any message body - not all IMAP servers accept empty emails
			[['id' => $messageId, 'type' => self::MESSAGE_TYPE]]
		);

		try {
			$this->transmission->sendMessage($messageData);
		} catch (SentMailboxNotSetException | ServiceException $e) {
			throw new ServiceException('Could not send report email from anti spam email service', 0, $e);
		}
	}
}
