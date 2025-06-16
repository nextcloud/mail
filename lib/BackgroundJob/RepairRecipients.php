<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class RepairRecipients extends TimedJob {

	public function __construct(
		protected ITimeFactory $time,
		private IDBConnection $db,
		private IJobList $jobService,
	) {
		parent::__construct($time);
		$this->setInterval(300);
	}

	protected function run($argument): void {
		// fetch all quoted emails
		$select = $this->db->getQueryBuilder();
		$select->select('id', 'email')
			->from('mail_recipients')
			->where(
				$select->expr()->like('email', $select->createNamedParameter('\'%\'', IQueryBuilder::PARAM_STR))
			)
			->setMaxResults(1000);
		$recipients = $select->executeQuery()->fetchAll();
		// update emails
		$update = $this->db->getQueryBuilder();
		$update->update('mail_recipients')
			->set('email', $update->createParameter('email'))
			->where($update->expr()->in('id', $update->createParameter('id'), IQueryBuilder::PARAM_STR));
		foreach ($recipients as $recipient) {
			$id = $recipient['id'];
			$email = $recipient['email'];
			$email = trim(str_replace('\'', '', (string)$email));
			$update->setParameter('id', $id, IQueryBuilder::PARAM_STR);
			$update->setParameter('email', $email, IQueryBuilder::PARAM_STR);
			$update->executeStatement();
		}
		// remove job depending on the result
		if ($recipients === []) {
			$this->jobService->remove(RepairRecipients::class);
		}
	}

}
