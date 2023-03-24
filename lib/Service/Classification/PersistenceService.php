<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Service\Classification;

use OCA\DAV\Connector\Sabre\File;
use OCA\Mail\Account;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\Classifier;
use OCA\Mail\Db\ClassifierMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\ClassifierPipeline;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\Files;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\ICacheFactory;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use Rubix\ML\Encoding;
use Rubix\ML\Estimator;
use Rubix\ML\Learner;
use Rubix\ML\Persistable;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Serializers\RBX;
use Rubix\ML\Transformers\Transformer;
use RuntimeException;
use function file_get_contents;
use function file_put_contents;
use function get_class;
use function strlen;

class PersistenceService {
	private const ADD_DATA_FOLDER = 'classifiers';

	/** @var ClassifierMapper */
	private $mapper;

	/** @var IAppData */
	private $appData;

	/** @var ITempManager */
	private $tempManager;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IAppManager */
	private $appManager;

	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(ClassifierMapper $mapper,
								IAppData $appData,
								ITempManager $tempManager,
								ITimeFactory $timeFactory,
								IAppManager $appManager,
								ICacheFactory $cacheFactory,
								LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->appData = $appData;
		$this->tempManager = $tempManager;
		$this->timeFactory = $timeFactory;
		$this->appManager = $appManager;
		$this->cacheFactory = $cacheFactory;
		$this->logger = $logger;
	}

	/**
	 * Persist the classifier data to the database, the estimator and its transformers to storage
	 *
	 * @param Classifier $classifier
	 * @param Learner&Persistable $estimator
	 * @param (Transformer&Persistable)[] $transformers
	 *
	 * @throws ServiceException
	 */
	public function persist(Classifier $classifier,
							Learner $estimator,
							array $transformers): void {
		/*
		 * First we have to insert the row to get the unique ID, but disable
		 * it until the model is persisted as well. Otherwise another process
		 * might try to load the model in the meantime and run into an error
		 * due to the missing data in app data.
		 */
		$classifier->setAppVersion($this->appManager->getAppVersion(Application::APP_ID));
		$classifier->setEstimator(get_class($estimator));
		$classifier->setActive(false);
		$classifier->setCreatedAt($this->timeFactory->getTime());
		$this->mapper->insert($classifier);

		/*
		 * Then we serialize the estimator into a temporary file
		 */
		$tmpPath = $this->tempManager->getTemporaryFile();
		try {
			$model = new PersistentModel($estimator, new Filesystem($tmpPath));
			$model->save();
			$serializedClassifier = file_get_contents($tmpPath);
			$this->logger->debug('Serialized classifier written to tmp file (' . strlen($serializedClassifier) . 'B');
		} catch (RuntimeException $e) {
			throw new ServiceException("Could not serialize classifier: " . $e->getMessage(), 0, $e);
		}

		/*
		 * Then we store the serialized model to app data
		 */
		try {
			try {
				$folder = $this->appData->getFolder(self::ADD_DATA_FOLDER);
				$this->logger->debug('Using existing folder for the serialized classifier');
			} catch (NotFoundException $e) {
				$folder = $this->appData->newFolder(self::ADD_DATA_FOLDER);
				$this->logger->debug('New folder created for serialized classifiers');
			}
			$file = $folder->newFile((string) $classifier->getId());
			$file->putContent($serializedClassifier);
			$this->logger->debug('Serialized classifier written to app data');
		} catch (NotPermittedException | NotFoundException $e) {
			throw new ServiceException("Could not create classifiers directory: " . $e->getMessage(), 0, $e);
		}

		/*
		 * Then we serialize the transformer pipeline to temporary files
		 */
		$transformerIndex = 0;
		$serializer = new RBX();
		foreach ($transformers as $transformer) {
			$tmpPath = $this->tempManager->getTemporaryFile();
			try {
				/**
				 * This is how to serialize a transformer according to the official docs.
				 * PersistentModel can only be used on Learners which transformers don't implement.
				 *
				 * Ref https://docs.rubixml.com/2.0/model-persistence.html#persisting-transformers
				 *
				 * @psalm-suppress InternalMethod
				 */
				$serializer->serialize($transformer)->saveTo(new Filesystem($tmpPath));
				$serializedTransformer = file_get_contents($tmpPath);
				$this->logger->debug('Serialized transformer written to tmp file (' . strlen($serializedTransformer) . 'B');
			} catch (RuntimeException $e) {
				throw new ServiceException("Could not serialize transformer: " . $e->getMessage(), 0, $e);
			}

			try {
				$file = $folder->newFile("{$classifier->getId()}_t$transformerIndex");
				$file->putContent($serializedTransformer);
				$this->logger->debug("Serialized transformer $transformerIndex written to app data");
			} catch (NotPermittedException | NotFoundException $e) {
				throw new ServiceException(
					"Failed to persist transformer $transformerIndex: " . $e->getMessage(),
					0,
					$e
				);
			}

			$transformerIndex++;
		}

		$classifier->setTransformerCount($transformerIndex);

		/*
		 * Now we set the model active so it can be used by the next request
		 */
		$classifier->setActive(true);
		$this->mapper->update($classifier);
	}

	/**
	 * @param Account $account
	 *
	 * @return ?ClassifierPipeline
	 *
	 * @throws ServiceException
	 */
	public function loadLatest(Account $account): ?ClassifierPipeline {
		try {
			$latestModel = $this->mapper->findLatest($account->getId());
		} catch (DoesNotExistException $e) {
			return null;
		}
		return $this->load($latestModel);
	}

	/**
	 * Load an estimator and its transformers of a classifier from storage
	 *
	 * @param Classifier $classifier
	 * @return ClassifierPipeline
	 *
	 * @throws ServiceException
	 */
	public function load(Classifier $classifier): ClassifierPipeline {
		$id = $classifier->getId();
		$cached = $this->getCached($classifier->getId(), $classifier->getTransformerCount());
		if ($cached !== null) {
			$this->logger->debug("Using cached serialized classifier $id");
			$serialized = $cached[0];
			$serializedTransformers = array_slice($cached, 1);
		} else {
			$this->logger->debug("Loading serialized classifier from app data");
			try {
				$modelsFolder = $this->appData->getFolder(self::ADD_DATA_FOLDER);
				$modelFile = $modelsFolder->getFile((string)$id);
			} catch (NotFoundException $e) {
				$this->logger->debug("Could not load classifier $id: " . $e->getMessage());
				throw new ServiceException("Could not load classifier $id: " . $e->getMessage(), 0, $e);
			}

			try {
				$serialized = $modelFile->getContent();
			} catch (NotFoundException | NotPermittedException $e) {
				$this->logger->debug("Could not load content for model file with classifier id $id: " . $e->getMessage());
				throw new ServiceException("Could not load content for model file with classifier id $id: " . $e->getMessage(), 0, $e);
			}
			$size = strlen($serialized);
			$this->logger->debug("Serialized classifier loaded (size=$size)");

			$serializedTransformers = [];
			for ($i = 0; $i < $classifier->getTransformerCount(); $i++) {
				try {
					$transformerFile = $modelsFolder->getFile("{$id}_t$i");
				} catch (NotFoundException $e) {
					$this->logger->debug("Could not load transformer $i of classifier $id: " . $e->getMessage());
					throw new ServiceException("Could not load transformer $i of classifier $id: " . $e->getMessage(), 0, $e);
				}

				try {
					$serializedTransformer = $transformerFile->getContent();
				} catch (NotFoundException | NotPermittedException $e) {
					$this->logger->debug("Could not load content for transformer file $i with classifier id $id: " . $e->getMessage());
					throw new ServiceException("Could not load content for transformer file $i with classifier id $id: " . $e->getMessage(), 0, $e);
				}
				$size = strlen($serializedTransformer);
				$this->logger->debug("Serialized transformer $i loaded (size=$size)");
				$serializedTransformers[] = $serializedTransformer;
			}

			$this->cache($id, $serialized, $serializedTransformers);
		}

		$tmpPath = $this->tempManager->getTemporaryFile();
		file_put_contents($tmpPath, $serialized);
		try {
			$estimator = PersistentModel::load(new Filesystem($tmpPath));
		} catch (RuntimeException $e) {
			throw new ServiceException("Could not deserialize persisted classifier $id: " . $e->getMessage(), 0, $e);
		}

		$transformers = array_map(function(string $serializedTransformer) use ($id) {
			$serializer = new RBX();
			$tmpPath = $this->tempManager->getTemporaryFile();
			file_put_contents($tmpPath, $serializedTransformer);
			try {
				$persister = new Filesystem($tmpPath);
				$transformer =  $persister->load()->deserializeWith($serializer);
			} catch (RuntimeException $e) {
				throw new ServiceException("Could not deserialize persisted transformer of classifier $id: " . $e->getMessage(), 0, $e);
			}

			if (!($transformer instanceof Transformer)) {
				throw new ServiceException("Transformer of classifier $id is not a transformer: Got " . $transformer::class);
			}

			return $transformer;
		}, $serializedTransformers);

		return new ClassifierPipeline($estimator, $transformers);
	}

	private function getCacheKey(int $id): string {
		return "mail_classifier_$id";
	}

	private function getTransformerCacheKey(int $id, int $index): string {
		return $this->getCacheKey($id) . "_transformer_$index";
	}

	/**
	 * @param int $id
	 * @param int $transformerCount
	 *
	 * @return string[]|null Array of serialized classifier and transformers
	 */
	private function getCached(int $id, int $transformerCount): ?array {
		if (!$this->cacheFactory->isLocalCacheAvailable()) {
			return null;
		}
		$cache = $this->cacheFactory->createLocal();

		$values = [];
		$values[] = $cache->get($this->getCacheKey($id));
		for ($i = 0; $i < $transformerCount; $i++) {
			$values[] = $cache->get($this->getTransformerCacheKey($id, $i));
		}
		return $values;
	}

	private function cache(int $id, string $serialized, array $serializedTransformers): void {
		if (!$this->cacheFactory->isLocalCacheAvailable()) {
			return;
		}
		$cache = $this->cacheFactory->createLocal();
		$cache->set($this->getCacheKey($id), $serialized);

		$transformerIndex = 0;
		foreach ($serializedTransformers as $transformer) {
			$cache->set($this->getTransformerCacheKey($id, $transformerIndex), $transformer);
			$transformerIndex++;
		}
	}
}
