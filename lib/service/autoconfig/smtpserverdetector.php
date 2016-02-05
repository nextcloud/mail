<?php

namespace OCA\Mail\Service\AutoConfig;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
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
