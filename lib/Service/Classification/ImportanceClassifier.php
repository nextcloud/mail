<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCP\ILogger;
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

	/** @var ILogger */
	private $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								CompositeExtractor $extractor,
								PersistenceService $persistenceService,
								PerformanceLogger $performanceLogger,
								ImportanceRulesClassifier $rulesClassifier,
								ILogger $logger) {
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->extractor = $extractor;
		$this->persistenceService = $persistenceService;
		$this->performanceLogger = $performanceLogger;
		$this->rulesClassifier = $rulesClassifier;
		$this->logger = $logger;
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
	public function train(Account $account): void {
		$perf = $this->performanceLogger->start('importance classifier training');
		$incomingMailboxes = $this->getIncomingMailboxes($account);
		$perf->step('find incoming mailboxes');
		$outgoingMailboxes = $this->getOutgoingMailboxes($account);
		$perf->step('find outgoing mailboxes');

		$mailboxIds = array_map(function (Mailbox $mailbox) {
			return $mailbox->getId();
		}, $incomingMailboxes);
		$messages = array_filter(
			$this->messageMapper->findLatestMessages($mailboxIds, self::MAX_TRAINING_SET_SIZE),
			function (Message $message) {
				return $message->getFrom()->first() !== null;
			}
		);
		$importantMessages = array_filter($messages, function (Message $message) {
			return $message->getFlagImportant();
		});
		if (count($importantMessages) < self::COLD_START_THRESHOLD) {
			$this->logger->warning('not enough messages to train a classifier');
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
		if (empty($validationSet) || empty($trainingSet)) {
			$this->logger->warning('not enough messages to train a classifier');
			$perf->end();
			return;
		}
		$validationEstimator = $this->trainClassifier($trainingSet);
		try {
			$classifier = $this->validateClassifier($validationEstimator, $trainingSet, $validationSet);
		} catch (ClassifierTrainingException $e) {
			$this->logger->logException($e, [
				'message' => 'Importance classifier training failed: ' . $e->getMessage(),
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
	}

	/**
	 * @param Account $account
	 *
	 * @return Mailbox[]
	 */
	private function getIncomingMailboxes(Account $account): array {
		return array_filter($this->mailboxMapper->findAll($account), function (Mailbox $mailbox) {
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
			return [
				$this->mailboxMapper->findSpecial($account, 'sent')
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
				'features' => $this->extractor->extract($sender->getEmail()),
				'label' => $message->getFlagImportant() ? self::LABEL_IMPORTANT : self::LABEL_NOT_IMPORTANT,
				'sender' => $sender->getEmail(),
			];
		}, $messages);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message[] $messages
	 *
	 * @return bool[]
	 * @throws ServiceException
	 */
	public function classifyImportance(Account $account, Mailbox $mailbox, array $messages): array {
		$estimator = $this->persistenceService->loadLatest($account);
		if ($estimator === null) {
			$predictions = $this->rulesClassifier->classifyImportance(
				$account,
				$this->getIncomingMailboxes($account),
				$this->getOutgoingMailboxes($account),
				$messages
			);
			return array_combine(
				array_map(function (Message $m) {
					return $m->getUid();
				}, $messages),
				array_map(function (Message $m) use ($predictions) {
					return ($predictions[$m->getUid()] ?? false) === true;
				}, $messages)
			);
		}

		$features = $this->getFeaturesAndImportance(
			$account,
			$this->getIncomingMailboxes($account),
			$this->getOutgoingMailboxes($account),
			$messages
		);
		$predictions = $estimator->predict(
			Unlabeled::build(array_column($features, 'features'))
		);
		return array_combine(
			array_map(function (Message $m) {
				return $m->getUid();
			}, $messages),
			array_map(function ($p) {
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
										array $validationSet): Classifier {
		$predictedValidationLabel = $estimator->predict(Unlabeled::build(
			array_column($validationSet, 'features')
		));

		$reporter = new MulticlassBreakdown();
		$report = $reporter->generate(
			$predictedValidationLabel,
			array_column($validationSet, 'label')
		);
		$recallImportant = $report['classes'][self::LABEL_IMPORTANT]['recall'];
		$precisionImportant = $report['classes'][self::LABEL_IMPORTANT]['precision'];
		$f1ScoreImportant = $report['classes'][self::LABEL_IMPORTANT]['f1_score'];

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
		$this->logger->debug("classification report: " . json_encode([
			'recall' => $recallImportant,
			'precision' => $precisionImportant,
			'f1Score' => $f1ScoreImportant,
		]));
		$this->logger->debug("classifier validated: recall(important)=$recallImportant, precision(important)=$precisionImportant f1(important)=$f1ScoreImportant");

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
