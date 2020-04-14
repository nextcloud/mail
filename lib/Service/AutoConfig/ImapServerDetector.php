<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\AutoConfig;

use OCA\Mail\Db\MailAccount;

class ImapServerDetector {

	/** @var MxRecord */
	private $mxRecord;

	/** @var ImapConnectivityTester */
	private $imapConnectivityTester;

	/**
	 * @param MxRecord $mxRecord
	 * @param ImapConnectivityTester $imapTester
	 */
	public function __construct(MxRecord $mxRecord,
								ImapConnectivityTester $imapTester) {
		$this->mxRecord = $mxRecord;
		$this->imapConnectivityTester = $imapTester;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return MailAccount|null
	 */
	public function detect(string $email,
						   string $password,
						   string $name) {
		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list($user, $host) = explode("@", $email);

		/*
		 * Try to get the mx record for the email address
		 */
		$mxHosts = $this->mxRecord->query($host);
		if ($mxHosts) {
			foreach ($mxHosts as $mxHost) {
				$result = $this->imapConnectivityTester->test(
					$email,
					$mxHost,
					[
						$user,
						$email
					],
					$password,
					$name
				);
				if ($result) {
					return $result;
				}
			}
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		return $this->imapConnectivityTester->test(
			$email,
			$host,
			[
				$user,
				$email
			],
			$password,
			$name
		);
	}
}
