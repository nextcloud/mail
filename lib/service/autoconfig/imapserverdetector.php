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
	 * @param $email
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
	 */
	public function detect($email, $password, $name) {
		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list($user, $host) = explode("@", $email);

		/*
		 * Try to get the mx record for the email address
		 */
		$mxHosts = $this->mxRecord->query($host);
		if ($mxHosts) {
			foreach ($mxHosts as $mxHost) {
				$result = $this->imapConnectivityTester->test($email, $mxHost,
					[$user, $email], $password, $name);
				if ($result) {
					return $result;
				}
			}
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		return $this->imapConnectivityTester->test($email, $host, [$user, $email],
				$password, $name);
	}

}
