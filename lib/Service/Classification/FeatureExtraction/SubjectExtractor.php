<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\MultibyteTextNormalizer;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use RuntimeException;
use function array_column;
use function array_map;

class SubjectExtractor implements IExtractor {
	private const MAX_VOCABULARY_SIZE = 500;

	private WordCountVectorizer $wordCountVectorizer;
	private TfIdfTransformer $tfidf;
	private int $max = -1;

	public function __construct() {
		// Limit vocabulary to limit memory usage
		$this->wordCountVectorizer = new WordCountVectorizer(self::MAX_VOCABULARY_SIZE);
		$this->tfidf = new TfIdfTransformer();
	}

	public function getWordCountVectorizer(): WordCountVectorizer {
		return $this->wordCountVectorizer;
	}

	public function setWordCountVectorizer(WordCountVectorizer $wordCountVectorizer): void {
		$this->wordCountVectorizer = $wordCountVectorizer;
		$this->limitFeatureSize();
	}

	public function getTfIdf(): TfIdfTransformer {
		return $this->tfidf;
	}

	public function setTfidf(TfIdfTransformer $tfidf): void {
		$this->tfidf = $tfidf;
	}

	#[\Override]
	public function prepare(Account $account, array $incomingMailboxes, array $outgoingMailboxes, array $messages): void {
		/** @var array<array-key, array<string, string>> $data */
		$data = array_map(static function (Message $message) {
			return [
				'text' => $message->getSubject() ?? '',
				'label' => $message->getFlagImportant()
					? ImportanceClassifier::LABEL_IMPORTANT
					: ImportanceClassifier::LABEL_NOT_IMPORTANT,
			];
		}, $messages);

		// Fit transformers
		Labeled::build(array_column($data, 'text'), array_column($data, 'label'))
			->apply(new MultibyteTextNormalizer())
			->apply($this->wordCountVectorizer)
			->apply($this->tfidf);

		$this->limitFeatureSize();
	}

	#[\Override]
	public function extract(Message $message): array {
		$sender = $message->getFrom()->first();
		if ($sender === null) {
			throw new RuntimeException('This should not happen');
		}

		// Build training data set
		$trainText = $message->getSubject() ?? '';

		$trainDataSet = Unlabeled::build([[$trainText]])
			->apply(new MultibyteTextNormalizer())
			->apply($this->wordCountVectorizer)
			->apply($this->tfidf);

		// Use zeroed vector if no features could be extracted
		if ($trainDataSet->numFeatures() === 0) {
			$textFeatures = array_fill(0, $this->max, 0);
		} else {
			$textFeatures = $trainDataSet->sample(0);
		}

		return $textFeatures;
	}

	/**
	 * Limit feature vector length to actual size of vocabulary.
	 */
	private function limitFeatureSize(): void {
		$vocabularies = $this->wordCountVectorizer->vocabularies();
		if (!isset($vocabularies[0])) {
			// Should not happen but better safe than sorry
			return;
		}

		$this->max = count($vocabularies[0]);
	}
}
