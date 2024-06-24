<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getAccountId()
 * @method void setAccountId(int $accountId)
 *
 * @method string getType()
 * @method void setType(string $type)
 *
 * @method string getEstimator()
 * @method void setEstimator(string $estimator)
 *
 * @method string getAppVersion()
 * @method void setAppVersion(string $version)
 *
 * @method int getTrainingSetSize()
 * @method void setTrainingSetSize(int $size)
 *
 * @method int getValidationSetSize()
 * @method void setValidationSetSize(int $size)
 *
 * @method float getRecallImportant()
 * @method void setRecallImportant(float $recall)
 *
 * @method float getPrecisionImportant()
 * @method void setPrecisionImportant(float $precision)
 *
 * @method float getF1ScoreImportant()
 * @method void setF1ScoreImportant(float $precision)
 *
 * @method int getDuration()
 * @method void setDuration(int $layers)
 *
 * @method int getActive()
 * @method void setActive(bool $active)
 *
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class Classifier extends Entity {
	public const TYPE_IMPORTANCE = 'importance';

	/** @var int */
	protected $accountId;

	/** @var string */
	protected $type;

	/** @var string */
	protected $estimator;

	/** @var string */
	protected $appVersion;

	/** @var int */
	protected $trainingSetSize;

	/** @var int */
	protected $validationSetSize;

	/** @var float */
	protected $recallImportant;

	/** @var float */
	protected $precisionImportant;

	/** @var float */
	protected $f1ScoreImportant;

	/** @var int */
	protected $duration;

	/** @var bool */
	protected $active;

	/** @var int */
	protected $createdAt;

	public function __construct() {
		$this->addType('accountId', 'int');
		$this->addType('type', 'string');
		$this->addType('appVersion', 'string');
		$this->addType('trainingSetSize', 'int');
		$this->addType('validationSetSize', 'int');
		$this->addType('recallImportant', 'float');
		$this->addType('precisionImportant', 'float');
		$this->addType('f1ScoreImportant', 'float');
		$this->addType('duration', 'int');
		$this->addType('active', 'boolean');
		$this->addType('createdAt', 'int');
	}
}
