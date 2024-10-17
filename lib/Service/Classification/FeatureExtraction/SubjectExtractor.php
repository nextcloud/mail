<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\MinMaxNormalizer;
use Rubix\ML\Transformers\MultibyteTextNormalizer;
use Rubix\ML\Transformers\PrincipalComponentAnalysis;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use RuntimeException;
use function array_column;
use function array_map;

class SubjectExtractor implements IExtractor {
	private WordCountVectorizer $wordCountVectorizer;
	private Transformer $dimensionalReductionTransformer;
	private Transformer $normalizer;
	private Transformer $tfidf;
	private int $max = -1;

	public function __construct() {
		// Limit vocabulary to limit memory usage
		$vocabSize = 500;
		$this->wordCountVectorizer = new WordCountVectorizer($vocabSize);

		$this->tfidf = new TfIdfTransformer();
		//$this->dimensionalReductionTransformer = new PrincipalComponentAnalysis((int)($vocabSize * 0.1));
		//$this->normalizer = new MinMaxNormalizer();
	}

	public function getWordCountVectorizer(): WordCountVectorizer {
		return $this->wordCountVectorizer;
	}

	public function setWordCountVectorizer(WordCountVectorizer $wordCountVectorizer): void {
		$this->wordCountVectorizer = $wordCountVectorizer;
		$this->limitFeatureSize();
	}

	public function getTfidf(): Transformer {
		return $this->tfidf;
	}

	public function setTfidf(TfIdfTransformer $tfidf): void {
		$this->tfidf = $tfidf;
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(Account $account, array $incomingMailboxes, array $outgoingMailboxes, array $messages): void {
		/** @var array<array-key, array<string, string>> $data */
		$data = array_map(static function (Message $message) {
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
			->apply($this->tfidf)
		;//->apply($this->dimensionalReductionTransformer);

		$this->limitFeatureSize();
	}

	/**
	 * @inheritDoc
	 */
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
			->apply($this->tfidf)
		;//->apply($this->dimensionalReductionTransformer);

		// Use zeroed vector if no features could be extracted
		if ($trainDataSet->numFeatures() === 0) {
			$textFeatures = array_fill(0, $this->max, 0);
		} else {
			$textFeatures = $trainDataSet->sample(0);
		}

		//var_dump($textFeatures);

		return $textFeatures;
	}

	/**
	 * Limit feature vector length to actual vocabulary size.
	 */
	private function limitFeatureSize(): void {
		$vocab = $this->wordCountVectorizer->vocabularies()[0];
		$this->max = count($vocab);
		echo("WCF vocab size: {$this->max}\n");
	}
}
