<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

namespace OCA\Mail\Service\AutoConfig;

use OCA\Mail\Db\MailAccount;

class SmtpServerDetector {

	/** @var MxRecord */
	private $mxRecord;

	/** @var SmtpConnectivityTester */
	private $smtpConnectivityTester;

	/** @var bool */
	private $testSmtp;

	/**
	 * @param MxRecord $mxRecord
	 * @param SmtpConnectivityTester $smtpTester
	 * @param bool $testSmtp
	 */
	public function __construct(MxRecord $mxRecord,
		SmtpConnectivityTester $smtpTester, $testSmtp) {
		$this->mxRecord = $mxRecord;
		$this->smtpConnectivityTester = $smtpTester;
		$this->testSmtp = $testSmtp;
	}

	public function detect(MailAccount $account, $email, $password) {
		if ($this->testSmtp === false) {
			return;
		}

		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list($user, $host) = explode("@", $email);

		/*
		 * Try to get the mx record for the email address
		 */
		$mxHosts = $this->mxRecord->query($host);
		if ($mxHosts) {
			foreach ($mxHosts as $mxHost) {
				$result = $this->smtpConnectivityTester->test($account, $mxHost,
					[$user, $email], $password);
				if ($result) {
					return;
				}
			}
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		$this->smtpConnectivityTester->test($account, $host, [$user, $email],
			$password, true);
	}

}
