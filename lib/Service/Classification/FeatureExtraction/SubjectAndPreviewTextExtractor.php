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
use OCA\Mail\Service\Classification\ImportanceClassifier;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\LinearDiscriminantAnalysis;
use Rubix\ML\Transformers\MultibyteTextNormalizer;
use Rubix\ML\Transformers\PrincipalComponentAnalysis;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use RuntimeException;
use function array_column;
use function array_map;

class SubjectAndPreviewTextExtractor implements IExtractor {
	private StatisticsDao $statisticsDao;
	private WordCountVectorizer $wordCountVectorizer;
	private Transformer $dimensionalReductionTransformer;
	private int $max = -1;

	private array $senderCache = [];

	public function __construct(StatisticsDao $statisticsDao) {
		$this->statisticsDao = $statisticsDao;
		// Limit vocabulary to limit ram usage. It takes about 5 GB of ram if an unbounded
		// vocabulary is used (and a lot more time to compute).
		$this->wordCountVectorizer = new WordCountVectorizer(100);
		$this->dimensionalReductionTransformer = new PrincipalComponentAnalysis(15);
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(Account $account, array $incomingMailboxes, array $outgoingMailboxes, array $messages): void {
		$data = array_map(function(Message $message) {
			return [
				'text' => ($message->getSubject() ?? '') . ' ' . ($message->getPreviewText() ?? ''),
				'label' => $message->getFlagImportant() ? 'i' : 'ni',
			];
		}, $messages);

		// Fit transformers
		Labeled::build(
			array_column($data, 'text'),
			array_column($data, 'label'),
		)
			->apply(new MultibyteTextNormalizer())
			->apply($this->wordCountVectorizer)
			->apply($this->dimensionalReductionTransformer);

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
		$trainText = ($message->getSubject() ?? '') . ' ' . ($message->getPreviewText() ?? '');

		$trainDataSet = Unlabeled::build([$trainText])
			->apply(new MultibyteTextNormalizer())
			->apply($this->wordCountVectorizer)
			->apply($this->dimensionalReductionTransformer);

		// Use zeroed vector if no features could be extracted
		if ($trainDataSet->numColumns() === 0) {
			$textFeatures = array_fill(0, $this->max, 0);
		} else {
			$textFeatures = $trainDataSet->sample(0);
		}

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
