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

use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Db\Classifier;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClassifierTrainingException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Classification\FeatureExtraction\CompositeExtractor;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Rubix\ML\Classifiers\GaussianNB;
use Rubix\ML\CrossValidation\Reports\MulticlassBreakdown;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Estimator;
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
	private const LABEL_IMPORTANT = 'i';

	/**
	 * @var string label for data sets that are classified as not important
	 */
	private const LABEL_NOT_IMPORTANT = 'ni';

	/**
	 * The minimum number of important messages. Without those the unsupervised
	 * training would yield random classification. Hence we switch to a rule-based
	 * classifier. This is known as the "cold start" problem.
	 */
	private const COLD_START_THRESHOLD = 20;

	/**
	 * The maximum number of data sets to train the classifier with
	 */
	private const MAX_TRAINING_SET_SIZE = 1000;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var CompositeExtractor */
	private $extractor;

	/** @var PersistenceService */
	private $persistenceService;

	/** @var PerformanceLogger */
	private $performanceLogger;

	/** @var ImportanceRulesClassifier */
	private $rulesClassifier;

	private LoggerInterface $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								CompositeExtractor $extractor,
								PersistenceService $persistenceService,
								PerformanceLogger $performanceLogger,
								ImportanceRulesClassifier $rulesClassifier,
								LoggerInterface $logger) {
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->extractor = $extractor;
		$this->persistenceService = $persistenceService;
		$this->performanceLogger = $performanceLogger;
		$this->rulesClassifier = $rulesClassifier;
		$this->logger = $logger;
	}

	private function filterMessageHasSenderEmail(Message $message): bool {
		return $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null;
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
	 */
	public function train(Account $account, LoggerInterface $logger): void {
		$perf = $this->performanceLogger->start('importance classifier training');
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
			$perf->end();
			return;
		}
		$perf->step('find latest ' . self::MAX_TRAINING_SET_SIZE . ' messages');

		$dataSet = $this->getFeaturesAndImportance($account, $incomingMailboxes, $outgoingMailboxes, $messages);
		$perf->step('extract features from messages');

		/**
		 * How many of the most recent messages are excluded from training?
		 */
		$validationThreshold = max(
			5,
			(int)(count($dataSet) * 0.1)
		);
		$validationSet = array_slice($dataSet, 0, $validationThreshold);
		$trainingSet = array_slice($dataSet, $validationThreshold);
		$logger->debug('data set split into ' . count($trainingSet) . ' training and ' . count($validationSet) . ' validation sets with ' . count($trainingSet[0]['features'] ?? []) . ' dimensions');
		if (empty($validationSet) || empty($trainingSet)) {
			$logger->info('not enough messages to train a classifier');
			$perf->end();
			return;
		}
		$validationEstimator = $this->trainClassifier($trainingSet);
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
			return;
		}
		$perf->step("train and validate classifier with training and validation sets");

		$estimator = $this->trainClassifier($dataSet);
		$perf->step("train classifier with full data set");

		$classifier->setAccountId($account->getId());
		$classifier->setDuration($perf->end());
		$this->persistenceService->persist($classifier, $estimator);
		$logger->debug("classifier {$classifier->getId()} persisted");
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
											  array $messages): array {
		$this->extractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);

		return array_map(function (Message $message) {
			$sender = $message->getFrom()->first();
			if ($sender === null) {
				throw new RuntimeException("This should not happen");
			}

			return [
				'features' => $this->extractor->extract($message),
				'label' => $message->getFlagImportant() ? self::LABEL_IMPORTANT : self::LABEL_NOT_IMPORTANT,
				'sender' => $sender->getEmail(),
			];
		}, $messages);
	}

	/**
	 * @param Account $account
	 * @param Message[] $messages
	 *
	 * @return bool[]
	 * @throws ServiceException
	 */
	public function classifyImportance(Account $account, array $messages): array {
		$estimator = null;
		try {
			$estimator = $this->persistenceService->loadLatest($account);
		} catch (ServiceException $e) {
			$this->logger->warning('Failed to load importance classifier: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}

		if ($estimator === null) {
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
			$messagesWithSender
		);
		$predictions = $estimator->predict(
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

	private function trainClassifier(array $trainingSet): GaussianNB {
		$classifier = new GaussianNB();
		$classifier->train(Labeled::build(
			array_column($trainingSet, 'features'),
			array_column($trainingSet, 'label')
		));
		return $classifier;
	}

	/**
	 * @param Estimator $estimator
	 * @param array $trainingSet
	 * @param array $validationSet
	 *
	 * @return Classifier
	 * @throws ClassifierTrainingException
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
		$logger->debug("classification report: " . json_encode([
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
