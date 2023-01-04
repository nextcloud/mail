<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\StatisticsDao;
use Rubix\ML\Datasets\Dataset;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\TextNormalizer;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use function OCA\Mail\array_flat_map;

class SubjectExtractor {
	/** @var StatisticsDao */
	private $statisticsDao;

	/** @var string[][] */
	private $subjects;

	public function __construct(StatisticsDao $statisticsDao) {
		$this->statisticsDao = $statisticsDao;
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(Account $account, array $incomingMailboxes, array $outgoingMailboxes, array $messages): void {
		/** @var string[] $senders */
		$senders = array_unique(array_map(function (Message $message) {
			return $message->getFrom()->first()->getEmail();
		}, array_filter($messages, function (Message $message) {
			return $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null;
		})));

		$this->subjects = $this->statisticsDao->getSubjectsGrouped($incomingMailboxes, $senders);
	}

	/**
	 * @inheritDoc
	 */
	public function extract(string $email): array {
		$concatSubjects = [];
		foreach ($this->subjects as $sender => $subjects) {
			if ($sender !== $email) {
				continue;
			}

			$concatSubjects[] = $subjects;
		}

		$subject = implode(' ', array_merge(...$concatSubjects));
		$subjects = array_unique(array_merge(...$concatSubjects));

		//$data = new Labeled([$subject], [$email]);
		//$data = new Unlabeled($subjects);
		$data = Unlabeled::build($subjects)
			->apply(new TextNormalizer())
			->apply(new WordCountVectorizer(20));
			//->apply(new TfIdfTransformer());
		return $data->samples();
	}

	public function getSubjects(): array {
		return array_merge(...array_values($this->subjects));
	}

	public function getSubjectsOfSender(string $email): array {
		$concatSubjects = [];
		foreach ($this->subjects as $sender => $subjects) {
			if ($sender !== $email) {
				continue;
			}

			$concatSubjects[] = $subjects;
		}

		return array_merge(...$concatSubjects);
	}
}
