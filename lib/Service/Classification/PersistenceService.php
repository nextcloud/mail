<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification;

use OCA\Mail\Account;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\Classifier;
use OCA\Mail\Db\ClassifierMapper;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ServiceException;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\ICacheFactory;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use Rubix\ML\Estimator;
use Rubix\ML\Learner;
use Rubix\ML\Persistable;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
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

	/** @var MailAccountMapper */
	private $accountMapper;

	public function __construct(ClassifierMapper $mapper,
		IAppData $appData,
		ITempManager $tempManager,
		ITimeFactory $timeFactory,
		IAppManager $appManager,
		ICacheFactory $cacheFactory,
		LoggerInterface $logger,
		MailAccountMapper $accountMapper) {
		$this->mapper = $mapper;
		$this->appData = $appData;
		$this->tempManager = $tempManager;
		$this->timeFactory = $timeFactory;
		$this->appManager = $appManager;
		$this->cacheFactory = $cacheFactory;
		$this->logger = $logger;
		$this->accountMapper = $accountMapper;
	}

	/**
	 * Persist the classifier data to the database and the estimator to storage
	 *
	 * @param Classifier $classifier
	 * @param Learner&Persistable $estimator
	 *
	 * @throws ServiceException
	 */
	public function persist(Classifier $classifier, Learner $estimator): void {
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
			throw new ServiceException('Could not serialize classifier: ' . $e->getMessage(), 0, $e);
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
			$file = $folder->newFile((string)$classifier->getId());
			$file->putContent($serializedClassifier);
			$this->logger->debug('Serialized classifier written to app data');
		} catch (NotPermittedException $e) {
			throw new ServiceException('Could not create classifiers directory: ' . $e->getMessage(), 0, $e);
		}

		/*
		 * Now we set the model active so it can be used by the next request
		 */
		$classifier->setActive(true);
		$this->mapper->update($classifier);
	}

	/**
	 * @param Account $account
	 *
	 * @return Estimator|null
	 * @throws ServiceException
	 */
	public function loadLatest(Account $account): ?Estimator {
		try {
			$latestModel = $this->mapper->findLatest($account->getId());
		} catch (DoesNotExistException $e) {
			return null;
		}
		return $this->load($latestModel->getId());
	}

	/**
	 * @param int $id
	 *
	 * @return Estimator
	 * @throws ServiceException
	 */
	public function load(int $id): Estimator {
		$cached = $this->getCached($id);
		if ($cached !== null) {
			$this->logger->debug("Using cached serialized classifier $id");
			$serialized = $cached;
		} else {
			$this->logger->debug('Loading serialized classifier from app data');
			try {
				$modelsFolder = $this->appData->getFolder(self::ADD_DATA_FOLDER);
				$modelFile = $modelsFolder->getFile((string)$id);
			} catch (NotFoundException $e) {
				$this->logger->debug("Could not load classifier $id: " . $e->getMessage());
				throw new ServiceException("Could not load classifier $id: " . $e->getMessage(), 0, $e);
			}

			try {
				$serialized = $modelFile->getContent();
			} catch (NotFoundException|NotPermittedException $e) {
				$this->logger->debug("Could not load content for model file with classifier id $id: " . $e->getMessage());
				throw new ServiceException("Could not load content for model file with classifier id $id: " . $e->getMessage(), 0, $e);
			}
			$size = strlen($serialized);
			$this->logger->debug("Serialized classifier loaded (size=$size)");

			$this->cache($id, $serialized);
		}

		$tmpPath = $this->tempManager->getTemporaryFile();
		file_put_contents($tmpPath, $serialized);

		try {
			$estimator = PersistentModel::load(new Filesystem($tmpPath));
		} catch (RuntimeException $e) {
			throw new ServiceException("Could not deserialize persisted classifier $id: " . $e->getMessage(), 0, $e);
		}

		return $estimator;
	}

	public function cleanUp(): void {
		$threshold = $this->timeFactory->getTime() - 30 * 24 * 60 * 60;
		$totalAccounts = $this->accountMapper->getTotal();
		$classifiers = $this->mapper->findHistoric($threshold, $totalAccounts * 10);
		foreach ($classifiers as $classifier) {
			try {
				$this->deleteModel($classifier->getId());
				$this->mapper->delete($classifier);
			} catch (NotPermittedException $e) {
				// Log and continue. This is not critical
				$this->logger->warning('Could not clean-up old classifier', [
					'id' => $classifier->getId(),
					'exception' => $e,
				]);
			}
		}
	}

	/**
	 * @throws NotPermittedException
	 */
	private function deleteModel(int $id): void {
		$this->logger->debug('Deleting serialized classifier from app data', [
			'id' => $id,
		]);
		try {
			$modelsFolder = $this->appData->getFolder(self::ADD_DATA_FOLDER);
			$modelFile = $modelsFolder->getFile((string)$id);
			$modelFile->delete();
		} catch (NotFoundException $e) {
			$this->logger->debug("Classifier model $id does not exist", [
				'exception' => $e,
			]);
		}
	}

	private function getCacheKey(int $id): string {
		return "mail_classifier_$id";
	}

	private function getCached(int $id): ?string {
		if (!$this->cacheFactory->isLocalCacheAvailable()) {
			return null;
		}
		$cache = $this->cacheFactory->createLocal();

		return $cache->get(
			$this->getCacheKey($id)
		);
	}

	private function cache(int $id, string $serialized): void {
		if (!$this->cacheFactory->isLocalCacheAvailable()) {
			return;
		}
		$cache = $this->cacheFactory->createLocal();
		$cache->set($this->getCacheKey($id), $serialized);
	}
}
