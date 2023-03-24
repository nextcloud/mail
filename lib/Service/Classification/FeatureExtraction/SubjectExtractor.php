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
use OCA\Mail\Exception\ServiceException;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\TSNE;
use Rubix\ML\Transformers\MultibyteTextNormalizer;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use RuntimeException;
use function array_column;
use function array_map;

class SubjectExtractor implements IExtractor {
	private WordCountVectorizer $wordCountVectorizer;
	private Transformer $dimensionalReductionTransformer;
	private int $max = -1;

	public function __construct() {
		// Limit vocabulary to limit memory usage
		$vocabSize = 100;
		$this->wordCountVectorizer = new WordCountVectorizer($vocabSize);

		$this->dimensionalReductionTransformer = new TSNE((int)($vocabSize * 0.1));
	}

	public function getWordCountVectorizer(): WordCountVectorizer {
		return $this->wordCountVectorizer;
	}

	public function setWordCountVectorizer(WordCountVectorizer $wordCountVectorizer): void {
		$this->wordCountVectorizer = $wordCountVectorizer;
		$this->limitFeatureSize();
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(Account $account, array $incomingMailboxes, array $outgoingMailboxes, array $messages): void {
		/** @var array<array-key, array<string, string>> $data */
		$data = array_map(static function(Message $message) {
			return [
				'text' => $message->getSubject() ?? '',
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

		$this->limitFeatureSize();
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

		// Build training data set
		$trainText = ($message->getSubject() ?? '') . ' ' . ($message->getPreviewText() ?? '');

		$trainDataSet = Unlabeled::build([[$trainText]])
			->apply(new MultibyteTextNormalizer())
			->apply($this->wordCountVectorizer)
			->apply($this->dimensionalReductionTransformer);

		// Use zeroed vector if no features could be extracted
		if ($trainDataSet->numFeatures() === 0) {
			$textFeatures = array_fill(0, $this->max, 0);
		} else {
			$textFeatures = $trainDataSet->sample(0);
		}

		return $textFeatures;
	}

	/**
	 * Limit feature vector length to actual vocabulary size.
	 */
	private function limitFeatureSize(): void {
		$vocab = $this->wordCountVectorizer->vocabularies()[0];
		$this->max = count($vocab);
	}
}
