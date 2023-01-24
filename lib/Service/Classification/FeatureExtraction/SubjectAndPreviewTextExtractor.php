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
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\MultibyteTextNormalizer;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use RuntimeException;

class SubjectAndPreviewTextExtractor implements IExtractor {
	private StatisticsDao $statisticsDao;
	private WordCountVectorizer $wordCountVectorizer;
	private TfIdfTransformer $tfIdfTransformer;
	private int $max = -1;
	private array $senderCache = [];

	/** @var string[][] */
	private array $subjects;

	/** @var string[][] */
	private array $previewTexts;

	public function __construct(StatisticsDao $statisticsDao) {
		$this->statisticsDao = $statisticsDao;
		// Limit vocabulary to limit ram usage. It takes about 5 GB of ram if an unbounded
		// vocabulary is used (and a lot more time to compute).
		$this->wordCountVectorizer = new WordCountVectorizer(1000);
		$this->tfIdfTransformer = new TfIdfTransformer(0.1);
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

		$this->subjects = $this->statisticsDao->getSubjects($incomingMailboxes, $senders);
		$this->previewTexts = $this->statisticsDao->getPreviewTexts($incomingMailboxes, $senders);

		// Fit transformers
		$fitText = implode(' ', [...$this->getSubjects(), ...$this->getPreviewTexts()]);
		Unlabeled::build([$fitText])
			->apply(new MultibyteTextNormalizer())
			->apply($this->wordCountVectorizer)
			->apply($this->tfIdfTransformer);

		// Limit feature vector length to actual vocabulary size
		$vocab = $this->wordCountVectorizer->vocabularies()[0];
		$this->max = count($vocab);
	}

	/**
	 * @inheritDoc
	 */
	public function extract(Message $message): array {
		$sender = $message->getFrom()->first();
		if ($sender === null) {
			throw new RuntimeException("This should not happen");
		}
		$email = $sender->getEmail();

		if (isset($this->senderCache[$email])) {
			return $this->senderCache[$email];
		}

		// Build training data set
		$subjects = $this->getSubjectsOfSender($email);
		$previewTexts = $this->getPreviewTextsOfSender($email);
		$trainText = implode(' ', [...$subjects, ...$previewTexts]);

		$textFeatures = [];
		if ($message->getSubject() !== null) {
			$trainDataSet = Unlabeled::build([$trainText])
				->apply(new MultibyteTextNormalizer())
				->apply($this->wordCountVectorizer)
				->apply($this->tfIdfTransformer);

			// Use zeroed vector if no features could be extracted
			if ($trainDataSet->numColumns() === 0) {
				$textFeatures = array_fill(0, $this->max, 0);
			} else {
				$textFeatures = $trainDataSet->sample(0);
			}
		}
		assert(count($textFeatures) === $this->max);

		$this->senderCache[$email] = $textFeatures;

		return $textFeatures;
	}

	private function getSubjects(): array {
		return array_merge(...array_values($this->subjects));
	}

	private function getPreviewTexts(): array {
		return array_merge(...array_values($this->previewTexts));
	}

	private function getSubjectsOfSender(string $email): array {
		$concatSubjects = [];
		foreach ($this->subjects as $sender => $subjects) {
			if ($sender !== $email) {
				continue;
			}

			$concatSubjects[] = $subjects;
		}

		return array_merge(...$concatSubjects);
	}

	private function getPreviewTextsOfSender(string $email): array {
		$concatPreviewTexts = [];
		foreach ($this->previewTexts as $sender => $previewTexts) {
			if ($sender !== $email) {
				continue;
			}

			$concatPreviewTexts[] = $previewTexts;
		}

		return array_merge(...$concatPreviewTexts);
	}
}
