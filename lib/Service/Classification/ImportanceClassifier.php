<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification;

use Closure;
use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClassifierTrainingException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\Classifier;
use OCA\Mail\Model\ClassifierPipeline;
use OCA\Mail\Service\Classification\FeatureExtraction\CompositeExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\IExtractor;
use OCA\Mail\Support\PerformanceLogger;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\CrossValidation\Reports\MulticlassBreakdown;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Estimator;
use Rubix\ML\Kernels\Distance\Manhattan;
use Rubix\ML\Learner;
use Rubix\ML\Persistable;
use RuntimeException;
use function array_column;
use function array_combine;
use function array_filter;
use function array_map;
use function array_slice;
use function count;
use function json_encode;

/**
 * Classify importance of messages
 *
 * This services uses machine learning techniques to guess the importance of in-
 * coming messages. The training will be done in the background, so the actual
 * classification can happen fast.
 *
 * To overcome the "cold start" problem there is also a fall-back mechanism of
 * rule-based classification that is active as long as there are too few important
 * messages to learn meaningful patterns of what the users typically considers
 * as important.
 */
class ImportanceClassifier {
	/**
	 * Mailbox special uses to exclude from the training
	 */
	private const EXEMPT_FROM_TRAINING = [
		Horde_Imap_Client::SPECIALUSE_ALL,
		Horde_Imap_Client::SPECIALUSE_DRAFTS,
		Horde_Imap_Client::SPECIALUSE_FLAGGED,
		Horde_Imap_Client::SPECIALUSE_JUNK,
		Horde_Imap_Client::SPECIALUSE_SENT,
		Horde_Imap_Client::SPECIALUSE_TRASH,
	];

	/**
	 * @var string label for data sets that are classified as important
	 */
	public const LABEL_IMPORTANT = 'i';

	/**
	 * @var string label for data sets that are classified as not important
	 */
	public const LABEL_NOT_IMPORTANT = 'ni';

	/**
	 * The minimum number of important messages. Without those the unsupervised
	 * training would yield random classification. Hence we switch to a rule-based
	 * classifier. This is known as the "cold start" problem.
	 */
	private const COLD_START_THRESHOLD = 20;

	/**
	 * The maximum number of data sets to train the classifier with
	 */
	private const MAX_TRAINING_SET_SIZE = 300;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var PersistenceService */
	private $persistenceService;

	/** @var PerformanceLogger */
	private $performanceLogger;

	/** @var ImportanceRulesClassifier */
	private $rulesClassifier;

	private ContainerInterface $container;

	public function __construct(MailboxMapper $mailboxMapper,
		MessageMapper $messageMapper,
		PersistenceService $persistenceService,
		PerformanceLogger $performanceLogger,
		ImportanceRulesClassifier $rulesClassifier,
		ContainerInterface $container) {
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->persistenceService = $persistenceService;
		$this->performanceLogger = $performanceLogger;
		$this->rulesClassifier = $rulesClassifier;
		$this->container = $container;
	}

	private static function createDefaultEstimator(): KNearestNeighbors {
		// A meta estimator was trained on the same data multiple times to average out the
		// variance of the trained model.
		// Parameters were chosen from the best configuration across 100 runs.
		// Both variance (spread) and f1 score were considered.
		// Note: Lower k values yield slightly higher f1 scores but show higher variances.
		return new KNearestNeighbors(15, true, new Manhattan());
	}

	/**
	 * @throws ServiceException If the extractor is not available
	 */
	private function createExtractor(): CompositeExtractor {
		try {
			return $this->container->get(CompositeExtractor::class);
		} catch (ContainerExceptionInterface $e) {
			throw new ServiceException('Default extractor is not available', 0, $e);
		}
	}

	private function filterMessageHasSenderEmail(Message $message): bool {
		return $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null;
	}

	/**
	 * Build a data set for training an importance classifier.
	 *
	 * @param Account $account
	 * @param IExtractor $extractor
	 * @param LoggerInterface $logger
	 * @param PerformanceLoggerTask|null $perf
	 * @param bool $shuffle
	 * @return array|null Returns null if there are not enough messages to train
	 */
	public function buildDataSet(
		Account $account,
		IExtractor $extractor,
		LoggerInterface $logger,
		?PerformanceLoggerTask $perf = null,
		bool $shuffle = false,
	): ?array {
		$perf ??= $this->performanceLogger->start('build data set for importance classifier training');

		$incomingMailboxes = $this->getIncomingMailboxes($account);
		$logger->debug('found ' . count($incomingMailboxes) . ' incoming mailbox(es)');
		$perf->step('find incoming mailboxes');
		$outgoingMailboxes = $this->getOutgoingMailboxes($account);
		$logger->debug('found ' . count($outgoingMailboxes) . ' outgoing mailbox(es)');
		$perf->step('find outgoing mailboxes');

		$mailboxIds = array_map(static function (Mailbox $mailbox) {
			return $mailbox->getId();
		}, $incomingMailboxes);
		$messages = array_filter(
			$this->messageMapper->findLatestMessages($account->getUserId(), $mailboxIds, self::MAX_TRAINING_SET_SIZE),
			[$this, 'filterMessageHasSenderEmail']
		);
		$importantMessages = array_filter($messages, static function (Message $message) {
			return ($message->getFlagImportant() === true);
		});
		$logger->debug('found ' . count($messages) . ' messages of which ' . count($importantMessages) . ' are important');
		if (count($importantMessages) < self::COLD_START_THRESHOLD) {
			$logger->info('not enough messages to train a classifier');
			return null;
		}
		$perf->step('find latest ' . self::MAX_TRAINING_SET_SIZE . ' messages');

		$dataSet = $this->getFeaturesAndImportance($account, $incomingMailboxes, $outgoingMailboxes, $messages, $extractor);
		if ($shuffle) {
			shuffle($dataSet);
		}

		return $dataSet;
	}

	/**
	 * Train an account's classifier of important messages
	 *
	 * Train a classifier based on a user's existing messages to be able to derive
	 * importance markers for new incoming messages.
	 *
	 * To factor in (server-side) filtering into multiple mailboxes, the algorithm
	 * will not only look for messages in the inbox but also other non-special
	 * mailboxes.
	 *
	 * To prevent memory exhaustion, the process will only load a fixed maximum
	 * number of messages per account.
	 *
	 * @param Account $account
	 * @param LoggerInterface $logger
	 * @param ?Closure $estimator Returned instance should at least implement Learner, Estimator and Persistable. If null, the default estimator will be used.
	 * @param bool $shuffleDataSet Shuffle the data set before training
	 * @param bool $persist Persist the trained classifier to use it for message classification
	 *
	 * @return ClassifierPipeline|null The validation estimator, persisted estimator (if `$persist` === true) or null in case none was trained
	 *
	 * @throws ServiceException
	 */
	public function train(
		Account $account,
		LoggerInterface $logger,
		?Closure $estimator = null,
		bool $shuffleDataSet = false,
		bool $persist = true,
	): ?ClassifierPipeline {
		$perf = $this->performanceLogger->start('importance classifier training');

		$extractor = $this->createExtractor();
		$dataSet = $this->buildDataSet($account, $extractor, $logger, $perf, $shuffleDataSet);
		if ($dataSet === null) {
			return null;
		}

		return $this->trainWithCustomDataSet(
			$account,
			$logger,
			$dataSet,
			$extractor,
			$estimator,
			$perf,
			$persist,
		);
	}

	/**
	 * Train a classifier using a custom data set.
	 *
	 * @param Account $account
	 * @param LoggerInterface $logger
	 * @param array $dataSet Training data set built by buildDataSet()
	 * @param CompositeExtractor $extractor Extractor used to extract the given data set
	 * @param ?Closure $estimator Returned instance should at least implement Learner, Estimator and Persistable. If null, the default estimator will be used.
	 * @param PerformanceLoggerTask|null $perf Optionally reuse a performance logger task
	 * @param bool $persist Persist the trained classifier to use it for message classification
	 *
	 * @return ClassifierPipeline|null The validation estimator, persisted estimator (if `$persist` === true) or null in case none was trained
	 *
	 * @throws ServiceException
	 */
	private function trainWithCustomDataSet(
		Account $account,
		LoggerInterface $logger,
		array $dataSet,
		CompositeExtractor $extractor,
		?Closure $estimator,
		?PerformanceLoggerTask $perf = null,
		bool $persist = true,
	): ?ClassifierPipeline {
		$perf ??= $this->performanceLogger->start('importance classifier training');
		$estimator ??= self::createDefaultEstimator(...);

		/**
		 * How many of the most recent messages are excluded from training?
		 */
		$validationThreshold = max(
			5,
			(int)(count($dataSet) * 0.2)
		);
		$validationSet = array_slice($dataSet, 0, $validationThreshold);
		$trainingSet = array_slice($dataSet, $validationThreshold);

		$validationSetImportantCount = 0;
		$trainingSetImportantCount = 0;
		foreach ($validationSet as $data) {
			if ($data['label'] === self::LABEL_IMPORTANT) {
				$validationSetImportantCount++;
			}
		}
		foreach ($trainingSet as $data) {
			if ($data['label'] === self::LABEL_IMPORTANT) {
				$trainingSetImportantCount++;
			}
		}

		$logger->debug('data set split into ' . count($trainingSet) . ' (' . self::LABEL_IMPORTANT . ': ' . $trainingSetImportantCount . ') training and ' . count($validationSet) . ' (' . self::LABEL_IMPORTANT . ': ' . $validationSetImportantCount . ') validation sets with ' . count($trainingSet[0]['features'] ?? []) . ' dimensions');

		if ($validationSet === [] || $trainingSet === []) {
			$logger->info('not enough messages to train a classifier');
			$perf->end();
			return null;
		}

		/** @var Learner&Estimator&Persistable $validationEstimator */
		$validationEstimator = $estimator();
		$this->trainClassifier($validationEstimator, $validationSet);
		try {
			$classifier = $this->validateClassifier(
				$validationEstimator,
				$trainingSet,
				$validationSet,
				$logger
			);
		} catch (ClassifierTrainingException $e) {
			$logger->error('Importance classifier training failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			$perf->end();
			return null;
		}
		$perf->step('train and validate classifier with training and validation sets');

		if (!$persist) {
			return new ClassifierPipeline($validationEstimator, $extractor);
		}

		/** @var Learner&Estimator&Persistable $persistedEstimator */
		$persistedEstimator = $estimator();
		$this->trainClassifier($persistedEstimator, $dataSet);
		$perf->step('train classifier with full data set');
		$classifier->setDuration($perf->end());
		$classifier->setAccountId($account->getId());
		$classifier->setEstimator(get_class($persistedEstimator));
		$classifier->setPersistenceVersion(PersistenceService::VERSION);

		$this->persistenceService->persist($account, $persistedEstimator, $extractor);
		$logger->debug("Classifier for account {$account->getId()} persisted", [
			'classifier' => $classifier,
		]);
		return new ClassifierPipeline($persistedEstimator, $extractor);
	}


	/**
	 * @param Account $account
	 *
	 * @return Mailbox[]
	 */
	private function getIncomingMailboxes(Account $account): array {
		return array_filter($this->mailboxMapper->findAll($account), static function (Mailbox $mailbox) {
			foreach (self::EXEMPT_FROM_TRAINING as $excluded) {
				if ($mailbox->isSpecialUse($excluded)) {
					return false;
				}
			}
			return true;
		});
	}

	/**
	 * @param Account $account
	 *
	 * @return Mailbox[]
	 * @todo allow more than one outgoing mailbox
	 */
	private function getOutgoingMailboxes(Account $account): array {
		try {
			$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
			if ($sentMailboxId === null) {
				return [];
			}

			return [
				$this->mailboxMapper->findById($sentMailboxId)
			];
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	/**
	 * Get the feature vector of every message
	 *
	 * @param Account $account
	 * @param Mailbox[] $incomingMailboxes
	 * @param Mailbox[] $outgoingMailboxes
	 * @param Message[] $messages
	 *
	 * @return array
	 */
	private function getFeaturesAndImportance(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages,
		IExtractor $extractor): array {
		$extractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);

		return array_map(static function (Message $message) use ($extractor) {
			$sender = $message->getFrom()->first();
			if ($sender === null) {
				throw new RuntimeException('This should not happen');
			}

			$features = $extractor->extract($message);

			return [
				'features' => $features,
				'label' => $message->getFlagImportant() ? self::LABEL_IMPORTANT : self::LABEL_NOT_IMPORTANT,
				'sender' => $sender->getEmail(),
			];
		}, $messages);
	}

	/**
	 * @param Account $account
	 * @param Message[] $messages
	 * @param LoggerInterface $logger
	 *
	 * @return bool[]
	 *
	 * @throws ServiceException
	 */
	public function classifyImportance(Account $account,
		array $messages,
		LoggerInterface $logger): array {
		$pipeline = null;
		try {
			$pipeline = $this->persistenceService->loadLatest($account);
		} catch (ServiceException $e) {
			$logger->warning('Failed to load persisted estimator and extractor: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}

		// Persistence is disabled on some instances (due to no memory cache being available).
		// Try to train a classifier on-the-fly on those instances.
		if ($pipeline === null) {
			$pipeline = $this->train($account, $logger);
		}

		// Can't train pipeline and no persistence available? -> Skip rule based classifier ...
		// It won't yield good results. Instead, we have to wait for the user to accumulate more
		// emails so that training a classifier succeeds.
		if ($pipeline === null && !$this->persistenceService->isAvailable()) {
			return [];
		}

		if ($pipeline === null) {
			$predictions = $this->rulesClassifier->classifyImportance(
				$account,
				$this->getIncomingMailboxes($account),
				$this->getOutgoingMailboxes($account),
				$messages
			);
			return array_combine(
				array_map(static function (Message $m) {
					return $m->getUid();
				}, $messages),
				array_map(static function (Message $m) use ($predictions) {
					return ($predictions[$m->getUid()] ?? false) === true;
				}, $messages)
			);
		}

		$messagesWithSender = array_filter($messages, [$this, 'filterMessageHasSenderEmail']);
		$features = $this->getFeaturesAndImportance(
			$account,
			$this->getIncomingMailboxes($account),
			$this->getOutgoingMailboxes($account),
			$messagesWithSender,
			$pipeline->getExtractor(),
		);
		$predictions = $pipeline->getEstimator()->predict(
			Unlabeled::build(array_column($features, 'features'))
		);
		return array_combine(
			array_map(static function (Message $m) {
				return $m->getUid();
			}, $messagesWithSender),
			array_map(static function ($p) {
				return $p === self::LABEL_IMPORTANT;
			}, $predictions)
		);
	}

	private function trainClassifier(Learner $classifier, array $trainingSet): void {
		$classifier->train(Labeled::build(
			array_column($trainingSet, 'features'),
			array_column($trainingSet, 'label')
		));
	}

	/**
	 * @param Estimator $estimator
	 * @param array $trainingSet
	 * @param array $validationSet
	 * @param LoggerInterface $logger
	 *
	 * @return Classifier
	 */
	private function validateClassifier(Estimator $estimator,
		array $trainingSet,
		array $validationSet,
		LoggerInterface $logger): Classifier {
		/** @var float[] $predictedValidationLabel */
		$predictedValidationLabel = $estimator->predict(Unlabeled::build(
			array_column($validationSet, 'features')
		));

		$reporter = new MulticlassBreakdown();
		$report = $reporter->generate(
			$predictedValidationLabel,
			array_column($validationSet, 'label')
		);
		$recallImportant = $report['classes'][self::LABEL_IMPORTANT]['recall'] ?? 0;
		$precisionImportant = $report['classes'][self::LABEL_IMPORTANT]['precision'] ?? 0;
		$f1ScoreImportant = $report['classes'][self::LABEL_IMPORTANT]['f1 score'] ?? 0;

		/**
		 * What we care most is the percentage of messages classified as important in relation to the truly important messages
		 * as we want to have a classification that rather flags too much as important that too little.
		 *
		 * The f1 score tells us how balanced the results are, as in, if the classifier blindly detects messages as important
		 * or if there is some a pattern it.
		 *
		 * Ref https://en.wikipedia.org/wiki/Precision_and_recall
		 * Ref https://en.wikipedia.org/wiki/F1_score
		 */
		$logger->debug('classification report: ' . json_encode([
			'recall' => $recallImportant,
			'precision' => $precisionImportant,
			'f1Score' => $f1ScoreImportant,
		]));
		$logger->debug("classifier validated: recall(important)=$recallImportant, precision(important)=$precisionImportant f1(important)=$f1ScoreImportant");

		$classifier = new Classifier();
		$classifier->setType(Classifier::TYPE_IMPORTANCE);
		$classifier->setTrainingSetSize(count($trainingSet));
		$classifier->setValidationSetSize(count($validationSet));
		$classifier->setRecallImportant($recallImportant);
		$classifier->setPrecisionImportant($precisionImportant);
		$classifier->setF1ScoreImportant($f1ScoreImportant);
		return $classifier;
	}
}
