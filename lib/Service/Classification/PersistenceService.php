<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification;

use OCA\Mail\Account;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\ClassifierPipeline;
use OCA\Mail\Service\Classification\FeatureExtraction\CompositeExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\IExtractor;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Rubix\ML\Learner;
use Rubix\ML\Persistable;
use Rubix\ML\PersistentModel;
use Rubix\ML\Serializers\RBX;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Transformers\WordCountVectorizer;
use RuntimeException;
use function get_class;

class PersistenceService {
	// Increment the version when changing the classifier or transformer pipeline
	public const VERSION = 1;

	public function __construct(
		private readonly ICacheFactory $cacheFactory,
		private readonly ContainerInterface $container,
	) {
	}

	/**
	 * Persist classifier, estimator and its transformers to the memory cache.
	 *
	 * @param Learner&Persistable $estimator
	 *
	 * @throws ServiceException If any serialization fails
	 */
	public function persist(
		Account $account,
		Learner $estimator,
		CompositeExtractor $extractor,
	): void {
		$serializedData = [];

		/*
		 * First we serialize the estimator
		 */
		try {
			$persister = new RubixMemoryPersister();
			$model = new PersistentModel($estimator, $persister);
			$model->save();
			$serializedData[] = $persister->getData();
		} catch (RuntimeException $e) {
			throw new ServiceException('Could not serialize classifier: ' . $e->getMessage(), 0, $e);
		}

		/*
		 * Then we serialize the transformer pipeline
		 */
		$transformers = [
			$extractor->getSubjectExtractor()->getWordCountVectorizer(),
			$extractor->getSubjectExtractor()->getTfIdf(),
		];
		$serializer = new RBX();
		foreach ($transformers as $transformer) {
			try {
				$persister = new RubixMemoryPersister();
				/**
				 * This is how to serialize a transformer according to the official docs.
				 * PersistentModel can only be used on Learners which transformers don't implement.
				 *
				 * Ref https://docs.rubixml.com/2.0/model-persistence.html#persisting-transformers
				 *
				 * @psalm-suppress InternalMethod
				 */
				$serializer->serialize($transformer)->saveTo($persister);
				$serializedData[] = $persister->getData();
			} catch (RuntimeException $e) {
				throw new ServiceException('Could not serialize transformer: ' . $e->getMessage(), 0, $e);
			}
		}

		$this->setCached((string)$account->getId(), $serializedData);
	}

	/**
	 * Load the latest estimator and its transformers.
	 *
	 * @throws ServiceException If any deserialization fails
	 */
	public function loadLatest(Account $account): ?ClassifierPipeline {
		$cached = $this->getCached((string)$account->getId());
		if ($cached === null) {
			return null;
		}

		$serializedModel = $cached[0];
		$serializedTransformers = array_slice($cached, 1);
		try {
			$estimator = PersistentModel::load(new RubixMemoryPersister($serializedModel));
		} catch (RuntimeException $e) {
			throw new ServiceException(
				'Could not deserialize persisted classifier: ' . $e->getMessage(),
				0,
				$e,
			);
		}

		$serializer = new RBX();
		$transformers = array_map(function (string $serializedTransformer) use ($serializer) {
			try {
				$persister = new RubixMemoryPersister($serializedTransformer);
				$transformer = $persister->load()->deserializeWith($serializer);
			} catch (RuntimeException $e) {
				throw new ServiceException(
					'Could not deserialize persisted transformer of classifier: ' . $e->getMessage(),
					0,
					$e,
				);
			}

			if (!($transformer instanceof Transformer)) {
				throw new ServiceException(sprintf(
					'Transformer is not an instance of %s: Got %s',
					Transformer::class,
					get_class($transformer),
				));
			}

			return $transformer;
		}, $serializedTransformers);

		$extractor = $this->loadExtractor($transformers);

		return new ClassifierPipeline($estimator, $extractor);
	}

	/**
	 * Load and instantiate extractor based on the given transformers.
	 *
	 * @throws ServiceException If the transformers array contains unexpected instances or the composite extractor can't be instantiated
	 */
	private function loadExtractor(array $transformers): IExtractor {
		$wordCountVectorizer = $transformers[0];
		if (!($wordCountVectorizer instanceof WordCountVectorizer)) {
			throw new ServiceException(sprintf(
				'Failed to load persisted transformer: Expected %s, got %s',
				WordCountVectorizer::class,
				get_class($wordCountVectorizer),
			));
		}

		$tfidfTransformer = $transformers[1];
		if (!($tfidfTransformer instanceof TfIdfTransformer)) {
			throw new ServiceException(sprintf(
				'Failed to load persisted transformer: Expected %s, got %s',
				TfIdfTransformer::class,
				get_class($tfidfTransformer),
			));
		}

		try {
			/** @var CompositeExtractor $extractor */
			$extractor = $this->container->get(CompositeExtractor::class);
		} catch (ContainerExceptionInterface $e) {
			throw new ServiceException('Failed to instantiate the composite extractor', 0, $e);
		}

		$extractor->getSubjectExtractor()->setWordCountVectorizer($wordCountVectorizer);
		$extractor->getSubjectExtractor()->setTfidf($tfidfTransformer);
		return $extractor;
	}

	private function getCacheInstance(): ?ICache {
		if (!$this->isAvailable()) {
			return null;
		}

		$version = self::VERSION;
		return $this->cacheFactory->createDistributed("mail-classifier/v$version/");
	}

	/**
	 * @return string[]|null Array of serialized classifier and transformers
	 */
	private function getCached(string $id): ?array {
		$cache = $this->getCacheInstance();
		if ($cache === null) {
			return null;
		}

		$json = $cache->get($id);
		if (!is_string($json)) {
			return null;
		}

		$serializedData = json_decode($json);
		$decodedData = array_map(base64_decode(...), $serializedData);
		foreach ($decodedData as $decoded) {
			if ($decoded === false) {
				// Decoding failed, abort
				return null;
			}
		}
		/** @var string[] $decodedData */
		return $decodedData;
	}

	/**
	 * @param string[] $serializedData Array of serialized classifier and transformers
	 */
	private function setCached(string $id, array $serializedData): void {
		$cache = $this->getCacheInstance();
		if ($cache === null) {
			return;
		}

		// Serialized data contains binary, non-utf8 data so we encode it as base64 first
		$encodedData = array_map(base64_encode(...), $serializedData);
		$json = json_encode($encodedData, JSON_THROW_ON_ERROR);

		// Set a ttl of a week because a new model will be generated daily
		$cache->set($id, $json, 3600 * 24 * 7);
	}

	/**
	 * Returns true if the persistence layer is available on this Nextcloud server.
	 */
	public function isAvailable(): bool {
		return $this->cacheFactory->isAvailable();
	}
}
